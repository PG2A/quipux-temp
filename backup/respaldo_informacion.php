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
include_once(dirname(__DIR__).'/funciones_interfaz.php');
include_once('../js/ajax.js');

//echo "<link rel='stylesheet' type='text/css' href='__DIR__./js/spiffyCal/spiffyCal_v2_1.css'>";
echo "<!DOCTYPE html>".html_head();
if (!$menu_ver) $menu_ver=1;	//define la pestaña de vista general por defecto
?>

<?php

$tipo_ventana = trim(limpiar_sql($_GET["tipo_ventana"]));;

if ($tipo_ventana=='popup'){
?>
<script type="text/javascript">
function llamaCuerpo(parametros){
     location.href = parametros;
}
</script>
<?php }else{
    ?>
<script type="text/javascript">
function llamaCuerpo(parametros){    
    top.frames['mainFrame'].location.href=parametros;
}
</script>
<?php }?>

<body>
<center>
<table width="90%" border="0" cellpadding="0" cellspacing="1">
    <tr>
        <td class="titulos5" align="center">
            <br>Solicitud de Respaldos<br>&nbsp;
        </td>
    </tr>
</table>
</center>
<center>
    <table border=0 align='center' cellpadding="0" cellspacing="0" width="90%" >
      <?
        $txt_resp_soli_codi = trim(limpiar_sql($_GET["txt_resp_soli_codi"]));
        $txt_tipo_lista= trim(limpiar_sql($_GET["txt_tipo_lista"]));
	$datos1 = "";$datos2 = "";
	${"datos".$menu_ver} = "_R";	//Pone la pestaña resaltada que el usuario eligio        
      ?>
      <tr>
      <td height="25" width="100%" class="listado2">
          <table border=0 width=100% cellpadding="0" cellspacing="0">
              <tr>
                <td height="99" rowspan="4" width="1%" valign="top" class="listado2">&nbsp;</td>
                <td valign="bottom" class="" >
                     <?php                       
                        $parametrosFuncion = "respaldo_informacion.php?txt_resp_soli_codi=$txt_resp_soli_codi&txt_tipo_lista=$txt_tipo_lista";
                        $parametrosFuncion = "'".$parametrosFuncion."&menu_ver=1"."&tipo_ventana=$tipo_ventana'";
                        $funcionjava = "llamaCuerpo($parametrosFuncion);";
                    ?>
                    <a onclick="<?php echo $funcionjava;?>" href='javascript:void(0);' >
                        <img alt="" src="../imagenes/infoGeneral<?=$datos1?>.gif" border=0 width="110" height="25">
                    </a>
                    <?php                       
                        $parametrosFuncion = "respaldo_informacion.php?txt_resp_soli_codi=$txt_resp_soli_codi&txt_tipo_lista=$txt_tipo_lista";
                        $parametrosFuncion = "'".$parametrosFuncion."&menu_ver=2"."&tipo_ventana=$tipo_ventana'";
                        $funcionjava = "llamaCuerpo($parametrosFuncion);";
                    ?>
                    <a onclick="<?php echo $funcionjava;?>" href='javascript:void(0);' >
                        <img alt=""  src="../imagenes/historico<?=$datos2?>.gif" border=0 width="110" height="25">
                    </a>
                </td>
             </tr>
         </table>
      </td>
      <td height="149" rowspan="4" class=""><td>
      <tr>
        <td  bgcolor="" width="100%" height="100">
        <?
            error_reporting(7);

            switch ($menu_ver) {
                case 1://Solicitud
                    include "./respaldo_solicitud.php";
                    break;
                case 2://Recorrido 
                    include "./respaldo_historico_lista.php";
                    break;              
                default:
                    break;
            }
        ?>
      </td>
    </tr>   
</table></center>

</body>
</html>
