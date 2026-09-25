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
 * @package    reportes
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();

//Datos para Reporte-Tareas
include_once(dirname(__DIR__).'/rec_session.php');
$db = new ConnectionHandler(dirname(__DIR__),"reportes");

// Obtener plantilla del area del usuario actual
include_once(dirname(__DIR__).'/obtenerdatos.php');
$area = ObtenerDatosDependencia($_SESSION["depe_codi"],$db);

//Incluir funciones para consultar tareas del radicado.
include_once(dirname(__DIR__)."/tareas/tareas_funciones.php");

$verrad = $_GET["verrad"];

$doc_pdf = "<!DOCTYPE html>
<head>
<title>.: QUIPUX - TAREAS :.</title>
<meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
</head>
<body><br>";

$doc_pdf .= dibujar_tareas($db, 0, $verrad, $ruta_raiz, "PDF");
$doc_pdf .= "<br>";
$doc_pdf .= dibujar_tareas($db, 1, $verrad, $ruta_raiz, "PDF");
$doc_pdf .= "</body></html>";

$doc_pdf = str_replace("width='95%'","width='95%' rules='rows'", $doc_pdf);

//GENERACION DEL PDF
include(dirname(__DIR__).'/config.php');
require_once(dirname(__DIR__).'/interconexion/generar_pdf.php');
$plantilla = "";
$plantilla = ObtenerRutaMembrete($_SESSION["depe_codi"], 0, $_SESSION["inst_codi"], $db)["ruta"];
$pdf = ws_generar_pdf($doc_pdf, $plantilla, $servidor_pdf,"","","","","R");
$nomArch="Reporte_Tareas.pdf";
header( "Content-Disposition: attachment; filename=$nomArch");
header("Content-Type:application/pdf");//.application/pdf
header("Content-Transfer-Encoding: binary");
echo  $pdf;
?>