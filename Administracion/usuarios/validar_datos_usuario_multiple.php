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
            , case when usua_esta = 1
                   then '<span style=\"display:inline-block;padding:2px 10px;border-radius:10px;background:#e6f4ea;color:#1e7e34;font-weight:600\">Activo</span>'
                   else '<span style=\"display:inline-block;padding:2px 10px;border-radius:10px;background:#fdecea;color:#c5221f;font-weight:600\">Inactivo</span>'
              end as \"SCR_Estado\"
            , coalesce((select case when x.usua_vigencia_desde is null and x.usua_vigencia_hasta is null then 'Sin límite'
                           else coalesce(to_char(x.usua_vigencia_desde,'YYYY-MM-DD'),'…') || ' a '
                             || coalesce(to_char(x.usua_vigencia_hasta,'YYYY-MM-DD'),'sin fin') end
                 from usuarios x where x.usua_codi = usuario.usua_codi), '—') as \"Vigencia\"
        from usuario
        -- Las cuentas desactivadas guardan la cédula como 'cedula-usua_codi': se incluyen
        -- para que se vea su estado.
        where (usua_cedula like '$usr_cedula' or usua_cedula like '$usr_cedula-%')
          and usua_codi<>$usr_codigo
        order by usua_esta desc, tipo_usuario asc, inst_codi asc, usua_codi asc";

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

echo "<div style='margin:8px 0 4px;font-weight:600;text-align:left'>Cuentas registradas con esta c&eacute;dula</div>";
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