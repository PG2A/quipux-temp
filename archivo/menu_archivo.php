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

//session_start();
include_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__).'/funciones_interfaz.php');

if (!isset($acceso_ciudadano_inst)) $acceso_ciudadano_inst = 0;

echo "<!DOCTYPE html>".html_head();
?>
<script language="JavaScript" type="text/javascript" >
function llamaCuerpo(parametros){
    top.frames['mainFrame'].location.href=parametros;

}
</script>
<body>
<center>
<br>
<?php if($_SESSION['inst_codi']==$acceso_ciudadano_inst){ ?>
<center>
<table width="32%" border="0" cellpadding="0" cellspacing="5" class="borde_tab">
  <tr >
    <td colspan="2" class="titulos4"><div align="center"><strong>No tiene acceso</strong></div></td>
  </tr>
</table>
</center>
<?php }else{ ?>
<table border="0" width="50%" cellpadding="0" cellspacing="5" class="borde_tab admin-card">
    <tr>
	<td class=titulos4 align="center" >Men&uacute; de Archivo F&iacute;sico</td>
    </tr>
    <tr>
	<td class="listado2">
            <?php
                 $parametrosFuncion = "incluir_doc_archivo_cuerpo.php";
                 $parametrosFuncion = "'".$parametrosFuncion."'";
                 ?>
	    <a onclick="llamaCuerpo(<?=$parametrosFuncion?>);" href="javascript:void(0);" target='mainFrame' class="vinculos">
		<b>1. Ubicaci&oacute;n F&iacute;sica de Documentos</b></a>  
	</td>
    </tr>
    <tr>
	<td class="listado2">
             <?php
                 $parametrosFuncion = "consultar_doc_archivo.php";
                 $parametrosFuncion = "'".$parametrosFuncion."'";
                 ?>
	    <a onclick="llamaCuerpo(<?=$parametrosFuncion?>);" href="javascript:void(0);" target='mainFrame' class="vinculos">
		<b>2. Consultar Ubicaci&oacute;n del Documento en el Archivo F&iacute;sico</b></a>  
	</td>
    </tr>
    <tr>
	<td class="listado2">
             <?php
                 $parametrosFuncion = "nueva_ubicacion_fisica.php";
                 $parametrosFuncion = "'".$parametrosFuncion."'";
                 ?>
	    <a onclick="llamaCuerpo(<?=$parametrosFuncion?>);" href="javascript:void(0);" target='mainFrame' class="vinculos">
		<b>3. Nueva Ubicaci&oacute;n F&iacute;sica</b></a>  
	</td>
    </tr>
    <tr>
	<td class="listado2">
             <?php
                 $parametrosFuncion = "consultar_archivo.php";
                 $parametrosFuncion = "'".$parametrosFuncion."'";
                 ?>
	    <a onclick="llamaCuerpo(<?=$parametrosFuncion?>);" href="javascript:void(0);" target='mainFrame' class="vinculos">
		<b>4. Consultar Estructura del Archivo F&iacute;sico</b></a>  
	</td>
    </tr>

<?php  if ($_SESSION["usua_admin_archivo"]==1) { ?>
    <tr>
	<td class="listado2">
             <?php
                 $parametrosFuncion = "adm_archivo_nivel.php";
                 $parametrosFuncion = "'".$parametrosFuncion."'";
                 ?>
	    <a onclick="llamaCuerpo(<?=$parametrosFuncion?>);" href="javascript:void(0);" target='mainFrame' class="vinculos">
		<b>5. Organizaci&oacute;n F&iacute;sica del Archivo</b></a>  
	</td>
    </tr>
<?php  }  ?>
</table>
<?php } ?>
</center>
</body>
</html>
