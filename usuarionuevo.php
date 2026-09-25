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

    include_once(__DIR__.'funciones_interfaz.php');

?>
<!DOCTYPE html>
    <?php echo html_head(true,true); /*Imprime el head definido para el sistema*/?>

    <script language="JavaScript" type="text/JavaScript">
        function popup_main()
        {
            var x = (screen.width - 1400) / 2;
            var y = (screen.height - 750) / 2;
            url = "./Administracion/usuarios/cambiar_password.php?krd=<?=$_GET['krd']?>&code=<?=$_GET['code']?>";
            ventana=window.open(url,"QUIPUX","toolbar=no,directories=no,menubar=no,status=no,scrollbars=yes, width=1400, height=750");
            ventana.moveTo(x, y);
            ventana.focus();
        }
    </script>

    <body class="f-default light_slate" onLoad='focus();'>
        <div id="wrapper">
        <?php  echo html_encabezado(); /*Imprime el encabezado del sistema*/ ?>
        <?php  //echo html_validar_browser(); /*Valida el browser*/ ?>
        <div id="mainbody"><div class="shad-1"><div class="shad-2"><div class="shad-3"><div class="shad-4"><div class="shad-5">
        <br /><br /><br />
        <table align="center" width="100%" cellpadding="0" cellspacing="0" class="mainbody">
            <tr valign="top" align="center">
                <td class="left"  align="center" width="100%">
                    <h1>Sistema de Gesti&oacute;n Documental - QUIPUX</h1><br /><br />
                    Para Ingresar al sistema, haga click &nbsp
                    <a href="javascript:popup_main();"><font color="blue" face="Verdana" size="3" ><b>&quot;AQU&Iacute;&quot;</b></font></a>
                </td>
            </tr>
        </table>
        <br /><br /><br />

        </div></div></div></div></div></div>
        <?php  echo html_pie_pagina(); /*Imprime el pie de pagina del sistema*/ ?>
        </div>
    </body>
</html>
