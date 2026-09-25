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
 * @package    anexos
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$output = array();
exec("df -h __DIR__./bodega/respaldos", $output);
//var_dump($output);

echo '<table class="borde_tab" border="0" cellpadding="0" cellspacing="3">
          <tr><td colspan="4" align="center"><b>Espacio Disponible en Disco</b></td></tr>';
for ($i=0; $i<count($output) ; ++$i) {
    $output[$i] = preg_replace('/\s\s+/', ' ', $output[$i]);
    $datos = explode(" ", $output[$i]);
    $tag = ($i == 0) ? $tag = "th" : "td";
    echo "<tr><$tag>".$datos[1]."</$tag><$tag>".$datos[2]."</$tag><$tag>".$datos[3]."</$tag><$tag>".$datos[4]."</$tag></tr>";
}
echo "</table>";

?>
