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
    echo html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
    die("");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php"); //para traer funciones p_get y p_post

p_register_globals(array());

?>

<table border=0 width="100%" class="borde_tab" cellpadding="0" cellspacing="5">
    <tr>
        <td width="30%" class="titulos5"><font class="tituloListado">Buscar usuarios por: </font></td>
        <td class="listado5" valign="middle">
        <table>
            <tr>
              <td><span class="listado5">Estado</span></td>
              <td>
                  <select name="cmb_estado" id="cmb_estado" class='select'>
                      <option value='1' selected>Activos</option>
                      <option value='0'>Inactivos</option>
                      <option value='2'>Todos</option>
                  </select>
              </td>
            </tr>
        </table>
        </td>
        <td width="20%" align="center" class="titulos5" >
            <input type="button" name="btn_buscar" value="Buscar" class="botones" onClick="consultar_usuarios(<?=$_GET['area']?>);">
        </td>
    </tr>
</table>