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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(__DIR__.'/rec_session.php');
include_once(__DIR__.'/funciones_interfaz.php');

global $CFG;
if (isset($CFG->replicacion) && $CFG->replicacion && !($CFG->config_db_replica_cuerpo_paginador === "")) {
    $db = new ConnectionHandler(__DIR__, $CFG->config_db_replica_cuerpo_paginador);
}

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

$solicitadas = isset($_GET['carpetas']) ? explode(',', $_GET['carpetas']) : array();
$contadores = array();
$vistas = array();

foreach ($solicitadas as $carpeta) {
    $carpeta = (int)trim($carpeta);
    if ($carpeta <= 0 || isset($vistas[$carpeta])) continue;
    $vistas[$carpeta] = true;
    if (count($vistas) > 40) break;
    $contadores[$carpeta] = count_inbox($carpeta);
}

echo json_encode($contadores);
