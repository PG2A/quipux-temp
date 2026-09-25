<?php
// This file is part of Quipux – Document Management System
//
// Quipux is free software and is currently under a process of technical
// modernization and functional improvement carried out by
// EXDUCERE ONLINE CIA. LTDA., as part of the development of a new version
// of the Quipux platform.
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
//
// You should have received a copy of the GNU General Public License
// along with Quipux. If not, see <http://www.gnu.org/licenses/>.

/**
 * Guardado de periodos: alta, edición (nombre, fechas, observación) y cambio de
 * modo jerárquico/lineal (op=modo). El historial lo escribe el trigger
 * trg_periodo_historial con usua_codi_actualiza; aquí sólo se valida.
 *
 * @package    periodos
 * @author     2026 Marco Terán <marcosdanny14@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');

if (($_SESSION["usua_admin_sistema"] ?? 0) != 1 and ($_SESSION["usua_codi"] ?? -1) != 0) {
    die( html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.") );
}

$inst_codi    = 0 + $_SESSION["inst_codi"];
$usua_codi    = (int)$_SESSION["usua_codi"];
$periodo_codi = 0 + trim(limpiar_numero($_REQUEST["periodo_codi"] ?? 0));
$op           = trim($_REQUEST["op"] ?? "");

// Regresa siempre al listado; el mensaje viaja en la URL.
function volver($msg) {
    $url = "periodos.php?msg=".rawurlencode($msg);
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'>"
       . "<meta http-equiv='refresh' content='0;url=".htmlspecialchars($url, ENT_QUOTES)."'>"
       . "<script>window.location='".addslashes($url)."';</script></head><body></body></html>";
    exit;
}

function fecha_valida($f) {
    $d = DateTime::createFromFormat('Y-m-d', $f);
    return ($d && $d->format('Y-m-d') === $f);
}

// Datos actuales del periodo (sólo de esta institución).
$actual = null;
if ($periodo_codi > 0) {
    $rs = $db->query(
        "select periodo_codi, (current_date > fecha_fin) as cerrado, vigente,
                to_char(fecha_inicio, 'YYYY-MM-DD') as desde,
                (select count(*) from radicado r where r.periodo_codi = p.periodo_codi) as documentos,
                (select to_char(max(r.radi_fech_radi)::date, 'YYYY-MM-DD') from radicado r
                  where r.periodo_codi = p.periodo_codi) as ultimo_doc
           from periodo p where periodo_codi = ? and inst_codi = ?",
        array($periodo_codi, $inst_codi));
    if (!$rs or $rs->EOF) volver("El periodo no existe en esta institución.");
    $actual = $rs->fields;
    $actual["CERRADO"] = ($actual["CERRADO"] === 't' || $actual["CERRADO"] === true);
    $actual["VIGENTE"] = ($actual["VIGENTE"] === 't' || $actual["VIGENTE"] === true);
}

// --------------------------------------------------------------------------
// Cambio de modo (jerárquico / lineal)
// --------------------------------------------------------------------------
if ($op == 'modo') {
    if (!$actual) volver("No se indicó el periodo.");
    if ($actual["CERRADO"]) volver("El periodo ya terminó; su modo no se puede cambiar.");
    if (!$actual["VIGENTE"]) volver("El periodo no está vigente; márquelo como vigente antes de cambiar su modo.");

    $jerarquico  = ((int)($_REQUEST['jerarquico'] ?? 1) == 1);
    $observacion = mb_substr(trim(limpiar_sql($_REQUEST['observacion'] ?? '')), 0, 500);

    $ok = $db->update('periodo',
        array('jerarquico' => $jerarquico ? 't' : 'f', 'observacion' => $observacion,
              'usua_codi_actualiza' => $usua_codi),
        array('periodo_codi' => $periodo_codi, 'inst_codi' => $inst_codi));
    volver($ok ? "El periodo trabaja ahora en modo ".($jerarquico ? "jerárquico" : "lineal")
                 .". Los documentos ya creados conservan su modo."
               : "No se pudo cambiar el modo: ".$db->conn->ErrorMsg());
}

// --------------------------------------------------------------------------
// Alta / edición
// --------------------------------------------------------------------------
$nombre      = mb_substr(trim(preg_replace('/\s+/', ' ', limpiar_sql($_POST['periodo_nombre'] ?? ''))), 0, 100);
$desde       = trim($_POST['fecha_inicio'] ?? '');
$hasta       = trim($_POST['fecha_fin'] ?? '');
$observacion = mb_substr(trim(limpiar_sql($_POST['observacion'] ?? '')), 0, 500);
$vigente     = ((int)($_POST['vigente'] ?? 0) == 1);

if ($nombre == '') volver("Debe ingresar el nombre del periodo.");
if (!fecha_valida($desde) || !fecha_valida($hasta)) volver("Las fechas del periodo no son válidas.");
if ($hasta < $desde) volver("La fecha de fin no puede ser anterior a la de inicio.");

// Un periodo con documentos no puede dejar a ninguno fuera de su rango.
if ($actual && (int)$actual["DOCUMENTOS"] > 0) {
    if ($desde != $actual["DESDE"]) volver("El periodo ya tiene documentos: la fecha de inicio no se puede cambiar.");
    if ($hasta < $actual["ULTIMO_DOC"]) volver("El periodo tiene documentos hasta el ".$actual["ULTIMO_DOC"].": la fecha de fin no puede ser anterior.");
}

// No puede haber otro periodo registrado (vigente o no) en ese rango de fechas.
// El formulario ya lo avisa y el trigger trg_periodo_validar también lo impide.
$rsSol = $db->query(
    "select periodo_nombre, to_char(fecha_inicio, 'YYYY-MM-DD') as desde, to_char(fecha_fin, 'YYYY-MM-DD') as hasta
       from periodo
      where inst_codi = ? and periodo_codi <> ? and fecha_inicio <= ?::date and fecha_fin >= ?::date",
    array($inst_codi, $periodo_codi, $hasta, $desde));
if ($rsSol && !$rsSol->EOF) {
    volver("No se guardó: ya existe un periodo registrado en ese rango de fechas: \""
           .$rsSol->fields["PERIODO_NOMBRE"]."\" (".$rsSol->fields["DESDE"]." a ".$rsSol->fields["HASTA"].").");
}

if ($actual) {
    $ok = $db->update('periodo',
        array('periodo_nombre' => $nombre, 'fecha_inicio' => $desde, 'fecha_fin' => $hasta,
              'vigente' => $vigente ? 't' : 'f',
              'observacion' => $observacion, 'usua_codi_actualiza' => $usua_codi),
        array('periodo_codi' => $periodo_codi, 'inst_codi' => $inst_codi));
    volver($ok ? "Periodo actualizado." : "No se pudo actualizar el periodo: ".$db->conn->ErrorMsg());
} else {
    $jerarquico = ((int)($_POST['jerarquico'] ?? 1) == 1);
    $ok = $db->insert('periodo',
        array('inst_codi' => $inst_codi, 'periodo_nombre' => $nombre, 'fecha_inicio' => $desde,
              'fecha_fin' => $hasta, 'jerarquico' => $jerarquico ? 't' : 'f', 'vigente' => $vigente ? 't' : 'f',
              'observacion' => $observacion, 'usua_codi_actualiza' => $usua_codi));
    volver($ok ? "Periodo creado." : "No se pudo crear el periodo: ".$db->conn->ErrorMsg());
}
