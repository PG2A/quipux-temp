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
 * @package    ciudadanos
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
require_once(dirname(__DIR__, 2).'/rec_session.php');
if (isset ($replicacion) && $replicacion && $config_db_replica_adm_criterios_permisos!="") {
    $db = new ConnectionHandler(__DIR__, $config_db_replica_adm_criterios_permisos);
}

$sql="select descripcion, id_permiso from permiso where estado=1 and perfil in (0,1,2,3,4) and id_permiso not in (26) order by 1";
 
$rs = $db->conn->query($sql);
    ?>
    <table class="borde_tab" width="100%">
        <tr><td class="titulos1"><center>SELECCIONE PERMISOS</center></td></tr>
      
        <?php
        while (!$rs->EOF) {
            $idperm=$rs->fields['ID_PERMISO'];
             
            echo "<tr id='tr_permisos_disponibles_$idperm' class='listado2' onclick='ver_nombre($idperm,2)'>
             <td title='".$rs->fields['DESCRIPCION']."'>".$rs->fields['DESCRIPCION']."</td>
                      </tr>";
            $rs->MoveNext();
    }
    ?>
        
    </table>

