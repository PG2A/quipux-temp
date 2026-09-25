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

?>
    
    <table width="100%" class="borde_tab" border="0">
        <tr>
            <td class="listado2">
                <?php echo graficarTabsMenuUsr($usr_codigo,$tiene_subrogacion,$usr_perfil,$usr_depe,5);?>
            </td>
        </tr>
    </table>
    <table width="100%" class="borde_tab" border="0">
        
           <?php  
    if ($tiene_subrogacion==1 and $usr_perfil!=1 ){
        $sizebnt = "33%";
        $col=3;
    } 
    else{
        $sizebnt = "50%";
        $col=2;
    }
        
    echo "<tr><td colspan='$col'>";
    echo $ciud->verHistorico($usr_codigo,1,2);
    echo "</td></tr>";
    ?>    
    <?php
     echo "<tr><td colspan='$col'>";
     echo $ciud->verHistoricoPermisos($usr_codigo, 'div_historico_permisos');
     echo "</td></tr>";
    ?><br>
    </table>
    <?php 
    if ($usr_codigo!=''){ ?>
    <script>       
    mostrar_div_historico('div_historico',<?=$usr_codigo?>,1,'2');
    
    </script>
    <?php } ?>

