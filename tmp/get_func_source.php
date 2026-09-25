<?php
include '/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca/config.php';
include '/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca/include/db/ConnectionHandler.php';
$db = new ConnectionHandler('/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca', 'bodega');

$rs = $db->conn->Execute("
    SELECT prosrc 
    FROM pg_proc 
    WHERE proname = 'func_grabar_archivo'
");
echo $rs->fields['PROSRC'];
?>
