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
 * @package    usuarios
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__DIR__, 2).'/config.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2)."/interconexion/validar_datos_ciudadano.php");

$cedula = trim(limpiar_sql($cedula));
$cedula = str_replace(array(" ","-","."), "", $cedula);

if(isset($tipo_identificacion))
    if ($tipo_identificacion==1) die("");

if (strlen($cedula)!=10) die("<center>El n&uacute;mero de c&eacute;dula ingresado no es v&aacute;lido.</center><br>");

// Consulto al registro civil
$datos_rc = ws_validar_datos_ciudadano($cedula);

?>

<table width="100%" class="borde_tab" border="0" cellpadding="0" cellspacing="5">
    <tr>
        <th colspan="4">
            <center>Datos tra&iacute;dos desde el Registro Civil</center>
        </th>
    </tr>
<?php  if ($datos_rc["error"]) { ?>
    <tr>
        <td class="listado1" colspan="4">
            <center><?= $datos_rc["descripcion"]?></center>
        </td>
    </tr>

<?php  } else { ?>
    <tr>
        <td class="listado2" width="20%">Nombres:</td>
        <td class="listado1" width="30%"><span id="lbl_datos_rc_nombre"><?= $datos_rc["nombre"]?></span><span id="lbl_datos_rc_apellido" style="display: none"><?= $datos_rc["nombre"]?></span></td>
        <td class="listado2" width="20%">G&eacute;nero:</td>
        <td class="listado1" width="30%"><span id="lbl_datos_rc_genero"><?= $datos_rc["genero"]?></span></td>
    </tr>
    <tr>
        <td class="listado2">Estado Civil:</td>
        <td class="listado1"><span id="lbl_datos_rc_estado_civil"><?= $datos_rc["estado_civil"]?></span></td>
        <td class="listado2">Direcci&oacute;n:</td>
        <td class="listado1"><span id="lbl_datos_rc_direccion"><?= $datos_rc["domicilio"]?></span></td>
    </tr>
<?php  } // Fin IF Error?>
</table>
<br>
