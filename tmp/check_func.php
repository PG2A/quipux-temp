<?php
include '/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca/config.php';
include '/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca/include/db/ConnectionHandler.php';
$db = new ConnectionHandler('/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca', 'bodega');

$rs = $db->conn->Execute("
    SELECT oid, proargnames, proargtypes::regtype[] 
    FROM pg_proc 
    WHERE proname = 'func_log_archivo' OR proname = 'func_grabar_archivo'
");
while (!$rs->EOF) {
    print_r($rs->fields);
    $rs->MoveNext();
}
?>
