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
include_once(dirname(__DIR__, 2)."/funciones_interfaz.php");
include_once("../tbasicas/listaAreas.php");

p_register_globals(array());

?>

<!-- LIBRERIAS PARA GENERADOR DE ARBOL AJAX -->
<link rel="StyleSheet" href="../../js/nornix-treemenu-2.2.0/example/style/menu_uno.css" type="text/css" media="screen" />
<script type="text/javascript" src="../../js/nornix-treemenu-2.2.0/treemenu/nornix-treemenu.js"></script>
<?php  require_once("../../js/ajax.js");?>
<link rel="stylesheet" href="../../estilos/orfeo.css">

<!-- Para utilizacion de Ajax -->
<script language="JavaScript" src="../../js/prototype.js" type ="text/javascript"></script>
<script language="JavaScript" src="../../js/general1.js"  type="text/javascript"></script>

<script type='text/JavaScript'>
    function datosArea(depeCodi) {
        nuevoAjax('usua_estado', 'GET', 'usuariosArea_ajax.php', 'area='+depeCodi);
        nuevoAjax('usua_area', 'POST', 'reporte_usuarios_01_cargar.php', 'area='+depeCodi+'&estado=1');
        return;
    }

    function consultar_usuarios(depeCodi) {
        if(depeCodi==undefined)
        {
            alert('Por favor, seleccione en area.');
            return false;
        }
        area = depeCodi;
        estado = document.getElementById('cmb_estado').value;
        nuevoAjax('usua_area', 'POST', 'reporte_usuarios_01_cargar.php', 'area='+area+'&estado='+estado);
        return;
    }

    function ltrim(s) {
       return s.replace(/^\s+/, "");
    }

    function imprimirUsuariosAreas(){
        // Generar pdf de usuarios por areas areas
        var x = (screen.width - 20) / 2;
        var y = (screen.height - 20) / 2;
        preview = window.open('generarPDFUsuariosAreas.php','', 'scrollbars=yes,menubar=no,height=20,width=20,resizable=yes,toolbar=no,location=no,status=no');
        preview.moveTo(x, y);
        preview.focus();
    }
</script>

<?php echo "<!DOCTYPE html>" . html_head() . "<body>"; ?>
        <table width="100%">
        <tr><td align="center" class="titulos4" colspan="2"><font size="2">Reporte de Usuarios por &Aacute;reas</font></td></tr>
            <tr>
                <td valign="top" width="25%">
                <table class="borde_tab">
                    <tr>
                    <?php $listAreas = obtenerAreas($_SESSION['inst_codi'], $db); ?>
                    <td valign="middle" width="10%">
                        <div id="menu" class="menu"><a href="javascript:;" title="Exportar Usuarios a pdf" onclick="imprimirUsuariosAreas();"><br>&nbsp;&nbsp;&nbsp;&nbsp;Exportar Usuarios a pdf&nbsp;&nbsp;</a>
                            <?php
                                echo $listAreas;
                            ?>
                        </div>
                    </td>
                    </tr>
                </table>
                </td>
                <td width="75%" valign="top">
                    <div id="usua_estado"></div>
                    <div id="usua_area"></div></br>
                    <center>
                    <input type="button" name="regresar" value="Regresar" class="botones" onclick="window.location='mnuUsuarios.php'"></center>
                </td>
            </tr>
        </table>
</body>
</html>
<script>
    nuevoAjax('usua_estado', 'GET', 'usuariosArea_ajax.php', '');
</script>