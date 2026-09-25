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
 * @package    usuarios
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
include_once(dirname(__DIR__, 2).'/funciones.php');

$codi_ciudad = (isset($_GET["codigo"])) ? (int)$_GET["codigo"] : 0;
$area = (isset ($_GET["area"])) ? 0 + limpiar_numero($_GET["area"]) : 0;
if($area!=0 and $codi_ciudad==0)
{
    $sql = "select depe_pie1 as ciu_id from dependencia where depe_codi = $area"; //selecciona las ciudades de la tabla ciudad, para llenar el combobox
    $rsCiudad = $db->conn->Execute($sql);
    $codi_ciudad = 0+$rsCiudad->fields["CIU_ID"];
}

$sqlCmbCiu = "select id, nombre from ciudad order by 2";
$rsCmbCiu = $db->conn->Execute($sqlCmbCiu);
echo "<select name='codi_ciudad' id='codi_ciudad' class='select'>";
echo "<option value='0'>&lt;&lt; seleccione &gt;&gt;</option>";
while ($rsCmbCiu && !$rsCmbCiu->EOF) {
    // Robust access: Try Associative keys first (Upper/Lower), then Numeric
    $f = $rsCmbCiu->fields;
    if (isset($f['ID'])) $val = $f['ID'];
    elseif (isset($f['id'])) $val = $f['id'];
    else $val = $f[0];

    if (isset($f['NOMBRE'])) $txt = $f['NOMBRE'];
    elseif (isset($f['nombre'])) $txt = $f['nombre'];
    else $txt = $f[1];

    $selected = ($val == (0+$codi_ciudad)) ? "selected" : "";
    echo "<option value='$val' $selected>$txt</option>";
    $rsCmbCiu->MoveNext();
}
echo "</select>";



?>