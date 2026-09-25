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
 * @package    mensajes_alerta
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
if ($_SESSION["admin_institucion"] != 1 and $_SESSION["usua_codi"] != 0) {
    die("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');

p_register_globals();

$orden_cambio = $orden_cambio ?? 0;
$orderTipo = $orderTipo ?? '';
$orderNo = $orderNo ?? '';
$linkPagina = $linkPagina ?? '';
$encabezado = $encabezado ?? array();
$descCarpetasGen = $descCarpetasGen ?? '';
$descCarpetasPer = $descCarpetasPer ?? '';

if($orden_cambio==1) {
    if(strtolower($orderTipo)=="desc")
	$orderTipo="asc";
    else
        $orderTipo="desc";
}
if (!$orderTipo) $orderTipo="asc";

$orden = (trim($orderNo)=="") ? "estado desc, 3" : (1+$orderNo);
$sql = "select -- Menu Bloqueo Sistema
            fecha_inicio as \"Fecha Inicio\"
            , fecha_fin as \"Fecha Fin\"
            , case when fecha_inicio<=now() and now()<fecha_fin and estado=1 then 'Ejecutando' else (case when fecha_fin<now() and estado=1 then 'Finalizado' else (case when estado=1 then 'Pendiente' else 'Cancelado' end) end) end as \"Estado\"
            , case when tipo_mensaje=0 then 'Bloqueo general' else (case when tipo_mensaje=1 then 'Bloqueo a nuevos usuarios' else 'Mensaje' end) end as \"Tipo Mensaje\"
            , mensaje_usuario as \"Mensaje\"
            , ver_usuarios(usua_acceso,',<br>') as \"Usuarios Acceso\"
            , 'Editar' as \"SCR_Acción\"
            , 'editar_mensaje(\"'|| bloq_codi ||'\");' as \"HID_FUNCION\"
        from bloqueo_sistema
        where estado in (0,1)
        order by $orden $orderTipo";

//echo $sql;
$pager = new ADODB_Pager($db->conn,$sql,'adodb', true,$orderNo,$orderTipo,true);
$pager->checkAll = false;
$pager->checkTitulo = true;
$pager->toRefLinks = $linkPagina;
$pager->toRefVars = $encabezado;
$pager->descCarpetasGen=$descCarpetasGen;
$pager->descCarpetasPer=$descCarpetasPer;
$pager->Render($rows_per_page=30,$linkPagina,$checkbox="chkAnulados");

?>