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
 * @package    tbasicas
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php"); //para traer funciones p_get y p_post
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');

p_register_globals(array());

$depeCodi = isset($_GET['depeCodi']) ? $_GET['depeCodi'] : 0;
// Validate that depeCodi is a number/valid ID
if (empty($depeCodi)) {
    echo "<center><br><font class='titulos4'>Seleccione una dependencia para ver su información</font></center>";
    die();
}

$area = ObtenerDatosDependencia($depeCodi,$db);

// Initialize vars
$nombrePadre = "";
$nombreArchivo = "";
$nombrePlantilla = "";

if(!empty($area)) {
    if(isset($area['padre']) && trim((string)$area['padre'])!='')
    {
        $sqlNomPadre = 'select depe_nomb from dependencia where depe_estado=1 and depe_codi = '.(int)$area['padre'];
        $rsPadre = $db->query($sqlNomPadre);
        if ($rsPadre && !$rsPadre->EOF) $nombrePadre = $rsPadre->fields['DEPE_NOMB'];
    }

    if(isset($area['archivo']) && trim((string)$area['archivo'])!='')
    {
        $sqlNomArch = 'select depe_nomb from dependencia where depe_estado=1 and depe_codi = '.(int)$area['archivo'];
        $rsArch = $db->query($sqlNomArch);
        if ($rsArch && !$rsArch->EOF) $nombreArchivo = $rsArch->fields['DEPE_NOMB'];
    }

    if(isset($area['plantilla']) && trim((string)$area['plantilla'])!='')
    {
        $sqlNomPlan = 'select depe_nomb from dependencia where depe_estado=1 and depe_codi = '.(int)$area['plantilla'];
        $rsPlan = $db->query($sqlNomPlan);
        if ($rsPlan && !$rsPlan->EOF) $nombrePlantilla = $rsPlan->fields['DEPE_NOMB'];
    }
} else {
   $area = array('nombre'=>'','sigla'=>'','ciudad'=>'');
}

//Obtener datos del Jefe de Área
$datosJefe = ObtenerJefeArea($_SESSION["inst_codi"] ?? 0, $depeCodi, '1', $db);
$tituloJefe = 'Datos del Jefe de &Aacute;rea';
$ciudadNombre = "";

if(isset($datosJefe['ciudad']))
{
    $codigoCiu = $datosJefe['ciudad'];
    $ciudad = ObtenerCiudadUsua(' ciudad ', ' id = '. (int)$codigoCiu, $db);
    $ciudadNombre = isset($ciudad['nombre']) ? $ciudad['nombre'] : '';
}

$verLis = isset($_GET['verLis']) ? $_GET['verLis'] : '';

if($verLis == '2'){
    $pagina = 'value="Cerrar" onClick="window.close();"';
}else
    $pagina = 'value="Regresar" onClick="location=\'../dependencias/mnu_dependencias.php\'"';
//var_dump($area);
$datos = '
        <table class="borde_tab" width="100%">
            <tr><td align="center" class="titulos4" colspan="2"><font size="2">Información del &Aacute;rea Seleccionada</font></td></tr>
            <tr>
                <td width="40%" align="left" class="titulos2">&Aacute;rea Padre:</td>
                <td class="listado2_ver">'. $nombrePadre .'</td>
            </tr>
            <tr>
                <td align="left" class="titulos2">Nombre:</td>
                <td class="listado2_ver">'. ($area['nombre'] ?? '') .'</td>
            </tr>
            <tr>
                <td align="left" class="titulos2">Sigla:</td>
                <td class="listado2_ver">'. ($area['sigla'] ?? '') .'</td>
            </tr>
            <tr>
                <td align="left" class="titulos2">Ciudad:</td>
                <td class="listado2_ver">'. ($area['ciudad'] ?? '') .'</td>
            </tr>
            <tr>
                <td align="left" class="titulos2">Ubicaci&oacute;n del Archivo F&iacute;sico:</td>
                <td class="listado2_ver">'. $nombreArchivo .'</td>
            </tr>
            <tr>
                <td align="left" class="titulos2">Área de la que se copiar&aacute; la plantilla del documento:</td>
                <td class="listado2_ver">'. $nombrePlantilla .'</td>
            </tr>
            <tr>
                <td align="center" class="listado2_ver" colspan="2">
                
                </td>
            </tr>
        </table>

        <br>
        <table width="100%" class="borde_tab">
        <tr>
            <td align="center" class="titulos4" colspan="4"><font size="2">'.$tituloJefe.'</font></td>
        </tr>';

        if(isset($datosJefe['usua_codi']) && trim((string)$datosJefe['usua_codi'])!='') {
        $datos .= '<tr>
            <td width="15%" align="left" class="titulos2">Puesto:</td>
            <td class="listado2_ver">'.($datosJefe['cargo'] ?? '').'</td>
            <td width="15%" align="left" class="titulos2">T&iacute;tulo:</td>
            <td class="listado2_ver">'.($datosJefe['titulo'] ?? '').'</td>
        </tr>
        <tr>
            <td align="left" class="titulos2">Nombre:</td>
            <td class="listado2_ver">'.($datosJefe['nombre'] ?? '').'</td>
            <td align="left" class="titulos2">E-mail:</td>
            <td class="listado2_ver">'.($datosJefe['email'] ?? '').'</td>
        </tr>';
        if($ciudadNombre != '') {
        $datos .= '<tr>
                    <td align="left" class="titulos2">Ciudad:</td>
                    <td class="listado2_ver" colspan="3">'.$ciudadNombre.'</td>
                   </tr>';
            }
        } else {
        $datos .= '<tr>
                    <td align="center" class="listado2_ver" colspan="4"><font size="2">El &Aacute;rea aun no tiene asignado un Jefe</font></td>
                   </tr>';
        }
        $datos .= '</table>';

echo $datos;
?>
