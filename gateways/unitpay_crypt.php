<?php

echo hash('sha256', $_POST['account'].'{up}'.$_POST['desc'].'{up}'.$_POST['sum'].'{up}'.$_POST['secret']);