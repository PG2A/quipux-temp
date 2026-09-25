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
 * Solicitudes de alta de ciudadanos (RQT-7) — listado.
 *
 * Con perm_aprobar_ciudadano (o administrador) se ven todas y se pueden resolver;
 * cualquier otro funcionario ve únicamente las que él mismo registró.
 *
 * @package    ciudadanos_solicitud
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/include/ciudadanos/SolicitudCiudadano.php');

if (($_SESSION["tipo_usuario"] ?? 0) == 2) {
    die( html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.") );
}
if (!SolicitudCiudadano::disponible($db)) {
    die( html_error("El m&oacute;dulo de solicitudes de ciudadanos no est&aacute; habilitado en la base de datos (ejecute db/ciudadanos_solicitud/ejecutar_migracion.sh).") );
}

$es_aprobador = (($_SESSION["perm_aprobar_ciudadano"] ?? 0) == 1 or ($_SESSION["usua_admin_sistema"] ?? 0) == 1);
// ?mias=1 fuerza la vista personal aunque se tenga el permiso
$solo_mias    = (!$es_aprobador or (int)($_GET['mias'] ?? 0) == 1);

$filtro_estado = $_GET['estado'] ?? ($solo_mias ? '' : '0');
if ($filtro_estado !== '' && !in_array($filtro_estado, array('0','1','2','3'), true)) $filtro_estado = '0';
$filtro_texto  = trim(limpiar_sql($_GET['texto'] ?? ''));

$solicitudes = new SolicitudCiudadano($db);
$filas = $solicitudes->listar(array(
    'estado'             => $filtro_estado,
    'texto'              => $filtro_texto,
    'usua_codi_solicita' => $solo_mias ? (int)$_SESSION["usua_codi"] : 0,
));

$nombres_estado = array(0 => 'Pendiente', 1 => 'Aprobada', 2 => 'Rechazada', 3 => 'Cancelada');
$colores_estado = array(0 => '#b36b00', 1 => '#1a7f37', 2 => '#b00', 3 => '#666');
$titulo = $solo_mias ? "Mis solicitudes de ciudadanos" : "Solicitudes de nuevos ciudadanos";

echo "<!DOCTYPE html>".html_head();
?>
<style type="text/css">
    .sol-wrap { padding:16px 14px 26px; box-sizing:border-box; }
    .sol-wrap .filtros td { padding:5px 9px; }
    .sol-wrap table.listado { width:100%; border-collapse:collapse; }
    .sol-wrap table.listado th { text-align:left; padding:6px 8px; }
    .sol-wrap table.listado td { padding:6px 8px; border-bottom:1px solid #e3e8ec; font-size:12px; vertical-align:top; }
    .sol-wrap table.listado tr:hover td { background:#eef3f7; }
    .sol-wrap .vacio { padding:24px; text-align:center; color:#666; }
    .sol-wrap .nota { color:#555; font-size:11px; }
</style>
<body>
<div class="sol-wrap">
<form name="formulario" method="get" action="aprobacion_ciudadanos.php">
    <?php if ($solo_mias && $es_aprobador) { ?><input type="hidden" name="mias" value="1"><?php } ?>
    <table width="100%" class="borde_tab barra_busqueda_top filtros" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td class="titulos4" colspan="6"><?=$titulo?></td>
        </tr>
        <tr>
            <td class="titulos2">Estado</td>
            <td class="listado2">
                <select name="estado" class="select" onchange="document.formulario.submit();">
                    <option value=""  <?=$filtro_estado === '' ? 'selected' : ''?>>Todas</option>
                    <?php foreach ($nombres_estado as $k => $v) { ?>
                    <option value="<?=$k?>" <?=($filtro_estado !== '' && (int)$filtro_estado === $k) ? 'selected' : ''?>><?=$v?></option>
                    <?php } ?>
                </select>
            </td>
            <td class="titulos2">Buscar</td>
            <td class="listado2">
                <input type="text" name="texto" class="tex_area" size="40" maxlength="100" value="<?=htmlspecialchars($filtro_texto, ENT_QUOTES)?>"
                       title="C&eacute;dula, nombre, instituci&oacute;n o n&uacute;mero de documento">
            </td>
            <td class="listado2">
                <input type="submit" value="Buscar" class="botones">
            </td>
            <td class="listado2" align="right">
                <?php if ($es_aprobador) { ?>
                    <?php if ($solo_mias) { ?>
                        <input type="button" value="Ver todas" class="botones_largo" onclick="window.location='aprobacion_ciudadanos.php'">
                    <?php } else { ?>
                        <input type="button" value="Ver s&oacute;lo las m&iacute;as" class="botones_largo" onclick="window.location='aprobacion_ciudadanos.php?mias=1'">
                    <?php } ?>
                <?php } ?>
                <input type="button" value="Regresar" class="botones" onclick="window.location='../formAdministracion.php'">
            </td>
        </tr>
    </table>
</form>

<table class="borde_tab listado" cellpadding="0" cellspacing="0">
    <tr class="grisCCCCCC">
        <th class="titulos5">Fecha</th>
        <th class="titulos5">Ciudadano</th>
        <th class="titulos5">C&eacute;dula</th>
        <th class="titulos5">Instituci&oacute;n</th>
        <?php if (!$solo_mias) { ?><th class="titulos5">Solicitante</th><?php } ?>
        <th class="titulos5">Documento</th>
        <th class="titulos5">Estado</th>
        <th class="titulos5">&nbsp;</th>
    </tr>
<?php if (!$filas) { ?>
    <tr><td colspan="8" class="vacio">No hay solicitudes<?=$filtro_estado !== '' ? " en estado " . strtolower($nombres_estado[(int)$filtro_estado]) : ""?>.</td></tr>
<?php } ?>
<?php foreach ($filas as $f) {
        $estado = (int)$f['estado'];
        $doc = $f['radi_nume_text'] ? htmlspecialchars($f['radi_nume_text']) : '<span class="nota">sin documento</span>';
        if ($f['radi_nume_text'] && $f['radi_asunto']) $doc .= '<br><span class="nota">' . htmlspecialchars(substr($f['radi_asunto'], 0, 60)) . '</span>';
?>
    <tr>
        <td><?=substr((string)$f['fecha_solicitud'], 0, 16)?></td>
        <td><?=htmlspecialchars($f['ciu_nombre_completo'])?><br><span class="nota"><?=htmlspecialchars((string)$f['ciu_email'])?></span></td>
        <td><?=htmlspecialchars((string)$f['ciu_cedula'])?></td>
        <td><?=htmlspecialchars((string)$f['ciu_empresa'])?></td>
        <?php if (!$solo_mias) { ?><td><?=htmlspecialchars((string)$f['solicitante'])?><br><span class="nota"><?=htmlspecialchars((string)$f['inst_solicitante'])?></span></td><?php } ?>
        <td><?=$doc?></td>
        <td><span style="color:<?=$colores_estado[$estado]?>;font-weight:bold"><?=$nombres_estado[$estado]?></span>
            <?php if ($estado != 0) { ?><br><span class="nota"><?=substr((string)$f['fecha_resolucion'], 0, 16)?> &middot; <?=htmlspecialchars((string)$f['aprobador'])?></span><?php } ?>
        </td>
        <td align="center">
            <input type="button" class="botones_azul" value="<?=($estado == 0 && $es_aprobador && !$solo_mias) ? 'Resolver' : 'Ver'?>"
                   onclick="window.location='aprobacion_ciudadano_ver.php?sol_codigo=<?=(int)$f['sol_codigo']?><?=$solo_mias ? '&mias=1' : ''?>'">
        </td>
    </tr>
<?php } ?>
</table>
<p class="nota">Se muestran como m&aacute;ximo 300 solicitudes; use los filtros para acotar.</p>
</div>
</body>
</html>
