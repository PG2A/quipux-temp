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
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php"); //para traer funciones p_get y p_post
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');

if(isset($_GET)){
    $ciudad = 0+limpiar_sql($_GET['ciudad']);   
    echo "<table width='100%'>";
    echo dibujarCiudad($db,$ciudad);
    echo "</table>";
}
function dibujarCiudad($db,$ciudad){
   $sql ="select * from ciudad    
            where id = $ciudad";
   $rsDepePadre=$db->conn->query($sql);
   $ciudadHija = $rsDepePadre->fields['NOMBRE'];
   //echo $ciudadHija."<br>";
   $ciudadPadre = $rsDepePadre->fields['ID_PADRE'];
   if ($ciudadPadre!=0){
       dibujarPadre($db,$ciudadPadre,$ciudadHija);
   }else{  
       $sql ="select * from ciudad    
            where id = $ciudad";       
   $rsDepePadre=$db->conn->query($sql);
   $ciudadNombre = $rsDepePadre->fields['NOMBRE'];
       echo "<tr><td class='titulos2' width='15%'>Ciudad</td>
           <td colspan='3' class='listado2'><input id='nombreCiudad' name='nombreCiudad' value='$ciudadNombre' size='45' readonly></td></tr>";
   }
  
}
function dibujarPadre($db,$idPadre,$ciudadOri){
    $sql ="select * from ciudad    
            where id = $idPadre";
    //echo $sql;
   $rsDepePadre=$db->conn->query($sql);
   $ciudadHija = $rsDepePadre->fields['NOMBRE'];
   $ciudadPadre = $rsDepePadre->fields['ID_PADRE'];
   $ciudadOri = $ciudadOri."/".$ciudadHija;
   
   if ($ciudadPadre!=0){
       dibujarPadre($db,$ciudadPadre,$ciudadOri);
   }else{      
      
       echo "<tr><td class='titulos2' width='15%'>Ciudad</td>
           <td colspan='3' class='listado2'><input id='nombreCiudad' name='nombreCiudad' value='$ciudadOri' size='45' readonly></td></tr>";
           
   }
}

?>