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
 * Alta y edición de un área en una sola pantalla (reemplaza al árbol). Campos:
 * nombre, sigla, ciudad, área padre y estado. El puesto y la plantilla se
 * gestionan aparte (Puestos y el flujo de plantillas existente).
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

$inst_codi     = 0 + $_SESSION["inst_codi"];
$txt_depe_codi = 0 + trim(limpiar_numero($_GET["txt_depe_codi"] ?? 0));

$nombre = ""; $sigla = ""; $ciudad = 0; $padre = 0; $estado = 1; $estructura = 0;

// En un alta iniciada desde "Sub áreas de X", el área padre viene preseleccionada.
if ($txt_depe_codi == 0) {
    $padre_pre = 0 + trim(limpiar_numero($_GET["padre"] ?? 0));
    if ($padre_pre > 0) {
        $rsPre = $db->conn->Execute("select depe_codi from dependencia where depe_codi = $padre_pre and inst_codi = $inst_codi and depe_estado = 1");
        if ($rsPre and !$rsPre->EOF) $padre = $padre_pre;
    }
}

if ($txt_depe_codi > 0) {
    // El filtro por institución impide editar el área de otra entidad por URL.
    $sql = "select depe_nomb, dep_sigla, depe_pie1, depe_codi_padre, depe_estado, estructura_organica
              from dependencia where depe_codi = $txt_depe_codi and inst_codi = $inst_codi";
    $rs = $db->conn->Execute($sql);
    if (!$rs or $rs->EOF) {
        die( html_error("El &aacute;rea solicitada no existe en esta instituci&oacute;n.") );
    }
    $nombre = $rs->fields["DEPE_NOMB"];
    $sigla  = $rs->fields["DEP_SIGLA"];
    $ciudad = 0 + $rs->fields["DEPE_PIE1"];
    $padre  = 0 + $rs->fields["DEPE_CODI_PADRE"];
    $estado = 0 + $rs->fields["DEPE_ESTADO"];
    $estructura = ($rs->fields["ESTRUCTURA_ORGANICA"] === 't' || $rs->fields["ESTRUCTURA_ORGANICA"] === true || $rs->fields["ESTRUCTURA_ORGANICA"] == 1) ? 1 : 0;
    // Un área cuyo padre es ella misma es una raíz: en el combo equivale a "sin padre".
    if ($padre == $txt_depe_codi) $padre = 0;
}

$titulo = ($txt_depe_codi > 0) ? "Modificar &Aacute;rea" : "Registrar &Aacute;rea";

// La estructura orgánica sólo la controla el área de PRIMER NIVEL (su padre es una
// raíz de la institución). Una sub área la hereda: aquí el checkbox va deshabilitado.
$padre_es_raiz = true;
if ($padre > 0) {
    $rsR = $db->conn->Execute("select coalesce(depe_codi_padre, depe_codi) = depe_codi as es_raiz
                                 from dependencia where depe_codi = $padre");
    $padre_es_raiz = $rsR && !$rsR->EOF
                     && ($rsR->fields['ES_RAIZ'] === 't' || $rsR->fields['ES_RAIZ'] === true || $rsR->fields['ES_RAIZ'] == 1);
}
$es_subarea = ($padre > 0 && !$padre_es_raiz);

// Raíces de la institución (para que el JS sepa cuándo el padre elegido es raíz).
$raices = array();
$rsRa = $db->conn->Execute("select depe_codi from dependencia
                             where inst_codi = $inst_codi and coalesce(depe_codi_padre, depe_codi) = depe_codi");
while ($rsRa && !$rsRa->EOF) { $raices[] = (int)$rsRa->fields['DEPE_CODI']; $rsRa->MoveNext(); }
$raices_js = implode(',', $raices);

// "Regresar" vuelve al listado de origen: el del área padre (donde vivía o donde
// se está creando el área). Si el padre es la raíz de la institución o no hay
// padre, vuelve al listado general. $padre ya trae el padre en ambos casos
// (el del área en edición, o el preseleccionado en el alta).
$volver_listado = "areas.php";
if ($padre > 0) {
    $rsVR = $db->conn->Execute("select coalesce(depe_codi_padre, depe_codi) = depe_codi as es_raiz
                                  from dependencia where depe_codi = $padre");
    $esRaiz = $rsVR && !$rsVR->EOF && ($rsVR->fields["ES_RAIZ"] === 't' || $rsVR->fields["ES_RAIZ"] === true || $rsVR->fields["ES_RAIZ"] == 1);
    if (!$esRaiz) $volver_listado = "areas.php?padre=".$padre;
}

// Combo de área padre: todas las áreas activas de la institución, excepto la
// propia y sus descendientes (evita ciclos en la jerarquía).
$excluir = "";
if ($txt_depe_codi > 0) {
    $desc = substr(buscar_areas_dependientes_rec($txt_depe_codi), 1);   // "cod,cod,..." con coma inicial
    if (trim($desc) != "") $excluir = " and depe_codi not in ($desc)";
}
$sqlPadre = "select depe_codi, depe_nomb from dependencia
              where depe_estado = 1 and inst_codi = $inst_codi $excluir order by depe_nomb";
$rsPadre = $db->conn->Execute($sqlPadre);

$sqlCiu = "select id, nombre from ciudad order by nombre";
$rsCiu = $db->conn->Execute($sqlCiu);

$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

echo "<!DOCTYPE html>".html_head();
?>
<script type="text/javascript">
    // Raíces de la institución: si el padre elegido es una de ellas (o "Ninguna"),
    // el área es de primer nivel y puede fijar su estructura orgánica. Si el padre
    // es otra área, es sub área y hereda: el checkbox se deshabilita.
    var raices_areas = [<?php echo $raices_js; ?>];
    function sincronizar_estructura() {
        var padre = 0 + document.getElementById('slc_padre').value;
        var chk = document.getElementById('txt_estructura');
        var nota = document.getElementById('nota_estructura');
        if (!chk) return;
        var es_sub = (padre > 0 && raices_areas.indexOf(padre) < 0);
        chk.disabled = es_sub;
        if (nota) nota.innerHTML = es_sub
            ? 'Se hereda del área principal. Sólo el área de primer nivel puede cambiarla, y el cambio baja a todas sus sub áreas.'
            : 'Marque si el área forma parte de la estructura orgánica. El valor se aplica a esta área y a todas sus sub áreas.';
    }

    function grabar_area() {
        var nombre = document.getElementById('txt_nombre').value.replace(/^\s+|\s+$/g, '');
        var sigla  = document.getElementById('txt_sigla').value.replace(/^\s+|\s+$/g, '');
        if (nombre == '') { alert('Ingrese el nombre del área.'); document.getElementById('txt_nombre').focus(); return false; }
        if (sigla == '')  { alert('Ingrese la sigla del área.');  document.getElementById('txt_sigla').focus();  return false; }
        if (document.getElementById('txt_ciudad').value == '0') { alert('Seleccione la ciudad del área.'); return false; }
        document.getElementById('txt_nombre').value = nombre;
        document.getElementById('txt_sigla').value = sigla;
        document.formulario.submit();
    }
</script>
<body>
<div class="sumillas-wrap">
<form name="formulario" action="areas_grabar.php" method="post">
    <input type="hidden" name="txt_depe_codi" id="txt_depe_codi" value="<?php echo $txt_depe_codi; ?>">

    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr><td class="titulos4"><?php echo $titulo; ?></td></tr>
        <tr><td class="listado2">El nombre y la sigla identifican al &aacute;rea en los documentos y en los combos del sistema.</td></tr>
    </table>

    <table class="borde_tab sumillas-form" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td class="titulos2">* Nombre</td>
            <td class="listado2">
                <input type="text" name="txt_nombre" id="txt_nombre" size="55" maxlength="150"
                       class="tex_area" value="<?php echo $h($nombre); ?>">
                <span class="sumillas-nota">Nombre corto del &aacute;rea (sin el nombre del &aacute;rea padre). M&aacute;ximo 150 caracteres.</span>
            </td>
        </tr>
        <tr>
            <td class="titulos2">* Sigla</td>
            <td class="listado2">
                <input type="text" name="txt_sigla" id="txt_sigla" size="20" maxlength="20"
                       class="tex_area" value="<?php echo $h($sigla); ?>"
                       onkeyup="this.value=this.value.toUpperCase();">
            </td>
        </tr>
        <tr>
            <td class="titulos2">* Ciudad</td>
            <td class="listado2">
                <select name="txt_ciudad" id="txt_ciudad" class="select">
                    <option value="0">&lt;&lt; seleccione &gt;&gt;</option>
<?php
    while ($rsCiu && !$rsCiu->EOF) {
        $id  = $rsCiu->fields["ID"];
        $nom = $rsCiu->fields["NOMBRE"];
        $sel = ($id == $ciudad) ? "selected" : "";
        echo "                    <option value=\"".$h($id)."\" $sel>".$h($nom)."</option>\n";
        $rsCiu->MoveNext();
    }
?>
                </select>
            </td>
        </tr>
        <tr>
            <td class="titulos2">&Aacute;rea Padre</td>
            <td class="listado2">
                <select name="slc_padre" id="slc_padre" class="select" onchange="sincronizar_estructura();">
                    <option value="0">&lt;&lt; Ninguna (&aacute;rea de primer nivel) &gt;&gt;</option>
<?php
    while ($rsPadre && !$rsPadre->EOF) {
        $id  = $rsPadre->fields["DEPE_CODI"];
        $nom = $rsPadre->fields["DEPE_NOMB"];
        $sel = ($id == $padre) ? "selected" : "";
        echo "                    <option value=\"".$h($id)."\" $sel>".$h($nom)."</option>\n";
        $rsPadre->MoveNext();
    }
?>
                </select>
                <span class="sumillas-nota">&Aacute;rea de la que depende. El nombre completo se arma con la ruta (Padre - Hija).</span>
            </td>
        </tr>
        <tr>
            <td class="titulos2">Estado</td>
            <td class="listado2">
                <select name="txt_estado" id="txt_estado" class="select">
                    <option value="1" <?php if ($estado == 1) echo "selected"; ?>>Activo</option>
                    <option value="0" <?php if ($estado != 1) echo "selected"; ?>>Inactivo</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="titulos2">Estructura org&aacute;nica</td>
            <td class="listado2">
                <input type="checkbox" name="txt_estructura" id="txt_estructura" value="1" <?php if ($estructura == 1) echo "checked"; ?> <?php if ($es_subarea) echo "disabled"; ?>>
                <span class="sumillas-nota" id="nota_estructura">
<?php if ($es_subarea) { ?>
                    Se hereda del &aacute;rea principal. S&oacute;lo el &aacute;rea de primer nivel puede cambiarla, y el cambio baja a todas sus sub &aacute;reas.
<?php } else { ?>
                    Marque si el &aacute;rea forma parte de la estructura org&aacute;nica. El valor se aplica a esta &aacute;rea y a todas sus sub &aacute;reas.
<?php } ?>
                </span>
            </td>
        </tr>
    </table>

    <div class="sumillas-botonera">
        <input type="button" name="btn_guardar" value="Guardar" class="botones" onClick="grabar_area();">
<?php if ($txt_depe_codi > 0) { ?>
        <input type="button" name="btn_puestos" value="Puestos" class="botones" onClick="window.location='areas_puestos.php?depe_codi=<?php echo $txt_depe_codi; ?>'">
<?php } ?>
        <input type="button" name="btn_regresar" value="Regresar" class="botones" onClick="window.location='<?php echo $volver_listado; ?>'">
    </div>
</form>
</div>
</body>
</html>
