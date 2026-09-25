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
include_once(dirname(__DIR__,3)."/Administracion/ciudadanos/util_ciudadano.php");

$ciud = New Ciudadano($db);
    switch($db->driver)	{
	case 'postgres': 
            $buscar_nom = trim(strtoupper($buscar_nom));            
            $sql = "select ciu_cedula as \"Cédula\",
                    ciu_nombre||' '||ciu_apellido as \"Nombre\",
                    ciu_titulo as \"Titulo\",
                    ciu_cargo as \"Cargo\",
                    ciu_empresa as \"Institución\",
                    ciu_email as \"Correo Electrónico\",
                    'Editar' as \"SCR_Acción\",
                    'comparar_ciudadanos(\"'||ciu_codigo||'\",\"'||ciu_cedula||'\");' as \"HID_FUNCION\"
                    from ciudadano_tmp
                    where ciu_estado=1";
             if ($buscar_nom!="") {                 
                 if ($ciud->esNumeroTxt($buscar_nom)==1)
                     $sql .= " and " . buscar_cadena($buscar_nom,'ciu_cedula')."";
                 else{                  
                $sql .=  ' and (' . nombre_cedula_tmp($buscar_nom,$ciud,'S');
                $sql .= " or ((" . buscar_cadena($buscar_nom,'ciu_email').")                                     
                    or (" . buscar_cadena($buscar_nom,'ciu_cargo')."))) ";		                
                    }
             }
             $sql .= " order by ".($orderNo+1)." $orderTipo";
             //echo $sql;
            break;
}

?>
