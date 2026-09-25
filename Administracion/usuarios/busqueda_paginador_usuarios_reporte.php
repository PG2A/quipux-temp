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
require_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/obtenerdatos.php");
if (isset ($replicacion) && $replicacion && $config_db_replica_adm_busqueda_paginador_usuarios!="") {
    $db = new ConnectionHandler(__DIR__, $config_db_replica_adm_busqueda_paginador_usuarios);
}

$nombre = trim(limpiar_sql($_GET['txt_nombre']));
$dependencia = 0+$_GET['txt_dependencia'];
$permiso = 0+$_GET['txt_permiso'];
$estado = 0+$_GET['txt_estado'];
$perfil = 0+$_GET['cmb_usr_perfil'];
$txt_reporte = 0+$_GET['txt_reporte'];
$inst_codi = 0+$_GET['inst_actu'];

$depe_codi_admin = obtenerAreasAdmin($_SESSION["usua_codi"],$_SESSION["inst_codi"],$_SESSION["usua_admin_sistema"],$db);

//$puesto_cabecera = trim(limpiar_sql($_GET['txt_puesto_cabecera']));
//$correo = trim(limpiar_sql($_GET['txt_correo']));
if($orden_cambio==1) {
    if(strtolower($orderTipo)=="desc")
	$orderTipo="asc";
    else
        $orderTipo="desc";
}
if (!$orderTipo) $orderTipo="asc";

$tipoReporte="utilSistema";
include(dirname(__DIR__, 2)."/include/query/administracion/queryCuerpoUsuarioReportes.php");

if ($txt_reporte==0){
    
    $pager = new ADODB_Pager($db->conn,$sql,'adodb', true,$orderNo,$orderTipo,true);
    $pager->checkAll = false;
    $pager->checkTitulo = true;
    $pager->toRefLinks = $linkPagina;
    $pager->toRefVars = $encabezado;
    $pager->descCarpetasGen=$descCarpetasGen;
    $pager->descCarpetasPer=$descCarpetasPer;
    $pager->Render($rows_per_page=20,$linkPagina,$checkbox="chkAnulados");
}else{
    $tipoReporte="utilSistemaReporte";
    include(dirname(__DIR__, 2)."/include/query/administracion/queryCuerpoUsuarioReportes.php");
     $rs_paginador=$db->conn->query($sql);
     include "busqueda_generar_paginador_usuario.php";


?><table width="60%" border="0">
        <tr>
            <td width="33%" align="center">
                <input type="button" name="btn_accion" class="botones_largo" value="Guardar como XLS" onclick="reportes_generar_guardar_como('XLS')">
            </td>
            <td width="33%" align="center">
                <input type="button" name="btn_accion" class="botones_largo" value="Guardar como PDF" onclick="reportes_generar_guardar_como('PDF')">
            </td>
        </tr>
    </table>
<?php } ?>
  </body>
</html>

