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
require_once(dirname(__DIR__).'/funciones.php'); //para traer funciones p_get y p_post
include_once(dirname(__DIR__).'/funciones_interfaz.php');
include_once(dirname(__DIR__).'/include/tx/Historico.php');

//Se guarda datos de radicado y metadato
$hist = new Historico($db);
$record = array();
unset($record);

//$radicados = $_POST['checkValue'] ?? '';
$txtMetRadiCodigo = $_POST['txtMetRadiCodi'] ?? '';
$radi_nume_radi = $_POST['txtRadiCodi'] ?? '';
$txtMetCodigo = $_POST['txtMetCodigo'] ?? '';
$txtListaMetadatos = $_POST['txtListaMetadatos'] ?? '';
$txtTexto = $_POST['txtTexto'] ?? '';
$txtListaCodMetadatos = $_POST['txtListaCodMetadatos'] ?? '';
$txtMetadatosTexto =  $_POST['txtMetadatosTexto'] ?? '';
$usua_codi = $_SESSION["usua_codi"];
$depe_codi = $_SESSION["depe_codi"];
$txtAccion = $_POST['txtAccion'] ?? 0;

if($txtAccion == 1){ //Guardar
    if($txtMetRadiCodigo!="")
        $record["MET_RADI_CODI"] = $txtMetRadiCodigo;
    $record["RADI_NUME_RADI"] = $radi_nume_radi;  
    $record["MET_CODI"] = $txtMetCodigo;    
    $record["DEPE_CODI"] = $depe_codi;
    $record["USUA_CODI"] = $usua_codi;    
    $record["TEXTO"] = "'".$txtTexto."'";
    $record["METADATO"] = "'".$txtListaMetadatos."'";
    $record["METADATO_TEXTO"] = "'".$txtMetadatosTexto."'";
    $record["METADATO_CODI"] = "'".$txtListaCodMetadatos."'";
    $record["FECHA"] = $db->conn->sysTimeStamp;
}

if($txtAccion == 2){ //Eliminar
    $record["MET_RADI_CODI"] = $txtMetRadiCodigo;   
    $record["ESTADO"] = 0;
    $record["FECHA"] = $db->conn->sysTimeStamp;
}

$insertSQL=$db->conn->Replace("METADATOS_RADICADO", $record, "MET_RADI_CODI", false, false);    

//Se guarda histórico
$hist->insertarHistorico($radi_nume_radi, $usua_codi, $usua_codi, $txtMetadatosTexto, 11);

echo "<script>opener.regresar();window.close();</script>";

?>