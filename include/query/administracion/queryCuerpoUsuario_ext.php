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
        $sql= "select case when trim(usua_nombre)='' then 'S/N' else usua_nombre end AS \"SCR_Nombre\"
                               ,'seleccionar_usuario(\"'|| usua_codi ||'\");' as \"HID_FUNCION\"
                               , usua_cedula AS \"Cédula\"
                                        ,usua_email AS \"Email\"
                                        , usua_cargo AS \"".$descCargo."\"
                                        , inst_nombre AS \"".$descEmpresa."\"
                                        ,case usua_esta
                                         when 1 then 'Activo'
                                         else 'Inactivo'
                                         end as \"Estado\"
                               from usuario
                               where inst_codi=0";
        if (trim($opc)=="a")//activos
            $sql.=" and usua_esta=1";
        elseif(trim($opc)=="b")
            $sql.=" and usua_esta=0";
        $sql.=" and usua_codi>0 and tipo_usuario=2 and usua_nombre <> '- -' 
            and usua_nombre <> ', ,' and usua_nombre <> '. .'";
        if ($ciud->esNumeroTxt($buscar_nom)==1){
            $sql.=" and (" . buscar_cadena($buscar_nom,'usua_cedula').")";           
        }elseif ($buscar_nom!="") {
            if ($ciud->esEmail($buscar_nom)==1)//si es mail
                $sql .= " and (" . buscar_cadena($buscar_nom,'usua_email').")";
                else{
                    $sql .=  ' and (' . buscar_cadena($buscar_nom,'usua_nombre');
                    $sql .= " or ((" . buscar_cadena($buscar_nom,'inst_nombre').")";

                    $sql .= " or (" . buscar_cadena($buscar_nom,'usua_cargo')."))) ";
                }            
            }
            $sql .= " order by ".($orderNo+1)." $orderTipo ";
           
            
            
        //echo '<font size=1>'.$sql.'</font>';
        //die();
break;
}

?>