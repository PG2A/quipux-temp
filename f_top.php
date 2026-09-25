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
 * @package    quipux
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$ruta_raiz = ".";
session_start();

include_once(__DIR__.'/rec_session.php');
require_once( __DIR__.'/funciones.php');
include_once(__DIR__.'/funciones_interfaz.php');

echo "<!DOCTYPE html>".html_head();
include_once(__DIR__.'/js/ajax.js');

?>
<script language="JavaScript" type="text/JavaScript">
    
    // [REQ-3] Cierre de sesión rompiendo el frameset completo.
    // window.top referencia la ventana raíz, escapando de cualquier
    // anidamiento de iframes heredado del layout Orfeo (topFrame/leftFrame/mainFrame).
    function cerrar_session() {
        if (window.top.confirmarSalir) { window.top.confirmarSalir(); return; }
        if (confirm('¿Está seguro de Cerrar la Sesión?'))
        {
            if (window.top.bloquearPantalla) { window.top.bloquearPantalla('Cerrando sesión...'); }
            window.top.location.href = 'cerrar_session.php?accion=cerrar';
        }
    }

    function reiniciar_session(){
        document.formulario.action = 'reiniciar_session.php';
        document.formulario.submit();
    }

    function popup_firma_digital() {
        var x = (screen.width) / 2;
        var y = (screen.height) / 2;
        windowprops = "top=100,left=100,scrollbars=yes, resizable=yes,width="+x+",height="+y;
        url = "http://firmadigital.informatica.gob.ec";
        ventana = window.open(url , "FirmaDigital");//, windowprops);
        ventana.focus();
    }

    var topTimerId = 0;
    var topTimerIdVerifica = 0;
    var topIntentosVerifica = 0; // Para que si no encuentra la alerta no se quede colgado
    var codigo_mensaje_alerta_top = '';

    function cargar_alerta_mensaje() {
        clearTimeout(topTimerId);
        nuevoAjax('div_mensaje_alerta', 'GET', './bodega/mensaje_alerta_top.html');
        topTimerIdVerifica = setInterval( "validar_cambio_mensaje()", 5000 ); // Verifica si el mensaje se modifico
        topTimerId = setInterval( "cargar_alerta_mensaje()", 300000 ); // Cada 5 minutos recarga el mensaje
    }

    function validar_cambio_mensaje() {
        try {
            if (document.getElementById('txt_codigo_mensaje_alerta_top').innerHTML != codigo_mensaje_alerta_top) {
                codigo_mensaje_alerta_top = document.getElementById('txt_codigo_mensaje_alerta_top').innerHTML;
                document.getElementById('div_mensaje_alerta').style.display = '';
            }
        } catch (e) { // Si no se cargo aun el mensaje
            if (++topIntentosVerifica <= 3) //Valida para que no se quede en un lazo infinito
                topTimerIdVerifica = setInterval( "validar_cambio_mensaje()", 5000 );
        }
        clearTimeout(topTimerIdVerifica);
    }

    function ocultar_mensaje_alerta() {
        document.getElementById('div_mensaje_alerta').style.display = 'none';
    }
</script>
<body  id="page-bg" class="f-default light_slate" onload="cargar_alerta_mensaje()">
  <form name='formulario' action="" target="_parent" method="post"> 
    <div id="header">
        <div id="div_mensaje_alerta" style="height: 70px; width: 522px; overflow: auto; position: fixed; top: 2px; right: 210px; z-index: 5; display: none;"></div>
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr height="74px">
                <td width="1%"></td>
				<td width="10%" align="rigth" valign="middle">
                    <img src="../imagenes/2.png" height="60" width="190" alt="Quipux"/></td>
                <td width="70%">
                    <h1 align="center"><font color='#002B5C'>Sistema Documental Universidad de Cuenca</font></h1>
                </td>
                <td width="20%">
                    <div id="nav-big">
                        <table align='right'>
                            <tr>
                                <td>
                                    <br>
                                </td>
                                <td>
                                    <!--ul>
                                        <li class="active_menu">
                                            <a href='#' onClick="popup_firma_digital();" class="b20" target="_self"></a>
                                        </li>
                                    </ul-->
                                </td>
                                <td>
                                    <ul>
                                        <li class="active_menu">
                                            <a href="inf_soporte.php" class="b6 navbtn navbtn-help" target="mainFrame" title="Ayuda"><svg class="navico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg><span>Ayuda</span></a>
                                        </li>
                                    </ul>
                                </td>
                                <td>
                                    <ul>
                                        <li class="active_menu">
                                            <a href='#' onClick="cerrar_session();" class="b51 navbtn navbtn-exit" title="Salir"><svg class="navico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg><span>Salir</span></a>
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
            <tr >
                <td width="100%" colspan="4" align="left" style="height: 22px; vertical-align: middle;">
                    <?php require "$ruta_raiz/cargo_usuario.php";?>
                </td>
            </tr>
        </table>
    </div>
  </form>
</body>
</html>
