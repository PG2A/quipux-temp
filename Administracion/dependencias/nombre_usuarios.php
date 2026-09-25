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
if($_SESSION["usua_admin_sistema"]!=1) {
    die("");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');

if (isset($_GET)){  
    //$nombre="";
    $codbuscar=limpiar_sql($_GET['cod_buscar']);
    $nombres = limpiar_sql($_GET['nombres']);
    $codbuscar = substr($codbuscar, 1);
    echo "<br> Buscar: ".$codbuscar;
    
//    if ($_GET['tipo']==1){
        $sql = "select usua_nombre from usuario where usua_codi in ($codbuscar)";
        echo $sql;
        $rs = $db->conn->query($sql);
        $nombre=$rs->fields['USUA_NOMBRE'];
        $nombre = $nombres.",".$nombre;  
//    }
//    else{
//        $sql = "select descripcion from permiso where id_permiso = ($codbuscar)";
//        echo $sql;
//        $rs = $db->conn->query($sql);
//        $nombre=$rs->fields['DESCRIPCION'];
//    }
    
    
   
    echo '<input type="text" name="seleccionado" id="seleccionado" value="'.$nombre.'"/>';
    

}?>



