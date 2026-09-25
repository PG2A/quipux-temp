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
 * @package    usuarios
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$cols = 7;

$estilo_tabla  = "style='border: thin solid #377584;'";
$estilo_titulo = "bgcolor='#6a819d' align='center' valign='middle' height='20px' style='color: #FFFFFF;'";
$estilo_tr0    = "bgcolor='#FFFFFF' align='left' valign='middle' height='20px' style='color: #000000;'";
$estilo_tr1    = "bgcolor='#e3e8ec' align='left' valign='middle' height='20px' style='color: #000000;'";
$estilo_a      = "style='color: #000000;'";

$tabla = "<table $estilo_tabla border='0' width='100%'>";
$tabla .= "<tr $estilo_titulo><td colspan='2'><font size='1'><b>Reportes - Sistema de Gesti&oacute;n Documental &quot;Quipux&quot;</b></font></td></tr>";
$tabla .= "<tr><td $estilo_tr1><font size='1'>Tipo de Reporte:</font></td><td $estilo_tr0><font size='1'>Actividad en el Sistema</font></td></tr>";

$tabla .= "<tr><td $estilo_tr1><font size='1'>Fecha:</font></td><td $estilo_tr0><font size='1'>".date("Y-m-d").$descZonaHoraria."</font></td></tr>";
$tabla .= "</table><br>";


$tabla  .= "<table $estilo_tabla border='0' width='100%'>";
$tabla .= "<tr $estilo_titulo>";

//for ($i=0 ; $i<$cols ; ++$i) {
        $tabla .= "<td><font size='1'><b>Cédula/Reporte</b></font></td>";
        $tabla .= "<td><font size='1'><b>Servidor Público</b></font></td>";
        $tabla .= "<td><font size='1'><b>Actividad</b></font></td>";
        $tabla .= "<td><font size='1'><b>Correo</b></font></td>";
        $tabla .= "<td><font size='1'><b>Área</b></font></td>";
        $tabla .= "<td><font size='1'><b>Días <br> Transcurridos de uso</b></font></td>";
        $tabla .= "<td><font size='1'><b>Fecha de Último ingreso al Sistema</b></font></td>";        
        
    /*else
        $tabla .= "<td><font size='1'><b>".$cols[$i]."</b></font></td>";
     */
//}
$tabla .= "</tr>";

$num_filas = 0;
$i=0;

while (!$rs_paginador->EOF) {
   $tabla .= "<tr>";
   
    
    $tabla .= "<td><font size='1'>".$rs_paginador->fields['CéDULA/PASAPORTE']."</font></td>";
    $tabla .= "<td><font size='1'>".$rs_paginador->fields['NOMBRE']."</font></td>";

    $tabla .= "<td><font size='1'>".$rs_paginador->fields['ACTIVIDAD']."</font></td>";
    $tabla .= "<td><font size='1'>".$rs_paginador->fields['CORREO']."</font></td>";
    $tabla .= "<td><font size='1'>".$rs_paginador->fields['ÁREA']."</font></td>";
    $tabla .= "<td><font size='1'>".$rs_paginador->fields['DIAS TRANSCURRIDOS DE USO']."</font></td>";
    $tabla .= "<td><font size='1'>".$rs_paginador->fields['ÚLTIMO INGRESO AL SISTEMA']."</font></td>";
    
   
   $tabla .= "</tr>";
    $rs_paginador->MoveNext();
    ++$num_filas;
}

$mensaje = "No. total de registros: $num_filas.";
$tabla .= "<tr $estilo_titulo><td colspan='7'><font size='1'><b>$mensaje</b></font></td></tr>";
$tabla .= "</table><br>";

$path_archivo = "/tmp/reporte_pdf_".$_SESSION["usua_codi"].".html";
file_put_contents(dirname(__DIR__, 2)."/bodega$path_archivo", $tabla);
echo $tabla;

?>
