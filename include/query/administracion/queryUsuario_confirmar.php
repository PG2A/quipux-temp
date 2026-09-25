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
 * @package    query/administracion
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

include_once(dirname(__DIR__,3).'/funciones.php');


    switch($db->driver)	{
	case 'postgres':
           $queryLimit=100;
           $buscar_nom = $buscar_nom ?? "";
           $orderNo = $orderNo ?? 0;
           $buscar_nom = trim(strtoupper($buscar_nom));            
           
            $sql = "select --Crear ciudadanos confirmacion
                    case when u.tipo_usuario = 2 then '<i>(Ciu.)</i>' else '<i>(Serv.)</i>' end as \"SCR_Tipo\",
                    u.usua_cedula as \"Cédula\",
                    u.usua_nomb||' '||usua_apellido as \"Nombre\",
                    u.usua_titulo as \"Título\",
                    u.usua_cargo as \"Cargo\",
                    u.inst_nombre as \"Institución\",
                    u.usua_email as \"Correo Electrónico\",
                    case when u.tipo_usuario = 2 then 'Editar' else '' end as \"SCR_Acción\",
                    'comparar_ciudadanos(\"'||u.usua_codi||'\");' as \"HID_FUNCION\"
                     from 
                     (
                        select usua_codi,usua_cedula,usua_nomb,usua_apellido,usua_titulo
                        ,usua_cargo,inst_nombre,usua_email,tipo_usuario from usuario where ";
                        if ($b_cedula!='')
                            $sql .= "usua_cedula = '$b_cedula' or ";
                        //otro tratamiento para la busqueda no es posible utilizar las funciones
                       if ($ciu_nombre!='')
                            $sql.= " ( translate(UPPER(usua_nomb),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN') 
                                     LIKE translate(upper('%$ciu_nombre%'),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN') 
                                     ) ";
                       if ($ciu_apellido!='')
                            $sql.= " or ( translate(UPPER(usua_apellido),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN') 
                                     LIKE translate(upper('%$ciu_apellido%'),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN') 
                                     )";
                        $sql.= " and usua_esta = 1";
                        $sql.= " order by ".($orderNo+1)." $orderTipo  limit $queryLimit offset 0";
                        $sql.= " ) as u";
             
            break;
}

?>
