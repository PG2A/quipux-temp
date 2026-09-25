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
 * Jefe de Área y bandeja compartida de un área, a pantalla completa. Reutiliza
 * los paneles ajax existentes (administrar_jefe_ajax.php y compartirBandeja_ajax.php)
 * y las funciones JS que operan sobre ellos, que antes vivían en el árbol de
 * adm_dependencias_nuevo.php. Se llega desde la columna "Jefe" de areas.php.
 *
 * @package    dependencias
 * @author     2026 Marco Terán <marcosdanny14@gmail.com>
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
$depe_codi = 0 + trim(limpiar_numero($_GET["depe_codi"] ?? 0));

if ($depe_codi <= 0) {
    die( html_error("No se indic&oacute; el &aacute;rea.") );
}

$rs = $db->conn->Execute("select depe_ruta($depe_codi) as ruta, inst_codi from dependencia where depe_codi = $depe_codi");
if (!$rs or $rs->EOF or (0 + $rs->fields["INST_CODI"]) != $inst_codi) {
    die( html_error("El &aacute;rea solicitada no existe en esta instituci&oacute;n.") );
}
$ruta = $rs->fields["RUTA"];

// "Regresar al listado" vuelve al listado del área padre (donde vivía esta área);
// si es de primer nivel, al listado general.
$volver_listado = "areas.php";
$rsPad = $db->conn->Execute(
    "select p.depe_codi as pad, (coalesce(p.depe_codi_padre, p.depe_codi) = p.depe_codi) as pad_es_raiz
       from dependencia d join dependencia p on p.depe_codi = d.depe_codi_padre
      where d.depe_codi = $depe_codi and d.depe_codi <> d.depe_codi_padre");
if ($rsPad and !$rsPad->EOF) {
    $esRaiz = ($rsPad->fields["PAD_ES_RAIZ"] === 't' || $rsPad->fields["PAD_ES_RAIZ"] === true || $rsPad->fields["PAD_ES_RAIZ"] == 1);
    if (!$esRaiz) $volver_listado = "areas.php?padre=".(0 + $rsPad->fields["PAD"]);
}

echo "<!DOCTYPE html>".html_head();
?>
<!-- Prototype/general1 aportan Ajax.Request y ajax_call_bandeja; ajax.js aporta nuevoAjax -->
<script language="JavaScript" src="../../js/prototype.js" type="text/javascript"></script>
<script language="JavaScript" src="../../js/general1.js" type="text/javascript"></script>
<?php include_once('../../js/ajax.js'); ?>
<script type="text/javascript">
    // Recarga los dos paneles del área (jefe y bandeja compartida).
    function datosArea(depeCodi) {
        var datos = '2&dependencia=' + depeCodi;
        nuevoAjax('div_jefe',   'GET', 'administrar_jefe_ajax.php', 'accion=' + datos);
        nuevoAjax('compartir',  'GET', 'compartirBandeja_ajax.php', 'accion=' + datos);
    }

    // Asigna o quita el Jefe del área. 'grabar' toma la persona seleccionada en el
    // combo 'usuario_jefe'; cualquier otro valor elimina al jefe indicado.
    function grabar_jefe(id_jefe, depeCodi, accion) {
        var i = 0, id_usuario, data;
        var clazz = "usuario", action = "";
        if (accion == 'grabar') {
            action = "grabar_jefe";
            if (document.getElementById("usuario_jefe").selectedIndex == -1) {
                alert("No ha seleccionado Usuarios"); return;
            }
            for (i = 0; i < document.getElementById("usuario_jefe").length; i++) {
                if (document.getElementById('usuario_jefe').options[i].selected) {
                    id_usuario = document.getElementById('usuario_jefe').options[i].value;
                    data = id_usuario + ',' + id_jefe + ',' + depeCodi;
                    ajax_call_bandeja(data, clazz, action, ver_datos);
                }
            }
        } else {
            action = "elimina_jefe_area";
            data = id_jefe + ',0,' + depeCodi;
            ajax_call_bandeja(data, clazz, action, ver_datos);
        }
    }

    // Comparte o retira la bandeja de documentos recibidos del jefe con las
    // personas seleccionadas en el combo 'usuario'.
    function compartir_bandeja(id_jefe, depeCodi, accion) {
        var i = 0, id_usuario, data;
        var clazz = "usuario", action = "";
        if (accion == 1) {
            action = "compartirUsuarioBandeja";
            if (document.getElementById("usuario").selectedIndex == -1) {
                alert("No ha seleccionado Usuarios"); return;
            }
            for (i = 0; i < document.getElementById("usuario").length; i++) {
                if (document.getElementById('usuario').options[i].selected) {
                    id_usuario = document.getElementById('usuario').options[i].value;
                    data = id_usuario + ',' + id_jefe + ',' + depeCodi;
                    ajax_call_bandeja(data, clazz, action, ver_datos);
                }
            }
        } else if (accion == 2) {
            action = "elim_compartirUsuarioBandeja";
            data = id_jefe + ',0,' + depeCodi;
            ajax_call_bandeja(data, clazz, action, ver_datos);
        }
    }

    // Tras cada operación: si hubo error lo muestra, si no recarga los paneles.
    function ver_datos(result, resp, depeCodi) {
        if (resp != "") alert(resp);
        else datosArea(depeCodi);
    }
</script>
<body onLoad="datosArea(<?php echo $depe_codi; ?>);">
<div class="sumillas-wrap">

    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr><td class="titulos4">Jefe de &Aacute;rea</td></tr>
        <tr><td class="listado2"><b><?php echo htmlspecialchars($ruta); ?></b></td></tr>
    </table>

    <div class="sumillas-botonera">
        <input type="button" name="btn_volver_area" value="Volver al &Aacute;rea" class="botones"
               onClick="window.location='areas_form.php?txt_depe_codi=<?php echo $depe_codi; ?>'">
        <input type="button" name="btn_regresar" value="Regresar al listado" class="botones"
               onClick="window.location='<?php echo $volver_listado; ?>'">
    </div>

    <div id="div_jefe"></div>
    <div id="compartir"></div>

</div>
</body>
</html>
