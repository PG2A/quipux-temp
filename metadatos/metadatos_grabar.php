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
 * @package    metadatos
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
require_once(dirname(__DIR__)."/funciones.php"); //para traer funciones p_get y p_post
include_once("metadatos_funciones.php");

p_register_globals(array());


if (trim($txtOk)=="1") {
    $record = array();
    if (trim($txtCodigo)!="") {
	$mensaje = "Se ha modificado el Item ";
    	$record["met_codi"] = trim($txtCodigo);
    } else {
	$mensaje = "Se ha creado el Item ";
        $record["met_codi"] = $db->nextId("sec_metadatos");
        $record["met_estado"] = 1;
    }
    if (trim($txtPadre)!="") 
        $record["met_padre"] = trim($txtPadre);
    $record["inst_codi"] = $_SESSION["inst_codi"];
    if($depe_actu != 0)
        $record["depe_codi"] = $depe_actu;
    $record["met_nombre"] = $db->conn->qstr(limpiar_sql($txtNombre));   
    $record["met_nivel"] = $db->conn->qstr($txtNivel);    
    
    $ok = $db->conn->Replace("metadatos", $record, "met_codi", false,false,true,false);  
    $mensaje .= ObtenerNombreCompletoMET($record["met_codi"],$db) . "<br/><br/>";

}
if (trim($txtOk)=="2") {
    $mensaje = "Se ha eliminado el Item " . ObtenerNombreCompletoMet($txtCodigo,$db) . "<br/><br/>";
    ModificarEstadoMet($txtCodigo, 2, $db);
}
if (trim($txtOk)=="3") {
    $mensaje = "Se ha activado el Item " . ObtenerNombreCompletoMet($txtCodigo,$db) . "<br/><br/>";
    ModificarEstadoMet($txtCodigo, 1, $db);
    ModificarEstadoMetAsc($txtCodigo, $txtPadre, 1, $db);
}
if (trim($txtOk)=="4") {
    $mensaje = "Se ha Desactivado el Item " . ObtenerNombreCompletoMet($txtCodigo,$db) . "<br/><br/>";
    ModificarEstadoMet($txtCodigo, 0, $db);
}
include_once("./metadatos.php");
?>