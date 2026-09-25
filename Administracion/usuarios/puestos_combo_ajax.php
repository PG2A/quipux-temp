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
 * Devuelve el combo de puestos (catálogo 'cargo') de un área para el formulario
 * de creación/edición de usuario (adm_usuario.php). Cada opción lleva en
 * atributos data-* el nombre, la cabecera, el perfil y el nivel, para que el
 * formulario rellene esos campos al elegir un puesto.
 *
 * @package    usuarios
 * @author     2026 Marco Terán <marcosdanny14@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
if (($_SESSION["usua_admin_sistema"] ?? 0) != 1) {
    die("");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');

$inst_codi = 0 + $_SESSION["inst_codi"];
$area      = 0 + trim(limpiar_numero($_GET["area"] ?? 0));
$cargo_id  = 0 + trim(limpiar_numero($_GET["cargo_id"] ?? 0));

$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

echo "<select id='cmb_puesto' class='select' style='width:350px' onchange='aplicarPuesto(this)'>";
echo "<option value='0'>&lt;&lt; seleccione el puesto &gt;&gt;</option>";

if ($area > 0) {
    $rs = $db->conn->Execute(
        "select cargo_id, cargo_nombre, cargo_cabecera, cargo_tipo, cargo_nivel
           from cargo
          where depe_codi = $area and inst_codi = $inst_codi and cargo_estado = 1
          order by cargo_tipo desc, lower(cargo_nombre)");
    while ($rs && !$rs->EOF) {
        $id  = (int)$rs->fields["CARGO_ID"];
        $nom = $rs->fields["CARGO_NOMBRE"];
        $cab = $rs->fields["CARGO_CABECERA"];
        $tip = (int)$rs->fields["CARGO_TIPO"];
        $niv = $rs->fields["CARGO_NIVEL"];
        $sel = ($id == $cargo_id) ? "selected" : "";
        $etq = $nom . (($tip == 1) ? "  (Jefe)" : "");
        echo "<option value='$id' data-nombre='".$h($nom)."' data-cabecera='".$h($cab)."'"
           . " data-tipo='$tip' data-nivel='".$h($niv)."' $sel>".$h($etq)."</option>";
        $rs->MoveNext();
    }
}

echo "</select>";
