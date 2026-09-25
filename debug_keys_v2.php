<?php
define('QUIPUX_INTERNAL', true); // Mimic config need
include_once('config.php');
include_once('include/db/ConnectionHandler.php');

$db = new ConnectionHandler('.');
$db->conn->SetFetchMode(ADODB_FETCH_ASSOC);

$sql = "select * from usuario where usua_codi > 0 limit 1";
$rs = $db->conn->Execute($sql);

if (!$rs) {
    echo "Query Error: " . $db->conn->ErrorMsg() . "\n";
} else {
    echo "Keys returned:\n";
    $row = $rs->fields;
    print_r(array_keys($row));
    
    echo "\nValues for first row:\n";
    print_r($row);
}
?>
