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
 * @package    core
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(__DIR__.'/rec_session.php');
include_once(__DIR__.'/funciones_interfaz.php');
include_once(__DIR__.'/funciones.php');

global $CFG;

if (isset($CFG->replicacion) && $CFG->replicacion && !($CFG->config_db_replica_cuerpo_paginador === "")) {
    $db = new ConnectionHandler(__DIR__, $CFG->config_db_replica_cuerpo_paginador);
}

//Get parameters
$orden_cambio = isset($_GET["orden_cambio"]) ? $_GET["orden_cambio"] : "";

$txt_fecha_desde = trim(limpiar_sql(isset($_GET['txt_fecha_desde']) ? $_GET['txt_fecha_desde'] : ""));
$txt_fecha_hasta = trim(limpiar_sql(isset($_GET["txt_fecha_hasta"]) ? $_GET["txt_fecha_hasta"] : ""));
$estado = isset($_GET["estado"]) ? (int)$_GET["estado"] : 0;
$busqRadicados = trim(limpiar_sql(isset($_GET["busqRadicados"]) ? $_GET["busqRadicados"] : ""));
$carpeta = 0 + (isset($_GET["carpeta"]) ? $_GET["carpeta"] : 0);
$tipoLectura = (isset($_GET["tipoLectura"]) && trim((string)$_GET["tipoLectura"]) !== '') ? (int)$_GET["tipoLectura"] : 2;
$tarea_tipo = isset($_GET["slc_tarea_tipo"]) ? (int)$_GET["slc_tarea_tipo"] : 0;
$tarea_estado = isset($_GET["slc_tarea_estado"]) ? (int)$_GET["slc_tarea_estado"] : 0;

$slc_tipo_fecha = isset($_GET['slc_tipo_fecha']) ? (int)$_GET['slc_tipo_fecha'] : 0;

// Init vars for query/pager
$orderTipo = isset($_GET['orderTipo']) ? $_GET['orderTipo'] : "desc";
$orderNo = isset($_GET['orderNo']) ? $_GET['orderNo'] : 0;
$ruta_raiz = ".";
$PHP_SELF = $_SERVER['PHP_SELF'];
$linkPagina = "$PHP_SELF?";
$descCarpetasGen = null;
$descCarpetasPer = null;
if (!isset($version_light)) $version_light = false;
if (!isset($descZonaHoraria)) $descZonaHoraria = "";
//tipo de documento
$radi_tipo = (int)($_GET['radi_tipo'] ?? 0);

$encabezado = "carpeta=$carpeta&";

$whereFiltro = "";
if ($carpeta!=13)
    if($tipoLectura!='2') $whereFiltro .= " and b.radi_leido=$tipoLectura ";

if ($busqRadicados != "") {
    $whereFiltro .= " and (" . buscar_cadena($busqRadicados,"coalesce(b.radi_nume_text,'')||' '||coalesce(b.radi_asunto,'')||' '||coalesce(b.radi_cuentai,'')").") ";
}

//tipo de documento
//Realizar la busqueda Elaboracion o Enviados o Recibidos
if ($carpeta==1 || $carpeta==8 || $carpeta==2)
if ($radi_tipo!=0) $whereFiltro .= " and b.radi_tipo=$radi_tipo ";


if($orden_cambio==1) {
    if(strtolower($orderTipo)=="desc")
	$orderTipo="asc";
    else
        $orderTipo="desc";
}
//if (!$orderTipo) $orderTipo="desc";

if (!$orderTipo) {
    $orderTipo="desc";
    if ($carpeta == "1" or $carpeta=="99") $orderTipo="asc";
}

//var_dump($carpeta); die();

include __DIR__."/include/query/queryCuerpo.php";

//var_dump(get_inbox_data($carpeta,$txt_fecha_desde, $txt_fecha_hasta, $orderTipo, $orderNo, $whereFiltro)); die();

$pager = new ADODB_Pager($db->conn,$isql,'adodb', true,$orderNo,$orderTipo,true);
$pager->checkAll = false;
$pager->checkTitulo = true;
$pager->toRefLinks = $linkPagina;
$pager->toRefVars = $encabezado;
$pager->descCarpetasGen=$descCarpetasGen;
$pager->descCarpetasPer=$descCarpetasPer;

if ($carpeta == 2 || $carpeta == 8 || $carpeta == 15 || $carpeta == 16){
    $pager->gridAttributes = "width='100%' border='1' bgcolor='white' style='table-layout:fixed'";
}

// Clase contenedora por bandeja
if ($carpeta == 2)        $claseBandeja = 'bandeja-recibidos';
elseif ($carpeta == 8)    $claseBandeja = 'bandeja-enviados';
elseif ($carpeta == 15)   $claseBandeja = 'bandeja-tareas-recibidas';
elseif ($carpeta == 16)   $claseBandeja = 'bandeja-tareas-enviadas';
else                      $claseBandeja = 'bandeja-generica';

echo "<div class='$claseBandeja'>";
$pager->Render($rows_per_page=20,$linkPagina,$checkbox="chkAnulados");
echo "</div>";

//echo $isql;

// El badge del menu es el total de la bandeja, no el del listado que se esta viendo:
// $pager->num_rows cuenta solo las filas que sobrevivieron al filtro de leidos/no leidos
// y al rango de fechas, asi que al filtrar "No leidos" el numero caia a 0 con la bandeja
// llena. count_inbox() devuelve "-1" para las carpetas que no sabe contar: solo ahi se
// usa num_rows.
$contador = "-1";
if (!$version_light) {
    $contador = count_inbox($carpeta);
    if ((string)$contador === "-1")
        $contador = $pager->num_rows;
}
?>
<input type="hidden" name="txt_contador" id="txt_contador" value="<?=$contador?>">
