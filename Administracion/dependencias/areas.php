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
 * Administración de Áreas en una sola pantalla, al estilo del catálogo de
 * sumillas: barra de búsqueda, botón "Registrar Área" y un listado paginado
 * con columnas de acción (Editar), de Sub áreas y de Puestos. Sustituye al menú
 * de tres opciones y al árbol de adm_dependencias_nuevo.php.
 *
 * @package    dependencias
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

$inst_codi = 0 + $_SESSION["inst_codi"];

// Si viene un 'padre', el listado muestra sus sub áreas (mismo diseño), y permite
// bajar de nivel navegando a otra página igual. Sin 'padre', muestra las áreas de
// primer nivel de la institución.
$padre       = 0 + trim(limpiar_numero($_GET["padre"] ?? 0));
$padre_ruta  = "";
$padre_super = 0;   // padre del padre, para el enlace "Subir un nivel"
if ($padre > 0) {
    $rsP = $db->conn->Execute("select depe_ruta($padre) as ruta, depe_codi_padre, inst_codi
                                 from dependencia where depe_codi = $padre");
    if (!$rsP or $rsP->EOF or (0 + $rsP->fields["INST_CODI"]) != $inst_codi) {
        $padre = 0;   // no existe o es de otra institución: se ignora el filtro
    } else {
        $padre_ruta = $rsP->fields["RUTA"];
        $sup = 0 + $rsP->fields["DEPE_CODI_PADRE"];
        // "Subir un nivel" solo si el nivel superior es un área real, no la raíz
        // de la institución (para esa ya está "Ver todas las áreas").
        if ($sup > 0 and $sup != $padre) {
            $rsS = $db->conn->Execute("select coalesce(depe_codi_padre, depe_codi) = depe_codi as es_raiz
                                         from dependencia where depe_codi = $sup");
            $es_raiz = $rsS && !$rsS->EOF && ($rsS->fields["ES_RAIZ"] === 't' || $rsS->fields["ES_RAIZ"] === true || $rsS->fields["ES_RAIZ"] == 1);
            $padre_super = $es_raiz ? 0 : $sup;
        }
    }
}

echo "<!DOCTYPE html>".html_head();
include_once('../../js/ajax.js');

// El listado se dibuja en "div_cuerpo". 'txt_buscar' y 'txt_padre' viajan en cada
// recarga (búsqueda, cambio de página, orden) para que el filtro no se pierda al navegar.
$paginador = new ADODB_Pager_Ajax(dirname(__DIR__, 2), "div_cuerpo", "areas_paginador.php", "txt_buscar,txt_padre,txt_estruct", "");

$inst_nombre = $_SESSION["inst_nombre"] ?? "";
?>
<script type="text/javascript">
    function sincronizar_busqueda() {
        var texto = document.getElementById('txt_buscar_area').value.replace(/^\s+|\s+$/g, '');
        document.getElementById('txt_buscar').value = encodeURIComponent(texto);
        document.getElementById('txt_estruct').value = document.getElementById('cmb_estruct').value;
    }
    function realizar_busqueda() {
        sincronizar_busqueda();
        paginador_reload_div('');
    }
    function limpiar_busqueda() {
        document.getElementById('txt_buscar_area').value = '';
        document.getElementById('cmb_estruct').value = '';
        realizar_busqueda();
    }
    function pulsar(e) {
        var tecla = (document.all) ? e.keyCode : e.which;
        if (tecla == 13) { realizar_busqueda(); return false; }
    }

    // Ver sub áreas: abre OTRO listado igual, filtrado a las hijas del área.
    // Al ser una página nueva se puede seguir bajando de nivel y usar "atrás".
    function ver_subareas(depe_codi) { window.location = 'areas.php?padre=' + depe_codi; }

    function registrar_area()      { window.location = 'areas_form.php?txt_depe_codi=0<?php echo ($padre > 0) ? '&padre='.$padre : ''; ?>'; }
    function editar_area(codi)     { window.location = 'areas_form.php?txt_depe_codi=' + codi; }
    function ver_puestos(codi)     { window.location = 'areas_puestos.php?depe_codi=' + codi; }
    function jefe_area(codi)       { window.location = 'areas_jefe.php?depe_codi=' + codi; }
    // Exporta a Excel todas las áreas/sub áreas/puestos, respetando el filtro de estructura.
    function descargar_excel() {
        var est = document.getElementById('cmb_estruct') ? document.getElementById('cmb_estruct').value : '';
        window.location = 'areas_excel.php?txt_estruct=' + encodeURIComponent(est);
    }

    // Pestañas: vista tabla / vista árbol (el árbol es sólo lectura)
    var arbol_cargado = false;
    function mostrar_tabla() {
        document.getElementById('pane_tabla').style.display = '';
        document.getElementById('pane_arbol').style.display = 'none';
        document.getElementById('tab_tabla').className = 'tab-button activa';
        document.getElementById('tab_arbol').className = 'tab-button';
    }
    function mostrar_arbol() {
        document.getElementById('pane_tabla').style.display = 'none';
        document.getElementById('pane_arbol').style.display = '';
        document.getElementById('tab_tabla').className = 'tab-button';
        document.getElementById('tab_arbol').className = 'tab-button activa';
        if (!arbol_cargado) {
            nuevoAjax('div_arbol', 'GET', 'areas_arbol.php', 't=' + (new Date()).getTime());
            arbol_cargado = true;
        }
    }
    // Plegar/desplegar nodos del árbol (delegación de eventos)
    document.addEventListener('click', function(e) {
        var t = e.target;
        while (t && t.className !== undefined && String(t.className).indexOf('sumillas-toggle') < 0 && t.id !== 'div_arbol') t = t.parentNode;
        if (t && t.className !== undefined && String(t.className).indexOf('sumillas-toggle') >= 0) {
            var li = t.parentNode;
            if (li.className.indexOf('cerrada') >= 0) li.className = li.className.replace(/\s*cerrada/, '');
            else li.className += ' cerrada';
        }
    });
</script>
<body onLoad="paginador_reload_div('');">
<div class="sumillas-wrap">
<form name="form1" id="form1" action="" method="POST" onsubmit="return false;">

    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td colspan="2" class="titulos4">Administraci&oacute;n de &Aacute;reas y Puestos &nbsp;&ndash;&nbsp; <?php echo htmlspecialchars($inst_nombre); ?></td>
        </tr>
<?php if ($padre > 0) { ?>
        <tr>
            <td colspan="2" class="listado2">
                Sub &aacute;reas de: <b><?php echo htmlspecialchars($padre_ruta); ?></b>
            </td>
        </tr>
<?php } else { ?>
        <tr>
            <td colspan="2" class="listado2">
                &Aacute;reas de la instituci&oacute;n. Use <b>Sub &aacute;reas</b> para ver las que dependen de un &aacute;rea,
                y <b>Puestos</b> para administrar sus cargos.
            </td>
        </tr>
<?php } ?>
    </table>

    <!-- Pestañas: Vista tabla (editable) / Vista árbol (sólo lectura) -->
    <div class="tabs-barra" style="margin:6px 0 10px;">
        <input type="button" id="tab_tabla" class="tab-button activa" value="Vista tabla" onclick="mostrar_tabla();">
        <input type="button" id="tab_arbol" class="tab-button" value="Vista árbol" onclick="mostrar_arbol();">
    </div>

    <div id="pane_tabla">
    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td width="90%">
                <table width="100%" border="0">
                    <tr>
                        <td width="19%" class="titulos2">Texto a Buscar</td>
                        <td width="81%" class="listado2">
                            <input type="text" name="txt_buscar_area" id="txt_buscar_area" size="40"
                                   class="tex_area" value="" maxlength="150" onkeypress="return pulsar(event);">
                            <input type="hidden" name="txt_buscar" id="txt_buscar" value="">
                            <input type="hidden" name="txt_padre" id="txt_padre" value="<?php echo $padre; ?>">
                            <input type="hidden" name="txt_estruct" id="txt_estruct" value="">
                            Nombre o sigla del &aacute;rea
                        </td>
                    </tr>
                    <tr>
                        <td width="19%" class="titulos2">Estructura org&aacute;nica</td>
                        <td width="81%" class="listado2">
                            <select id="cmb_estruct" class="select" onchange="realizar_busqueda();">
                                <option value="">Todas</option>
                                <option value="1">S&iacute;</option>
                                <option value="0">No</option>
                            </select>
                        </td>
                    </tr>
                </table>
            </td>
            <td width="10%" align="center">
                <input type="button" value="Buscar" name="Buscar" class="botones"
                       title="Busca el texto ingresado en el nombre o la sigla del &aacute;rea" onclick="realizar_busqueda();">
                <input type="button" value="Limpiar" name="Limpiar" class="botones" onclick="limpiar_busqueda();">
            </td>
        </tr>
    </table>

<?php if ($padre > 0) { ?>
    <div class="listado2" style="margin:0 0 8px;">
<?php if ($padre_super > 0) { ?>
        <input type="button" class="botones" value="&laquo; Subir un nivel" onclick="window.location='areas.php?padre=<?php echo $padre_super; ?>'">
<?php } ?>
        <input type="button" class="botones" value="Ver todas las áreas" onclick="window.location='areas.php'">
    </div>
<?php } ?>

    <div class="sumillas-botonera">
        <input type="button" name="btn_nueva" class="botones" value="Registrar &Aacute;rea" onClick="registrar_area();">
        <input type="button" name="btn_excel" class="botones" value="Descargar Excel" title="Exporta todas las áreas, sub áreas y puestos" onClick="descargar_excel();">
        <input type="button" name="btn_comprobar" class="botones" value="Comprobaci&oacute;n de destinatarios" title="Dado un remitente, muestra a qué servidores puede enviar o reasignar un documento de periodo jerárquico" onClick="window.location='areas_comprobar_destinatarios.php';">
<?php
    // En un listado de sub áreas, Regresar vuelve al listado de áreas del nivel
    // anterior (o al general); en el listado general, a Administración.
    if ($padre > 0) $url_regresar = ($padre_super > 0) ? "areas.php?padre=".$padre_super : "areas.php";
    else            $url_regresar = "../formAdministracion.php";
?>
        <input type="button" name="btn_regresar" class="botones" value="Regresar" onClick="window.location='<?php echo $url_regresar; ?>'">
    </div>

    <center>
        <div id="div_cuerpo"></div>
    </center>
    </div><!-- /pane_tabla -->

    <div id="pane_arbol" style="display:none;">
        <div id="div_arbol"><center><br>Cargando &aacute;rbol&hellip;<br>&nbsp;</center></div>
    </div>

</form>
</div>
</body>
</html>
