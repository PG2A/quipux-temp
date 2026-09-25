<?php
include '/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca/config.php';
include '/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca/include/db/ConnectionHandler.php';
$db = new ConnectionHandler('/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca', 'bodega');

$rs = $db->conn->Execute("
    SELECT column_name, data_type 
    FROM information_schema.columns 
    WHERE table_name = 'archivo'
");
while (!$rs->EOF) {
    print_r($rs->fields);
    $rs->MoveNext();
}
?>
