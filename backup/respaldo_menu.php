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
include_once("respaldo_funciones.php");

echo "<!DOCTYPE html>".html_head();

//Se consulta usuario que autoriza
$var_aprueba = 0;
//Se comenta autorización
//$usua_codi_autoriza = ObtenerCodigoUsuarioAutoriza(33,0,0,$_SESSION["usua_codi"],$db);
//if ($usua_codi_autoriza == $_SESSION["usua_codi"])
//    $var_aprueba = 1;

?>
<body>
<center>
<form name='frmAdministracion' action='../Administracion/formAdministracion.php' method="post">
  <br><br>
  <table width="32%" align="center" border="0" cellpadding="0" cellspacing="5" class="borde_tab admin-card">
  <tr>
    <td colspan="2" class="titulos4"><center><strong>Solicitudes personales</strong></center></td>
  </tr>
  <tr>
    <td class="listado2" width="98%"><a href="respaldo_informacion.php?txt_tipo_lista=4" class="vinculos" target='mainFrame'>1. Solicitar respaldos</a></td>
  </tr>
  <tr>
    <td class="listado2" width="98%"><a href="respaldo_lista.php?txt_tipo_lista=1" class="vinculos" target='mainFrame'>2. Mis Solicitudes</a></td>
  </tr> 
  <?php if($var_aprueba == 1){?>
  <tr>
    <td colspan="2" class="titulos4"><center><strong>Autorización de Solicitudes</strong></center></td>
  </tr>
  <tr>
    <td class="listado2" width="98%"><a href="respaldo_lista.php?txt_tipo_lista=2" class="vinculos" target='mainFrame'>1. Solicitudes por autorizar</a></td>
  </tr>
  <tr>
    <td class="listado2" width="98%"><a href="respaldo_lista.php?txt_tipo_lista=3" class="vinculos" target='mainFrame'>2. Listado de Solicitudes</a></td>
  </tr>
   <?php }?>

  <?php if($_SESSION["usua_admin_sistema"]==1){ ?>
  <tr>
    <td colspan="2" class="titulos4"><center><strong>Solicitudes de la Institución</strong></center></td>
  </tr>
  <tr>
    <td class="listado2" width="98%"><a href="respaldo_informacion.php?txt_tipo_lista=5" class="vinculos" target='mainFrame'>1. Solicitar respaldos</a></td>
  </tr>
  <tr>
    <td class="listado2" width="98%"><a href="respaldo_lista.php?txt_tipo_lista=6" class="vinculos" target='mainFrame'>2. Solicitudes por enviar</a></td>
  </tr>
  <tr>
    <td class="listado2" width="98%"><a href="respaldo_lista.php?txt_tipo_lista=7" class="vinculos" target='mainFrame'>3. Listado de Solicitudes</a></td>
  </tr>
   <?php }?> 
  <tr>
    <td align="center" class="listado2">
      <center><input align="middle" class="botones" type="submit" name="Submit" value="Regresar"></center>
    </td>
  </tr>
</table>
</form>
</center>
</body>
</html>