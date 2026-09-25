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
 * @package    core
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

include_once(__DIR__.'/rec_session.php');
require_once( __DIR__.'/funciones.php');

global $db, $CFG;

$username = limpiar_sql($_SESSION["krd"]);

$where = "";
if($CFG->config_bloquear_acceso_ciudadano) {
    $where = " AND tipo_usuario = 1 ";
}

$sql = "SELECT u.usua_codi, 
            u.inst_nombre, 
            u.depe_nomb, 
            u.usua_cargo, 
            u.usua_nombre,
            u.tipo_usuario
        FROM usuario u
        WHERE u.usua_login LIKE UPPER('$username') AND u.usua_esta = 1 $where
        ORDER BY u.tipo_usuario ASC, u.usua_nombre, u.inst_nombre";

$rs = $db->query($sql);
if ($rs and !$rs->EOF) {
    $nombre = "&nbsp;&nbsp;Usuario: ";
    $cargoCombo = "<select name='cargo_usuario' id='cargo_usuario' class='selectCargo' style='width:850px' onchange='reiniciar_session();'>";
    while (!$rs->EOF){
        if($rs->fields["USUA_CODI"] == $_SESSION["usua_codi"])
            $seleccion = 'selected';
        else
            $seleccion = "";
        $tipo_usuario = ($rs->fields["TIPO_USUARIO"]==1) ? "<i>(Serv.) </i>" : "<i>(Ciu.) </i>";

        $cargoCombo .= "<option value='".$rs->fields["USUA_CODI"]."' $seleccion>
                            ".$tipo_usuario . $rs->fields["USUA_NOMBRE"]."
                        / Institución: ".$rs->fields["INST_NOMBRE"];
         if ($rs->fields["TIPO_USUARIO"]==1)
          $cargoCombo .= " / Área: ".$rs->fields["DEPE_NOMB"];
          $cargoCombo .= " / Puesto: ".$rs->fields["USUA_CARGO"];
                                
        $cargoCombo .= "</option>";
        $rs->MoveNext();
    }
    $cargoCombo .= "</select>";
    echo "<table border='0' cellspacing='2' cellpadding='0' class='selectCargo' style='border: none;' width='100%'><tr><td>$nombre</td><td>$cargoCombo</td></tr></table>";
}
?>
