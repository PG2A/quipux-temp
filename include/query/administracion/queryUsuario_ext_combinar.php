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

// Verificar si existen ciudadanos con nombres similares o la misma cédula
    $where = buscar_2campos($buscar_nombre, "usua_nombre", "usua_cedula");

    //Se podrá combinar unicamente usuarios sin firma
    $sql = "select usua_cedula as \"Cédula\",
            usua_nombre as \"Nombre\",
            usua_titulo as \"Título\",
            usua_cargo as \"Cargo\",
            inst_nombre as \"Institución\",
            usua_email as \"Correo Electrónico\",
            'Seleccionar' as \"SCR_Usuario a Desactivar\",
            'usr_origen(\"'||usua_codi||'\");' as \"HID_FUNCION1\",
            'Seleccionar' as \"SCR_Usuario Final\",
            'usr_destino(\"'||usua_codi||'\",\"'||usua_cedula||'\");' as \"HID_FUNCION2\"
            from usuario where $where and inst_codi=0 and usua_esta=1 and tipo_usuario=2 
            order by 1 ";
             
   
            break;
    }
 ?>