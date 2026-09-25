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
 * Listado de los periodos de trabajo de la institución. Cada periodo es un rango
 * de fechas con un modo (jerárquico / lineal) que puede cambiarse mientras el
 * periodo no haya terminado; cada cambio queda en periodo_historial y cada
 * documento creado guarda el periodo y el modo vigentes (db/periodos/).
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

$inst_codi = 0 + $_SESSION["inst_codi"];
$msg = trim($_GET["msg"] ?? "");

$rsList = $db->conn->Execute(
    "select p.periodo_codi, p.periodo_nombre, p.jerarquico, p.vigente, p.observacion,
            to_char(p.fecha_inicio, 'YYYY-MM-DD') as desde,
            to_char(p.fecha_fin, 'YYYY-MM-DD')    as hasta,
            case when current_date < p.fecha_inicio then 'FUTURO'
                 when current_date > p.fecha_fin    then 'CERRADO'
                 else 'VIGENTE' end as situacion,
            (select count(*) from periodo_historial h where h.periodo_codi = p.periodo_codi) as cambios,
            (select count(*) from radicado r where r.periodo_codi = p.periodo_codi) as documentos
       from periodo p
      where p.inst_codi = $inst_codi
      order by p.fecha_inicio desc");

$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
$es_true = function ($v) { return ($v === 't' || $v === true || $v === 1 || $v === '1'); };

echo "<!DOCTYPE html>".html_head();
?>
<script type="text/javascript">
    function nuevo_periodo()       { window.location = 'periodo_form.php?periodo_codi=0'; }
    function editar_periodo(id)    { window.location = 'periodo_form.php?periodo_codi=' + id; }
    function cambiar_modo(id, jerarquico) {
        var modo = jerarquico ? 'JERÁRQUICO' : 'LINEAL';
        var obs = prompt('El periodo pasará a trabajar en modo ' + modo + '.\n' +
                         'Los documentos ya creados conservan el modo con el que se hicieron.\n\n' +
                         'Motivo del cambio (queda en el historial):', '');
        if (obs === null) return;
        window.location = 'periodo_grabar.php?op=modo&periodo_codi=' + id + '&jerarquico=' + (jerarquico ? 1 : 0) +
                          '&observacion=' + encodeURIComponent(obs);
    }
    function ver_historial(id) {
        window.open('periodo_historial.php?periodo_codi=' + id, 'historial_periodo',
                    'width=900, height=520, scrollbars=yes, resizable=yes, toolbar=no, menubar=no, location=no, status=no');
    }
</script>
<body>
<div class="sumillas-wrap">

    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr><td class="titulos4">Periodos de Trabajo</td></tr>
        <tr><td class="listado2">
            Cada periodo define un rango de fechas y si se trabaja en modo <b>Jer&aacute;rquico</b> o <b>Lineal</b>.
            Todo documento creado dentro del rango guarda el periodo y el modo vigentes en ese momento.
        </td></tr>
<?php if ($msg != "") { ?>
        <tr><td class="listado2_ver"><b><?php echo $h($msg); ?></b></td></tr>
<?php } ?>
    </table>

    <div class="sumillas-botonera">
        <input type="button" name="btn_nuevo" value="Registrar periodo" class="botones" onClick="nuevo_periodo();">
        <input type="button" name="btn_regresar" value="Regresar" class="botones"
               onClick="window.location='../formAdministracion.php'">
    </div>

    <center>
    <table width="100%" class="borde_tab" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td width="20%" align="center" class="titulos2">Periodo</td>
            <td width="10%" align="center" class="titulos2">Desde</td>
            <td width="10%" align="center" class="titulos2">Hasta</td>
            <td width="10%" align="center" class="titulos2">Fechas</td>
            <td width="6%"  align="center" class="titulos2">Vigente</td>
            <td width="10%" align="center" class="titulos2">Modo actual</td>
            <td width="8%"  align="center" class="titulos2">Documentos</td>
            <td width="8%"  align="center" class="titulos2">Historial</td>
            <td width="18%" align="center" class="titulos2">Acci&oacute;n</td>
        </tr>
<?php
if (!$rsList or $rsList->EOF) {
    echo '<tr><td colspan="9" class="listado2_ver" align="center">No hay periodos registrados. Los documentos se crean "sin periodo".</td></tr>';
}
while ($rsList && !$rsList->EOF) {
    $p = $rsList->fields;
    $id = (int)$p['PERIODO_CODI'];
    $jer = $es_true($p['JERARQUICO']);
    $vig = $es_true($p['VIGENTE']);
    $sit = $p['SITUACION'];
    $clase = ($sit == 'VIGENTE' && $vig) ? 'listado2_ver' : 'listado2';
    $sit_txt = array('VIGENTE' => '<b>En curso</b>', 'FUTURO' => 'Programado', 'CERRADO' => '<i>Finalizado</i>');
    echo '<tr>';
    echo '<td class="'.$clase.'">'.$h($p['PERIODO_NOMBRE']).'</td>';
    echo '<td class="'.$clase.'" align="center">'.$h($p['DESDE']).'</td>';
    echo '<td class="'.$clase.'" align="center">'.$h($p['HASTA']).'</td>';
    echo '<td class="'.$clase.'" align="center">'.$sit_txt[$sit].'</td>';
    echo '<td class="'.$clase.'" align="center">'.($vig ? 'S&iacute;' : '<i>No</i>').'</td>';
    echo '<td class="'.$clase.'" align="center">'.($jer ? 'Jer&aacute;rquico' : 'Lineal').'</td>';
    echo '<td class="'.$clase.'" align="center">'.number_format((int)$p['DOCUMENTOS'], 0, ',', '.').'</td>';
    echo '<td class="'.$clase.'" align="center"><a href="#" class="vinculos" onclick="ver_historial('.$id.'); return false;">'
        .(int)$p['CAMBIOS'].' cambio(s)</a></td>';
    echo '<td class="listado2" align="center">';
    echo '<input type="button" class="botones" value="Editar" onclick="editar_periodo('.$id.');">&nbsp;';
    if ($sit != 'CERRADO' && $vig) {
        if ($jer)
            echo '<input type="button" class="botones" value="Pasar a Lineal" onclick="cambiar_modo('.$id.', false);">';
        else
            echo '<input type="button" class="botones" value="Pasar a Jer&aacute;rquico" onclick="cambiar_modo('.$id.', true);">';
    }
    echo '</td>';
    echo '</tr>';
    $rsList->MoveNext();
}
?>
    </table>
    </center>

</div>
</body>
</html>
