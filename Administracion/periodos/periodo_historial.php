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
 * Historial de un periodo (ventana aparte): alta, cambios de modo y de fechas,
 * con quién y cuándo. Sólo lectura.
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

$rs = $db->conn->Execute("select periodo_nombre from periodo where periodo_codi = $periodo_codi and inst_codi = $inst_codi");
if (!$rs or $rs->EOF) {
    die( html_error("El periodo solicitado no existe en esta instituci&oacute;n.") );
}
$nombre = $rs->fields["PERIODO_NOMBRE"];

$rsList = $db->conn->Execute(
    "select h.accion, h.jerarquico, h.vigente, h.periodo_nombre, h.observacion,
            to_char(h.fecha, 'YYYY-MM-DD HH24:MI') as fecha,
            to_char(h.fecha_inicio, 'YYYY-MM-DD') as desde,
            to_char(h.fecha_fin, 'YYYY-MM-DD')    as hasta,
            coalesce(u.usua_nomb || ' ' || u.usua_apellido, '') as usuario
       from periodo_historial h
       left join usuarios u on u.usua_codi = h.usua_codi
      where h.periodo_codi = $periodo_codi
      order by h.fecha desc, h.periodo_hist_codi desc");

$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
$acciones = array('CREACION' => 'Creaci&oacute;n', 'CAMBIO_MODO' => '<b>Cambio de modo</b>',
                  'ACTIVACION' => 'Marcado vigente', 'DESACTIVACION' => 'Marcado no vigente',
                  'CAMBIO_FECHAS' => 'Cambio de fechas', 'EDICION' => 'Edici&oacute;n');

echo "<!DOCTYPE html>".html_head();
?>
<body>
<div class="sumillas-wrap">
    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr><td class="titulos4">Historial del Periodo</td></tr>
        <tr><td class="listado2"><b><?php echo $h($nombre); ?></b></td></tr>
    </table>

    <table width="100%" class="borde_tab" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td width="14%" align="center" class="titulos2">Fecha</td>
            <td width="14%" align="center" class="titulos2">Acci&oacute;n</td>
            <td width="11%" align="center" class="titulos2">Modo</td>
            <td width="7%"  align="center" class="titulos2">Vigente</td>
            <td width="18%" align="center" class="titulos2">Rango</td>
            <td width="18%" align="center" class="titulos2">Usuario</td>
            <td width="18%" align="center" class="titulos2">Observaci&oacute;n</td>
        </tr>
<?php
while ($rsList && !$rsList->EOF) {
    $f = $rsList->fields;
    $jer = ($f['JERARQUICO'] === 't' || $f['JERARQUICO'] === true);
    echo '<tr>';
    echo '<td class="listado2" align="center">'.$h($f['FECHA']).'</td>';
    echo '<td class="listado2" align="center">'.($acciones[$f['ACCION']] ?? $h($f['ACCION'])).'</td>';
    echo '<td class="listado2" align="center">'.($jer ? 'Jer&aacute;rquico' : 'Lineal').'</td>';
    echo '<td class="listado2" align="center">'.(($f['VIGENTE'] === 'f' || $f['VIGENTE'] === false) ? 'No' : 'S&iacute;').'</td>';
    echo '<td class="listado2" align="center">'.$h($f['DESDE']).' a '.$h($f['HASTA']).'</td>';
    echo '<td class="listado2">'.$h($f['USUARIO']).'</td>';
    echo '<td class="listado2">'.$h($f['OBSERVACION']).'</td>';
    echo '</tr>';
    $rsList->MoveNext();
}
?>
    </table>

    <div class="sumillas-botonera">
        <input type="button" value="Cerrar" class="botones" onClick="window.close();">
    </div>
</div>
</body>
</html>
