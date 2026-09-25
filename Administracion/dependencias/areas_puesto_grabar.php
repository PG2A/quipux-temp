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
 * Guarda el alta, la edición o el cambio de estado de un puesto (catálogo
 * 'cargo') y regresa al listado areas_puestos.php con un mensaje. El texto de
 * usuarios.usua_cargo se propaga sólo si se marcó la casilla en la edición.
 *
 * @package    dependencias
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

$inst_codi = 0 + $_SESSION["inst_codi"];
$depe_codi = 0 + trim(limpiar_numero($_REQUEST["depe_codi"] ?? 0));
$cargo_id  = 0 + trim(limpiar_numero($_REQUEST["cargo_id"] ?? 0));
$op        = trim($_REQUEST["op"] ?? "");

// Regresa siempre al listado del área; el mensaje viaja en la URL. Se redirige por
// JavaScript para no depender de que ningún include haya emitido salida antes.
function volver($depe_codi, $msg) {
    $url = "areas_puestos.php?depe_codi=".(int)$depe_codi."&msg=".rawurlencode($msg);
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'>"
       . "<meta http-equiv='refresh' content='0;url=".htmlspecialchars($url, ENT_QUOTES)."'>"
       . "<script>window.location='".addslashes($url)."';</script></head><body></body></html>";
    exit;
}

if ($depe_codi <= 0) {
    die( html_error("No se indic&oacute; el &aacute;rea.") );
}

$rsD = $db->conn->Execute("select inst_codi from dependencia where depe_codi = $depe_codi");
if (!$rsD or $rsD->EOF or (0 + $rsD->fields["INST_CODI"]) != $inst_codi) {
    die( html_error("El &aacute;rea solicitada no existe en esta instituci&oacute;n.") );
}

$en_ambito = obtenerCodigos($_SESSION['usua_codi'], $depe_codi, $db, 1);
$puede_editar = ((int)$en_ambito == 1 || $_SESSION['usua_codi'] == 0 || ($_SESSION['perm_admin_institucional'] ?? 0) == 1);
if (!$puede_editar) {
    die( html_error("No tiene permisos para administrar los puestos de esta &aacute;rea.") );
}

// --------------------------------------------------------------------------
// Cambio de estado (activar / desactivar)
// --------------------------------------------------------------------------
if ($op == 'estado' && $cargo_id > 0) {
    $estado = ((int)($_REQUEST['estado'] ?? 1) == 1) ? 1 : 0;
    $db->update('cargo',
        array('cargo_estado' => $estado, 'usua_codi_actualiza' => (int)$_SESSION['usua_codi']),
        array('cargo_id' => $cargo_id, 'depe_codi' => $depe_codi));
    volver($depe_codi, $estado ? "Puesto activado." : "Puesto desactivado. Los usuarios que lo tienen no se modifican.");
}

// --------------------------------------------------------------------------
// Alta / edición
// --------------------------------------------------------------------------
$nombre   = trim(preg_replace('/\s+/', ' ', limpiar_sql($_POST['puesto_nombre'] ?? '')));
$cabecera = trim(preg_replace('/\s+/', ' ', limpiar_sql($_POST['puesto_cabecera'] ?? '')));
$tipo     = ((int)($_POST['puesto_tipo'] ?? 0) == 1) ? 1 : 0;
$propagar = ((int)($_POST['puesto_propagar'] ?? 0) == 1);
// Nivel: entero opcional. Vacío se guarda como NULL.
$nivel_in = trim($_POST['puesto_nivel'] ?? '');
$nivel    = ($nivel_in === '') ? null : (int)limpiar_numero($nivel_in);

$nombre   = mb_substr($nombre, 0, 200);
$cabecera = mb_substr($cabecera, 0, 200);

if ($nombre == '') {
    volver($depe_codi, "Debe ingresar el nombre del puesto.");
}
if ($cabecera == '') $cabecera = $nombre;

// No se repite el mismo puesto dentro del área (sin mayúsculas ni espacios dobles).
$rsDup = $db->query(
    "select cargo_id from cargo
      where depe_codi = ? and cargo_id <> ?
        and lower(regexp_replace(trim(cargo_nombre), '\\s+', ' ', 'g')) = lower(?)",
    array($depe_codi, $cargo_id, $nombre));
if ($rsDup && !$rsDup->EOF) {
    volver($depe_codi, "Ya existe el puesto \"$nombre\" en esta área.");
}

if ($cargo_id > 0) {
    // La edición sólo opera sobre puestos del área en curso.
    $rsE = $db->conn->Execute("select cargo_id from cargo where cargo_id = $cargo_id and depe_codi = $depe_codi");
    if (!$rsE or $rsE->EOF) {
        volver($depe_codi, "El puesto que intenta modificar no existe en esta área.");
    }

    $ok = $db->update('cargo',
        array('cargo_nombre' => $nombre, 'cargo_cabecera' => $cabecera, 'cargo_tipo' => $tipo,
              'cargo_nivel' => $nivel, 'usua_codi_actualiza' => (int)$_SESSION['usua_codi']),
        array('cargo_id' => $cargo_id, 'depe_codi' => $depe_codi));

    $msg = $ok ? "Puesto actualizado." : "No se pudo actualizar el puesto.";

    // Opcional: llevar el nuevo texto a los usuarios que ya tienen el puesto. El
    // perfil (Jefe/Normal) NO se propaga: se gestiona en "Jefe de Área".
    if ($ok && $propagar) {
        $rsProp = $db->query(
            "update usuarios set usua_cargo = ?, usua_cargo_cabecera = ? where cargo_id = ? and depe_codi = ?",
            array($nombre, $cabecera, $cargo_id, $depe_codi));
        if ($rsProp) {
            $n = $db->conn->Affected_Rows();
            $msg .= " Se actualizó el puesto en $n usuario(s).";
        }
    }
    volver($depe_codi, $msg);
} else {
    $ok = $db->insert('cargo',
        array('depe_codi' => $depe_codi, 'inst_codi' => $inst_codi,
              'cargo_nombre' => $nombre, 'cargo_cabecera' => $cabecera, 'cargo_tipo' => $tipo,
              'cargo_nivel' => $nivel, 'usua_codi_actualiza' => (int)$_SESSION['usua_codi']));
    volver($depe_codi, $ok ? "Puesto creado." : "No se pudo crear el puesto.");
}
