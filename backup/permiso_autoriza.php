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
  require_once(dirname(__DIR__)."/funciones.php");

 $usr_inst_actual = $_SESSION["inst_codi"];
 $usr_usua_codi = limpiar_numero($_GET["dat"]);
 $permiso = limpiar_numero($_GET["per"]);

  include_once(dirname(__DIR__).'/funciones_interfaz.php');
  echo "<!DOCTYPE html>".html_head();

  //Se consulta áreas
  $sql = "select dep.DEPE_CODI, dep.DEPE_NOMB, permiso_usr_dep.* from dependencia as dep
          left outer join (select depe_codi as permiso_asignado
          from permiso_usuario_dep
	  where usua_codi = $usr_usua_codi
	  and id_permiso = $permiso) as permiso_usr_dep
          on  dep.depe_codi = permiso_usr_dep.permiso_asignado
          where dep.inst_codi = $usr_inst_actual and dep.depe_estado = 1
          order by dep.DEPE_NOMB";
   $rs = $db->conn->Execute($sql);   
?>
<script language="JavaScript" type="text/javascript" >
   function metodoSeleccionarTodo(accion)
   {    
       elms = document.getElementsByName("dependencia[]");
       for(i=0;i<elms.length;i++){
        if(elms[i].type=="checkbox")
            elms[i].checked = accion;
        }
   }
</script>
<center>
<form name="formulario" action="permiso_autoriza_grabar.php" method="POST">
    <input type="hidden" name="txt_usua_codi" id="txt_usua_codi" value="<?php echo $usr_usua_codi; ?>" maxlength="10">
    <input type="hidden" name="txt_permiso" id="txt_permiso" value="<?php echo $permiso; ?>" maxlength="10">
    <table width="100%" align="center" border="0" cellpadding="0" cellspacing="3" class="borde_tab">
        <tr><td class="titulos2" align="center">Permiso para aprobar solicitudes de respaldos</td></tr>
        <tr><td class="listado2" >Seleccione las áreas en las que el usuario aprobará solicitudes de respaldos:</td></tr>
        <tr><td class="listado2" align="right">
            <a href="javascript:;" onClick="metodoSeleccionarTodo(1);" class="Ntooltip"><font color="black">Marcar Todo </font></a>
            &nbsp;&nbsp;
            <a href="javascript:;" onClick="metodoSeleccionarTodo(0);" class="Ntooltip"><font color="black">Quitar Todo </font></a>
            </td>
        <tr><td class="listado1" >
        <?while (!$rs->EOF) {
            $fecha = date('Y-m-d h:m:s', strtotime($rs->fields["FECHA"])) . " " . $descZonaHoraria;
            $depe_codi = $rs->fields["DEPE_CODI"];
            $depe_nomb = $rs->fields["DEPE_NOMB"];
            $asignado = $rs->fields["PERMISO_ASIGNADO"];
            $checked = "";
            if($asignado != "")
                $checked = "checked";
           ?>
        <input type="checkbox" name="dependencia[]" value=<?php  echo $depe_codi ?>  <?php  echo $checked ?> /><?php  echo $depe_nomb ?>
        <br>       
        <?php  $rs->MoveNext();
         }
        ?>
        </td></tr>
     </table>
    <br>    
    <input type='submit' name='btn_grabar' value='Grabar' class='botones'>
    <input type='button' name='btn_cancelar' value='Regresar' class='botones' onClick='window.close()'>
    <br><br>
</form>
</center>