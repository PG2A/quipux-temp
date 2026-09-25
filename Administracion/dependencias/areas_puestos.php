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
 * Listado de los puestos (catálogo 'cargo') de un área, a pantalla completa. El
 * alta y la edición se hacen en una ventana aparte (areas_puesto_form.php) y al
 * guardar se regresa aquí. Se llega desde la columna "Puestos" de areas.php.
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

if ($depe_codi <= 0) {
    die( html_error("No se indic&oacute; el &aacute;rea.") );
}

$rs = $db->conn->Execute("select depe_ruta($depe_codi) as ruta, inst_codi from dependencia where depe_codi = $depe_codi");
if (!$rs or $rs->EOF or (0 + $rs->fields["INST_CODI"]) != $inst_codi) {
    die( html_error("El &aacute;rea solicitada no existe en esta instituci&oacute;n.") );
}
$ruta = $rs->fields["RUTA"];

// "Regresar al listado" vuelve al listado donde vivía esta área: el de su área
// padre (areas.php?padre=N). Si el área es de primer nivel (su padre es la raíz de
// la institución) o no tiene padre, vuelve al listado general (areas.php).
$volver_listado = "areas.php";
$rsPad = $db->conn->Execute(
    "select p.depe_codi as pad, (coalesce(p.depe_codi_padre, p.depe_codi) = p.depe_codi) as pad_es_raiz
       from dependencia d join dependencia p on p.depe_codi = d.depe_codi_padre
      where d.depe_codi = $depe_codi and d.depe_codi <> d.depe_codi_padre");
if ($rsPad and !$rsPad->EOF) {
    $esRaiz = ($rsPad->fields["PAD_ES_RAIZ"] === 't' || $rsPad->fields["PAD_ES_RAIZ"] === true || $rsPad->fields["PAD_ES_RAIZ"] == 1);
    if (!$esRaiz) $volver_listado = "areas.php?padre=".(0 + $rsPad->fields["PAD"]);
}

// Puede editar si el área está en su ámbito, o es superadmin / admin institucional.
$en_ambito = obtenerCodigos($_SESSION['usua_codi'], $depe_codi, $db, 1);
$puede_editar = ((int)$en_ambito == 1 || $_SESSION['usua_codi'] == 0 || ($_SESSION['perm_admin_institucional'] ?? 0) == 1);

// Mensaje de retorno tras guardar/eliminar (lo pasa areas_puesto_grabar.php).
$msg = trim($_GET["msg"] ?? "");

$rsList = $db->conn->Execute(
    "select c.cargo_id, c.cargo_nombre, c.cargo_cabecera, c.cargo_tipo, c.cargo_nivel, c.cargo_estado,
            (select count(*) from usuarios u where u.cargo_id = c.cargo_id and u.usua_esta = 1) as titulares
       from cargo c
      where c.depe_codi = $depe_codi
      order by c.cargo_estado desc, c.cargo_tipo desc, lower(c.cargo_nombre)");

$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

echo "<!DOCTYPE html>".html_head();
?>
<script type="text/javascript">
    function nuevo_puesto()          { window.location = 'areas_puesto_form.php?depe_codi=<?php echo $depe_codi; ?>&cargo_id=0'; }
    function editar_puesto(cargoId)  { window.location = 'areas_puesto_form.php?depe_codi=<?php echo $depe_codi; ?>&cargo_id=' + cargoId; }
    function cambiar_estado(cargoId, estado) {
        var txt = (estado == 0) ? '¿Desactivar el puesto?' : '¿Activar el puesto?';
        if (confirm(txt))
            window.location = 'areas_puesto_grabar.php?depe_codi=<?php echo $depe_codi; ?>&cargo_id=' + cargoId + '&op=estado&estado=' + estado;
    }
    function ver_titulares(cargoId) {
        window.open('areas_puesto_titulares.php?cargo_id=' + cargoId, 'titulares',
                    'width=760, height=520, scrollbars=yes, resizable=yes, toolbar=no, menubar=no, location=no, status=no');
    }
</script>
<body>
<div class="sumillas-wrap">

    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr><td class="titulos4">Puestos del &Aacute;rea</td></tr>
        <tr><td class="listado2"><b><?php echo $h($ruta); ?></b></td></tr>
<?php if ($msg != "") { ?>
        <tr><td class="listado2_ver"><b><?php echo $h($msg); ?></b></td></tr>
<?php } ?>
    </table>

    <div class="sumillas-botonera">
<?php if ($puede_editar) { ?>
        <input type="button" name="btn_nuevo" value="Registrar nuevo puesto" class="botones" onClick="nuevo_puesto();">
<?php } ?>
        <input type="button" name="btn_volver_area" value="Volver al &Aacute;rea" class="botones"
               onClick="window.location='areas_form.php?txt_depe_codi=<?php echo $depe_codi; ?>'">
        <input type="button" name="btn_regresar" value="Regresar al listado" class="botones"
               onClick="window.location='<?php echo $volver_listado; ?>'">
    </div>

    <center>
    <table width="100%" class="borde_tab" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td width="30%" align="center" class="titulos2">Puesto (pie de firma)</td>
            <td width="30%" align="center" class="titulos2">Cabecera del documento</td>
            <td width="7%"  align="center" class="titulos2">Perfil</td>
            <td width="6%"  align="center" class="titulos2">Nivel</td>
            <td width="7%"  align="center" class="titulos2">Titulares</td>
            <td width="8%"  align="center" class="titulos2">Estado</td>
            <td width="12%" align="center" class="titulos2">Acci&oacute;n</td>
        </tr>
<?php
if (!$rsList or $rsList->EOF) {
    echo '<tr><td colspan="7" class="listado2_ver" align="center">El &Aacute;rea a&uacute;n no tiene puestos registrados.</td></tr>';
}
while ($rsList && !$rsList->EOF) {
    $c = $rsList->fields;
    $id = (int)$c['CARGO_ID'];
    $activo = ((int)$c['CARGO_ESTADO'] == 1);
    $clase = $activo ? 'listado2_ver' : 'listado2';
    $nivel = ($c['CARGO_NIVEL'] === null || $c['CARGO_NIVEL'] === '') ? '&nbsp;' : (int)$c['CARGO_NIVEL'];
    echo '<tr>';
    echo '<td class="'.$clase.'">'.$h($c['CARGO_NOMBRE']).'</td>';
    echo '<td class="'.$clase.'">'.$h($c['CARGO_CABECERA']).'</td>';
    echo '<td class="'.$clase.'" align="center">'.(((int)$c['CARGO_TIPO'] == 1) ? '<b>Jefe</b>' : 'Normal').'</td>';
    echo '<td class="'.$clase.'" align="center">'.$nivel.'</td>';
    $nt = (int)$c['TITULARES'];
    if ($nt > 0)
        echo '<td class="'.$clase.'" align="center"><a href="#" class="vinculos" onclick="ver_titulares('.$id.'); return false;">'.$nt.'</a></td>';
    else
        echo '<td class="'.$clase.'" align="center">0</td>';
    echo '<td class="'.$clase.'" align="center">'.($activo ? 'Activo' : '<i>Inactivo</i>').'</td>';
    echo '<td class="listado2" align="center">';
    if ($puede_editar) {
        echo '<input type="button" class="botones" value="Editar" onclick="editar_puesto('.$id.');">&nbsp;';
        if ($activo)
            echo '<input type="button" class="botones" value="Desactivar" onclick="cambiar_estado('.$id.', 0);">';
        else
            echo '<input type="button" class="botones" value="Activar" onclick="cambiar_estado('.$id.', 1);">';
    } else {
        echo '&nbsp;';
    }
    echo '</td>';
    echo '</tr>';
    $rsList->MoveNext();
}
?>
    </table>
    </center>

</div>
</body>
</html>
