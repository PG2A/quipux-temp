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
 * @package    quipux
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(__DIR__.'/rec_session.php');
include_once(__DIR__.'/funciones.php');

/**
* Permite descargar los archivos en formato .pdf
**/

$path_arch = "bodega".$_GET["path_arch"];
if (strpos($path_arch,"../")===false) {
    if (is_file($path_arch)) {
	if (trim($_GET["nomb_arch"])!="") $nomb_arch = $_GET["nomb_arch"];
	    else $nomb_arch = basename($path_arch);

	/*header( "Content-Type: application/octet-stream"); 
	header( "Content-Length: ".filesize($path_arch)); 
	header( "Content-Disposition: attachment; filename=".$nomb_arch.""); 
	header( "Content-Type: application/download");*/

	//$mime = mime_content_type($path_arch);
	$mime = get_mime_tipe($nomb_arch);

	header( "Content-Disposition: attachment; filename=".$nomb_arch."");
	header( "Content-Length: ".filesize($path_arch));
	header("Content-Type: $mime");
	header("Content-Transfer-Encoding: binary");

	readfile($path_arch);
    } else
	die ("El archivo no fue encontrado.");
} else
    die ("No tiene permiso para acceder a archivos en este directorio.");

?>
