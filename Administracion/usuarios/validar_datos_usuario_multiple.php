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
require_once(dirname(__DIR__, 2).'/funciones.php');
if (isset ($replicacion) && $replicacion && $config_db_replica_adm_validar_usuario_multiple!="") {
    $db = new ConnectionHandler(__DIR__, $config_db_replica_adm_validar_usuario_multiple);
}

$usr_cedula = trim(limpiar_sql($_POST['cedula']));
$usr_codigo = (int)($_POST['usr_codigo'] ?? 0);
$usr_tipo = 0 + ($_POST['usr_tipo'] ?? 0);
$descDependencia = $descDependencia ?? "Dependencia";
$descEmpresa = $descEmpresa ?? "Empresa";

$sql = "select --Administracion/usuarios/validar_datos_usuario_multiple - usr=".$_SESSION['usua_codi']."
            case when tipo_usuario='2' then '<i>(Ciu.)</i>' else '<i>(Serv.)</i>' end as \"SCR_Tipo\"
            , usua_nombre as \"Nombre\"
            , usua_cargo as \"Puesto\"
            , depe_nomb as \"$descDependencia\"
            , inst_nombre as \"$descEmpresa\"
        from usuario where usua_cedula like '$usr_cedula' and usua_codi<>$usr_codigo
        order by tipo_usuario asc, inst_codi asc, usua_codi asc";

$rs = $db->conn->Execute($sql);
if (!$rs or $rs->EOF) die("");

//    echo str_replace("<", "&lt;", $isql)."<br>";

$ruta_raiz = $ruta_raiz ?? "../..";
$orderNo = $orderNo ?? 0;
$orderTipo = $orderTipo ?? "asc";
$linkPagina = $linkPagina ?? "";
$encabezado = $encabezado ?? "";
$descCarpetasGen = $descCarpetasGen ?? "";
$descCarpetasPer = $descCarpetasPer ?? "";

echo "<center><blink><img src='$ruta_raiz/iconos/img_alerta_2.gif'>&nbsp;&nbsp;&nbsp;Existen usuarios registrados con el mismo n&uacute;mero de c&eacute;dula.</blink></center>";
$pager = new ADODB_Pager($db->conn,$sql,'adodb', true,$orderNo,$orderTipo);
$pager->checkAll = false;
$pager->checkTitulo = false;
$pager->toRefLinks = $linkPagina;
$pager->toRefVars = $encabezado;
$pager->descCarpetasGen=$descCarpetasGen;
$pager->descCarpetasPer=$descCarpetasPer;
$pager->Render($rows_per_page=20,$linkPagina,$checkbox="chkAnulados");

?>
<br>