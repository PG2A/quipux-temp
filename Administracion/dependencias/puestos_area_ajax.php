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
 * Panel "Puestos del Área" de Administración -> Áreas -> Editar.
 *
 * Lista, crea, edita y activa/desactiva los puestos del catálogo 'cargo' para
 * un área. Se carga por AJAX en el div 'div_puestos' de adm_dependencias_nuevo.php
 * y se vuelve a pintar completo tras cada operación.
 *
 * Parámetros (GET o POST):
 *   dependencia   área (obligatorio)
 *   op            '' | editar | grabar | estado
 *   cargo_id      puesto sobre el que se opera (editar, grabar en modo edición, estado)
 *   nombre, cabecera, tipo, propagar   campos del formulario (grabar)
 *   estado        1 | 0 (op=estado)
 *
 * @package    dependencias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
if (($_SESSION["usua_admin_sistema"] ?? 0) != 1) {
    die("");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');

$depe_codi = (int)($_REQUEST['dependencia'] ?? 0);
$op        = trim($_REQUEST['op'] ?? '');
$cargo_id  = (int)($_REQUEST['cargo_id'] ?? 0);

if ($depe_codi <= 0) {
    die("");
}

$rsDepe = $db->query("select depe_nomb, inst_codi from dependencia where depe_codi = ?", array($depe_codi));
if (!$rsDepe || $rsDepe->EOF) {
    die("");
}
$inst_codi = (int)$rsDepe->fields['INST_CODI'];

// Puede editar si el área está en su ámbito de administración (usuario_dependencia),
// o si es el superadministrador o administrador institucional.
$en_ambito = obtenerCodigos($_SESSION['usua_codi'], $depe_codi, $db, 1);   // tipo=1: devuelve 1 si el área está en la lista
$puede_editar = ((int)$en_ambito == 1 || $_SESSION['usua_codi'] == 0 || ($_SESSION['perm_admin_institucional'] ?? 0) == 1);

$mensaje = "";
$error   = "";

// ------------------------------------------------------------------------------
// Operaciones de escritura
// ------------------------------------------------------------------------------
if ($puede_editar && $op == 'grabar') {
    $nombre   = trim(preg_replace('/\s+/', ' ', limpiar_sql($_POST['nombre'] ?? '')));
    $cabecera = trim(preg_replace('/\s+/', ' ', limpiar_sql($_POST['cabecera'] ?? '')));
    $tipo     = ((int)($_POST['tipo'] ?? 0) == 1) ? 1 : 0;
    $propagar = ((int)($_POST['propagar'] ?? 0) == 1);

    if ($nombre == '') {
        $error = "Ingrese el nombre del puesto.";
    } elseif (mb_strlen($nombre) > 200 || mb_strlen($cabecera) > 200) {
        $error = "El nombre y la cabecera no pueden superar 200 caracteres.";
    } else {
        if ($cabecera == '') $cabecera = $nombre;

        // Un mismo puesto no se repite dentro del área (misma regla que el índice único)
        $rsDup = $db->query(
            "select cargo_id from cargo
              where depe_codi = ? and cargo_id <> ?
                and lower(regexp_replace(trim(cargo_nombre), '\\s+', ' ', 'g')) = lower(?)",
            array($depe_codi, $cargo_id, $nombre));
        if ($rsDup && !$rsDup->EOF) {
            $error = "Ya existe el puesto \"".htmlspecialchars($nombre)."\" en esta área.";
        }
    }

    if ($error == '') {
        if ($cargo_id > 0) {
            $ok = $db->update('cargo',
                array('cargo_nombre' => $nombre, 'cargo_cabecera' => $cabecera, 'cargo_tipo' => $tipo,
                      'usua_codi_actualiza' => (int)$_SESSION['usua_codi']),
                array('cargo_id' => $cargo_id, 'depe_codi' => $depe_codi));
            $mensaje = $ok ? "Puesto actualizado." : "No se pudo actualizar el puesto.";

            // Opcional: llevar el nuevo texto a los usuarios que ya tienen el puesto.
            // El perfil (Jefe/Normal) NO se propaga: se gestiona desde "Jefe de Área".
            if ($ok && $propagar) {
                $rsProp = $db->query(
                    "update usuarios set usua_cargo = ?, usua_cargo_cabecera = ?
                      where cargo_id = ? and depe_codi = ?",
                    array($nombre, $cabecera, $cargo_id, $depe_codi));
                if ($rsProp) {
                    $n = $db->conn->Affected_Rows();
                    $mensaje .= " Se actualizó el puesto en $n usuario(s).";
                }
            }
        } else {
            $ok = $db->insert('cargo',
                array('depe_codi' => $depe_codi, 'inst_codi' => $inst_codi,
                      'cargo_nombre' => $nombre, 'cargo_cabecera' => $cabecera, 'cargo_tipo' => $tipo,
                      'usua_codi_actualiza' => (int)$_SESSION['usua_codi']));
            $mensaje = $ok ? "Puesto creado." : "No se pudo crear el puesto.";
        }
        $cargo_id = 0;
        $op = '';
    }
}

if ($puede_editar && $op == 'estado' && $cargo_id > 0) {
    $estado = ((int)($_REQUEST['estado'] ?? 1) == 1) ? 1 : 0;
    $db->update('cargo',
        array('cargo_estado' => $estado, 'usua_codi_actualiza' => (int)$_SESSION['usua_codi']),
        array('cargo_id' => $cargo_id, 'depe_codi' => $depe_codi));
    $mensaje = $estado ? "Puesto activado." : "Puesto desactivado. Los usuarios que lo tienen no se modifican.";
    $cargo_id = 0;
    $op = '';
}

// ------------------------------------------------------------------------------
// Datos del formulario (nuevo o edición)
// ------------------------------------------------------------------------------
$f_nombre = ""; $f_cabecera = ""; $f_tipo = 0; $f_titulares = 0;
if ($op == 'editar' && $cargo_id > 0) {
    $rsEd = $db->query(
        "select c.cargo_nombre, c.cargo_cabecera, c.cargo_tipo,
                (select count(*) from usuarios u where u.cargo_id = c.cargo_id and u.usua_esta = 1) as titulares
           from cargo c where c.cargo_id = ? and c.depe_codi = ?",
        array($cargo_id, $depe_codi));
    if ($rsEd && !$rsEd->EOF) {
        $f_nombre    = $rsEd->fields['CARGO_NOMBRE'];
        $f_cabecera  = $rsEd->fields['CARGO_CABECERA'];
        $f_tipo      = (int)$rsEd->fields['CARGO_TIPO'];
        $f_titulares = (int)$rsEd->fields['TITULARES'];
    } else {
        $cargo_id = 0;
        $op = '';
    }
}
if ($op == 'grabar' && $error != '') {   // conservar lo escrito para corregir
    $f_nombre = $_POST['nombre'] ?? ''; $f_cabecera = $_POST['cabecera'] ?? ''; $f_tipo = (int)($_POST['tipo'] ?? 0);
}

// ------------------------------------------------------------------------------
// Listado
// ------------------------------------------------------------------------------
$rsList = $db->query(
    "select c.cargo_id, c.cargo_nombre, c.cargo_cabecera, c.cargo_tipo, c.cargo_estado,
            (select count(*) from usuarios u where u.cargo_id = c.cargo_id and u.usua_esta = 1) as titulares
       from cargo c
      where c.depe_codi = ?
      order by c.cargo_estado desc, c.cargo_tipo desc, lower(c.cargo_nombre)",
    array($depe_codi));

$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
?>
<br>
<table width="100%" class="borde_tab">
<tr>
    <td align="center" class="titulos4" colspan="6"><font size="2">Puestos del &Aacute;rea</font></td>
</tr>
<?php if ($mensaje != '') { ?>
<tr><td colspan="6" class="listado2_ver" align="center"><b><?=$h($mensaje)?></b></td></tr>
<?php } ?>
<?php if ($error != '') { ?>
<tr><td colspan="6" class="listado2_ver" align="center"><font color="red"><b><?=$h($error)?></b></font></td></tr>
<?php } ?>
<tr>
    <td width="32%" align="center" class="titulos2">Puesto (pie de firma)</td>
    <td width="32%" align="center" class="titulos2">Cabecera del documento</td>
    <td width="8%"  align="center" class="titulos2">Perfil</td>
    <td width="8%"  align="center" class="titulos2">Titulares</td>
    <td width="8%"  align="center" class="titulos2">Estado</td>
    <td width="12%" align="center" class="titulos2">Acci&oacute;n</td>
</tr>
<?php
if (!$rsList || $rsList->EOF) {
    echo '<tr><td colspan="6" class="listado2_ver" align="center">El &Aacute;rea a&uacute;n no tiene puestos registrados.</td></tr>';
}
while ($rsList && !$rsList->EOF) {
    $c = $rsList->fields;
    $id = (int)$c['CARGO_ID'];
    $activo = ((int)$c['CARGO_ESTADO'] == 1);
    $clase = $activo ? 'listado2_ver' : 'listado2';
    echo '<tr>';
    echo '<td class="'.$clase.'">'.$h($c['CARGO_NOMBRE']).'</td>';
    echo '<td class="'.$clase.'">'.$h($c['CARGO_CABECERA']).'</td>';
    echo '<td class="'.$clase.'" align="center">'.(((int)$c['CARGO_TIPO'] == 1) ? '<b>Jefe</b>' : 'Normal').'</td>';
    echo '<td class="'.$clase.'" align="center">'.(int)$c['TITULARES'].'</td>';
    echo '<td class="'.$clase.'" align="center">'.($activo ? 'Activo' : '<i>Inactivo</i>').'</td>';
    echo '<td class="listado2" align="center">';
    if ($puede_editar) {
        echo '<input type="button" class="botones" value="Editar" onclick="puestosArea('.$depe_codi.', \'op=editar&cargo_id='.$id.'\');">&nbsp;';
        if ($activo)
            echo '<input type="button" class="botones" value="Desactivar" onclick="if(confirm(\'¿Desactivar el puesto?\')) puestosArea('.$depe_codi.', \'op=estado&estado=0&cargo_id='.$id.'\');">';
        else
            echo '<input type="button" class="botones" value="Activar" onclick="puestosArea('.$depe_codi.', \'op=estado&estado=1&cargo_id='.$id.'\');">';
    }
    echo '</td>';
    echo '</tr>';
    $rsList->MoveNext();
}
?>
</table>

<?php if ($puede_editar) { ?>
<br>
<table width="100%" class="borde_tab">
<tr>
    <td align="center" class="titulos4" colspan="4"><font size="2"><?=($cargo_id > 0) ? 'Editar Puesto' : 'Nuevo Puesto'?></font></td>
</tr>
<tr>
    <td width="18%" class="titulos2">* Puesto (pie de firma)</td>
    <td class="listado2" colspan="3">
        <input type="hidden" id="puesto_cargo_id" value="<?=$cargo_id?>">
        <input type="text" id="puesto_nombre" class="tex_area" size="80" maxlength="200" value="<?=$h($f_nombre)?>"
               onblur="if(document.getElementById('puesto_cabecera').value=='') document.getElementById('puesto_cabecera').value=this.value;">
    </td>
</tr>
<tr>
    <td class="titulos2">Cabecera del documento</td>
    <td class="listado2" colspan="3">
        <input type="text" id="puesto_cabecera" class="tex_area" size="80" maxlength="200" value="<?=$h($f_cabecera)?>">
        <br><font size="1">Si se deja vac&iacute;o se usa el mismo texto del puesto.</font>
    </td>
</tr>
<tr>
    <td class="titulos2">Perfil</td>
    <td class="listado2" width="22%">
        <select id="puesto_tipo" class="select">
            <option value="0" <?=($f_tipo == 0) ? 'selected' : ''?>>Normal</option>
            <option value="1" <?=($f_tipo == 1) ? 'selected' : ''?>>Jefe</option>
        </select>
    </td>
    <td class="listado2" colspan="2">
        <?php if ($cargo_id > 0 && $f_titulares > 0) { ?>
        <input type="checkbox" id="puesto_propagar" value="1">
        Actualizar el texto del puesto en los <b><?=$f_titulares?></b> usuario(s) que lo tienen
        <br><font size="1">El perfil Jefe/Normal de los usuarios no cambia desde aqu&iacute;; se gestiona en "Jefe de &Aacute;rea".</font>
        <?php } else { ?>
        <input type="hidden" id="puesto_propagar" value="0">
        <?php } ?>
    </td>
</tr>
<tr>
    <td colspan="4" class="listado2" align="center">
        <input type="button" class="botones" value="Guardar" onclick="grabarPuesto(<?=$depe_codi?>);">
        <?php if ($cargo_id > 0) { ?>
        &nbsp;<input type="button" class="botones" value="Cancelar" onclick="puestosArea(<?=$depe_codi?>, '');">
        <?php } ?>
    </td>
</tr>
</table>
<?php } ?>
