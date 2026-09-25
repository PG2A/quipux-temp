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
 * @package    tipo_documental
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
require_once(dirname(__DIR__)."/funciones.php"); //para traer funciones p_get y p_post
include_once("obtener_datos_trd.php");

p_register_globals(array());

if (trim($txtOk)=="1") {
    $record = array();
    if (trim($txtCodigo)!="") {
	$mensaje = "Se ha modificado el Item ";
    	$record["TRD_CODI"] = trim($txtCodigo);
    } else {
	$mensaje = "Se ha creado el Item ";
    	$record["TRD_CODI"] = $db->nextId("sec_trd");
    	$record["TRD_FECHA_DESDE"] = $db->conn->sysTimeStamp;
    }
    if (trim($txtPadre)!="") $record["TRD_PADRE"] = trim($txtPadre);
    $record["TRD_NOMBRE"] = $db->conn->qstr($txtNombre);
    $record["TRD_ARCH_GESTION"] = $txtArch1;
    $record["TRD_ARCH_CENTRAL"] = $txtArch2;
    $record["DEPE_CODI"] = $_SESSION['depe_codi'];
    $ok = $db->conn->Replace("TRD", $record, "TRD_CODI", false,false,true,false);
    if (trim($txtCodigo)=="" and $txtEstado==1) 
    	ActivarTRD($record["TRD_CODI"],$db);
    $mensaje .= ObtenerNombreCompletoTRD($record["TRD_CODI"],$db) . "<br/><br/>";

}
if (trim($txtOk)=="2") {
    $mensaje = "Se ha eliminado el Item " . ObtenerNombreCompletoTRD($txtCodigo,$db) . "<br/><br/>";
    DesactivarTRD($txtCodigo,$db);
    $sql = "delete from trd where trd_codi=$txtCodigo";
    $db->conn->Execute($sql);
}
if (trim($txtOk)=="3") {
    $mensaje = "Se ha activado el Item " . ObtenerNombreCompletoTRD($txtCodigo,$db) . "<br/><br/>";
    ActivarTRD($txtCodigo,$db);
}
if (trim($txtOk)=="4") {
    $mensaje = "Se ha Desactivado el Item " . ObtenerNombreCompletoTRD($txtCodigo,$db) . "<br/><br/>";
    DesactivarTRD($txtCodigo,$db);
}
include_once "./nuevo_trd.php";
?>

