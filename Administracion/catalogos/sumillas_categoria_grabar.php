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
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');

if (($_SESSION["usua_admin_sistema"] ?? 0) != 1 and ($_SESSION["usua_codi"] ?? -1) != 0) {
    die( html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.") );
}

$inst_codi   = 0 + $_SESSION["inst_codi"];
$cate_codi   = 0 + trim(limpiar_numero($_POST["txt_cate_codi"] ?? 0));
$cate_nombre = trim(limpiar_sql($_POST["txt_cate_nombre"] ?? ""));
$cate_orden  = 0 + trim(limpiar_numero($_POST["txt_cate_orden"] ?? 0));
$cate_activo = (isset($_POST["txt_cate_activo"]) and $_POST["txt_cate_activo"] == 0) ? 0 : 1;
$eliminar    = (isset($_POST["txt_eliminar"]) and $_POST["txt_eliminar"] == 1);

$mensaje = "";
$ok      = false;

// El nombre entra a la tabla con un largo máximo de 50 caracteres.
$cate_nombre = mb_substr($cate_nombre, 0, 50);
if ($cate_orden < 0) $cate_orden = 0;

if (!$eliminar and $cate_nombre == "") {
    $mensaje = "Debe ingresar el nombre de la categor&iacute;a.";
} else {

    // Editar y eliminar sólo operan sobre categorías de la institución en curso.
    if ($cate_codi > 0) {
        $rs = $db->conn->Execute("select cate_codi from accion_categoria
                                   where cate_codi = $cate_codi and inst_codi = $inst_codi");
        if (!$rs or $rs->EOF) {
            $cate_codi = 0;
            $mensaje = "La categor&iacute;a que intenta modificar no existe en esta instituci&oacute;n.";
        }
    }

    if ($mensaje == "" and $eliminar) {
        if ($cate_codi == 0) {
            $mensaje = "No se indic&oacute; la categor&iacute;a a eliminar.";
        } else {
            // Borrar una categoría con sumillas rompería la llave foránea; se
            // explica el caso en vez de dejar que salte el error de la base.
            $rs = $db->conn->Execute("select count(*) as total from accion where cate_codi = $cate_codi");
            $usadas = ($rs and !$rs->EOF) ? 0 + $rs->fields["TOTAL"] : 0;

            if ($usadas > 0) {
                $mensaje = "No se puede eliminar: $usadas sumilla(s) est&aacute;n clasificadas en esta "
                         . "categor&iacute;a. Mu&eacute;valas a otra categor&iacute;a o elim&iacute;nelas primero.";
            } else {
                $sql = "delete from accion_categoria where cate_codi = $cate_codi and inst_codi = $inst_codi";
                $ok = $db->conn->Execute($sql);
                $mensaje = $ok ? "La categor&iacute;a fue eliminada correctamente."
                               : "Error al eliminar la categor&iacute;a: ".$db->conn->ErrorMsg();
            }
        }
    } elseif ($mensaje == "") {

        // No se admiten dos categorías con el mismo nombre dentro de la
        // institución; se comparan sin tildes ni mayúsculas.
        $nombre_qs = $db->conn->qstr($cate_nombre);
        $sql = "select cate_codi from accion_categoria
                 where inst_codi = $inst_codi
                   and translate(upper(cate_nombre),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                     = translate(upper($nombre_qs),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')";
        if ($cate_codi > 0) $sql .= " and cate_codi <> $cate_codi";
        $rs = $db->conn->Execute($sql);

        if ($rs and !$rs->EOF) {
            $mensaje = "La categor&iacute;a <b>".htmlspecialchars($cate_nombre)."</b> ya existe en esta instituci&oacute;n.";
        } else {
            if ($cate_codi > 0) {
                $sql = "update accion_categoria
                           set cate_nombre = $nombre_qs
                             , cate_activo = $cate_activo
                             , cate_orden  = $cate_orden
                         where cate_codi = $cate_codi and inst_codi = $inst_codi";
            } else {
                $cate_codi = $db->nextId("sec_accion_categoria");
                $sql = "insert into accion_categoria (cate_codi, cate_nombre, cate_activo, cate_orden, inst_codi)
                        values ($cate_codi, $nombre_qs, $cate_activo, $cate_orden, $inst_codi)";
            }
            $ok = $db->conn->Execute($sql);
            $mensaje = $ok ? "Los datos de la categor&iacute;a se guardaron correctamente."
                           : "Error al guardar la categor&iacute;a: ".$db->conn->ErrorMsg();
        }
    }
}

echo "<!DOCTYPE html>".html_head();
?>
<body>
<div class="sumillas-wrap">
    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td class="titulos4">Categor&iacute;as de Sumillas</td>
        </tr>
        <tr>
            <td class="listado2"><?php echo $mensaje; ?></td>
        </tr>
    </table>
    <div class="sumillas-botonera">
        <input type='button' name='btn_aceptar' value='Aceptar' class='botones' onClick="window.location='sumillas_categorias.php'">
    </div>
</div>
</body>
</html>
