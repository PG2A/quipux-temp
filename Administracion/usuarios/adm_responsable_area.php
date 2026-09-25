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

session_start();
if ($_SESSION["usua_admin_sistema"] != 1) {
    die( html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.") );
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');

if (isset($_GET["dependenciaRes"])){
        $dependenciaC = trim($_GET["dependenciaRes"]);
        // Ensure it's numeric before adding 0, otherwise standard casting
        if (is_numeric($dependenciaC)) {
            $dependenciaC = 0 + $dependenciaC;
        } else {
             // Handle case where it might be a name due to GetMenu2 config
             // If non-numeric, likely invalid for this query, so set to 0 or handle
             $dependenciaC = 0; 
        }
        $existeResponsable = obtenerResponsableArea($dependenciaC,$db,0);
     }

     
     // Initialize variables to avoid undefined variable warnings
     $usr_responsable_area = $usr_responsable_area ?? '';
     $read = $read ?? '';

if ($existeResponsable==0){
    ?>
<input type="checkbox" name="usr_area_responsable" id="usr_area_responsable" value="0" onclick="Obtener_val(this)" title="Iniciales del usuario que se visualizará el pie de página (mayùculas) de un documento" <?php echo $usr_responsable_area ."  " .$read; ?>/>Responsable de Área
<?php }else{
   echo "<br> Ya existe responsable de documentación para esta Area.<br>";
   ?><table><tr><td>
    Desactivar como Responsable de Area a: <?=obtenerResponsableArea($dependenciaC,$db,1);?>
    <a class="menu_princ" onclick="eliminarResponsable(<?=$existeResponsable?>,<?=$dependenciaC?>);" href="javascript:void(0);">&nbsp;<img name="quitar" title="Quitar Responsable" alt="Quitar Responsable" src="/iconos/visto.png" width="20" align="left" border="0"></a>
           </td></tr>

   <?php
}?>