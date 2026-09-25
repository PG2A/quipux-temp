<?php
session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
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
$descargar = (int)($_GET["descargar"] ?? 0) === 1;
$nombre_archivo = preg_replace('/[^A-Za-z0-9_-]+/', '_', iconv('UTF-8', 'ASCII//TRANSLIT', (string)$membrete['memb_nombre']));
$nombre_archivo = trim($nombre_archivo, '_');
if ($nombre_archivo === '') $nombre_archivo = 'hoja_membretada_' . $membrete['memb_codi'];
header('Content-Type: application/pdf');
header('Content-Disposition: ' . ($descargar ? 'attachment' : 'inline') . '; filename="' . $nombre_archivo . '.pdf"');
header('Content-Length: ' . filesize($membrete['ruta']));
header('Cache-Control: no-store');
readfile($membrete['ruta']);
