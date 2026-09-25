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

session_start();
require_once(dirname(__DIR__, 2) ."/rec_session.php");
require_once(dirname(__DIR__, 2) ."/funciones.php");
require_once(dirname(__DIR__, 2) . "/obtenerdatos.php");
if (isset ($replicacion) && $replicacion && $config_db_replica_adm_busqueda_paginador_usuarios!="") {
    $db = new ConnectionHandler(__DIR__, $config_db_replica_adm_busqueda_paginador_usuarios);
}

$nombre = trim(limpiar_sql($_GET['txt_nombre']));
$dependencia = (int)($_GET['txt_dependencia'] ?? 0);
$permiso = (int)($_GET['txt_permiso'] ?? 0);
$estado = (int)($_GET['txt_estado'] ?? 0);
$perfil = (int)($_GET['cmb_usr_perfil'] ?? 0);
$txt_reporte = (int)($_GET['txt_reporte'] ?? 0);

$depe_codi_admin = obtenerAreasAdmin($_SESSION["usua_codi"],$_SESSION["inst_codi"],$_SESSION["usua_admin_sistema"],$db);

//$puesto_cabecera = trim(limpiar_sql($_GET['txt_puesto_cabecera']));
//$correo = trim(limpiar_sql($_GET['txt_correo']));
$orden_cambio = isset($_GET['orden_cambio']) ? $_GET['orden_cambio'] : 0;
$orderTipo = isset($_GET['orderTipo']) ? $_GET['orderTipo'] : 'asc';
if($orden_cambio==1) {
    if(strtolower($orderTipo)=="desc")
	$orderTipo="asc";
    else
        $orderTipo="desc";
}
if (!$orderTipo) $orderTipo="asc";

$sql = '';
include(dirname(__DIR__, 2)."/include/query/administracion/queryCuerpoUsuario.php");
      
//    echo str_replace("<", "&lt;", $isql)."<br>";
$linkPagina = $_SERVER['PHP_SELF']; 
$encabezado = "";
$descCarpetasGen = null;
$descCarpetasPer = null;
$orderNo = isset($_GET['orderNo']) ? (int)$_GET['orderNo'] : 0;

if ($txt_reporte!=1){
    $pager = new ADODB_Pager($db->conn,$sql,'adodb', true,$orderNo,$orderTipo,true);
    $pager->checkAll = false;
    $pager->checkTitulo = true;
    $pager->toRefLink = $linkPagina;
    $pager->toRefVar = $encabezado;
    $pager->descCarpetasGen=$descCarpetasGen;
    $pager->descCarpetasPer=$descCarpetasPer;
    $pager->Render($rows_per_page=20,$linkPagina,$checkbox="chkAnulados");
}else{
     $rs_paginador=$db->conn->query($sql);
     include "busqueda_reporte_manual_usuarios_exportar.php";


?><table width="60%" border="0">
        <tr>
            <td width="33%" align="center">
                <input type="button" name="btn_accion" class="botones_largo" value="Guardar como XLSX" onclick="reportes_generar_guardar_como('XLS')">
            </td>
            <td width="33%" align="center">
                <input type="button" name="btn_accion" class="botones_largo" value="Guardar como PDF" onclick="reportes_generar_guardar_como('PDF')">
            </td>
        </tr>
    </table>
<?php } ?>
  </body>
</html>

