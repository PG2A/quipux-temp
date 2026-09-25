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
 * @package    archivos
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

include_once(dirname(__DIR__, 2).'/config.php');
include_once(dirname(__DIR__, 2).'/include/db/ConnectionHandler.php');

error_reporting(7);
$db_bodega = new ConnectionHandler(dirname(__DIR__, 2), "bodega_test");
$db_bodega->conn->SetFetchMode(ADODB_FETCH_ASSOC);

$sql = "select last_value from sec_archivo";
$rs = $db_bodega->query($sql);

$arch_codi = round(rand(0, $rs->fields["LAST_VALUE"]));

$sql = "select func_recuperar_archivo($arch_codi) as archivo";

list($useg, $seg) = explode(" ", microtime());
$tiempo_inicio = 0 + $seg + $useg;

$rs_bodega = $db_bodega->query($sql);

list($useg, $seg) = explode(" ", microtime());
$tiempo_fin = 0 + $seg + $useg;

echo ($tiempo_fin-$tiempo_inicio) . " - ". $rs_bodega->fields["ARCH_CODI"]."<br>";

$sql = "insert into tmp_tiempo_read (arch_codi, tiempo) values ($arch_codi, ".($tiempo_fin-$tiempo_inicio).")";
$db_bodega->query($sql);

?>