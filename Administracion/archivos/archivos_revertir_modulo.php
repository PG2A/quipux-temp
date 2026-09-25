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
 * @package    archivos
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
if($_SESSION["perm_actualizar_sistema"]!=1) {
    die("Usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
}
require_once(dirname(__DIR__, 2).'/rec_session.php');
if (!is_dir(dirname(__DIR__, 2)."/bodega/2013/reversa")){
    if (!mkdir($concurrentDirectory = __DIR__ . "/bodega/2013/reversa") && !is_dir($concurrentDirectory)) {
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
    }
}

include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
echo "<!DOCTYPE html>".html_head();
include_once(dirname(__DIR__, 2).'/js/ajax.js');

?>
<script type="text/javascript">

    var timer_id_archivos_revertir_modulo = 0; // Temporizador

    function fjs_play_copia() {
        document.getElementById('img_play').setAttribute('onclick', "");
        timer_id_archivos_revertir_modulo = setTimeout("fjs_ejecutar_copia()", 300);
        document.getElementById('spn_estado').innerHTML = 'Ejecutandose';
        return;
    }

    function fjs_pausar_copia() {
        document.getElementById('img_play').setAttribute('onclick', "fjs_play_copia()");
        clearTimeout(timer_id_archivos_revertir_modulo);
        document.getElementById('spn_estado').innerHTML = 'Detenido';
        return;
    }

    function fjs_ejecutar_copia() {
        nuevoAjax('div_ejecutar_copia', 'POST', 'archivos_revertir_modulo_copiar.php', '', 'fjs_play_copia()');
        return;
    }

</script>

<body>
  <center>
    <br>
    <table width="90%" align="center" class=borde_tab border="0">
        <tr>
            <th width="100%" colspan="3">
              <center>
                  <br><b>REVERTIR M&Oacute;DULO DE ARCHIVO</b><br>Copiar los archivos de la BDD al File System<br>&nbsp;
              </center>
            </th>
        </tr>
        <tr>
            <td width="10%">&nbsp;</td>
            <td width="80%" align="left">
                <br>Ejecutar proceso:<br><br>
                <center>
                    <img src="../imagenes/play.png" id="img_play" alt="ejecutar" style="width: 20px; height: 20px;" onclick='fjs_ejecutar_copia()'>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <img src="../imagenes/pause.png" id="img_pause" alt="detener" style="width: 20px; height: 20px;" onclick='fjs_pausar_copia()'>
                </center>
                <br>
            </td>
            <td width="10%" align="right" valign="middle">
                &nbsp;
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td align="left">
                <br>Estado de la Ejecución: <b><span id="spn_estado">No iniciado</span></b><br><br>
                <div id="div_ejecutar_copia" style="width: 100%; text-align: center;"></div>
                <br>&nbsp;
            </td>
            <td>&nbsp;</td>
        </tr>
    </table>
    <br>

    <input type="button" name="btn_cancelar" value="Cerrar" class="botones_largo" onClick="window.close();">
  </center>

</body>
</html>
<?

?>