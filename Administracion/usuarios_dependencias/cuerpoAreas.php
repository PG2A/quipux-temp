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
    die(html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina."));
}
require_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2)."/funciones_interfaz.php");
include_once(dirname(__DIR__, 2)."/obtenerdatos.php");

echo "<!DOCTYPE html>".html_head();

$usr_codigo = 0 + limpiar_sql($_GET["usr_codigo"] ?? 0);
$carpeta = 0;
$txt_nombre_buscar = "";

$paginador = new ADODB_Pager_Ajax(dirname(__DIR__, 2), "div_busqueda_area", "busqueda_paginador_areas.php",
                                  "txt_nombre_buscar","carpeta=$carpeta");
?>

<script type="text/javascript">
    
   function realizar_busqueda() { 
       paginador_reload_div('');
    }
    
 
function administrar(depe_codigo){
       usr_codigo = document.getElementById('usr_codigo').value;
       inst_codi = document.getElementById('inst_codi').value;
  
       if (inst_codi == depe_codigo)
           document.getElementById('div_mensaje').style.display='';
       else
           if (usr_codigo!=''){          
            ventana_abrir='../usuarios_dependencias/adm_buscar_arbol.php?usr_codigo=' + usr_codigo + '&depe_codi_instancia='+depe_codigo;      
            window.open(ventana_abrir, 'Administrar Áreas', 'left=150, top=300, width=1050, height=500,scrollbars=yes');
            }
    }
</script>
<?php $td1='20%'; $td2='60%'?>
<body onload="realizar_busqueda();">
  <center>
    <table width="100%" class="borde_tab">  
        <tr>
            <td align="center" colspan="2" class="listado2">
            <?php
                $sql = "select inst_nombre as inst_nombre from institucion where inst_codi = ".$_SESSION['inst_codi'];           
                $rs=$db->conn->query($sql);
                if (!$rs->EOF){
                $inst_nombre=$rs->fields['INST_NOMBRE'];
                echo $inst_nombre;
            }
            ?>
            </td>
        </tr>
        <tr>
            <td align="center" colspan="2" class="listado2">
                La Institución seleccionada es extensa, por favor seleccione la dependencia que desee Administrar
            </td>
        </tr>
        <tr>   
            <input  name="usr_codigo" id="usr_codigo" type="hidden" value="<?=$usr_codigo?>" />  
            <?php
            $sql = "select min (depe_codi) as instancia from dependencia where inst_codi = ".$_SESSION['inst_codi'];           
            $rs=$db->conn->query($sql);
            if (!$rs->EOF){
                $instancia=$rs->fields['INSTANCIA'];
            }
            ?>
            <input  name="inst_codi" id="inst_codi" type="hidden" value="<?=$instancia?>" />
            <td align="left" class="titulos2">
              Nombre, Sigla: <input  name="txt_nombre_buscar" id="txt_nombre_buscar" type="text" size="40" maxlength="150" value="<?=$txt_nombre_buscar?>" onKeyPress="if (event.keyCode==13) return realizar_busqueda();">  
            </td>
            <td class="listado2_ver">               
                    <input  name="btn_accion" type="button" class="botones" value="Buscar" onClick="return realizar_busqueda();" title="Busca Área por nombre o sigla"/>
            </td>          
      </tr>
      <tr><td colspan="2">
              <div id="div_mensaje" name="div_mensaje" style="display: none">
                  <center><font size="2">Para su comodidad, por favor seleccione otra dependencia, la dependencia seleccionada es demasiado extensa.
                  </font></center>
              </div></td></tr>
      </table>
           <div id="div_busqueda_area"></div>
  </center>
</body>
</html>