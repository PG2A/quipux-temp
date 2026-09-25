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
 * @package    busqueda
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
$ruta_raiz = "..";
include_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__)."/funciones.php");
if (isset ($replicacion) && $replicacion && $config_db_replica_busqueda_paginador!="") {
    $db = new ConnectionHandler(__DIR__, $config_db_replica_busqueda_paginador);
}

$orden_cambio = isset($_POST['orden_cambio']) ? $_POST['orden_cambio'] : (isset($_GET['orden_cambio']) ? $_GET['orden_cambio'] : "");
$orderTipo = isset($_POST['orderTipo']) ? $_POST['orderTipo'] : (isset($_GET['orderTipo']) ? $_GET['orderTipo'] : "");
$txt_tipo_busqueda = isset($_POST['txt_tipo_busqueda']) ? $_POST['txt_tipo_busqueda'] : (isset($_GET['txt_tipo_busqueda']) ? $_GET['txt_tipo_busqueda'] : "");
if (!isset($orderNo)) $orderNo = 0;
if (!isset($linkPagina)) $linkPagina = "";
if (!isset($encabezado)) $encabezado = "";
if (!isset($descCarpetasGen)) $descCarpetasGen = "";
if (!isset($descCarpetasPer)) $descCarpetasPer = "";
if (!isset($descZonaHoraria)) $descZonaHoraria = "";

if($orden_cambio==1) {
    if(strtolower($orderTipo)=="desc")
	$orderTipo="asc";
    else
        $orderTipo="desc";
}
if (!$orderTipo) $orderTipo="desc";

$nestloop = "on";
switch ($txt_tipo_busqueda) {
    case "tramites":
        include "busqueda_tramites_query.php";
        break;
    case "adscritas";
        include "busqueda_adscritas_query.php";
        break;
    default:
        include "busqueda_query.php";
        break;
}


    if ($txt_reporte!=1){
        $pager = new ADODB_Pager($db->conn,$isql,'adodb', true,$orderNo,$orderTipo,true);
        $pager->checkAll = false;
        $pager->checkTitulo = true;
        $pager->toRefLinks = $linkPagina;
        $pager->toRefVars = $encabezado;
        $pager->descCarpetasGen=$descCarpetasGen;
        $pager->descCarpetasPer=$descCarpetasPer;
        $db->conn->pageExecuteCountRows=false;
        $pager->Render($rows_per_page=30,$linkPagina,$checkbox="chkAnulados");
    } else {
        $rs_paginador=$db->conn->query($isql2);
        include "busqueda_generar_paginador.php";
?>
    <table width="60%" border="0">
        <tr>
            <td width="33%" align="center">
                <input type="button" name="btn_accion" class="botones_largo" value="Guardar como XLS" onclick="reportes_generar_guardar_como('XLS')">
            </td>
            <td width="33%" align="center">
                <input type="button" name="btn_accion" class="botones_largo" value="Guardar como PDF" onclick="reportes_generar_guardar_como('PDF')">
            </td>
        </tr>
    </table>
<?php
    }
    //if ($nestloop=="off") $db->query("set enable_nestloop = on");
?>
<input type="hidden" name="hid_flag_activar_boton_buscar" id="hid_flag_activar_boton_buscar" value="1">