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
$es_post = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
if ($es_post) membrete_requerir_post_csrf();
$tipo = membrete_texto_tipo($db, (int)($es_post ? ($_POST["tipo"] ?? 0) : ($_GET["tipo"] ?? 0)));
if (!$tipo) {
    echo html_error("El tipo de documento no existe o no est&aacute; activo.");
    die("");
}
$texto = $es_post ? membrete_texto_limpio($_POST["texto"] ?? '') : $tipo['texto'];
$texto = membrete_texto_resolver_tokens($db, $texto);
$hoja = ObtenerRutaMembrete((int)($_SESSION['depe_codi'] ?? 0), $tipo['trad_codigo'], (int)($_SESSION['inst_codi'] ?? 0), $db);
if ($hoja['ruta'] === '') {
    echo html_error("No hay hoja membretada disponible para generar la vista previa.");
    die("");
}
$gen = new CLASS_GEN();
$fecha = 'Cuenca, ' . $gen->traducefecha(date('Y-m-d'));
$abr = $tipo['trad_abreviatura'] !== '' ? $tipo['trad_abreviatura'] : 'X';
$numero = $tipo['trad_descr'] . ' Nro. UC-PRUEBA-' . date('Y') . '-0001-' . $abr;
$html = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>Vista previa del texto por defecto</title></head>
<body style="text-align: justify;">
<b>Asunto: </b>Vista previa del texto por defecto de "' . membrete_h($tipo['trad_descr']) . '"<br>&nbsp;<br>&nbsp;<br>
' . $texto . '<br>&nbsp;<br>
Atentamente,<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>
Mgt. Mar&iacute;a L&oacute;pez<br><b>DIRECTORA</b><br>
</body></html>';
$pdf = ws_generar_pdf_base64($html, $hoja['ruta'], $servidor_pdf, "3", $numero, $fecha, "1", "V");
if (trim((string)$pdf) == '' || $pdf === "0") {
    echo html_error("No se pudo generar el PDF de vista previa. Revise el servicio de generaci&oacute;n de PDF.");
    die("");
}
$bin = base64_decode($pdf);
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="texto_tipo_' . $tipo['trad_codigo'] . '.pdf"');
header('Content-Length: ' . strlen($bin));
header('Cache-Control: no-store');
echo $bin;
