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
if (isset ($replicacion) && $replicacion && $config_db_replica_adm_busqueda_paginador_usuarios!="") {
    $db = new ConnectionHandler(dirname(__DIR__, 2),$config_db_replica_adm_busqueda_paginador_usuarios);
}
require_once(dirname(__DIR__, 2).'/funciones.php');

$orden_cambio = (int)($_GET["orden_cambio"] ?? 0);
$orderTipo = $_GET["orderTipo"] ?? "asc";

if($orden_cambio==1) {
    if(strtolower($orderTipo)=="desc")
	$orderTipo="asc";
    else
        $orderTipo="desc";
}
if (!$orderTipo) $orderTipo="asc";

$buscar_nom = trim(limpiar_sql($_GET['txt_buscar_nombre'] ?? ''));

$tipo_query = trim(limpiar_sql($_GET['tipo_query'] ?? ''));
//opcion
$opc = trim(limpiar_sql($_GET['ciu_ver'] ?? ''));

// Initialize variables to prevent warnings
$orderNo = (int)($_GET['orderNo'] ?? 0);
$descCargo = $descCargo ?? "Cargo";
$descEmpresa = $descEmpresa ?? "Empresa";
$linkPagina = $linkPagina ?? "";
$encabezado = $encabezado ?? "";
$descCarpetasGen = $descCarpetasGen ?? "";
$descCarpetasPer = $descCarpetasPer ?? "";

include(dirname(__DIR__, 2)."/include/query/administracion/queryCuerpoSolicitud_ext.php");
//$orderNo=$orderNo-1;
//echo $sql;
$pager = new ADODB_Pager($db->conn,$sql,'adodb', true,$orderNo,$orderTipo,true);
$pager->checkAll = false;
$pager->checkTitulo = true;
$pager->toRefLinks = $linkPagina;
$pager->toRefVars = $encabezado;
$pager->descCarpetasGen=$descCarpetasGen;
$pager->descCarpetasPer=$descCarpetasPer;
$pager->Render($rows_per_page=15,$linkPagina,$checkbox="chkAnulados");

?>

  </body>
</html>