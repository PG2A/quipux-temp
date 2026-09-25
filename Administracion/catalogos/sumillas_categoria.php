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

$inst_codi = 0 + $_SESSION["inst_codi"];
$cate_codi = 0 + trim(limpiar_numero($_GET["txt_cate_codi"] ?? 0));

$cate_nombre = "";
$cate_activo = 1;   // Una categoría nueva nace activa.
$cate_orden  = 0;
$num_sumillas = 0;

if ($cate_codi > 0) {
    // El filtro por institución impide editar la categoría de otra entidad
    // cambiando el código en la URL.
    $sql = "select cate_nombre, cate_activo, cate_orden from accion_categoria
            where cate_codi = $cate_codi and inst_codi = $inst_codi";
    $rs = $db->conn->Execute($sql);
    if (!$rs or $rs->EOF) {
        die( html_error("La categor&iacute;a solicitada no existe en esta instituci&oacute;n.") );
    }
    $cate_nombre = $rs->fields["CATE_NOMBRE"];
    $cate_activo = 0 + $rs->fields["CATE_ACTIVO"];
    $cate_orden  = 0 + $rs->fields["CATE_ORDEN"];

    $rs = $db->conn->Execute("select count(*) as total from accion where cate_codi = $cate_codi");
    $num_sumillas = ($rs and !$rs->EOF) ? 0 + $rs->fields["TOTAL"] : 0;
} else {
    // Una categoría nueva se propone al final del árbol.
    $rs = $db->conn->Execute("select coalesce(max(cate_orden),0) + 1 as orden
                                from accion_categoria where inst_codi = $inst_codi");
    $cate_orden = ($rs and !$rs->EOF) ? 0 + $rs->fields["ORDEN"] : 1;
}

$titulo = ($cate_codi > 0) ? "Modificar Categor&iacute;a" : "Nueva Categor&iacute;a";

echo "<!DOCTYPE html>".html_head();
?>
<script type="text/javascript">
    function grabar_categoria() {
        var nombre = document.getElementById('txt_cate_nombre').value.replace(/^\s+|\s+$/g, '');
        if (nombre == '') {
            alert('Debe ingresar el nombre de la categoría.');
            document.getElementById('txt_cate_nombre').focus();
            return false;
        }
        document.getElementById('txt_cate_nombre').value = nombre;
        document.formulario.action = 'sumillas_categoria_grabar.php';
        document.formulario.submit();
    }

    function eliminar_categoria() {
        if (!confirm('¿Seguro que desea eliminar esta categoría?\n\n' +
                     'Si sólo desea retirarla del árbol de reasignación, ' +
                     'marque el estado como Inactivo en lugar de eliminarla.')) return false;
        document.getElementById('txt_eliminar').value = '1';
        document.formulario.action = 'sumillas_categoria_grabar.php';
        document.formulario.submit();
    }
</script>
<body>
<div class="sumillas-wrap">
<form name="formulario" action="" method="post">
    <input type="hidden" name="txt_cate_codi" id="txt_cate_codi" value="<?php echo $cate_codi; ?>">
    <input type="hidden" name="txt_eliminar" id="txt_eliminar" value="0">

    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td class="titulos4"><?php echo $titulo; ?></td>
        </tr>
        <tr>
            <td class="listado2">
                La categor&iacute;a es el motivo bajo el que se agrupan las sumillas en el &aacute;rbol de reasignaci&oacute;n.
            </td>
        </tr>
    </table>

    <table class="borde_tab sumillas-form" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td class="titulos2">* Nombre</td>
            <td class="listado2">
                <input type="text" name="txt_cate_nombre" id="txt_cate_nombre" size="45" maxlength="50"
                       class="tex_area" value="<?php echo htmlspecialchars($cate_nombre, ENT_QUOTES); ?>">
                <span class="sumillas-nota">M&aacute;ximo 50 caracteres.</span>
            </td>
        </tr>
        <tr>
            <td class="titulos2">Orden</td>
            <td class="listado2">
                <input type="text" name="txt_cate_orden" id="txt_cate_orden" size="6" maxlength="6"
                       class="tex_area" value="<?php echo $cate_orden; ?>">
                <span class="sumillas-nota">
                    Posici&oacute;n de la rama en el &aacute;rbol, de menor a mayor. Con el mismo n&uacute;mero
                    se ordenan alfab&eacute;ticamente.
                </span>
            </td>
        </tr>
        <tr>
            <td class="titulos2">Estado</td>
            <td class="listado2">
                <select name="txt_cate_activo" id="txt_cate_activo" class="select">
                    <option value="1" <?php if ($cate_activo == 1) echo "selected"; ?>>Activo</option>
                    <option value="0" <?php if ($cate_activo != 1) echo "selected"; ?>>Inactivo</option>
                </select>
                <span class="sumillas-nota">
                    Al desactivarla, sus sumillas siguen disponibles pero se muestran en la rama
                    &laquo;Sin categor&iacute;a&raquo;.
                </span>
            </td>
        </tr>
<?php if ($num_sumillas > 0) { ?>
        <tr>
            <td class="titulos2">Sumillas</td>
            <td class="listado2">
                <b><?php echo $num_sumillas; ?></b> sumilla(s) clasificadas en esta categor&iacute;a.
                <span class="sumillas-nota">
                    Para eliminar la categor&iacute;a primero debe mover esas sumillas a otra o eliminarlas.
                </span>
            </td>
        </tr>
<?php } ?>
    </table>

    <div class="sumillas-botonera">
        <input type='button' name='btn_guardar' value='Guardar' class='botones' onClick='grabar_categoria();'>
<?php if ($cate_codi > 0) { ?>
        <input type='button' name='btn_eliminar' value='Eliminar' class='botones' onClick='eliminar_categoria();'>
<?php } ?>
        <input type='button' name='btn_regresar' value='Regresar' class='botones' onClick="window.location='sumillas_categorias.php'">
    </div>

</form>
</div>
</body>
</html>
