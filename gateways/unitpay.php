<?php
function unitpay_config() {
    $configarray = array(
        "FriendlyName" => array("Type" => "System", "Value"=>"UnitPay"),
        "DOMAIN" => array("FriendlyName" => "Домен", "Type" => "text", "Size" => "60", "Description" => "<b>Подсказка:</b> Домен платежки (unitpay.money) [Добавлен, если касса вдруг изменит домен]" ),
        "URL" => array("FriendlyName" => "Ссылка на форму оплаты", "Type" => "text", "Size" => "60", "Description" => "<b>Подсказка:</b> https://domain_name/pay/<b>262fde297f8e4e3d31e272d74aa39401</b>" ),
        "SecretKey" => array("FriendlyName" => "Секретный ключ", "Type" => "text", "Size" => "60", "Description" => "<b>Подсказка:</b> В настройках проекта UnitPay"),
        "hideHint" => array("FriendlyName" => "Скрывать подсказки", "Type" => "yesno","Description" => ""),
        "hideBackUrl" => array("FriendlyName" => "Скрывать ссылку на магазин", "Type" => "yesno","Description" => ""),
        "hideOrderCost" => array("FriendlyName" => "Скрывать стоимость заказа", "Type" => "yesno","Description" => ""),
        "hideLogo" => array("FriendlyName" => "Скрывать логотип UnitPay", "Type" => "yesno","Description" => "")
    );
    return $configarray;
}
function unitpay_link($params) {
    global $_LANG;
    $code = '<script>
            function getSignature(form) {
               $.post("modules/gateways/unitpay_crypt.php", {
                   account: document.getElementById("account").value,
                   desc: document.getElementById("desc").value,
                   sum: document.getElementById("sum").value,
                   secret: \''.$params['SecretKey'].'\'
               }, 
               function(response) {
                    document.getElementById(\'sig\').value = response;
                    form.submit();
               });
            }
        </script>
        
        <form method="post" action="https://'.$params['DOMAIN'].'/pay/'.$params['URL'].'" onsubmit="getSignature(this);return false;">
                <input type="hidden" name="account" id="account" value="'.$params['invoiceid'].'" />
                <input type="hidden" name="sum" id="sum" value="'.$params['amount'].'" />
                <input type="hidden" name="desc" id="desc"  value="'.$params["description"].'" />
                <input type="hidden" name="hideHint"  value="'.(($params["hideHint"]=="on")?"true":"false").'" />
                <input type="hidden" name="hideBackUrl"  value="'.(($params["hideBackUrl"]=="on")?"true":"false").'" />
                <input type="hidden" name="hideOrderCost"  value="'.(($params["hideOrderCost"]=="on")?"true":"false").'" />
                <input type="hidden" name="hideLogo"  value="'.(($params["hideLogo"]=="on")?"true":"false").'" />
                <input type="hidden" name="signature" id="sig" value=""/>
                <input type="submit" value="'.$_LANG["invoicespaynow"].'" />
		</form>';
    return $code;
}

