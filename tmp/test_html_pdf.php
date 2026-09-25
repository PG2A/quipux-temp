<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include "/Users/pg2a/Documents/exducere/ucuenca/quipux-ucuenca/html_a_pdf/html_a_pdf.php";
echo "Starting...\n";
$out = html_a_pdf("<h1>Test</h1>", "", "", "", "", "1", "R");
echo "Done.\n";
