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
 * @package    tareas
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$drill = "";
function drill_anadir($reporte, $campo="", $valor="") {
    global $drill, $sql, $group, $nomb_as;
    $campo = trim($campo);
    $valor = trim($valor);
    $nomb_as_drill = trim(substr($nomb_as, 0,-1)) . '_drill"';
    $drill2 = $drill;
    if ($campo!="" and $valor!="")
        $drill2 .= "||'&$campo='||$valor";

    $sql["select"] .= "'generar_reporte(\"$reporte\",\"'$drill2||'\")' $nomb_as_drill, ";
    $sql["group"] .= ++$group . ", ";
}

function drill_parametro($campo, $valor) {
    global $drill;
    $campo = trim($campo);
    $valor = trim($valor);
    $drill .= "||'&$campo='||$valor";
}



?>