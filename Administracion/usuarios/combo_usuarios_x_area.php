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
if ($_SESSION["usua_admin_sistema"] != 1) {
    die("SIN SESION DE ADMINISTRADOR");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php");

p_register_globals(array(
    '_POST' => array(
        'depe_destino',
        'read2',
        'usr_destino'
    ),
    '_SESSION' => array(
        'inst_codi'
    )
));

if ($depe_destino == 'TODOS') {
    $where = "where inst_codi = " . $inst_codi ;
} else {
    $where = "where depe_codi = " . $depe_destino;
}

$sql = "select usua_nombre, usua_codi from usuario $where order by usua_apellido asc";
$rs = $db->conn->Execute($sql);
echo $rs->GetMenu2("usr_destino", $usr_destino, "0:&lt;&lt seleccione &gt;&gt;", false,"","class='select' $read2");
?>
