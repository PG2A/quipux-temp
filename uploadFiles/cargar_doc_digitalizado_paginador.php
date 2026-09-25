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
require_once(dirname(__DIR__)."/funciones.php");

$txt_fecha_desde = trim(limpiar_sql($_GET['txt_fecha_desde']));
$txt_fecha_hasta = trim(limpiar_sql($_GET["txt_fecha_hasta"]));
$txt_dependencia = trim(limpiar_sql($_GET['txt_depe_codi']));
$txt_usuario = trim(limpiar_sql($_GET["txt_usua_codi"]));
$busqRadicados = trim(limpiar_sql($_GET["busqRadicados"]));
$carpeta = 0 + $_GET["carpeta"];
$imprimir = $_GET["imprimir_comprobante"];

$encabezado = "carpeta=$carpeta&";

$busq_radicados_tmp = "";

//Pa buscar por Asunto, Número de Documento ó Número de Referencia
if ($busqRadicados != "") {
    $busq_radicados_tmp .= " and coalesce(radi_nume_text,'')||coalesce(radi_asunto,'')||coalesce(radi_cuentai,'') ilike '%$busqRadicados%' ";
}


//Para buscar por usuario
if($txt_dependencia!="0" and $txt_usuario!="0" and $txt_usuario!="")
    $busq_radicados_tmp .= " and (string_to_array(trim(both '-' from radi_usua_rem), '--') @> array[$txt_usuario::text] or string_to_array(trim(both '-' from radi_usua_dest), '--') @> array[$txt_usuario::text])";

//Para buscar todos
//echo "depen: ".$txt_dependencia;

if ($txt_dependencia!="0" and $txt_usuario=="0") {
                $usr = "'-";
                if ($txt_usua_codi == "0") {
                    $sql = "select usua_codi from usuarios where depe_codi=$txt_dependencia";
                    $rs = $db->conn->Execute($sql);
                    if (!$rs->EOF) {
                        while (!$rs->EOF) {
                            $usr .= $rs->fields["USUA_CODI"] . "-','-";
                            $rs->MoveNext();
                        }
                        $usr = substr($usr,0,-3);
                    }
                } else {
                    $usr = $txt_usua_codi;
                }
               //echo $usr;
    $busq_radicados_tmp .= " and (radi_usua_rem in ($usr) or radi_usua_dest in($usr))";           
}

if ($txt_dependencia=="0" and $txt_usuario!="0"){
    $busq_radicados_tmp .= " and (string_to_array(trim(both '-' from radi_usua_rem), '--') @> array[$txt_usuario::text] or string_to_array(trim(both '-' from radi_usua_dest), '--') @> array[$txt_usuario::text])";
}



$orden_cambio = $_GET['orden_cambio'] ?? 0;
$orderTipo = $_GET['orderTipo'] ?? '';
$orderNo = $_GET['orderNo'] ?? '';

if($orden_cambio==1) {
    if(strtolower($orderTipo)=="desc")
	$orderTipo="asc";
    else
        $orderTipo="desc";
}
//if (!$orderTipo) $orderTipo="desc";

if (!$orderTipo) {
    $orderTipo="desc";
}

if ($orderNo=='') { $orderNo = 5; $orderNo2 = 4; }
$ruta_raiz = "..";
$linkPagina = "$ruta_raiz/uploadFiles/cargar_doc_digitalizado.php?txt_fecha_desde=$txt_fecha_desde&txt_fecha_hasta=$txt_fecha_hasta&txt_depe_codi=$txt_dependencia&txt_usua_codi=$txt_usuario&busqRadicados=$busqRadicados&carpeta=$carpeta&imprimir_comprobante=$imprimir";
$descCarpetasGen = "";
$descCarpetasPer = "";

include_once(dirname(__DIR__)."/include/local/localEcuador.php");
?>
  <body>
    <br>
<?php
    include_once(dirname(__DIR__)."/include/query/uploadFile/queryUploadFileRad.php");
    if (trim($imprimir)=="si")
        $query = $query1;
    if (isset($_GET["asocImgRad"]) && $_GET["asocImgRad"]=="0")
        $query = $query3;

    //    $db->query('set enable_nestloop = off');
    $pager = new ADODB_Pager($db->conn,$query,'adodb',true,$orderNo,$orderTipo,true);
    $pager->checkAll = false;
    $pager->checkTitulo = true;
    $pager->toRefLinks = $linkPagina;
    $pager->toRefVars = $encabezado;
    $pager->descCarpetasGen=$descCarpetasGen;
    $pager->descCarpetasPer=$descCarpetasPer;
    $pager->Render($rows_per_page=20,$linkPagina,$checkbox="chkAnulados");
//    $db->query('set enable_nestloop = on');
?>
  </body>
</html>