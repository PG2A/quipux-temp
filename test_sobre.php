<?php
require_once "config.php";
require_once "interconexion/generar_pdf.php";

$html = '<!DOCTYPE html>
<head>
<title></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body>
<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;';

$html.="<table width='1000px' align='center' cellpadding='0' cellspacing='0'>";
$html .="<tr><td width='230px'>&nbsp;&nbsp;</td><td width='770px'><font size='3' style='line-height: 0.9em;'>";
$html .= "Some arbitrary text inside the envelope testing TCPDF capabilities";
$html .= '</font></td></tr>';
$html .= "</table></body></html>";

echo "Memory before: " . memory_get_usage() . "\n";
$pdf = ws_generar_pdf($html, "", $servidor_pdf, "1", "DOC-123", "2026-03-27", 1, "R");
echo "Memory after: " . memory_get_usage() . "\n";
echo is_string($pdf) && strlen($pdf) > 100 ? "SUCCESS: ".strlen($pdf)." bytes\n" : "FAILED: ".substr($pdf, 0, 100)."\n";
