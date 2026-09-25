<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
chdir(dirname(__DIR__, 2));
require_once 'config/autoload.php';
require_once 'config.php';
require_once __DIR__ . '/membrete_pdf.php';
$solo_verificar = in_array('--verificar', $argv, true);
$dir = dirname(__DIR__, 2) . '/bodega/plantillas/membretes';
$historial = $dir . '/historial';
if (!is_dir($historial)) @mkdir($historial, 0775, true);
$archivos = glob($dir . '/*.pdf');
if (!$archivos) { echo "No hay hojas membretadas en $dir\n"; exit(0); }
$incompatibles = 0;
$convertidos = 0;
$fallidos = 0;
foreach ($archivos as $ruta) {
    $nombre = basename($ruta);
    $diag = MembretePdfCompat::diagnosticar($ruta);
    $motor = $diag['compatible'] ? membrete_pdf_probar_motor($ruta) : null;
    if ($diag['compatible'] && $motor === null) { echo "OK          $nombre (PDF {$diag['version']})\n"; continue; }
    $incompatibles++;
    $motivo = html_entity_decode($diag['compatible'] ? (string)$motor : $diag['motivo'], ENT_QUOTES, 'UTF-8');
    echo "INCOMPATIBLE $nombre (PDF {$diag['version']}): $motivo\n";
    if ($solo_verificar) continue;
    $tmp = tempnam(sys_get_temp_dir(), 'MEMB');
    $res = MembretePdfCompat::normalizar($ruta, $tmp);
    $err = $res === true ? membrete_pdf_probar_motor($tmp) : $res;
    if ($err !== null) {
        @unlink($tmp);
        $fallidos++;
        echo "   NO SE PUDO CONVERTIR: " . html_entity_decode((string)$err, ENT_QUOTES, 'UTF-8') . "\n";
        echo "   Vuelva a exportar el archivo como PDF 1.4 (Acrobat 5) y c\u{e1}rguelo desde Administraci\u{f3}n > Hojas Membretadas.\n";
        continue;
    }
    $respaldo = $historial . '/' . pathinfo($nombre, PATHINFO_FILENAME) . '_' . date('Ymd_His') . '_original.pdf';
    if (!copy($ruta, $respaldo)) { @unlink($tmp); $fallidos++; echo "   NO SE PUDO RESPALDAR en $respaldo\n"; continue; }
    if (!@rename($tmp, $ruta) && !(copy($tmp, $ruta) && @unlink($tmp))) { $fallidos++; echo "   NO SE PUDO REEMPLAZAR el archivo\n"; continue; }
    @chmod($ruta, 0664);
    $convertidos++;
    echo "   CONVERTIDO a PDF 1.4. Respaldo: " . basename($respaldo) . "\n";
}
echo "\nTotal: " . count($archivos) . " archivo(s), $incompatibles incompatible(s), $convertidos convertido(s), $fallidos con error.\n";
exit($fallidos > 0 ? 1 : 0);
