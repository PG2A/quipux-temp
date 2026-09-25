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
if($_SESSION["usua_admin_sistema"]!=1) {
    die("");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php"); //para traer funciones p_get y p_post
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');

if(isset($_GET)){
    $usrCodigo = 0+limpiar_sql($_GET['usrCodigo']);
    $datosUsrI=array();
    $datosUsrI= ObtenerDatosUsuario($usrCodigo, $db);
    
    echo "<table width='100%'>";
    echo dibujarAreas($datosUsrI['institucion'], $db,$usrCodigo);
    echo "</table>";
}
function dibujarAreas($nombre_institucion,$db,$usrCodigo){
   $menu_depeHijo .= '<tr><td colspan="4" align="center" class="titulos4"><font size="2">'.$nombre_institucion.'</font></td></tr>';
    
   $sql ="select * from usuario_dependencia u    
            where usua_codi = $usrCodigo";
   
   $rsDepePadre=$db->conn->query($sql);
   $depeCodiTmp = $rsDepePadre->fields['DEPE_CODI_TMP'];
   $depeOrdenTmp = substr($depeCodiTmp,1);
   if ($depeCodiTmp!=''){
   $sql_orden= "select depe_codi,depe_codi_padre,depe_nomb from dependencia 
            where depe_codi in ($depeOrdenTmp) and depe_estado = 1";
   $sql_orden.=" group by depe_codi,depe_codi_padre,depe_nomb
            order by depe_codi_padre,depe_nomb";
   //echo $sql_orden;
   $rs=$db->conn->query($sql_orden);
   
   while(!$rs->EOF){
              $padre = $rs->fields['DEPE_CODI_PADRE'];
              
               if ($padre!=$padre2)
                   $menu_depeHijo .= '<tr><td class="titulos2"><font size="1">'. mostrarNombre($padre,$db).'</font></td></tr>';
                $menu_depeHijo .= '<tr><td class="listado2"><font size="1">'.$rs->fields["DEPE_NOMB"].'</font></td></tr>';
               $padre2 = $rs->fields['DEPE_CODI_PADRE'];
               $rs->MoveNext();                 
    }
   }
    return $menu_depeHijo; 
}
function mostrarNombre($depe_codi,$db){
    $sql="select depe_nomb from dependencia where depe_codi = $depe_codi";
    $rs=$db->conn->query($sql);
    if (!$rs->EOF)
            return $rs->fields["DEPE_NOMB"];
}
?>