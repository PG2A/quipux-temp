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
 * @package    tareas
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__).'/funciones.php');

$txt_tipo_reporte = limpiar_sql($_REQUEST["txt_tipo_reporte"] ?? "");

if (isset ($replicacion) && $replicacion && $config_db_replica_rep_reportes_generar!="") {
    $db = new ConnectionHandler(__DIR__, $config_db_replica_rep_reportes_generar);
}
include_once("reportes_generar_funciones.php");
// $txt_tipo_reporte MUST be defined before this include
include_once("reportes_datos_reportes.php");

if ($_SESSION["usua_perm_estadistica"] != 1) {
    $txt_depe_codi = $_SESSION["depe_codi"];
} else {
    $txt_depe_codi = limpiar_numero($_POST["txt_depe_codi"] ?? "0");
    if ($txt_depe_codi != "0" and isset ($_POST["chk_areas_dependientes"])) $txt_depe_codi = buscar_areas_dependientes($txt_depe_codi,"R");
}
$txt_inst_codi = limpiar_numero($_POST["txt_inst_codi"] ?? "0");

if ($lista_reportes[$txt_tipo_reporte]["mostrar"]=="NO") $txt_lista_columnas = $columnas_default;




include("query_reporte_$txt_tipo_reporte.php");
//echo "<hr>".str_replace("<", "&lt;", $isql)."<hr>";


$rs_paginador = $db->query($isql);
if (!$rs_paginador) {
    echo "<br>Error al generar el reporte.<br><br>";
} else {
    if ($agrupar_reporte) {
        include "reportes_generar_paginador_agrupar.php";
    } else {
        include "reportes_generar_paginador.php";
    }
}
?>
    <br>
    <table width="60%" border="0">
        <tr>
            <td width="33%" align="center">
                <input type="button" name="btn_accion" class="botones_largo" value="Regresar" onclick="reportes_regresar()">
            </td>
            <td width="33%" align="center">
                <input type="button" name="btn_accion" class="botones_largo" value="Guardar como XLS" onclick="reportes_generar_guardar_como('XLS')">
            </td>
            <td width="33%" align="center">
                <input type="button" name="btn_accion" class="botones_largo" value="Guardar como PDF" onclick="reportes_generar_guardar_como('PDF')">
            </td>
        </tr>
    </table>
    <!--input type="button" name="btn_accion" class="botones_largo" value="imprimir" onclick="window.print()"-->
