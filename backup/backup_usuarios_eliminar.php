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

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
require_once(dirname(__DIR__).'/funciones.php'); //para traer funciones p_get y p_post

$resp_codi = limpiar_sql($_POST["txt_resp_codi"]);


$sql = "select coalesce(fecha_fin::text,'NO') as \"fecha_fin\" from respaldo_usuario where resp_codi=$resp_codi";
$rs = $db->query($sql);

$path = dirname(__DIR__)."/bodega/respaldos/respaldo_$resp_codi";

if (trim($rs->fields["FECHA_FIN"]) == "NO") {
    if (is_dir($path)) exec("rm -rf $path");
    $sql = "delete from respaldo_usuario_radicado where resp_codi=$resp_codi";
    $db->query($sql);
} else {
//    unlink ("$path.zip");
    exec("rm -f $path.z*");
    exec("rm -R $path");

    $sql = "delete from respaldo_usuario_radicado where resp_codi=$resp_codi";
    $db->query($sql);
}
$sql = "update respaldo_usuario set fecha_eliminado=".$db->conn->sysTimeStamp." where resp_codi=$resp_codi";
$db->query($sql);
die ("El respaldo se elimin&oacute; correctamente.");
?>