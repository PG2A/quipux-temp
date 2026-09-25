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
 * @package    core
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

include_once(__DIR__.'/config.php');
include_once(__DIR__.'/funciones_interfaz.php');

//if (is_file("./config.php")) $ruta_raiz = ".";
//elseif (is_file("../config.php")) $ruta_raiz = "..";
//elseif (is_file("../../config.php")) $ruta_raiz = "../..";
//else die ("Su sesi&oacute;n ha expirado o ha ingresado en otro equipo");
//include(__DIR__.'/config.php');
//include_once(__DIR__.'/funciones_interfaz.php');

global $nombre_servidor;
$nombre_servidor = $nombre_servidor ?? '';

// [REQ-3] Enlace al portal dual (index.php) en lugar del formulario
// de login local (login.php), para que el usuario pueda elegir
// entre autenticación SAML/ADFS o base de datos local.
$mensaje = "Su sesi&oacute;n ha expirado o ha ingresado en otro equipo <br><br>
            Para Ingresar haga, click &nbsp<a href='$nombre_servidor/index.php' target='_parent' class='aqui'>&quot;AQU&Iacute;&quot;</a><br>";

echo html_error($mensaje);
?>
