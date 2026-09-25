<?php
session_start();
// Simulate necessary session vars if missing (though run from browser/cli context matters)
if (!isset($_SESSION['usua_admin_sistema'])) $_SESSION['usua_admin_sistema'] = 1;
if (!isset($_SESSION['inst_codi'])) $_SESSION['inst_codi'] = 1; 

include_once('rec_session.php');
include_once('include/db/ConnectionHandler.php');
$db = new ConnectionHandler('.');

$sql = "select u.usua_codi, u.usua_cargo_tipo, u.cargo_tipo, u.usua_nombre from usuario u where u.usua_codi > 0 limit 5";
$rs = $db->conn->Execute($sql);

echo "Fetch Mode: " . $db->conn->fetchMode . "\n";
echo "ADODB_ASSOC_CASE: " . ADODB_ASSOC_CASE . "\n";

while (!$rs->EOF) {
    echo "----------------\n";
    print_r($rs->fields);
    $rs->MoveNext();
}
?>
