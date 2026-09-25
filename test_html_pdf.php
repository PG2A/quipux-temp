<?php
include "config.php";
require_once "html_a_pdf/html_a_pdf.php";

echo "Starting...\n";
$pdf = html_a_pdf(base64_encode("<html><body><h1>Hello, testing!</h1></body></html>"), "", "1", "DOC-123", "2026-03-27", 1, "R");
echo "Done.\n\n";
echo "Base64 Length: " . strlen($pdf) . "\n";
