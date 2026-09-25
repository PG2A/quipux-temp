<?php
include '/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca/config.php';
include '/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca/include/db/ConnectionHandler.php';
$db = new ConnectionHandler('/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca', 'bodega');

$rs = $db->conn->Execute("
    SELECT proname, proargnames, proargtypes::regtype[] 
    FROM pg_proc 
    WHERE proname LIKE '%log%' OR proname LIKE '%archivo%'
");
while (!$rs->EOF) {
    print_r($rs->fields);
    $rs->MoveNext();
}
?>
