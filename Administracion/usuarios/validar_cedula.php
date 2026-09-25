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

// Borramos el cache del navegador
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Fecha en el pasado
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // siempre modificado
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache"); // HTTP/1.0

include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');

echo "<!DOCTYPE html>" . html_head();
include_once('../../js/ajax.js');
?>
<script language="javascript" type="">
    function cambiar_password_validar_usuario() {
        parametros = "txt_cedula=" + document.getElementById("txt_cedula").value;
        nuevoAjax('div_validar_usuario', 'POST', 'validar_cedula_verificar.php', parametros);
    }

    function cambiar_password() {
        try {
            if (document.getElementById("txt_cedula").value == "") {
                alert ('Por favor ingrese su número de cédula.');
                return;
            }
        } catch (e) {}
    }
</script>
<body>
  <center>
    <form name="formulario" action="javascript:cambiar_password()" method="post">
        <br>
        <table class="borde_tab" width="90%" cellspacing="8">
            <tr>
                <th colspan="2"><center>Cambio de contrase&ntilde;as para Ciudadanos</center></th>
            </tr>
            <tr>
                <td width="50%" align="right">Ingrese su No. de c&eacute;dula o<br>el c&oacute;digo de usuario generado por Quipux</td>
                <td width="50%"><input type="text" id='txt_login' name="txt_login" size="20" maxlength="15" class="tex_area" onchange="cambiar_password_validar_usuario()"></td>
            </tr>
            <tr>
                <td colspan="2"><div id="div_validar_usuario"></div></td>
            </tr>
        </table>
        <br>
        <input type="submit" name="btn_accion" class="botones" value="Aceptar">
        &nbsp;&nbsp;
        <input type="button" name="btn_accion" class="botones" value="Cancelar" onclick="javascript:window.close()">
    </form>
  </center>
</body>
