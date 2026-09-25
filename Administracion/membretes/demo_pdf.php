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
include_once(__DIR__.'/demo_puntos.php');
if (($_SESSION["usua_admin_sistema"] ?? 0) != 1) {
    echo html_error("No tiene permisos para administrar hojas membretadas.");
    die("");
}
$puntos = membrete_demo_puntos();
$i = (int)($_GET["punto"] ?? 0);
if (!isset($puntos[$i]) || $puntos[$i]['estado'] != 'modulo') {
    echo html_error("Este punto no genera ejemplo desde el m&oacute;dulo.");
    die("");
}
$p = $puntos[$i];
$depe = (int)($_GET["depe"] ?? ($_SESSION["depe_codi"] ?? 0));
$tipo = (int)($_GET["tipo"] ?? 0);
$inst = (int)($_SESSION["inst_codi"] ?? 0);
$area_nombre = membrete_area_en_alcance($db, $depe);
if ($area_nombre === null) { echo html_error("&Aacute;rea no v&aacute;lida."); die(""); }
$gen = new CLASS_GEN();
$fecha = 'Cuenca, ' . $gen->traducefecha(date('Y-m-d'));
$institucion = strtoupper((string)($_SESSION['inst_nombre'] ?? 'Universidad de Cuenca'));
$titulo_punto = html_entity_decode($p['accion'], ENT_QUOTES, 'UTF-8');

if ($p['formato'] == 'V') {
    $r = ObtenerRutaMembrete($depe, $tipo, $inst, $db);
    $es_memo = $p['muestra'] == 'memorando';
    $numero = ($es_memo ? 'Memorando' : 'Oficio') . ' Nro. UC-DEMO-' . date('Y') . '-0001-' . ($es_memo ? 'M' : 'O');
    $html = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>Demo</title></head><body style="text-align: justify;">';
    if ($es_memo) {
        $html .= '<b>PARA:</b> Dra. Ana Torres, <b>Directora de Talento Humano</b><br>&nbsp;<br>'
               . '<b>ASUNTO:</b> Ejemplo generado desde el m&oacute;dulo de hojas membretadas (' . membrete_h($titulo_punto) . ')<br>&nbsp;<br>&nbsp;<br>';
    } else {
        $html .= '<b>Asunto: </b>Ejemplo generado desde el m&oacute;dulo de hojas membretadas (' . membrete_h($titulo_punto) . ')<br>&nbsp;<br>&nbsp;<br>'
               . 'Se&ntilde;or Doctor<br>Juan P&eacute;rez<br><b>Decano</b><br><b>' . membrete_h($institucion) . '</b><br>En su despacho.<br>&nbsp;<br>&nbsp;<br>De mi consideraci&oacute;n:<br>&nbsp;<br>';
    }
    $html .= 'Este PDF reproduce el punto de generaci&oacute;n "' . membrete_h($titulo_punto) . '" tal como lo producir&iacute;a el sistema para el &aacute;rea <b>' . membrete_h($area_nombre) . '</b>. La hoja membretada superpuesta es la que resuelve el m&oacute;dulo para esa &aacute;rea y tipo de documento; el n&uacute;mero, la fecha y el pie los coloca el motor real.<br>&nbsp;<br>'
           . 'Con sentimientos de distinguida consideraci&oacute;n.<br>&nbsp;<br>Atentamente,<br>&nbsp;<br>&nbsp;<br>&nbsp;<br>Mgt. Mar&iacute;a L&oacute;pez<br><b>' . membrete_h(strtoupper($area_nombre)) . '</b></body></html>';
    $pdf = ws_generar_pdf_base64($html, $r['ruta'], $servidor_pdf, "3", $numero, $fecha, "1", "V");
} else {
    $r = ObtenerRutaMembrete($depe, 0, $inst, $db);
    $filas = '';
    for ($k = 1; $k <= 6; $k++) $filas .= '<tr><td>' . $k . '</td><td>UC-DEMO-' . date('Y') . '-000' . $k . '-O</td><td>' . date('Y-m-d') . '</td><td>Ejemplo de fila ' . $k . ' del reporte</td></tr>';
    $html = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>Demo reporte</title></head><body>'
          . '<center><font size="4"><b>' . membrete_h($institucion) . '</b></font><br><font size="3"><b>' . membrete_h(strtoupper($titulo_punto)) . '</b></font><br><font size="2">' . membrete_h($area_nombre) . ' &middot; ' . membrete_h($fecha) . '</font></center><br>'
          . '<table width="100%" border="1" cellpadding="3" cellspacing="0"><tr><th>#</th><th>N&uacute;mero</th><th>Fecha</th><th>Detalle</th></tr>' . $filas . '</table><br>'
          . '<font size="2">Ejemplo generado desde el m&oacute;dulo de hojas membretadas. La hoja superpuesta es la que resuelve el m&oacute;dulo para el &aacute;rea del usuario que genera el reporte.</font></body></html>';
    $pdf = ws_generar_pdf($html, $r['ruta'], $servidor_pdf, "", "", "", 100, "R");
}
if (trim((string)$pdf) == '' || $pdf === "0") {
    echo html_error("No se pudo generar el PDF de ejemplo. Revise el servicio de generaci&oacute;n de PDF.");
    die("");
}
$bin = ($p['formato'] == 'V') ? base64_decode($pdf) : $pdf;
if (substr((string)$bin, 0, 4) != '%PDF') {
    echo html_error("El motor no devolvi&oacute; un PDF v&aacute;lido para este ejemplo.");
    die("");
}
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="demo_membrete_' . $i . '.pdf"');
header('Content-Length: ' . strlen($bin));
header('Cache-Control: no-store');
echo $bin;
