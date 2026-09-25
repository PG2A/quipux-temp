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
require_once(dirname(__DIR__, 2)."/funciones.php"); //para traer funciones p_get y p_post

if (isset($_GET)){  
    $dependencia = isset($_GET['depe_codi']) ? (int)$_GET['depe_codi'] : 0;
    
    if ($dependencia==0){
       echo "<table class='borde_tab' width='100%'><tr><td class='listado2'><font size='2' color='red'><center>Seleccione el Área</center></font></td></tr></table>";
    }else{
    $permisos = isset($_GET['codPermiso']) ? (int)$_GET['codPermiso'] : 0;
    
    // Fix undefined variable radio_filtro
    $radio_filtro = isset($_GET['radio_filtro']) ? $_GET['radio_filtro'] : '';

    if ($radio_filtro=='NO')
        $filtro=" not ";
    else
        $filtro="";
    $sql = "select u.usua_codi,u.usua_nomb || ' ' ||u.usua_apellido as usua_nombre";
    $sql.= " from usuarios u where ";
    if ($permisos!=0)

            $sql.= " usua_codi $filtro in (select usua_codi from permiso_usuario where id_permiso in ($permisos))";
    if ($permisos!=0)
    $sql.= " and depe_codi = $dependencia";
    else
        $sql.= " depe_codi = $dependencia";
    $sql.= " and usua_esta=1 order by 1";
    
    $rs = $db->conn->Execute($sql);
    $i=0;
    ?>
<!--    <input type="button" name="btn_accion" class="botones_2" value="&gt;" onclick="usuarios_todos();" title="Seleccionar Todos."/>-->
    <table class="borde_tab" width="100%">
        <a href="javascript:;" onclick="usuarios_todos();">
                    <img alt="Seleccionar toda la lista" src="<?=dirname(__DIR__, 2)?>/imagenes/flechadesc.gif"/></a><font size="1">Seleccionar Todos</font>
        <tr><td class="titulos1"><center>SELECCIONE USUARIOS</center></td></tr>
       
        <?php
        $todosseleccionados = "";
        while (!$rs->EOF) {
            $ucod=$rs->fields['USUA_CODI'];
//            echo "<option id='usuario_cod' onclick='ver_nombre($ucod,1)' value='".$rs->fields['USUA_CODI']."'>".$rs->fields['USUA_NOMBRE']."</option>" ;
            
             echo "<tr id='tr_usr_disponibles_$ucod' class='listado2' onclick='ver_nombre($ucod,1)'>
                        <td title='".$rs->fields['USUA_NOMBRE']."'>".$rs->fields['USUA_NOMBRE']."</td>
                      </tr>";
             $rs->MoveNext();
             $i++;
             
             $todosseleccionados = $todosseleccionados.",".$ucod; 
             
    }
    ?> 
    <input type='hidden' name='todos_usuarios' id='todos_usuarios' value="<?=$todosseleccionados?>"/>
    <input type='hidden' name='sel_todos_usuarios' id='sel_todos_usuarios' value="<?=$todosseleccionados?>"/>
    </table>
    <?php }?>
<?php
}?>



