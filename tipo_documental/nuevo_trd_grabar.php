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

//session_start();
include_once(dirname(__DIR__).'/rec_session.php');
require_once(dirname(__DIR__)."/funciones.php"); //para traer funciones p_get y p_post
include_once("obtener_datos_trd.php");

p_register_globals(array());

if (trim($txtOk)=="1") {
    $record = array();
    if (trim($txtCodigo)!="") {
	$mensaje = "Se ha modificado el Item ";
    	$record["trd_codi"] = trim($txtCodigo);
    } else {
	$mensaje = "Se ha creado el Item ";
    	$record["trd_codi"] = $db->nextId("sec_trd");
    	$record["trd_fecha_desde"] = $db->conn->sysTimeStamp;
    }
    if (trim($txtPadre)!="") $record["trd_padre"] = trim($txtPadre);
    $record["trd_nombre"] = $db->conn->qstr(limpiar_sql($txtNombre));
    $record["trd_arch_gestion"] = $db->conn->qstr($txtArch1);
    $record["trd_arch_central"] = $db->conn->qstr($txtArch2);
    $record["trd_nivel"] = $db->conn->qstr($txtNivel);
    $record["depe_codi"] = $depe_actu;
    ////$record["TRD_OCUPADO"] = "1";
    if ($txtEstado==1 or $txtEstado==0)
        $record["trd_estado"] = "$txtEstado";    
    $ok = $db->conn->Replace("trd", $record, "trd_codi", false,false,true,false);
    //Se comenta este codigo porque es para actualizar el campo trd_ocupado que no se usa
    /*if (trim($txtCodigo)=="" and $txtEstado==1) {
    	ActivarTRD($record["trd_codi"], "E", $db);
    	ActivarTRD($record["trd_codi"], "O", $db);
    }*/
    $mensaje .= ObtenerNombreCompletoTRD($record["trd_codi"],$db) . "<br/><br/>";

}
if (trim($txtOk)=="2") {
    $mensaje = "Se ha eliminado el Item " . ObtenerNombreCompletoTRD($txtCodigo,$db) . "<br/><br/>";
    ModificarEstadoTRD($txtCodigo, 2, $db);
}
if (trim($txtOk)=="3") {
    $mensaje = "Se ha activado el Item " . ObtenerNombreCompletoTRD($txtCodigo,$db) . "<br/><br/>";
    ModificarEstadoTRD($txtCodigo, 1, $db);
}
if (trim($txtOk)=="4") {
    $mensaje = "Se ha Desactivado el Item " . ObtenerNombreCompletoTRD($txtCodigo,$db) . "<br/><br/>";
    ModificarEstadoTRD($txtCodigo, 0, $db);
}
include_once "./nuevo_trd.php";
?>

