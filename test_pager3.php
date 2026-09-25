<?php
include_once("config.php");
include_once("include/db/ConnectionHandler.php");
$db = new ConnectionHandler(__DIR__);
$rs = $db->query("SELECT '2026123456789' as \"CHR_DATO\"");
include_once("lib/adodb/tohtml.inc.php");
ob_start();
rs2html($rs);
$html = ob_get_clean();
file_put_contents('test_out.txt', $html);
echo "HTML written to test_out.txt\n";
