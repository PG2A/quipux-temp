<?php
require_once "config.php";
require_once "html_a_pdf/tcpdf/tcpdf.php";

$pdf = new TCPDF();
$pdf->AddPage();

$html = "<img src=\"https://quipux-ucuenca-php83.test/iconos/logo_quipux.png\">";

// Translating URL to local path
$html = str_replace($servidor_pdf, dirname(__FILE__).'/', $html);

echo "Translated HTML: $html\n";

$pdf->writeHTML($html, true, false, true, false, "");
$pdf->Output(__DIR__."/test_logo.pdf", "F");
echo "Done!\n";
