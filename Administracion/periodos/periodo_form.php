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
 * Alta / edición de un periodo de trabajo. Si el periodo ya tiene documentos, la
 * fecha de inicio queda fija y la de fin no puede quedar antes del último
 * documento: así ningún documento queda fuera del rango de su propio periodo.
 *
 * @package    periodos
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

$inst_codi    = 0 + $_SESSION["inst_codi"];
$periodo_codi = 0 + trim(limpiar_numero($_GET["periodo_codi"] ?? 0));

$nombre = ""; $desde = ""; $hasta = ""; $jerarquico = true; $vigente = true; $observacion = "";
$documentos = 0; $ultimo_doc = ""; $cerrado = false;
$es_true = function ($v) { return ($v === 't' || $v === true || $v === 1 || $v === '1'); };

if ($periodo_codi > 0) {
    $rs = $db->conn->Execute(
        "select periodo_nombre, jerarquico, vigente, observacion,
                to_char(fecha_inicio, 'YYYY-MM-DD') as desde,
                to_char(fecha_fin, 'YYYY-MM-DD')    as hasta,
                (current_date > fecha_fin) as cerrado,
                (select count(*) from radicado r where r.periodo_codi = p.periodo_codi) as documentos,
                (select to_char(max(r.radi_fech_radi)::date, 'YYYY-MM-DD') from radicado r
                  where r.periodo_codi = p.periodo_codi) as ultimo_doc
           from periodo p where periodo_codi = $periodo_codi and inst_codi = $inst_codi");
    if (!$rs or $rs->EOF) {
        die( html_error("El periodo solicitado no existe en esta instituci&oacute;n.") );
    }
    $nombre      = $rs->fields["PERIODO_NOMBRE"];
    $desde       = $rs->fields["DESDE"];
    $hasta       = $rs->fields["HASTA"];
    $jerarquico  = $es_true($rs->fields["JERARQUICO"]);
    $vigente     = $es_true($rs->fields["VIGENTE"]);
    $observacion = $rs->fields["OBSERVACION"];
    $cerrado     = $es_true($rs->fields["CERRADO"]);
    $documentos  = (int)$rs->fields["DOCUMENTOS"];
    $ultimo_doc  = (string)$rs->fields["ULTIMO_DOC"];
}

// Los demás periodos de la institución (vigentes o no), para avisar de un
// solapamiento antes de enviar. periodo_grabar.php y el trigger lo vuelven a validar.
$otros = array();
$rsO = $db->conn->Execute(
    "select periodo_nombre, to_char(fecha_inicio, 'YYYY-MM-DD') as desde, to_char(fecha_fin, 'YYYY-MM-DD') as hasta
       from periodo where inst_codi = $inst_codi and periodo_codi <> $periodo_codi order by fecha_inicio");
while ($rsO && !$rsO->EOF) {
    $otros[] = array('nombre' => $rsO->fields["PERIODO_NOMBRE"], 'desde' => $rsO->fields["DESDE"], 'hasta' => $rsO->fields["HASTA"]);
    $rsO->MoveNext();
}

$titulo = ($periodo_codi > 0) ? "Modificar Periodo" : "Registrar Periodo";
$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

echo "<!DOCTYPE html>".html_head();
?>
<script type="text/javascript">
    var otros_periodos = <?php echo json_encode($otros, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    // Periodo ya registrado cuyo rango se cruza con [desde, hasta], o null.
    function periodo_solapado(desde, hasta) {
        for (var i = 0; i < otros_periodos.length; i++) {
            var p = otros_periodos[i];
            if (p.desde <= hasta && p.hasta >= desde) return p;
        }
        return null;
    }

    function grabar_periodo() {
        var f = document.formulario;
        var nombre = f.periodo_nombre.value.replace(/^\s+|\s+$/g, '');
        if (nombre == '') { alert('Ingrese el nombre del periodo.'); f.periodo_nombre.focus(); return false; }
        if (f.fecha_inicio.value == '' || f.fecha_fin.value == '') { alert('Ingrese las fechas del periodo.'); return false; }
        if (f.fecha_fin.value < f.fecha_inicio.value) { alert('La fecha de fin no puede ser anterior a la de inicio.'); return false; }
        var sol = periodo_solapado(f.fecha_inicio.value, f.fecha_fin.value);
        if (sol) {
            alert('Ya existe un periodo registrado en ese rango de fechas:\n\n' +
                  sol.nombre + ' (' + sol.desde + ' a ' + sol.hasta + ')\n\nElija otras fechas.');
            f.fecha_inicio.focus();
            return false;
        }
        f.periodo_nombre.value = nombre;
        f.submit();
    }
</script>
<body>
<div class="sumillas-wrap">
<form name="formulario" action="periodo_grabar.php" method="post">
    <input type="hidden" name="periodo_codi" value="<?php echo $periodo_codi; ?>">

    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr><td class="titulos4"><?php echo $titulo; ?></td></tr>
<?php if ($documentos > 0) { ?>
        <tr><td class="listado2">
            Este periodo ya tiene <b><?php echo number_format($documentos, 0, ',', '.'); ?></b> documento(s):
            la fecha de inicio no puede cambiarse y la de fin no puede ser anterior al <b><?php echo $h($ultimo_doc); ?></b>.
        </td></tr>
<?php } ?>
    </table>

    <table class="borde_tab sumillas-form" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td class="titulos2">* Nombre</td>
            <td class="listado2">
                <input type="text" name="periodo_nombre" id="periodo_nombre" size="50" maxlength="100"
                       class="tex_area" value="<?php echo $h($nombre); ?>">
                <span class="sumillas-nota">Ej.: Periodo 2026-2027.</span>
            </td>
        </tr>
        <tr>
            <td class="titulos2">* Desde</td>
            <td class="listado2">
                <input type="date" name="fecha_inicio" id="fecha_inicio" class="tex_area" value="<?php echo $h($desde); ?>"
                       <?php if ($documentos > 0) echo "readonly"; ?>>
            </td>
        </tr>
        <tr>
            <td class="titulos2">* Hasta</td>
            <td class="listado2">
                <input type="date" name="fecha_fin" id="fecha_fin" class="tex_area" value="<?php echo $h($hasta); ?>"
                       <?php if ($ultimo_doc != "") echo 'min="'.$h($ultimo_doc).'"'; ?>>
            </td>
        </tr>
        <tr>
            <td class="titulos2">Modo de trabajo</td>
            <td class="listado2">
<?php if ($periodo_codi > 0) { ?>
                <b><?php echo $jerarquico ? "Jer&aacute;rquico" : "Lineal"; ?></b>
                <span class="sumillas-nota">
                    <?php echo ($cerrado || !$vigente) ? "El periodo est&aacute; finalizado o no vigente; su modo no se puede cambiar."
                                        : "Se cambia desde el listado con el bot&oacute;n \"Pasar a ...\", que deja el motivo en el historial."; ?>
                </span>
<?php } else { ?>
                <label><input type="radio" name="jerarquico" value="1" checked> Jer&aacute;rquico</label>
                &nbsp;&nbsp;
                <label><input type="radio" name="jerarquico" value="0"> Lineal</label>
<?php } ?>
            </td>
        </tr>
        <tr>
            <td class="titulos2">Vigente</td>
            <td class="listado2">
                <label><input type="checkbox" name="vigente" id="vigente" value="1" <?php if ($vigente) echo "checked"; ?>> Periodo vigente</label>
                <span class="sumillas-nota">Si no est&aacute; vigente, los documentos creados en su rango quedan &quot;sin periodo&quot;. Su rango sigue ocupado.</span>
            </td>
        </tr>
        <tr>
            <td class="titulos2">Observaci&oacute;n</td>
            <td class="listado2">
                <textarea name="observacion" id="observacion" cols="60" rows="3" maxlength="500"
                          class="tex_area"><?php echo $h($observacion); ?></textarea>
            </td>
        </tr>
    </table>

    <div class="sumillas-botonera">
        <input type="button" name="btn_guardar" value="Guardar" class="botones" onClick="grabar_periodo();">
        <input type="button" name="btn_regresar" value="Regresar" class="botones" onClick="window.location='periodos.php'">
    </div>
</form>
</div>
</body>
</html>
