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
 * Exporta a Excel (.xls) todas las áreas, sub áreas y puestos de la institución,
 * una fila por puesto (las áreas sin puestos salen con una fila vacía de puesto).
 * Columnas: ruta del área, sigla, nivel, estado, estructura orgánica, y del puesto
 * su nombre, cabecera, perfil, nivel y titulares activos.
 *
 * @package    dependencias
 * @author     2026 Marco Terán <marcosdanny14@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Buffer desde el inicio: cualquier salida de los includes se descarta antes de
// enviar las cabeceras de descarga (evita "headers already sent").
ob_start();
session_start();
if (($_SESSION["usua_admin_sistema"] ?? 0) != 1 and ($_SESSION["usua_codi"] ?? -1) != 0) {
    die("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');

$inst_codi   = 0 + $_SESSION["inst_codi"];
$inst_nombre = $_SESSION["inst_nombre"] ?? "";

// Filtro opcional por estructura orgánica (mismo que la vista tabla): '', '1', '0'
$txt_estruct = trim($_GET["txt_estruct"] ?? "");
$filtro_est = "";
if ($txt_estruct === '1')      $filtro_est = " and a.estructura_organica = true";
elseif ($txt_estruct === '0')  $filtro_est = " and a.estructura_organica = false";

$sql = "select a.depe_codi,
               depe_ruta(a.depe_codi)                                           as ruta,
               a.dep_sigla,
               array_length(string_to_array(depe_ruta(a.depe_codi), ' - '), 1)  as nivel_area,
               a.depe_estado,
               a.estructura_organica,
               c.cargo_nombre,
               c.cargo_cabecera,
               c.cargo_tipo,
               c.cargo_nivel,
               c.cargo_estado,
               (select count(*) from usuarios u where u.cargo_id = c.cargo_id and u.usua_esta = 1) as titulares
          from dependencia a
          left join cargo c on c.depe_codi = a.depe_codi
         where a.inst_codi = $inst_codi $filtro_est
         order by depe_ruta(a.depe_codi), c.cargo_tipo desc, lower(c.cargo_nombre)";

$rs = $db->conn->Execute($sql);

$archivo = "areas_puestos_".date("Ymd_His").".xls";
if (ob_get_length() !== false) ob_end_clean();   // descarta lo que hubieran emitido los includes
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$archivo\"");
header("Pragma: no-cache");
header("Expires: 0");

// BOM UTF-8 para que Excel respete los acentos
echo "\xEF\xBB\xBF";

$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
$boolSN = function ($v) { return ($v === 't' || $v === true || $v == 1) ? 'Sí' : 'No'; };

$th = "bgcolor='#6a819d' style='color:#FFFFFF;font-weight:bold' align='center'";
?>
<html>
<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"></head>
<body>
<table border="1" cellspacing="0" cellpadding="3">
    <tr><td colspan="11" style="font-weight:bold">Áreas y Puestos — <?php echo $h($inst_nombre); ?> — <?php echo date("Y-m-d H:i"); ?></td></tr>
    <tr>
        <td <?php echo $th; ?>>Área (ruta)</td>
        <td <?php echo $th; ?>>Sigla</td>
        <td <?php echo $th; ?>>Nivel área</td>
        <td <?php echo $th; ?>>Estado área</td>
        <td <?php echo $th; ?>>Estructura orgánica</td>
        <td <?php echo $th; ?>>Puesto</td>
        <td <?php echo $th; ?>>Puesto cabecera</td>
        <td <?php echo $th; ?>>Perfil</td>
        <td <?php echo $th; ?>>Nivel puesto</td>
        <td <?php echo $th; ?>>Estado puesto</td>
        <td <?php echo $th; ?>>Titulares</td>
    </tr>
<?php
while ($rs && !$rs->EOF) {
    $f = $rs->fields;
    $tiene_puesto = (trim((string)$f['CARGO_NOMBRE']) !== '');
    $perfil = ((int)$f['CARGO_TIPO'] == 1) ? 'Jefe' : ($tiene_puesto ? 'Normal' : '');
    $nivelP = ($f['CARGO_NIVEL'] === null || $f['CARGO_NIVEL'] === '') ? '' : (int)$f['CARGO_NIVEL'];
    $estP   = !$tiene_puesto ? '' : (((int)$f['CARGO_ESTADO'] == 1) ? 'Activo' : 'Inactivo');
    echo "<tr>";
    echo "<td style='mso-number-format:\\@'>".$h($f['RUTA'])."</td>";
    echo "<td style='mso-number-format:\\@'>".$h($f['DEP_SIGLA'])."</td>";
    echo "<td align='center'>".(int)$f['NIVEL_AREA']."</td>";
    echo "<td>".(((int)$f['DEPE_ESTADO'] == 1) ? 'Activo' : 'Inactivo')."</td>";
    echo "<td align='center'>".$boolSN($f['ESTRUCTURA_ORGANICA'])."</td>";
    echo "<td style='mso-number-format:\\@'>".$h($f['CARGO_NOMBRE'])."</td>";
    echo "<td style='mso-number-format:\\@'>".$h($f['CARGO_CABECERA'])."</td>";
    echo "<td align='center'>".$h($perfil)."</td>";
    echo "<td align='center'>".$h($nivelP)."</td>";
    echo "<td>".$h($estP)."</td>";
    echo "<td align='center'>".($tiene_puesto ? (int)$f['TITULARES'] : '')."</td>";
    echo "</tr>\n";
    $rs->MoveNext();
}
?>
</table>
</body>
</html>
