<?php
$ruta_raiz = '/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca';
include_once $ruta_raiz.'/config.php';
include_once $ruta_raiz.'/include/db/ConnectionHandler.php';
$db = new ConnectionHandler($ruta_raiz);

$rs = $db->conn->query("SELECT relname FROM pg_class WHERE relkind = 'S' AND relname LIKE '%usu%'");
if ($rs) {
   while(!$rs->EOF) {
       echo $rs->fields['relname'] ?? $rs->fields['RELNAME'];
       echo "\n";
       $rs->MoveNext();
   }
} else {
    echo $db->conn->ErrorMsg();
}
