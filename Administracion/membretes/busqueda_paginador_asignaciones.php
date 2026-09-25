<?php
session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once(__DIR__.'/membretes_lib.php');
if (($_SESSION["usua_admin_sistema"] ?? 0) != 1) die("");
$orden_cambio = (int)($_GET["orden_cambio"] ?? 0);
$orderTipo = strtolower((string)($_GET["orderTipo"] ?? "asc"));
if ($orden_cambio == 1) $orderTipo = ($orderTipo == "desc") ? "asc" : "desc";
if ($orderTipo != "desc") $orderTipo = "asc";
$orderNo = (int)($_GET["orderNo"] ?? 0);
$txt_buscar_area = trim(limpiar_sql($_GET["txt_buscar_area"] ?? ""));
$slc_filtro = (string)($_GET["slc_filtro"] ?? "todas");
$slc_hoja = (int)($_GET["slc_hoja"] ?? 0);
$linkPagina = "";
$encabezado = "";
$where = membrete_filtro_areas($db);
$defecto = membrete_defecto($db);
$inst = (int)($_SESSION["inst_codi"] ?? 0);
$texto_defecto = $defecto ? "(por defecto) " . str_replace("'", "''", $defecto['memb_nombre']) : "(archivo del área en Administración de Áreas)";

$asig = "(select a.depe_codi,
                 count(*) as n,
                 string_agg(coalesce(t.trad_descr,'Todos los tipos')||': <b>'||m.memb_nombre||'</b>', '<br>' order by a.trad_codigo nulls first) as detalle,
                 max(a.masi_fecha_crea) as ultima,
                 bool_or(a.memb_codi=$slc_hoja) as usa_hoja
          from membrete_asignacion a
          join membrete m on m.memb_codi=a.memb_codi and m.memb_estado=1 and m.inst_codi=$inst
          left join tiporad t on t.trad_codigo=a.trad_codigo
          where a.masi_estado=1 and a.masi_uso='documento'
          group by a.depe_codi)";

$sql = "select d.depe_nomb as \"Área\",
               coalesce(d.dep_sigla,'') as \"Sigla\",
               coalesce(x.detalle, '$texto_defecto') as \"SCR_Hoja membretada\",
               coalesce(to_char(x.ultima,'YYYY-MM-DD HH24:MI'),'') as \"Última asignación\",
               '<a class=\"vinculos\" href=\"javascript:abrir_modal('||d.depe_codi||', '''||replace(replace(d.depe_nomb,'''',''),'\"','')||''')\">'||case when x.depe_codi is null then 'Asignar' else 'Editar' end||'</a>' as \"SCR_Acción\"
        from dependencia d
        left join $asig x on x.depe_codi=d.depe_codi
        where $where";
if ($txt_buscar_area != "") {
    $t = strtoupper($txt_buscar_area);
    $sql .= " and (upper(d.depe_nomb) like '%$t%' or upper(coalesce(d.dep_sigla,'')) like '%$t%')";
}
if ($slc_filtro == "asignadas") $sql .= " and x.depe_codi is not null";
if ($slc_filtro == "defecto") $sql .= " and x.depe_codi is null";
if ($slc_hoja > 0) $sql .= " and x.usa_hoja = true";
$sql .= " order by " . ($orderNo + 1) . " $orderTipo";

$pager = new ADODB_Pager($db->conn, $sql, 'adodb', true, $orderNo, $orderTipo, true);
$pager->checkAll = false;
$pager->checkTitulo = false;
$pager->toRefLinks = $linkPagina;
$pager->toRefVars = $encabezado;
$pager->Render($rows_per_page = 20, $linkPagina, $checkbox = "chkAnulados");
?>
