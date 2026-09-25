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
membrete_requerir_post_csrf();
$accion = (string)($_POST["accion"] ?? '');
$tipo = membrete_texto_tipo($db, (int)($_POST["tipo"] ?? 0));
if (!$tipo) {
    echo html_error("El tipo de documento no existe o no est&aacute; activo.");
    die("");
}
$nuevo = ($accion === 'vaciar') ? '' : membrete_texto_limpio($_POST["texto"] ?? '');
if (strlen($nuevo) > 20000) {
    echo html_error("El texto es demasiado largo (m&aacute;ximo 20.000 caracteres).");
    die("");
}
try {
    $db->conn->BeginTrans();
    membrete_ejecutar($db, "update tiporad set trad_texto_inicio=" . $db->conn->qstr($nuevo) . " where trad_codigo=" . $tipo['trad_codigo']);
    membrete_log($db, null, 'TEXTO_TIPO', "Tipo " . $tipo['trad_codigo'] . " (" . $tipo['trad_descr'] . "): " . strlen($tipo['texto']) . " -> " . strlen($nuevo) . " caracteres" . ($accion === 'vaciar' ? " (vaciado)" : ""));
    $db->conn->CommitTrans();
} catch (Exception $e) {
    $db->conn->RollbackTrans();
    echo html_error($e->getMessage());
    die("");
}
$mensaje = ($accion === 'vaciar')
    ? "El tipo <b>" . membrete_h($tipo['trad_descr']) . "</b> ya no precarga texto."
    : "Texto por defecto de <b>" . membrete_h($tipo['trad_descr']) . "</b> grabado correctamente.";
membrete_pagina_resultado($mensaje, 'textos_tipo.php');
