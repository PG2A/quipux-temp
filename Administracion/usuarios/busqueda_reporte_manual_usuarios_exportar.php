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

$cols = 8;

$estilo_tabla  = "style='border: thin solid #377584;'";
$estilo_titulo = "bgcolor='#6a819d' align='center' valign='middle' height='20px' style='color: #FFFFFF;'";
$estilo_tr0    = "bgcolor='#FFFFFF' align='left' valign='middle' height='20px' style='color: #000000;'";
$estilo_tr1    = "bgcolor='#e3e8ec' align='left' valign='middle' height='20px' style='color: #000000;'";
$estilo_a      = "style='color: #000000;'";

$tabla = "<table $estilo_tabla border='0' width='100%'>";
$tabla .= "<tr $estilo_titulo><td colspan='2'><font size='1'><b>Reportes - Sistema de Gesti&oacute;n Documental &quot;Quipux&quot;</b></font></td></tr>";
$tabla .= "<tr><td $estilo_tr1><font size='1'>Tipo de Reporte:</font></td><td $estilo_tr0><font size='1'>Usuarios de la Institución</font></td></tr>";

$descZonaHoraria = isset($descZonaHoraria) ? $descZonaHoraria : "";
$tabla .= "<tr><td $estilo_tr1><font size='1'>Fecha:</font></td><td $estilo_tr0><font size='1'>".date("Y-m-d").$descZonaHoraria."</font></td></tr>";
$tabla .= "</table><br>";


$tabla  .= "<table $estilo_tabla border='0' width='100%'>";
$tabla .= "<tr $estilo_titulo>";

//for ($i=0 ; $i<$cols ; ++$i) {

        $tabla .= "<td><font size='1'><b>Nombre</b></font></td>";
        $tabla .= "<td><font size='1'><b>Perfil</b></font></td>";
        $tabla .= "<td><font size='1'><b>Subrogación</b></font></td>";
        $tabla .= "<td><font size='1'><b>Email</b></font></td>";
        $tabla .= "<td><font size='1'><b>Área</b></font></td>";
        $tabla .= "<td><font size='1'><b>Puesto</b></font></td>";
        $tabla .= "<td><font size='1'><b>Puesto Cabecera</b></font></td>";
        $tabla .= "<td><font size='1'><b>Estado</b></font></td>";
        
    /*else
        $tabla .= "<td><font size='1'><b>".$cols[$i]."</b></font></td>";
     */
//}
$tabla .= "</tr>";

$num_filas = 0;
$i=0;

while (!$rs_paginador->EOF) {
    
   $tabla .= "<tr>";

    $puesto_cabecera = limpiar_sql($rs_paginador->fields['PUESTO CABECERA']);
    $puesto = limpiar_sql($rs_paginador->fields['PUESTO']);
    $puesto_cabecera = str_replace("′","'", $puesto_cabecera);
    $puesto = str_replace("′","'", $puesto);
    $tabla .= "<td><font size='1'>".$rs_paginador->fields['SCR_NOMBRE']."</font></td>";
    $tabla .= "<td><font size='1'>".$rs_paginador->fields['PERFIL']."</font></td>";

    $tabla .= "<td><font size='1'>".$rs_paginador->fields['SUBROGACIóN']."</font></td>";
    $tabla .= "<td><font size='1'>".$rs_paginador->fields['EMAIL']."</font></td>";
    $tabla .= "<td><font size='1'>".$rs_paginador->fields['ÁREA']."</font></td>";
    $tabla .= "<td><font size='1'>".$puesto."</font></td>";
    $tabla .= "<td><font size='1'>".$puesto_cabecera."</font></td>";
    // En el listado el estado va como etiqueta de color (SCR_Estado); aquí, texto plano.
    $tabla .= "<td><font size='1'>".strip_tags($rs_paginador->fields['SCR_ESTADO'] ?? $rs_paginador->fields['ESTADO'] ?? '')."</font></td>";
    
   
   $tabla .= "</tr>";
    $rs_paginador->MoveNext();
    ++$num_filas;
}

$mensaje = "No. total de registros: $num_filas.";
$tabla .= "<tr $estilo_titulo><td colspan='8'><font size='1'><b>$mensaje</b></font></td></tr>";
$tabla .= "</table><br>";

$path_archivo = "/tmp/reporte_pdf_".$_SESSION["usua_codi"].".html";
file_put_contents(dirname(__DIR__, 2)."/bodega$path_archivo", $tabla);
echo $tabla;

?>
