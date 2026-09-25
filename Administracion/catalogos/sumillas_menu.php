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
 * @package    catalogos
 * @author     2026 Marco Terán <marcosdanny14@gmail.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');

if (($_SESSION["usua_admin_sistema"] ?? 0) != 1 and ($_SESSION["usua_codi"] ?? -1) != 0) {
    die( html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.") );
}

echo "<!DOCTYPE html>".html_head();
include_once('../../js/ajax.js');

// El listado se dibuja en "div_cuerpo", igual que las bandejas de documentos,
// para que herede el mismo estilo de tabla y de paginación.
// 'txt_buscar' viaja en cada recarga del paginador, incluidos los enlaces de
// página y de ordenamiento, para que el filtro no se pierda al navegar.
$paginador = new ADODB_Pager_Ajax(dirname(__DIR__, 2), "div_cuerpo", "sumillas_paginador.php", "txt_buscar", "");

$inst_nombre = $_SESSION["inst_nombre"] ?? "";
?>
<script type="text/javascript">
    function sincronizar_busqueda() {
        // El paginador concatena el valor tal cual en la URL; se guarda ya
        // codificado para que un '&' o un '#' en la búsqueda no la rompa.
        var texto = document.getElementById('txt_buscar_sumilla').value.replace(/^\s+|\s+$/g, '');
        document.getElementById('txt_buscar').value = encodeURIComponent(texto);
    }

    function realizar_busqueda() {
        sincronizar_busqueda();
        paginador_reload_div('');
    }

    function limpiar_busqueda() {
        document.getElementById('txt_buscar_sumilla').value = '';
        realizar_busqueda();
    }

    function pulsar(e) {
        // Realiza la busqueda si el usuario presiona enter
        tecla = (document.all) ? e.keyCode : e.which;
        if (tecla==13) {
            realizar_busqueda();
            return false;
        }
    }

    function editar_sumilla(accion_codi) {
        window.location = 'sumillas.php?txt_accion_codi=' + accion_codi;
    }
</script>
<body onLoad="paginador_reload_div('');">
<div class="sumillas-wrap">
<form name="form1" id="form1" action="" method="POST" onsubmit="return false;">

    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td colspan="2" class="titulos4">Administraci&oacute;n de Sumillas &nbsp;&ndash;&nbsp; <?php echo htmlspecialchars($inst_nombre); ?></td>
        </tr>
        <tr>
            <td colspan="2" class="listado2">
                Sumillas que se ofrecen al reasignar un documento, agrupadas por el motivo
                (categor&iacute;a) por el que se emiten.
            </td>
        </tr>
        <tr>
            <td width="90%">
                <table width="100%" border="0">
                    <tr>
                        <td width="19%" class="titulos2">Texto a Buscar</td>
                        <td width="81%" class="listado2">
                            <input type="text" name="txt_buscar_sumilla" id="txt_buscar_sumilla" size="40"
                                   class="tex_area" value="" maxlength="50" onkeypress="return pulsar(event);">
                            <input type="hidden" name="txt_buscar" id="txt_buscar" value="">
                            Descripci&oacute;n de la sumilla
                        </td>
                    </tr>
                </table>
            </td>
            <td width="10%" align="center">
                <input type="button" value="Buscar" name="Buscar" class="botones"
                       title="Busca el texto ingresado en la descripci&oacute;n de la sumilla" onclick="realizar_busqueda();">
                <input type="button" value="Limpiar" name="Limpiar" class="botones" onclick="limpiar_busqueda();">
            </td>
        </tr>
    </table>

    <div class="sumillas-botonera">
        <input type="button" name="btn_nueva" class="botones" value="Nueva Sumilla" onClick="editar_sumilla('0');">
        <input type="button" name="btn_categorias" class="botones" value="Categor&iacute;as" onClick="window.location='sumillas_categorias.php'">
        <input type="button" name="btn_regresar" class="botones" value="Regresar" onClick="window.location='../formAdministracion.php'">
    </div>

    <center>
        <div id="div_cuerpo"></div>
    </center>

</form>
</div>
</body>
</html>
