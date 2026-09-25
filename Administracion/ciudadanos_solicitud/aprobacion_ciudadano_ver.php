<?php
// This file is part of Quipux – Document Management System
//
// Quipux is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Quipux is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * Solicitud de alta de ciudadano (RQT-7) — detalle y resolución.
 *
 * GET  sol_codigo            muestra la solicitud.
 * POST accion=aprobar|rechazar|cancelar (+ observacion) la resuelve y vuelve a
 *      mostrarla con el resultado.
 *
 * @package    ciudadanos_solicitud
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/include/ciudadanos/SolicitudCiudadano.php');
include_once('../ciudadanos/util_ciudadano.php');

if (($_SESSION["tipo_usuario"] ?? 0) == 2) {
    die( html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.") );
}
if (!SolicitudCiudadano::disponible($db)) {
    die( html_error("El m&oacute;dulo de solicitudes de ciudadanos no est&aacute; habilitado en la base de datos.") );
}

$es_aprobador = (($_SESSION["perm_aprobar_ciudadano"] ?? 0) == 1 or ($_SESSION["usua_admin_sistema"] ?? 0) == 1);
$sol_codigo   = (int)($_POST['sol_codigo'] ?? $_GET['sol_codigo'] ?? 0);
$mias         = (int)($_POST['mias'] ?? $_GET['mias'] ?? 0);
$volver       = "aprobacion_ciudadanos.php" . ($mias ? "?mias=1" : "");

$solicitudes = new SolicitudCiudadano($db);
$sol = $solicitudes->obtener($sol_codigo);
if ($sol === null) {
    die( html_error("La solicitud no existe.") );
}
$es_solicitante = ((int)$sol['usua_codi_solicita'] === (int)$_SESSION["usua_codi"]);
if (!$es_aprobador && !$es_solicitante) {
    die( html_error("Lo sentimos, usted no tiene permisos suficientes para ver esta solicitud.") );
}

// -------------------------------------------------------------------------
// Resolución
// -------------------------------------------------------------------------
$mensaje = "";
$error   = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    $observacion = trim(limpiar_sql($_POST['observacion'] ?? ''));
    switch ($_POST['accion']) {
        case 'aprobar':
            if (!$es_aprobador) { $error = "No tiene permiso para aprobar solicitudes."; break; }
            $error = $solicitudes->aprobar($sol_codigo, (int)$_SESSION["usua_codi"], $observacion);
            if ($error === '') $mensaje = "Solicitud aprobada. El ciudadano ya est&aacute; activo y recibi&oacute; sus datos de acceso por correo.";
            break;
        case 'rechazar':
            if (!$es_aprobador) { $error = "No tiene permiso para rechazar solicitudes."; break; }
            $error = $solicitudes->rechazar($sol_codigo, (int)$_SESSION["usua_codi"], $observacion);
            if ($error === '') $mensaje = "Solicitud rechazada. El ciudadano fue desactivado y retirado de los documentos en elaboraci&oacute;n.";
            break;
        case 'cancelar':
            $error = $solicitudes->cancelar($sol_codigo, (int)$_SESSION["usua_codi"]);
            if ($error === '') $mensaje = "Solicitud cancelada. El ciudadano fue desactivado y retirado de los documentos en elaboraci&oacute;n.";
            break;
        default:
            $error = "Acci&oacute;n no reconocida.";
    }
    $sol = $solicitudes->obtener($sol_codigo); // estado actualizado
}

$estado = (int)$sol['estado'];
$nombres_estado = array(0 => 'Pendiente', 1 => 'Aprobada', 2 => 'Rechazada', 3 => 'Cancelada');
$colores_estado = array(0 => '#b36b00', 1 => '#1a7f37', 2 => '#b00', 3 => '#666');

// Aviso previo: la cédula pudo registrarse por otra vía mientras esperaba.
$ciud = new Ciudadano($db);
$conflicto = ($estado === 0) ? $ciud->cuentaExistentePorCedula($sol['ciu_cedula'], $sol['ciu_codigo']) : null;

function fila($etiqueta, $valor) {
    return "<tr><td class='titulos2' width='22%'>$etiqueta</td><td class='listado2'>" . ($valor === '' ? '&nbsp;' : $valor) . "</td></tr>";
}
$h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };

echo "<!DOCTYPE html>".html_head();
?>
<style type="text/css">
    .sol-wrap { padding:16px 14px 26px; box-sizing:border-box; }
    .sol-wrap table.datos { width:100%; max-width:860px; margin-bottom:14px; }
    .sol-wrap table.datos td { padding:5px 9px; font-size:12px; }
    .sol-wrap .aviso { padding:10px 12px; margin:0 0 14px; max-width:860px; border:1px solid; }
    .sol-wrap .ok    { background:#e8f5e9; border-color:#1a7f37; color:#1a7f37; }
    .sol-wrap .err   { background:#fdecea; border-color:#b00; color:#b00; }
    .sol-wrap .warn  { background:#fff8e1; border-color:#b36b00; color:#7a4a00; }
    .sol-wrap .botonera { margin:10px 0 0; max-width:860px; text-align:right; }
    .sol-wrap textarea { width:100%; box-sizing:border-box; }
</style>
<script type="text/javascript">
    function resolver(accion) {
        var obs = document.getElementById('observacion').value.replace(/^\s+|\s+$/g, '');
        var texto = {aprobar: '¿Aprobar la solicitud? El ciudadano quedará activo y recibirá sus datos de acceso.',
                     rechazar: '¿Rechazar la solicitud? El ciudadano será desactivado y retirado de los documentos.',
                     cancelar: '¿Cancelar su solicitud? El ciudadano será desactivado y retirado de los documentos.'}[accion];
        if (accion == 'rechazar' && obs == '') {
            alert('Indique el motivo del rechazo en Observación.');
            document.getElementById('observacion').focus();
            return false;
        }
        if (!confirm(texto)) return false;
        document.getElementById('accion').value = accion;
        document.formulario.submit();
        return true;
    }
</script>
<body>
<div class="sol-wrap">
<form name="formulario" method="post" action="aprobacion_ciudadano_ver.php">
    <input type="hidden" name="sol_codigo" value="<?=$sol_codigo?>">
    <input type="hidden" name="mias" value="<?=$mias?>">
    <input type="hidden" name="accion" id="accion" value="">

    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr><td class="titulos4">Solicitud de nuevo ciudadano N&deg; <?=$sol_codigo?> &mdash;
            <span style="color:#fff;font-weight:bold"><?=$nombres_estado[$estado]?></span></td></tr>
    </table>

    <?php if ($mensaje !== '') { ?><div class="aviso ok"><?=$mensaje?></div><?php } ?>
    <?php if ($error !== '')   { ?><div class="aviso err"><?=$error?></div><?php } ?>
    <?php if ($conflicto !== null) { ?><div class="aviso warn"><b>Atenci&oacute;n:</b> <?=$ciud->mensajeCuentaExistente($conflicto, $sol['ciu_cedula'])?> No es posible aprobarla.</div><?php } ?>

    <table class="borde_tab datos" cellpadding="0" cellspacing="3">
        <tr><td class="titulos4" colspan="2">Solicitud</td></tr>
        <?=fila("Solicitante", $h($sol['solicitante']) . " <span style='color:#555'>(" . $h($sol['inst_solicitante']) . ")</span>")?>
        <?=fila("Fecha", $h(substr((string)$sol['fecha_solicitud'], 0, 16)))?>
        <?=fila("Documento", $sol['radi_nume_text']
                ? "<a href='../../verradicado.php?verrad=" . $h($sol['radi_nume_radi']) . "' target='_blank' class='vinculos'>" . $h($sol['radi_nume_text']) . "</a>"
                  . " &mdash; " . $h($sol['radi_asunto']) . ($sol['esta_codi'] == 1 ? " <span style='color:#555'>(en elaboraci&oacute;n)</span>" : "")
                : "<span style='color:#555'>sin documento vinculado</span>")?>
        <?=fila("Colocar como", ((int)$sol['tipo_destinatario'] === 3) ? "Copia" : "Para (destinatario)")?>
        <?=fila("Observaci&oacute;n del solicitante", nl2br($h($sol['observacion_solicita'])))?>
        <?php if ($estado !== 0) { ?>
        <?=fila("Resuelta por", $h($sol['aprobador']) . " &middot; " . $h(substr((string)$sol['fecha_resolucion'], 0, 16)))?>
        <?=fila("Observaci&oacute;n de la resoluci&oacute;n", nl2br($h($sol['observacion_resolucion'])))?>
        <?php } ?>
    </table>

    <table class="borde_tab datos" cellpadding="0" cellspacing="3">
        <tr><td class="titulos4" colspan="2">Datos del ciudadano</td></tr>
        <?=fila("C&eacute;dula", $h($sol['ciu_cedula']))?>
        <?=fila("Otro documento", $h($sol['ciu_documento']))?>
        <?=fila("Nombres", $h($sol['ciu_nombre']))?>
        <?=fila("Apellidos", $h($sol['ciu_apellido']))?>
        <?=fila("T&iacute;tulo", $h($sol['ciu_abr_titulo']) . " " . $h($sol['ciu_titulo']))?>
        <?=fila("Instituci&oacute;n", $h($sol['ciu_empresa']))?>
        <?=fila("Puesto", $h($sol['ciu_cargo']))?>
        <?=fila("E-mail", $h($sol['ciu_email']))?>
        <?=fila("Tel&eacute;fono", $h($sol['ciu_telefono']))?>
        <?=fila("Ciudad", $h($sol['ciudad_nombre']))?>
        <?=fila("Direcci&oacute;n", $h($sol['ciu_direccion']) . ($sol['ciu_referencia'] ? " &mdash; " . $h($sol['ciu_referencia']) : ""))?>
    </table>

    <?php if ($estado === 0) { ?>
    <table class="borde_tab datos" cellpadding="0" cellspacing="3">
        <tr><td class="titulos4" colspan="2">Resoluci&oacute;n</td></tr>
        <tr>
            <td class="titulos2" width="22%">Observaci&oacute;n<br><span style="font-weight:normal;color:#555">obligatoria al rechazar</span></td>
            <td class="listado2"><textarea name="observacion" id="observacion" rows="3" maxlength="600" class="tex_area"></textarea></td>
        </tr>
    </table>
    <div class="botonera">
        <?php if ($es_aprobador) { ?>
            <input type="button" value="Aprobar" class="botones" onclick="resolver('aprobar');" <?=$conflicto !== null ? 'disabled' : ''?>>
            <input type="button" value="Rechazar" class="botones" onclick="resolver('rechazar');">
        <?php } ?>
        <?php if ($es_solicitante) { ?>
            <input type="button" value="Cancelar solicitud" class="botones_largo" onclick="resolver('cancelar');">
        <?php } ?>
        <input type="button" value="Regresar" class="botones" onclick="window.location='<?=$volver?>'">
    </div>
    <?php } else { ?>
    <div class="botonera">
        <input type="button" value="Regresar" class="botones" onclick="window.location='<?=$volver?>'">
    </div>
    <?php } ?>
</form>
</div>
</body>
</html>
