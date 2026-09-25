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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if($_SESSION["usua_admin_sistema"]!=1) {
      die("");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php"); //para traer funciones p_get y p_post
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');

$txt_busqueda_texto = limpiar_sql(trim($_GET['txt_nombre_buscar'] ?? ''));
$des_activar = limpiar_sql(trim($_GET['des_activar'] ?? ''));
$usr_codigo = 0+limpiar_sql(trim($_GET['usr_codigo'] ?? 0));

$areas_admin = obtenerAreasAdmin($_SESSION["usua_codi"],$_SESSION["inst_codi"], $_SESSION["usua_admin_sistema"], $db);

?>  

      <?php
      
            $sql = "select dm.nombre as \"SCR_Nombre Área \",
                'administrar(\"'||hijo||'\")' as \"HID_POPUP\",
                dm.sigla as \"Sigla\", dm.padre as \"Área Padre\"";
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
            $sql.= " and dp.depe_codi=d.depe_codi_padre";
            if ($areas_admin!='')
            $sql.= " and d.depe_codi in ($areas_admin)";
            $sql.= " ) as dm order by 1";
           //echo $sql;
           ?>
   