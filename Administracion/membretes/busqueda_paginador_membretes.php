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
$txt_buscar = trim(limpiar_sql($_GET["txt_buscar"] ?? ""));
$estado = ((string)($_GET["slc_estado"] ?? "1") === "0") ? 0 : 1;
$inst = (int)($_SESSION["inst_codi"] ?? 0);
$linkPagina = "";
$encabezado = "";

$vista = "'<a href=\"ver_membrete.php?id='||m.memb_codi||'\" target=\"_blank\" title=\"Abrir en tama&ntilde;o completo\"><embed src=\"ver_membrete.php?id='||m.memb_codi||'#toolbar=0&navpanes=0&scrollbar=0&view=Fit\" type=\"application/pdf\" class=\"memb-thumb\"></a>'";
$ver = "'<a class=\"vinculos\" href=\"ver_membrete.php?id='||m.memb_codi||'\" target=\"_blank\">Ver</a><br><a class=\"vinculos\" href=\"ver_membrete.php?id='||m.memb_codi||'&descargar=1\" target=\"_blank\" title=\"Descargar el PDF original\">Descargar</a><br><a class=\"vinculos\" href=\"probar_membrete.php?id='||m.memb_codi||'\" target=\"_blank\">Probar</a>'";
$nombre_js = "replace(replace(m.memb_nombre,'''',''),'\"','')";
if ($estado == 1) {
    $alcance = "'<div class=\"memb-alcance-acciones\"><a class=\"vinculos\" href=\"javascript:usar_en_todas('||m.memb_codi||', '''||$nombre_js||''')\" title=\"Hoja por defecto y sin excepciones por &aacute;rea ni tipo\">Aplicar a todas</a><a class=\"vinculos\" href=\"javascript:personalizar('||m.memb_codi||', '''||$nombre_js||''')\" title=\"Elegir las &aacute;reas o los tipos de documento que la usan\">Personalizar</a></div>'";
    $accion = "'<a class=\"vinculos\" href=\"javascript:editar_membrete('||m.memb_codi||')\">Editar</a><br><a class=\"vinculos\" href=\"javascript:eliminar_membrete('||m.memb_codi||','||m.memb_defecto||')\">Eliminar</a>'";
    $defecto = "'<input type=\"checkbox\" name=\"chk_defecto_'||m.memb_codi||'\" title=\"Solo una hoja puede ser la hoja por defecto\" onclick=\"cambiar_defecto('||m.memb_codi||', this.checked)\"'||case when m.memb_defecto=1 then ' checked' else '' end||'>'";
} else {
    $alcance = "''";
    $accion = "'<a class=\"vinculos\" href=\"javascript:restaurar_membrete('||m.memb_codi||')\">Restaurar</a>'";
    $defecto = "''";
}
$resumen = "concat_ws('<br>',
        case when m.memb_defecto=1 then '<span class=\"memb-estado propia\">Hoja por defecto</span>' end,
        (select '&Aacute;reas: <b>'||count(*)||'</b>' from membrete_asignacion a where a.memb_codi=m.memb_codi and a.masi_estado=1 and a.depe_codi is not null having count(*)>0),
        (select 'Tipos de documento (todas las &aacute;reas): <b>'||string_agg(t.trad_descr, ', ' order by t.trad_descr)||'</b>' from membrete_asignacion a join tiporad t on t.trad_codigo=a.trad_codigo where a.memb_codi=m.memb_codi and a.masi_estado=1 and a.depe_codi is null having count(*)>0))";
$resumen = "coalesce(nullif($resumen,''), '<span class=\"memb-estado sin\">Sin asignaci&oacute;n</span>')";
$sql = "select $vista as \"SCR_Vista previa\",
               m.memb_nombre as \"Nombre\",
               coalesce(m.memb_descripcion,'') as \"Descripción\",
               $defecto as \"SCR_Por defecto\",
               $resumen||$alcance as \"SCR_Alcance\",
               to_char(coalesce(m.memb_fecha_modi, m.memb_fecha_crea),'YYYY-MM-DD HH24:MI') as \"Actualizada\",
               $ver as \"SCR_Archivo\",
               $accion as \"SCR_Acción\"
        from membrete m
        where m.inst_codi=$inst and m.memb_estado=$estado";
if ($txt_buscar != "") {
    $t = strtoupper($txt_buscar);
    $sql .= " and (upper(m.memb_nombre) like '%$t%' or upper(coalesce(m.memb_descripcion,'')) like '%$t%')";
}
if ($orderNo == 0) $orderNo = 1;
$sql .= " order by " . ($orderNo + 1) . " $orderTipo";

$pager = new ADODB_Pager($db->conn, $sql, 'adodb', true, $orderNo, $orderTipo, true);
$pager->checkAll = false;
$pager->checkTitulo = false;
$pager->toRefLinks = $linkPagina;
$pager->toRefVars = $encabezado;
$pager->Render($rows_per_page = 10, $linkPagina, $checkbox = "chkAnulados");
?>
