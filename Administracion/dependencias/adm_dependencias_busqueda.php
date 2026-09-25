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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if($_SESSION["usua_admin_sistema"]!=1){
      die("");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php"); //para traer funciones p_get y p_post

p_register_globals(array());
$txt_nombre_buscar = isset($_GET['txt_nombre_buscar']) ? $_GET['txt_nombre_buscar'] : '';
$txt_busqueda_texto = limpiar_sql(trim((string)$txt_nombre_buscar));

$des_activar_val = isset($_GET['des_activar']) ? $_GET['des_activar'] : '';
$des_activar = limpiar_sql(trim((string)$des_activar_val));

?>  

      <?php
      
            $sql = "select dm.nombre as \"SCR_Nombre Área \",
                'datosArea(\"'||hijo||'\")' as \"HID_POPUP\",
                dm.sigla as \"Sigla\", dm.padre as \"Área Padre\"";
            
            $sql.= ", case when dm.estado = 0 then
                 'Activar' else 'Desactivar'
                 end as \"SCR_Acción \"
           ,case when dm.estado = 0 then 
                 'desactivarArea(\"'||hijo||'\",1)' 
                 else 
                 'desactivarArea(\"'||hijo||'\",0)' 
                 end as \"HID_POPUP \"";
            $sql.=" from 
                (select d.depe_codi as hijo, d.depe_nomb as nombre, d.dep_sigla as sigla,dp.depe_nomb as padre";
            
            $sql.= ", d.depe_estado as estado";
            $sql.= " from dependencia d, dependencia dp";
            $sql.= " where";
            $sql.= " (translate(upper(d.depe_nomb),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')";
            $sql.= " like translate(upper('%".$txt_busqueda_texto."%'),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN') "; 
            $sql.= " or translate(upper(d.dep_sigla),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')";
            $sql.= " like translate(upper('%".$txt_busqueda_texto."%'),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN'))";
            $sql.= " and d.inst_codi=".$_SESSION["inst_codi"];
            $sql.= " and dp.depe_codi=d.depe_codi_padre) as dm order by 1";
           
           ?>
   