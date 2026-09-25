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
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php"); //para traer funciones p_get y p_post
require_once(dirname(__DIR__, 2)."/funciones_interfaz.php");
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once("../usuarios/mnuUsuariosH.php");
include_once("../ciudadanos/util_ciudadano.php");

$ciud = New Ciudadano($db);

if($_SESSION["usua_admin_sistema"]!=1 and $_SESSION["usua_perm_ciudadano"]!=1) {
      echo html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
      die("");
  }


$paginador = new ADODB_Pager_Ajax(dirname(__DIR__, 2), "div_buscar_ciudadanos", "../ciudadanos/busqueda_paginador.php",
                  "ciu_ver,txt_buscar_nombre,tipo_query");
                  
$txt_buscar_nombre = $txt_buscar_nombre ?? "";
$opcion = $opcion ?? "";
$cerrar = $cerrar ?? "";
$linkPagina = $linkPagina ?? $_SERVER['PHP_SELF'];
?>
<!DOCTYPE html>
<?php echo html_head(); ?>
<script>
    
    function realizar_busqueda() { 
       nombre=document.getElementById('txt_buscar_nombre').value;
            if (nombre.length<=100){
                paginador_reload_div('');
            }else{
                    document.getElementById('txt_buscar_nombre').value='';
                    alert("El texto no tienen coincidencias en la búsqueda");
                }
    }
    function seleccionar_ciudadano(codigo,cedula) {   
        
        opcion=getRadioButtonSelectedValue(); 
        window.location='adm_solicitud_ext.php?ciu_codigo=' + codigo + '&opcion='+opcion+'&cedula='+cedula;         
        return true;
    }
    function getRadioButtonSelectedValue()
    {
        ctrl=document.formEnviar.ciu_ver;
        for(i=0;i<ctrl.length;i++)
            if(ctrl[i].checked) return ctrl[i].value;
    }


</script>
<body>
<form name="formEnviar" action="<?=$linkPagina?>" method="post">
<?php $ciud->cajaHidden('tipo_query', 4);//tipo busqueda
?>
    <?php graficarTabsCiud();?>
<table width="100%" border="1" align="center" class="t_bordeGris">
  	<tr>
    	    <td class="titulos4">
		<center>
		<p><B><span class=etexto>Existen Solicitudes por Autorizar</span></B> </p>
		</center>
	    </td>
	</tr>
</table>
<table border=0 width="100%" class="borde_tab" cellpadding="0" cellspacing="5">
	<tr>
	    <td width="30%" class="titulos5"><font class="tituloListado">Buscar ciudadano: </font></td>
	    <td class="listado5" valign="middle">
	    	<table>
 	  	    <tr>
			<td><span class="listado5">Nombre / C.I. </span> </td>
			<td><input type=text name="txt_buscar_nombre" id="txt_buscar_nombre" value="<?=$txt_buscar_nombre?>" class="tex_area"/></td>
                    </tr>
	    	</table>
	    </td>
       <td class="titulos5">
                <input type="radio"  name="ciu_ver" id="ciu_ver" value="a" onclick="realizar_busqueda();"
                <?php if ($opcion=="a") echo "checked "; ?>>Rechazado
                <input type="radio"  name="ciu_ver" id="ciu_ver" value="c" onclick="realizar_busqueda();"
                <?php if ($opcion=="c") echo "checked "; ?>>Enviado
                <input type="radio"  name="ciu_ver" id="ciu_ver" value="d"  onclick="realizar_busqueda();"
                <?php if ($opcion=="d") echo "checked "; ?>> Autorizado
                <input type="radio"  name="ciu_ver" id="ciu_ver" value="e" onclick="realizar_busqueda();"
                <?php if ($opcion!="a" && $opcion!="c" && $opcion!="d") echo "checked "; ?>>Ver Todos
        </td>
        <td width="20%" align="center" class="titulos5" >
        <?php if ($_SESSION["usua_admin_sistema"]==1){?>
                <input type="button" name="btn_buscar" value="Buscar" class="botones" onClick="realizar_busqueda();"/>
         
         <?php }?>
	</td>
    </tr>
</table>  
    <br/>
    <center><div id='div_buscar_ciudadanos' style="width: 99%"></div></cente>
    <center><input  name="btn_accion" type="button" class="botones" value="Regresar" onClick="location='../usuarios/mnuUsuarios_ext.php?cerrar=<?=$cerrar?>'"/></center>

</form>
</body>
</html>

<script>
realizar_busqueda();
</script>