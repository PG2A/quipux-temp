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
 * @package    tbasicas
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
if($_SESSION["usua_codi"]!=0) {
    die ("Usted no tiene los permisos suficientes para acceder a esta p&aacute;gina.");
}
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');

$anio = 0+date("Y");
$mes =  0+date("m");
if ($mes == 12) ++$anio;

echo "<!DOCTYPE html>".html_head();
include_once('../../js/ajax.js');

?>
    <script>
        var proceso = new Array();
        proceso[0] = 'Inicializando proceso para nuevo año';
        proceso[1] = 'Creación del directorio <?=$anio?> en la bodega';
        proceso[2] = 'Inicialización de las secuencias';
        proceso[3] = 'Verificación de la bodega';
        proceso[4] = 'Verificación de las secuencias';
        proceso[5] = 'Desbloquear Sistema';

        var pagina = new Array();
        pagina[0] = '';
        pagina[1] = 'cambio_de_anio_bodega.php';
        pagina[2] = 'cambio_de_anio_secuencia.php';
        pagina[3] = 'cambio_de_anio_bodega_verifica.php';
        pagina[4] = 'cambio_de_anio_secuencia_verifica.php';
        pagina[5] = 'cambio_de_anio_desbloquear_sistema.php';

        var num_proceso = 0;
        var max_proceso = 5;
        var timerID;
        
        proceso[max_proceso+1] = '<h3><i>Proceso finalizado.<br><br>¡Feliz año <b><?=$anio?></b>!.</i></h3>';

        function timer_inicializar_proceso() {
            valor = document.getElementById("div_proceso").innerHTML;
            if (valor=='') {
                anadir_log('Procesando...');
                timerID = setTimeout("timer_inicializar_proceso()", 10000);
                return;
            } else {
                anadir_log(document.getElementById("div_proceso").innerHTML);
            }
            if (valor == 'OK') {
                ++num_proceso;
                mostrar_estado();
                document.getElementById("div_proceso").innerHTML = '';
                if (num_proceso <= max_proceso) {
                    anadir_log('<br><b>' + proceso[num_proceso] + '</b>');
                    nuevoAjax('div_proceso', 'POST', pagina[num_proceso], '');
                    timerID = setTimeout("timer_inicializar_proceso()", 10000);
                } else {
                    detener_proceso();
                }
            } else {
                document.getElementById("div_proceso").innerHTML = '';
                anadir_log('<br>Intentando de nuevo ejecutar el proceso ' + proceso[num_proceso]);
                nuevoAjax('div_proceso', 'POST', pagina[num_proceso], '');
                timerID = setTimeout("timer_inicializar_proceso()", 10000);
            }
        }

        function detener_proceso() {
            clearTimeout(timerID);
            if (num_proceso > max_proceso) {
                alert('El proceso de inicializacion de secuencias ha finalizado.');
            }
            return;
        }

        function anadir_log(mensaje) {
            document.getElementById("div_log").innerHTML += '<br>'+ mensaje;
        }

        function mostrar_estado() {
            document.getElementById("div_estado").innerHTML = '<center><b>' + proceso[num_proceso] + '</b></center>';
            if (num_proceso <= max_proceso) {
                document.getElementById("div_estado").innerHTML += '<center><br><br><img src="../../imagenes/progress_bar.gif"><br>Por favor espere.</center>';
            }
        }

    </script>
<body>
  <center>
    <table width="70%" border="1" cellspacing="0" cellpadding="0" class="borde_tab">
        <tr>
            <th width="100%">Proceso para inicializar el nuevo año</th>
        </tr>
        <tr>
            <td class="listado1"><br><br><div id="div_estado"></div><br></td>
        </tr>
        <tr>
            <th>Proceso para inicializar el nuevo año</th>
        </tr>
        <tr>
            <td class="listado1"><div id="div_log"></div><br></td>
        </tr>
    </table>
    <div id="div_proceso" style="display: none;">OK</div>
    <br>
    <script>
        anadir_log('<br><b>' + proceso[0] + '</b>');
        timer_inicializar_proceso();
    </script>
  </center>
</body>
</html>

