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
include_once(dirname(__DIR__, 2).'/include/sumillas/Sumillas.php');

if (($_SESSION["usua_admin_sistema"] ?? 0) != 1 and ($_SESSION["usua_codi"] ?? -1) != 0) {
    die( html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.") );
}

$inst_codi     = 0 + $_SESSION["inst_codi"];
$accion_codi   = 0 + trim(limpiar_numero($_POST["txt_accion_codi"] ?? 0));
$accion_nombre = trim(limpiar_sql($_POST["txt_accion_nombre"] ?? ""));
$accion_activo = (isset($_POST["txt_accion_activo"]) and $_POST["txt_accion_activo"] == 0) ? 0 : 1;
$accion_cate   = 0 + trim(limpiar_numero($_POST["txt_accion_cate"] ?? 0));
$eliminar      = (isset($_POST["txt_eliminar"]) and $_POST["txt_eliminar"] == 1);

$mensaje = "";
$ok      = false;

// La descripción entra a la tabla con un largo máximo de 50 caracteres.
$accion_nombre = mb_substr($accion_nombre, 0, 50);

if (!$eliminar and $accion_nombre == "") {
    $mensaje = "Debe ingresar la descripci&oacute;n de la sumilla.";
} else {

    // Editar y eliminar sólo operan sobre sumillas de la institución en curso.
    if ($accion_codi > 0) {
        $sql = "select accion_codi from accion where accion_codi = $accion_codi and inst_codi = $inst_codi";
        $rs = $db->conn->Execute($sql);
        if (!$rs or $rs->EOF) {
            $accion_codi = 0;
            $mensaje = "La sumilla que intenta modificar no existe en esta instituci&oacute;n.";
        }
    }

    // La categoría elegida tiene que ser de la misma institución; el filtro
    // impide colgar la sumilla de un motivo de otra entidad manipulando el POST.
    if ($mensaje == "" and !$eliminar and $accion_cate > 0) {
        $rs = $db->conn->Execute("select cate_codi from accion_categoria
                                   where cate_codi = $accion_cate and inst_codi = $inst_codi");
        if (!$rs or $rs->EOF) {
            $mensaje = "La categor&iacute;a seleccionada no existe en esta instituci&oacute;n.";
        }
    }

    if ($mensaje == "" and $eliminar) {
        if ($accion_codi == 0) {
            $mensaje = "No se indic&oacute; la sumilla a eliminar.";
        } else {
            $sql = "delete from accion where accion_codi = $accion_codi and inst_codi = $inst_codi";
            $ok = $db->conn->Execute($sql);
            $mensaje = $ok ? "La sumilla fue eliminada correctamente."
                           : "Error al eliminar la sumilla: ".$db->conn->ErrorMsg();
        }
    } elseif ($mensaje == "") {

        // No se admiten dos sumillas con la misma descripción dentro de la
        // institución; se comparan sin tildes ni mayúsculas para que "Análisis"
        // y "ANALISIS" no convivan en el mismo combo.
        $nombre_qs = $db->conn->qstr($accion_nombre);
        $sql = "select accion_codi from accion
                 where inst_codi = $inst_codi
                   and translate(upper(accion_nombre),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                     = translate(upper($nombre_qs),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')";
        if ($accion_codi > 0) $sql .= " and accion_codi <> $accion_codi";
        $rs = $db->conn->Execute($sql);

        if ($rs and !$rs->EOF) {
            $mensaje = "La sumilla <b>".htmlspecialchars($accion_nombre)."</b> ya existe en esta instituci&oacute;n.";
        } else {
            $cate_sql = ($accion_cate > 0) ? $accion_cate : "null";

            if ($accion_codi > 0) {
                $sql = "update accion
                           set accion_nombre = $nombre_qs
                             , accion_activo = $accion_activo
                             , cate_codi     = $cate_sql
                         where accion_codi = $accion_codi and inst_codi = $inst_codi";
            } else {
                // 'accion' no tiene secuencia: el código se toma del máximo global,
                // porque la llave primaria es única entre todas las instituciones.
                $rs = $db->conn->Execute("select coalesce(max(accion_codi),0) + 1 as id from accion");
                $accion_codi = ($rs and !$rs->EOF) ? 0 + $rs->fields["ID"] : 1;
                $sql = "insert into accion (accion_codi, accion_nombre, inst_codi, accion_activo, cate_codi)
                        values ($accion_codi, $nombre_qs, $inst_codi, $accion_activo, $cate_sql)";
            }
            $ok = $db->conn->Execute($sql);
            $mensaje = $ok ? "Los datos de la sumilla se guardaron correctamente."
                           : "Error al guardar la sumilla: ".$db->conn->ErrorMsg();
        }
    }
}

echo "<!DOCTYPE html>".html_head();
?>
<body>
<div class="sumillas-wrap">
    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td class="titulos4">Administraci&oacute;n de Sumillas</td>
        </tr>
        <tr>
            <td class="listado2"><?php echo $mensaje; ?></td>
        </tr>
    </table>
    <div class="sumillas-botonera">
        <input type='button' name='btn_aceptar' value='Aceptar' class='botones' onClick="window.location='sumillas_menu.php'">
    </div>
</div>
</body>
</html>
