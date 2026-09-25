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
 * @package    catalogos
 * @author     2026 Marco Terán <marcosdanny14@gmail.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
if (($_SESSION["usua_admin_sistema"] ?? 0) != 1 and ($_SESSION["usua_codi"] ?? -1) != 0) {
    die("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');

p_register_globals();

$orden_cambio = $orden_cambio ?? 0;
$orderTipo = $orderTipo ?? '';
$orderNo = $orderNo ?? '';
$PHP_SELF = $_SERVER['PHP_SELF'];
$linkPagina = "$PHP_SELF?";
$encabezado = $encabezado ?? "";
$descCarpetasGen = $descCarpetasGen ?? '';
$descCarpetasPer = $descCarpetasPer ?? '';

if($orden_cambio==1) {
    if(strtolower($orderTipo)=="desc")
        $orderTipo="asc";
    else
        $orderTipo="desc";
}
if (!$orderTipo) $orderTipo="asc";

$inst_codi  = 0 + $_SESSION["inst_codi"];
$txt_buscar = trim(limpiar_sql($_GET["txt_buscar"] ?? ""));

$where = "where c.inst_codi = $inst_codi";
if ($txt_buscar != "") {
    // Coincidencia parcial ignorando tildes y mayúsculas.
    $buscar_qs = $db->conn->qstr("%".$txt_buscar."%");
    $where .= " and translate(upper(c.cate_nombre),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                 like translate(upper($buscar_qs),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')";
}

// Por defecto se listan en el mismo orden en que aparecen las ramas del árbol.
$orden = (trim($orderNo)=="") ? "c.cate_orden, c.cate_nombre" : (1+$orderNo);

$sql = "select -- Categorias de Sumillas
            c.cate_nombre as \"Categoría\"
            , c.cate_orden as \"Orden\"
            , (select count(*) from accion a where a.cate_codi = c.cate_codi) as \"Sumillas\"
            , case when c.cate_activo = 1 then 'Activo' else 'Inactivo' end as \"Estado\"
            , 'Editar' as \"SCR_Acción\"
            , 'editar_categoria(\"'|| c.cate_codi ||'\");' as \"HID_FUNCION\"
        from accion_categoria c
        $where
        order by $orden $orderTipo";

$rs_total = $db->conn->Execute("select count(*) as total from accion_categoria c $where");
if ($rs_total and !$rs_total->EOF and $rs_total->fields["TOTAL"] == 0) {
    echo "<div class='bandeja-generica'><br><center>No se encontraron categor&iacute;as".
         (($txt_buscar != "") ? " para la b&uacute;squeda <b>".htmlspecialchars($txt_buscar)."</b>" : "").
         ".</center><br></div>";
    exit;
}

$pager = new ADODB_Pager($db->conn,$sql,'adodb', true,$orderNo,$orderTipo,true);
$pager->checkAll = false;
$pager->checkTitulo = true;
$pager->toRefLinks = $linkPagina;
$pager->toRefVars = $encabezado;
$pager->descCarpetasGen=$descCarpetasGen;
$pager->descCarpetasPer=$descCarpetasPer;

// Mismo envoltorio y tamaño de página que las bandejas de documentos.
echo "<div class='bandeja-generica'>";
$pager->Render($rows_per_page=20,$linkPagina,$checkbox="chkCategorias");
echo "</div>";
?>
