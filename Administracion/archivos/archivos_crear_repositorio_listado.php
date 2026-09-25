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
 * @package    archivos
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
if($_SESSION["perm_actualizar_sistema"]!=1) {
    die("Usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
}
require_once(dirname(__DIR__, 2).'/rec_session.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
$dba =  new ConnectionHandler(dirname(__DIR__, 2), "bodega");

if($orden_cambio==1) {
    if(strtolower($orderTipo)=="desc")
	$orderTipo="asc";
    else
        $orderTipo="desc";
}
if (!$orderTipo) $orderTipo="desc";

$seleccionar = "";
if (isset($_GET["accion"]) and $_GET["accion"]==1) {
    $seleccionar = ", case when i.esta_codi in (1,2) then 'Seleccionar' else '' end as \"SCR_Accion\"
                    , 'fjs_seleccionar_repositorio(\"'||i.indi_codi||'\")' as \"HID_POPUP\"";
}

$isql = "select -- Repositorio de archivos
              i.indi_codi as \"No.\"
            , i.nombre_tabla as \"Tabla\"
            , i.nombre_tablespace as \"Tablespace\"
            , round(i.tamanio::numeric/1073741824,2)::text||' Gb' as \"Tamaño Actual\" --1073741824 Gb
            , round(i.tamanio_maximo::numeric/1073741824,2)::text||' Gb' as \"Tamaño Máximo\"
            , round(i.tamanio::numeric/i.tamanio_maximo::numeric*100,2) as \"% Uso\"
            , e.nombre as \"Estado\"
            $seleccionar
         from indice i
            left outer join estado_indice e on e.esta_codi = i.esta_codi
         order by ".(1+$orderNo)." $orderTipo";
//echo $isql;

$pager = new ADODB_Pager($dba,$isql,'adodb', true,$orderNo,$orderTipo,true);
$pager->checkAll = false;
$pager->checkTitulo = true;
$pager->toRefLinks = $linkPagina;
$pager->toRefVars = $encabezado;
$pager->descCarpetasGen=$descCarpetasGen;
$pager->descCarpetasPer=$descCarpetasPer;
$pager->Render($rows_per_page=20,$linkPagina,$checkbox="chkAnulados");


?>