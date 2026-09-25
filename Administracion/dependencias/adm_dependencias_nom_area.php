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
 * @package    ciudadanos
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
if($_SESSION["usua_admin_sistema"]!=1) {
    die("");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
include_once(dirname(__DIR__, 2).'/funciones.php');

$nombre_o_sigla= trim(limpiar_sql($_GET['valor']));
$dep_codigo_js= 0+trim ($_GET['dep_codigo_js']);

//Permite grabar el area en la base de datos con todos sus atributos

        $sqlNomSigla = "select depe_nomb, dep_sigla from dependencia where inst_codi = ".$_SESSION['inst_codi'];         
        $sqlNomSigla.= " and (translate(upper(depe_nomb),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')";        
        $sqlNomSigla.= " like translate(upper('".$nombre_o_sigla."'),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')) "; 
        $sqlNomSigla.= " and depe_estado = 1";          
        if (trim($dep_codigo_js)!='')
        $sqlNomSigla.= " and depe_codi <> ".$dep_codigo_js;
        $rsNomSigla = $db->conn->query($sqlNomSigla);        
        $sirepite=0;
         if ($rsNomSigla && !$rsNomSigla->EOF)         
                    $sirepite = 1;
       ?>
         <?php          
         if ($sirepite==1){
             echo "<font color='red'>Intente otro nombre de Área</font>";             
         }
         ?>
          
          <input type="hidden" name="txt_modifica_area" id="txt_modifica_area" value="<?=$sirepite?>" >
