#!/usr/bin/php -q
<?php
include("phpagi.php");
$agi = new AGI();
$numero = $argv[1];
$chave  = "suachaveDeacesso";
$url="http://consulta.brfonetelecom.com.br/gestor/consultaOperadora.php?chave=$chave&numero=$numero";
$operadora = trim(file_get_contents($url));
$agi->set_variable("OPERADORA", $operadora);
exit();
?>
