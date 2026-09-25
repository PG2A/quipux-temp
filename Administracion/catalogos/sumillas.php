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

$inst_codi   = 0 + $_SESSION["inst_codi"];
$accion_codi = 0 + trim(limpiar_numero($_GET["txt_accion_codi"] ?? 0));

$accion_nombre = "";
$accion_activo = 1;   // Una sumilla nueva nace activa.
$accion_cate   = 0;

if ($accion_codi > 0) {
    // El filtro por institución impide editar la sumilla de otra entidad
    // cambiando el código en la URL.
    $sql = "select accion_nombre, accion_activo, cate_codi from accion
            where accion_codi = $accion_codi and inst_codi = $inst_codi";
    $rs = $db->conn->Execute($sql);
    if (!$rs or $rs->EOF) {
        die( html_error("La sumilla solicitada no existe en esta instituci&oacute;n.") );
    }
    $accion_nombre = $rs->fields["ACCION_NOMBRE"];
    $accion_activo = 0 + $rs->fields["ACCION_ACTIVO"];
    $accion_cate   = 0 + $rs->fields["CATE_CODI"];
}

// Se listan también las categorías desactivadas: si la sumilla está en una, hay
// que poder verla en el combo en lugar de que aparezca como "sin categoría".
$categorias = sumillas_categorias($db, $inst_codi, false);

$titulo = ($accion_codi > 0) ? "Modificar Sumilla" : "Nueva Sumilla";

echo "<!DOCTYPE html>".html_head();
?>
<script type="text/javascript">
    function grabar_sumilla() {
        var descripcion = document.getElementById('txt_accion_nombre').value.replace(/^\s+|\s+$/g, '');
        if (descripcion == '') {
            alert('Debe ingresar la descripción de la sumilla.');
            document.getElementById('txt_accion_nombre').focus();
            return false;
        }
        document.getElementById('txt_accion_nombre').value = descripcion;
        document.formulario.action = 'sumillas_grabar.php';
        document.formulario.submit();
    }

    function eliminar_sumilla() {
        if (!confirm('¿Seguro que desea eliminar esta sumilla?\n\n' +
                     'Si sólo desea retirarla de la pantalla de reasignación, ' +
                     'marque el estado como Inactivo en lugar de eliminarla.')) return false;
        document.getElementById('txt_eliminar').value = '1';
        document.formulario.action = 'sumillas_grabar.php';
        document.formulario.submit();
    }
</script>
<body>
<div class="sumillas-wrap">
<form name="formulario" action="" method="post">
    <input type="hidden" name="txt_accion_codi" id="txt_accion_codi" value="<?php echo $accion_codi; ?>">
    <input type="hidden" name="txt_eliminar" id="txt_eliminar" value="0">

    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td class="titulos4"><?php echo $titulo; ?></td>
        </tr>
        <tr>
            <td class="listado2">
                La descripci&oacute;n es el texto que el usuario ve y escoge al reasignar un documento.
            </td>
        </tr>
    </table>

    <table class="borde_tab sumillas-form" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td class="titulos2">* Descripci&oacute;n</td>
            <td class="listado2">
                <input type="text" name="txt_accion_nombre" id="txt_accion_nombre" size="45" maxlength="50"
                       class="tex_area" value="<?php echo htmlspecialchars($accion_nombre, ENT_QUOTES); ?>">
                <span class="sumillas-nota">M&aacute;ximo 50 caracteres.</span>
            </td>
        </tr>
        <tr>
            <td class="titulos2">Categor&iacute;a (motivo)</td>
            <td class="listado2">
                <select name="txt_accion_cate" id="txt_accion_cate" class="select">
                    <option value="0">&lt;&lt; Sin categor&iacute;a &gt;&gt;</option>
<?php
    foreach ($categorias as $cat) {
        $sel      = ($cat["codi"] == $accion_cate) ? "selected" : "";
        $inactiva = $cat["activo"] ? "" : " (inactiva)";
        echo "                    <option value=\"".$cat["codi"]."\" $sel>"
            . htmlspecialchars($cat["nombre"]).$inactiva."</option>\n";
    }
?>
                </select>
                <span class="sumillas-nota">
                    Motivo bajo el que se agrupa esta sumilla en el &aacute;rbol de reasignaci&oacute;n.
                    Las sumillas sin categor&iacute;a se muestran al final del &aacute;rbol, en una rama
                    &laquo;Sin categor&iacute;a&raquo;.
                </span>
            </td>
        </tr>
        <tr>
            <td class="titulos2">Estado</td>
            <td class="listado2">
                <select name="txt_accion_activo" id="txt_accion_activo" class="select">
                    <option value="1" <?php if ($accion_activo == 1) echo "selected"; ?>>Activo</option>
                    <option value="0" <?php if ($accion_activo != 1) echo "selected"; ?>>Inactivo</option>
                </select>
                <span class="sumillas-nota">S&oacute;lo las sumillas activas se ofrecen al reasignar un documento.</span>
            </td>
        </tr>
    </table>

    <div class="sumillas-botonera">
        <input type='button' name='btn_guardar' value='Guardar' class='botones' onClick='grabar_sumilla();'>
<?php if ($accion_codi > 0) { ?>
        <input type='button' name='btn_eliminar' value='Eliminar' class='botones' onClick='eliminar_sumilla();'>
<?php } ?>
        <input type='button' name='btn_regresar' value='Regresar' class='botones' onClick="window.location='sumillas_menu.php'">
    </div>

</form>
</div>
</body>
</html>
