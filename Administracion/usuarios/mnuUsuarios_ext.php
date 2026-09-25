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
if($_SESSION["usua_admin_sistema"]!=1 and $_SESSION["usua_perm_ciudadano"]!=1) {
    die("");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php"); //para traer funciones p_get y p_post
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');

p_register_globals(array());


if (!isset($cerrar)) $cerrar="No";

echo "<!DOCTYPE html>".html_head();
?>
<script>
function llamaCuerpo(parametros){   
    top.frames['mainFrame'].location.href=parametros;

}
</script>
<body>
<form name='frmMnuUsuarios' action='../formAdministracion.php' method="post">
<br>
<br>
<center>
  <table width="32%"  border="0" cellpadding="0" cellspacing="5" class="borde_tab">
  <tr bordercolor="#FFFFFF">
    <td colspan="2" class="titulos4"><div align="center"><strong>Administraci&oacute;n de Ciudadanos</strong></div></td>
  </tr>
  
  <tr bordercolor="#FFFFFF">
    <td class="listado2" width="98%">
         <?php
                $parametrosFuncion = "../ciudadanos/cuerpoUsuario_ext.php?cerrar=$cerrar&accion=2";
                $parametrosFuncion = "'".$parametrosFuncion."'";
            ?>
	<a onclick="llamaCuerpo(<?=$parametrosFuncion?>);" href="javascript:void(0);" class="vinculos">1. Editar Ciudadano</a>
    </td>
  </tr>
  <?php
  /*<tr bordercolor="#FFFFFF">
    <td  class="listado2" width="98%">
         <?php
                $parametrosFuncion = "../ciudadanos/adm_usuario_ext.php?cerrar=$cerrar&accion=1";
                $parametrosFuncion = "'".$parametrosFuncion."'";
            ?>
	<a onclick="llamaCuerpo(<?=$parametrosFuncion?>);" href="javascript:void(0);" class="vinculos">1. Crear Ciudadano</a>
    </td>
  </tr>
  <tr bordercolor="#FFFFFF">
    <td class="listado2" width="98%">
        <?php
                $parametrosFuncion = "../ciudadanos/cuerpoUsuario_ext.php?cerrar=$cerrar&accion=3";
                $parametrosFuncion = "'".$parametrosFuncion."'";
            ?>
	<a onclick="llamaCuerpo(<?=$parametrosFuncion?>);" href="javascript:void(0);" class="vinculos">3. Consultar Ciudadano</a>
    </td>
  </tr>*/
  ?>
<?php  if ($_SESSION["perm_validar_ciudadano"]==1) { ?>
      <tr bordercolor="#FFFFFF">
        <td class="listado2" width="98%">
           <?php
                $parametrosFuncion = "../ciudadanos/adm_ciudadano_confirmar.php";
                $parametrosFuncion = "'".$parametrosFuncion."'";
            ?>
        <a onclick="llamaCuerpo(<?=$parametrosFuncion?>);" href="javascript:void(0);" class="vinculos">2. Confirmar datos ingresados por ciudadanos</a>
        </td>
      </tr>
      <tr bordercolor="#FFFFFF">
        <td class="listado2" width="98%">
        <?php
                $parametrosFuncion = "../ciudadanos/adm_usuario_ext_combinar.php";
                $parametrosFuncion = "'".$parametrosFuncion."'";
            ?>
            <a onclick="llamaCuerpo(<?=$parametrosFuncion?>);" href="javascript:void(0);" class="vinculos">3. Combinar dos ciudadanos</a>
        </td>
      </tr>
      <tr bordercolor="#FFFFFF">
        <td class="listado2" width="98%">
            <?php                
                $parametrosFuncion = "../ciudadanos_solicitud/cuerpoSolicitud_ext.php";
                echo $parametros;
                $parametrosFuncion = "'".$parametrosFuncion."'";
            ?>
        <a onclick="llamaCuerpo(<?=$parametrosFuncion?>);" href="javascript:void(0);" class="vinculos">4. Solicitudes generar firmar documentos</a>
        </td>
      </tr>
<?php  } ?>
  <tr bordercolor="#FFFFFF">
  	<td align="center" class="listado2"><center>
<?php  	if ($cerrar=="Si") 
 	    echo '<input align="middle" class="botones" type="button" name="Submit" value="Cerrar" onClick="window.close();">';
    	else
	    echo '<input align="middle" class="botones" type="submit" name="Submit" value="Regresar">';
?>
	</center>
	</td> </tr>
</table>
</center>
</form>
</body>
</html>
