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
 * Alta y edición de un puesto (catálogo 'cargo') de un área, en ventana propia.
 * Guarda mediante areas_puesto_grabar.php y regresa al listado areas_puestos.php.
 *
 * @package    dependencias
 * @author     2026 Marco Terán <marcosdanny14@gmail.com>
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
$depe_codi = 0 + trim(limpiar_numero($_GET["depe_codi"] ?? 0));
$cargo_id  = 0 + trim(limpiar_numero($_GET["cargo_id"] ?? 0));

if ($depe_codi <= 0) {
    die( html_error("No se indic&oacute; el &aacute;rea.") );
}

$rs = $db->conn->Execute("select depe_ruta($depe_codi) as ruta, inst_codi from dependencia where depe_codi = $depe_codi");
if (!$rs or $rs->EOF or (0 + $rs->fields["INST_CODI"]) != $inst_codi) {
    die( html_error("El &aacute;rea solicitada no existe en esta instituci&oacute;n.") );
}
$ruta = $rs->fields["RUTA"];

$en_ambito = obtenerCodigos($_SESSION['usua_codi'], $depe_codi, $db, 1);
$puede_editar = ((int)$en_ambito == 1 || $_SESSION['usua_codi'] == 0 || ($_SESSION['perm_admin_institucional'] ?? 0) == 1);
if (!$puede_editar) {
    die( html_error("No tiene permisos para administrar los puestos de esta &aacute;rea.") );
}

$nombre = ""; $cabecera = ""; $tipo = 0; $nivel = ""; $titulares = 0;
if ($cargo_id > 0) {
    $rsC = $db->conn->Execute(
        "select cargo_nombre, cargo_cabecera, cargo_tipo, cargo_nivel,
                (select count(*) from usuarios u where u.cargo_id = c.cargo_id and u.usua_esta = 1) as titulares
           from cargo c where c.cargo_id = $cargo_id and c.depe_codi = $depe_codi");
    if (!$rsC or $rsC->EOF) {
        die( html_error("El puesto solicitado no existe en esta &aacute;rea.") );
    }
    $nombre    = $rsC->fields["CARGO_NOMBRE"];
    $cabecera  = $rsC->fields["CARGO_CABECERA"];
    $tipo      = (int)$rsC->fields["CARGO_TIPO"];
    $nivel     = ($rsC->fields["CARGO_NIVEL"] === null || $rsC->fields["CARGO_NIVEL"] === "") ? "" : (int)$rsC->fields["CARGO_NIVEL"];
    $titulares = (int)$rsC->fields["TITULARES"];
}

$titulo = ($cargo_id > 0) ? "Modificar Puesto" : "Registrar Nuevo Puesto";
$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

echo "<!DOCTYPE html>".html_head();
?>
<script type="text/javascript">
    function grabar_puesto() {
        var nombre = document.getElementById('puesto_nombre').value.replace(/^\s+|\s+$/g, '');
        if (nombre == '') {
            alert('Ingrese el nombre del puesto.');
            document.getElementById('puesto_nombre').focus();
            return false;
        }
        document.getElementById('puesto_nombre').value = nombre;
        document.formulario.submit();
    }
</script>
<body>
<div class="sumillas-wrap">
<form name="formulario" action="areas_puesto_grabar.php" method="post">
    <input type="hidden" name="depe_codi" value="<?php echo $depe_codi; ?>">
    <input type="hidden" name="cargo_id"  value="<?php echo $cargo_id; ?>">

    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr><td class="titulos4"><?php echo $titulo; ?></td></tr>
        <tr><td class="listado2"><b><?php echo $h($ruta); ?></b></td></tr>
    </table>

    <table class="borde_tab sumillas-form" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td class="titulos2">* Puesto (pie de firma)</td>
            <td class="listado2">
                <input type="text" name="puesto_nombre" id="puesto_nombre" size="60" maxlength="200"
                       class="tex_area" value="<?php echo $h($nombre); ?>"
                       onblur="if(document.getElementById('puesto_cabecera').value=='') document.getElementById('puesto_cabecera').value=this.value;">
                <span class="sumillas-nota">Texto del puesto tal como sale en el pie de firma del documento.</span>
            </td>
        </tr>
        <tr>
            <td class="titulos2">Cabecera del documento</td>
            <td class="listado2">
                <input type="text" name="puesto_cabecera" id="puesto_cabecera" size="60" maxlength="200"
                       class="tex_area" value="<?php echo $h($cabecera); ?>">
                <span class="sumillas-nota">Si se deja vac&iacute;o se usa el mismo texto del puesto.</span>
            </td>
        </tr>
        <tr>
            <td class="titulos2">Perfil</td>
            <td class="listado2">
                <select name="puesto_tipo" id="puesto_tipo" class="select">
                    <option value="0" <?php if ($tipo == 0) echo "selected"; ?>>Normal</option>
                    <option value="1" <?php if ($tipo == 1) echo "selected"; ?>>Jefe</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="titulos2">Nivel</td>
            <td class="listado2">
                <input type="number" name="puesto_nivel" id="puesto_nivel" min="0" step="1" size="6"
                       class="tex_area" value="<?php echo $h($nivel); ?>">
                <span class="sumillas-nota">Nivel jer&aacute;rquico del puesto (opcional).</span>
            </td>
        </tr>
<?php if ($cargo_id > 0 && $titulares > 0) { ?>
        <tr>
            <td class="titulos2">Titulares</td>
            <td class="listado2">
                <input type="checkbox" name="puesto_propagar" id="puesto_propagar" value="1">
                Actualizar el texto del puesto en los <b><?php echo $titulares; ?></b> usuario(s) que lo tienen
                <span class="sumillas-nota">El perfil Jefe/Normal de los usuarios no cambia desde aqu&iacute;; se gestiona en "Jefe de &Aacute;rea".</span>
            </td>
        </tr>
<?php } ?>
    </table>

    <div class="sumillas-botonera">
        <input type="button" name="btn_guardar" value="Guardar" class="botones" onClick="grabar_puesto();">
        <input type="button" name="btn_regresar" value="Regresar" class="botones"
               onClick="window.location='areas_puestos.php?depe_codi=<?php echo $depe_codi; ?>'">
    </div>
</form>
</div>
</body>
</html>
