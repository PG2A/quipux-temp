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
 * Popup con las personas (usuarios activos) que ocupan un puesto del catálogo
 * 'cargo'. Se abre desde la columna "Titulares" del listado areas_puestos.php.
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
$cargo_id  = 0 + trim(limpiar_numero($_GET["cargo_id"] ?? 0));

if ($cargo_id <= 0) {
    die( html_error("No se indic&oacute; el puesto.") );
}

// El puesto debe ser de un área de la institución en curso.
$rsC = $db->conn->Execute(
    "select c.cargo_nombre, depe_ruta(c.depe_codi) as ruta
       from cargo c join dependencia d on d.depe_codi = c.depe_codi
      where c.cargo_id = $cargo_id and d.inst_codi = $inst_codi");
if (!$rsC or $rsC->EOF) {
    die( html_error("El puesto solicitado no existe en esta instituci&oacute;n.") );
}
$cargo_nombre = $rsC->fields["CARGO_NOMBRE"];
$ruta         = $rsC->fields["RUTA"];

$rsU = $db->conn->Execute(
    "select usua_codi, trim(coalesce(usua_nomb,'')||' '||coalesce(usua_apellido,'')) as nombre,
            usua_cargo, usua_email
       from usuarios
      where cargo_id = $cargo_id and usua_esta = 1
      order by usua_apellido, usua_nomb");

$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

echo "<!DOCTYPE html>".html_head();
?>
<body>
<div class="sumillas-wrap">

    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr><td class="titulos4">Titulares del puesto</td></tr>
        <tr><td class="listado2"><b><?php echo $h($cargo_nombre); ?></b><br><?php echo $h($ruta); ?></td></tr>
    </table>

    <table width="100%" class="borde_tab" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td width="5%"  align="center" class="titulos2">#</td>
            <td width="35%" align="center" class="titulos2">Nombre</td>
            <td width="35%" align="center" class="titulos2">Puesto (pie de firma)</td>
            <td width="25%" align="center" class="titulos2">Correo</td>
        </tr>
<?php
$n = 0;
if (!$rsU or $rsU->EOF) {
    echo '<tr><td colspan="4" class="listado2_ver" align="center">Este puesto no tiene titulares activos.</td></tr>';
}
while ($rsU && !$rsU->EOF) {
    $n++;
    echo '<tr>';
    echo '<td class="listado2_ver" align="center">'.$n.'</td>';
    echo '<td class="listado2_ver">'.$h($rsU->fields["NOMBRE"]).'</td>';
    echo '<td class="listado2_ver">'.$h($rsU->fields["USUA_CARGO"]).'</td>';
    echo '<td class="listado2_ver">'.$h($rsU->fields["USUA_EMAIL"]).'</td>';
    echo '</tr>';
    $rsU->MoveNext();
}
?>
    </table>

    <div class="sumillas-botonera">
        <input type="button" value="Cerrar" class="botones" onClick="window.close();">
    </div>

</div>
</body>
</html>
