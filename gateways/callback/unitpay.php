<?php

if (file_exists("../../../init.php")) {
    include_once("../../../init.php");
} else {
    include_once("../../../dbconnect.php");
    include_once("../../../includes/functions.php");
}
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$module = "unitpay";
$gateway_vars = getGatewayVariables($module);

if(!$gateway_vars['type']) {
    echo '{"error": {"code": -32000, "message": "Module Not Activated"}}';
    exit();
}

$account = $_REQUEST['params']['account'];
$unitpay_id = $_REQUEST['params']['unitpayId'];
$client_sum = $_REQUEST['params']['sum'];
$profit = $_REQUEST['params']['profit'];

$result = mysql_fetch_array(select_query( "tblinvoices", "id", array( "id" => $account )));
if(!$result['id']) {
    echo '{"error": {"code": -32000, "message": "Invoice ID Not Found"}}';
    exit();
}

$result = select_query( "tblaccounts", "id", array("transid" => $unitpay_id));
if (mysql_num_rows($result)) {
    echo '{"result": {"message":"Payment successful"}}';
    exit();
}

switch($_REQUEST["method"]) {
    case 'pay':
        handle_pay();
        break;
    case 'check':
        handle_check($gateway_vars['name'], $gateway_vars['secret_key'], $account, $_REQUEST['orderCurrency'], "", $client_sum);
        break;
    case 'error':
        handle_error($gateway_vars['name']);
}

echo '{"error": {"code": -32000, "message": "Unknown query"}}';
exit();

function handle_error($name) {
    echo '{"result": {"message":"Error logged"}}';
    logTransaction($name, $_REQUEST['params'], "Error");
    exit();
}

function handle_check($name, $secret_key, $account, $currency, $desc, $sum) {
    $request_params = $_REQUEST['params'];
    unset($request_params['sign']);
    $md5 = md5(join(null, $request_params).$secret_key);

    if($_REQUEST['params']['sign'] == $md5) {
        echo '{"result": {"message":"Check successful"}}';
    } else if($request_params['signature'] == getFormSignature($account, $currency, $desc, $sum, $secret_key)) {
        echo '{"result": {"message":"Check successful"}}';
    } else {
        echo '{"error": {"code": -32000, "message": "Signature invalid Check"}}';
    }

    logTransaction($name, $_REQUEST['params'], "Check");
    exit();
}

function handle_pay($name, $secret_key, $convert_to, $account, $currency, $desc, $sum, $vars, $unitpay_id, $client_sum, $profit) {
    $params=$_REQUEST['params'];
    ksort($params);
    unset($params['sign']);
    $_REQUEST['params']['md5'] = md5(join(null, $params).$secret_key);

    if ($params['signature'] != getFormSignature($account, $currency, $desc, $sum, $secret_key)) {
        logTransaction($name,$_REQUEST['params'],"Unsuccessful");

        echo '{"error": {"code": -32000, "message": "Signature invalid Pay"}}';
        exit();
    }

    $result = select_query( "tblinvoices", "userid,total", array( "id" => $account ) );
    $data = mysql_fetch_array( $result );
    $userid = $data['userid'];
    $total = $data['total'];
    $currency = getCurrency( $userid );
    if ($convert_to) {
        $client_amount = convertCurrency( $client_sum, $convert_to, $currency['id'] );
        $your_amount = convertCurrency( $profit, $convert_to, $currency['id'] );
    }

    if ($total < $client_amount && $your_amount < $total) {
        $amount = $total;
        $fee = $total - $your_amount;
    } elseif($client_amount == $total) {
        $amount = $total;
        $fee = 0;
    } elseif($your_amount == $total) {
        $amount = $total; $fee = 0;
    } else {
        $amount = $client_amount;
    }

    if ($amount == 0) {
        logTransaction($name, $POST, "Zero Payment"); echo '{"error": {"code": -32000, "message": "Zero payment"}}';
        exit();
    }

    addInvoicePayment($account, $unitpay_id, $amount, $fee, $vars);
    logTransaction($name,$_REQUEST['params'],"Successful");

    echo '{"result": {"message":"Payment successful"}}';
    exit();
}

function getFormSignature($account, $currency, $desc, $sum, $secretKey) {
    $hashStr = $account.'{up}'.$currency.'{up}'.$desc.'{up}'.$sum.'{up}'.$secretKey;
    return hash('sha256', $hashStr);
}


