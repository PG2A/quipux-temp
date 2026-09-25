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
 * @package    migrar_bodega
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
if($_SESSION["perm_actualizar_sistema"]!=1) {
    die("Usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
}
require_once(dirname(__DIR__, 2).'/rec_session.php');

$txt_anio = 0 + $_POST["txt_anio"];
$sql = "select count(1) as total_registros
            , count(case when radi_nume_radi::text like '%0' and esta_codi in (0,6) then 1 else null end) as total_pdf
        from radicado
        where radi_nume_temp::text like '$txt_anio%0' and radi_path is null and esta_codi in (0,2,5,6)";
$rs = $db->conn->query($sql);

echo "<font size=3>";
echo "A&ntilde;o: <b>$txt_anio</b><br>";
echo "No. archivos PDF por generar: <b>".$rs->fields["TOTAL_PDF"]."</b><br>";
echo "No. registros por actualizar: <b>".$rs->fields["TOTAL_REGISTROS"]."</b><br>";
echo "</font>"

?>
