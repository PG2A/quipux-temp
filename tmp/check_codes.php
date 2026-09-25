<?php
include "/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca/config.php";
include "/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca/include/db/ConnectionHandler.php";
$db = new ConnectionHandler("/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca");
$rs = $db->conn->query("SELECT sgd_ttr_codigo, sgd_ttr_descrip FROM sgd_ttr_transaccion ORDER BY sgd_ttr_codigo ASC");
while($rs && !$rs->EOF) {
    echo $rs->fields["SGD_TTR_CODIGO"] . " => " . $rs->fields["SGD_TTR_DESCRIP"] . "\n";
    $rs->MoveNext();
}
