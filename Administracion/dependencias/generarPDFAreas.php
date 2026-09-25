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
 * @package    ciudadanos
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
include_once(dirname(__DIR__, 2)."/class_control/class_gen.php");
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');

$areas_pdf = "<!DOCTYPE html>".html_head().'
            <body >';

// Obtener plantilla del area de usuario actual
$area = ObtenerDatosDependencia($_SESSION["depe_codi"],$db);
$plantilla = ObtenerRutaMembrete($_SESSION["depe_codi"], 0, $_SESSION["inst_codi"], $db)["ruta"];

$gen_fecha = new CLASS_GEN();
$date =  date("m/d/Y");
$fecha = $gen_fecha->traducefecha($date);

$datosFecha = "Fecha: ".$fecha."  -  Generado por: ".$_SESSION['usua_nomb'];

$areas_pdf .= '<center>';
$areas_pdf .= "<h5>&Aacute;reas de la Instituci&oacute;n ".$_SESSION["inst_nombre"]."</h5>";

include_once(dirname(__DIR__, 2)."/recursivas/funciones_recursivas.php");
$lista = new FuncionesRecursivas($db);
$lista->tabla = "dependencia";
$lista->id_tabla = "depe_codi";
$lista->id_padre = "depe_codi_padre";
$lista->tabulacion = 6;
$lista->condicion = "depe_estado=1";
$lista->use_memory_cache = true; // Use in-memory cache to avoid timeout
$lista->buscar_padre('depe_codi', "inst_codi = ".$_SESSION['inst_codi']);
$lista->add_campo("Nombre", "80", "depe_nomb");
$lista->add_campo("Siglas", "20", "dep_sigla");
//$lista->display["debug"] = true;
$areas_pdf .= $lista->generar_tabla_recursiva();
$areas_pdf .= '</center></body></html>';

$areas_pdf = preg_replace(':<a.*?/a>:is', '	&diams;', $areas_pdf);
//echo "planitllas".$plantilla;
//echo "<br>areas_sesion_".$_SESSION["depe_codi"];
//echo $areas_pdf;
include(dirname(__DIR__, 2).'/config.php');
require_once(dirname(__DIR__, 2).'/interconexion/generar_pdf.php');

$pdf = ws_generar_pdf($areas_pdf, $plantilla, $servidor_pdf, "", $datosFecha, "", "");

$nombreArch = "reporteAreas.pdf";
header( "Content-Disposition: attachment; filename=".$nombreArch);

header("Content-Type: application/pdf");

print $pdf;
?>