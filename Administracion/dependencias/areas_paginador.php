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
 * Listado paginado de áreas para areas.php. Devuelve las áreas de la institución
 * (o las hijas de un área si viaja txt_padre), con columnas de acción: Editar,
 * Sub áreas (filtra a las hijas) y Puestos (abre el catálogo de cargos).
 *
 * @package    dependencias
 * @author     2026 Marco Terán <marcosdanny14@gmail.com>
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

if ($orden_cambio == 1) {
    $orderTipo = (strtolower($orderTipo) == "desc") ? "asc" : "desc";
}
if (!$orderTipo) $orderTipo = "asc";

$inst_codi   = 0 + $_SESSION["inst_codi"];
$txt_buscar  = trim(limpiar_sql($_GET["txt_buscar"] ?? ""));
$txt_padre   = 0 + ($_GET["txt_padre"] ?? 0);
$txt_estruct = trim($_GET["txt_estruct"] ?? "");   // '', '1' (Sí), '0' (No)

$where = "where d.inst_codi = $inst_codi";

if ($txt_estruct === '1') {
    $where .= " and d.estructura_organica = true";
} elseif ($txt_estruct === '0') {
    $where .= " and d.estructura_organica = false";
}

if ($txt_padre > 0) {
    // Hijas directas del área (sin incluirla a ella misma).
    $where .= " and d.depe_codi_padre = $txt_padre and d.depe_codi <> d.depe_codi_padre";
}

if ($txt_buscar != "") {
    // Coincidencia parcial ignorando tildes y mayúsculas, en nombre o sigla.
    $buscar_qs = $db->conn->qstr("%".$txt_buscar."%");
    $where .= " and ( translate(upper(d.depe_nomb),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                        like translate(upper($buscar_qs),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                   or translate(upper(coalesce(d.dep_sigla,'')),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                        like translate(upper($buscar_qs),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN') )";
}

// Listado general (sin padre): sólo las áreas de PRIMER NIVEL, es decir, las que
// cuelgan directamente de una raíz de la institución. Las sub áreas no se muestran
// aquí; se ven al entrar por la columna "Sub áreas". Se excluyen también las raíces.
if ($txt_padre == 0) {
    $where .= " and d.depe_codi_padre in (select r.depe_codi from dependencia r
                                           where coalesce(r.depe_codi_padre, r.depe_codi) = r.depe_codi)
               and coalesce(d.depe_codi_padre, d.depe_codi) <> d.depe_codi";
}

$desde = "from dependencia d";

// En el listado general se muestra la ruta jerárquica completa (depe_ruta), para
// que el nombre corto de una hija no aparezca huérfano. En un listado de sub áreas
// la cabecera ya indica el área padre, así que basta el nombre corto del área.
$col_area = ($txt_padre > 0) ? "d.depe_nomb" : "depe_ruta(d.depe_codi)";

if (trim($orderNo) == "") {
    $orden = $col_area;
} else {
    $orden = (1 + $orderNo);
}

$sql = "select -- Administracion de Areas
            $col_area as \"Área\"
            , d.dep_sigla as \"Sigla\"
            , case when d.depe_estado = 1 then 'Activo' else 'Inactivo' end as \"Estado\"
            , case when d.estructura_organica then 'Sí' else 'No' end as \"Estruct. orgánica\"
            , case when (select count(*) from dependencia h where h.depe_codi_padre = d.depe_codi and h.depe_codi <> d.depe_codi) > 0
                   then '<a href=\"#\" class=\"vinculos\" onclick=\"ver_subareas(' || d.depe_codi || '); return false;\">'
                        || (select count(*) from dependencia h where h.depe_codi_padre = d.depe_codi and h.depe_codi <> d.depe_codi)
                        || ' sub área(s)</a>'
                   else '<span style=\"color:#8a94a6\">0</span>' end as \"SCR_Sub áreas\"
            , '<a href=\"#\" class=\"vinculos\" onclick=\"ver_puestos(' || d.depe_codi || '); return false;\">'
              || (select count(*) from cargo c where c.depe_codi = d.depe_codi and c.cargo_estado = 1)
              || ' puesto(s)</a>' as \"SCR_Puestos\"
            , '<a href=\"#\" class=\"vinculos\" onclick=\"jefe_area(' || d.depe_codi || '); return false;\">'
              || case when exists (select 1 from usuarios u where u.depe_codi = d.depe_codi and u.cargo_tipo = 1 and u.usua_esta = 1)
                      then 'Jefe' else 'Sin jefe' end
              || '</a>' as \"SCR_Jefe\"
            , '<a href=\"#\" class=\"vinculos\" onclick=\"editar_area(' || d.depe_codi || '); return false;\">Editar</a>' as \"SCR_Acción\"
        $desde
        $where
        order by $orden $orderTipo";

$rs_total = $db->conn->Execute("select count(*) as total $desde $where");
if ($rs_total and !$rs_total->EOF and $rs_total->fields["TOTAL"] == 0) {
    echo "<div class='bandeja-generica'><br><center>No se encontraron &aacute;reas".
         (($txt_buscar != "") ? " para la b&uacute;squeda <b>".htmlspecialchars($txt_buscar)."</b>" : "").
         ".</center><br></div>";
    exit;
}

$pager = new ADODB_Pager($db->conn, $sql, 'adodb', true, $orderNo, $orderTipo, true);
$pager->checkAll = false;
$pager->checkTitulo = true;
$pager->toRefLinks = $linkPagina;
$pager->toRefVars = $encabezado;
$pager->descCarpetasGen = $descCarpetasGen;
$pager->descCarpetasPer = $descCarpetasPer;

echo "<div class='bandeja-generica'>";
$pager->Render($rows_per_page = 20, $linkPagina, $checkbox = "chkAreas");
echo "</div>";
?>
