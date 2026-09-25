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
 * @package    anexos
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
require_once(dirname(__DIR__)."/funciones.php");

p_register_globals($_GET);

$txt_radi_nume = limpiar_sql(trim($txt_radi_nume));

$orden_cambio = isset($_GET['orden_cambio']) ? $_GET['orden_cambio'] : 0;
$orderTipo = isset($_GET['orderTipo']) ? $_GET['orderTipo'] : 'desc';
$orderNo = isset($_GET['orderNo']) ? $_GET['orderNo'] : 0;
$linkPagina = isset($_GET['linkPagina']) ? $_GET['linkPagina'] : '';
$encabezado = isset($_GET['encabezado']) ? $_GET['encabezado'] : '';
$descCarpetasGen = isset($_GET['descCarpetasGen']) ? $_GET['descCarpetasGen'] : '';
$descCarpetasPer = isset($_GET['descCarpetasPer']) ? $_GET['descCarpetasPer'] : '';

if($orden_cambio=="1") {
    if(strtolower($orderTipo)=="desc")
	$orderTipo="asc";
    else
        $orderTipo="desc";
}

if (!$orderTipo) $orderTipo = "desc";
if (!$orderNo) $orderNo = 3;

    include("incluir_doc_archivo_query.php");
    //$isql = "select * from radicado";

    // Disable page count to avoid timeout on large tables
    $db->conn->pageExecuteCountRows = false; 

//    $db->query('set enable_nestloop = off');
	$pager = new ADODB_Pager($db->conn,$isql,'adodb', true,$orderNo,$orderTipo,true);
	$pager->checkAll = false;
	$pager->checkTitulo = true;
	$pager->toRefLinks = $linkPagina;
	$pager->toRefVars = $encabezado;
	$pager->descCarpetasGen=$descCarpetasGen;
	$pager->descCarpetasPer=$descCarpetasPer;
	$pager->htmlSpecialChars = false;
	$pager->Render($rows_per_page=20,$linkPagina,$checkbox="chkAnulados");
//    $db->query('set enable_nestloop = on');

?>
