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
 * @package    barcode
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

@ob_start();
$height="50";
$bgcolor="#FFFFFF";
$color="#333366";
$type="png";
$encode = "CODE128";
$fechah = date("YmdHms");
$height = "50";
$scale = "1.5";
$bdata = $nurad;
$file = dirname(__DIR__,2)."/bodega/tmp/$fileDat";
include("barcode.php");
echo $file;
?>
<!--
<table border=1 background="<?="$file".".png"?>" width=360 height=90 class="borde_tab">
<TR height=50>
	<TD>
	</TD>
</TR>
<tr><td class=listado1>Numero de Radicado <?=$nurad?>
</td></tr>
</table>
-->
<img src='<?="$file".".png"?>'>
