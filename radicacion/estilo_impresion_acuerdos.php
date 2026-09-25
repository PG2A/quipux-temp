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
 * @package    radicacion
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__).'/obtenerdatos.php');

//Variables
$radi_nume = limpiar_numero($_POST['radi_nume']);

$datos_usr = ObtenerDatosUsuario($_SESSION["usua_codi"], $db);
$datos_opc_imp = ObtenerDatosOpcImpresion($radi_nume, $db);

?>

<br/><br/>
<div id="div_opciones_acuerdos" >
    <fieldset  class="borde_tab">
        <legend>OPCIONES GENERALES DEL DOCUMENTO</legend>
            <table>
                <tr>
                    <td class="listado1_ver" width="13%">Dado en:</td>
                    <td width="25%">
                       <input type="text" id="txt_ciudad_dado_en" name="txt_ciudad_dado_en" class="text_transparente" value="<?=$datos_usr["ciudad"]?>" readonly/>
                    </td>
                    <td width="30%">
                        <input type="text" id="txt_opc_ciudad_dado_en" name="txt_opc_ciudad_dado_en" class="text_transparente" size="30" value="<?=$datos_opc_imp["CIUDAD_DADO_EN"]?>" onblur="deshabilitaObj('ciu_ori'); histop('f',this,<?="'".$datos_opc_imp["CIUDAD_DADO_EN"]."'"?>);" readonly>
                    </td>
                    <td>
                        <img src="../imagenes/internas/pencil_add.png" name="Image1" align="middle" border="0" title="Modifica la ciudad del texto &quot;Dado en&quot;" onclick="habilitaObj('ciu_ori')">
                    </td>
                                   
                </tr>
            </table>
           
        </fieldset>
</div>