<?php
session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once(dirname(__DIR__, 2).'/config.php');
require_once(dirname(__DIR__, 2).'/interconexion/generar_pdf.php');
include_once(dirname(__DIR__, 2).'/class_control/class_gen.php');
include_once(__DIR__.'/membretes_lib.php');
if (($_SESSION["usua_admin_sistema"] ?? 0) != 1) {
    echo html_error("No tiene permisos para administrar hojas membretadas.");
    die("");
}
$membrete = membrete_obtener($db, (int)($_GET["id"] ?? 0));
if (!$membrete || !$membrete['existe']) {
    echo html_error("La hoja membretada no tiene archivo en el servidor.");
    die("");
}
$gen = new CLASS_GEN();
$fecha = 'Cuenca, ' . $gen->traducefecha(date('Y-m-d'));
$numero = 'Oficio Nro. UC-PRUEBA-' . date('Y') . '-0001-O';
$institucion = strtoupper((string)($_SESSION['inst_nombre'] ?? 'Universidad de Cuenca'));
$html = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>Prueba de hoja membretada</title></head>
<body style="text-align: justify;">
<b>Asunto: </b>Prueba de la hoja membretada "' . membrete_h($membrete['memb_nombre']) . '"<br>&nbsp;<br>&nbsp;<br>
Se&ntilde;or Doctor<br>Juan P&eacute;rez<br><b>Decano</b><br><b>' . membrete_h($institucion) . '</b><br>En su despacho.<br>&nbsp;<br>&nbsp;<br>
De mi consideraci&oacute;n:<br>&nbsp;<br>
Este documento se gener&oacute; &uacute;nicamente para comprobar c&oacute;mo se ve la hoja membretada. El n&uacute;mero, la fecha y el pie de p&aacute;gina los coloca el sistema en la misma posici&oacute;n que en un oficio real.<br>&nbsp;<br>
Con sentimientos de distinguida consideraci&oacute;n.<br>&nbsp;<br>
Atentamente,<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>
Mgt. Mar&iacute;a L&oacute;pez<br><b>DIRECTORA</b><br>
<dl><dt><font size=2>Anexos: </font></dt><dd><font size=2> - Anexo de ejemplo</font></dd></dl>
</body></html>';
$pdf = ws_generar_pdf_base64($html, $membrete['ruta'], $servidor_pdf, "3", $numero, $fecha, "1", "V");
if (trim((string)$pdf) == '' || $pdf === "0") {
    echo html_error("No se pudo generar el PDF de prueba. Revise el servicio de generaci&oacute;n de PDF.");
    die("");
}
$bin = base64_decode($pdf);
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="prueba_hoja_membretada_' . $membrete['memb_codi'] . '.pdf"');
header('Content-Length: ' . strlen($bin));
header('Cache-Control: no-store');
echo $bin;
