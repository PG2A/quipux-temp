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
 * @package    tx
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

switch($db->getDriver())	{
    case 'postgres':
        if (!isset($orderNo) || $orderNo == '') $orderNo=0;        
        $nombre = trim(strtoupper($nombre));
        $sql = "select u.usua_nombre AS \"SCR_Nombre\"            
            ,'seleccionar_usuario(\"'|| u.usua_codi ||'\");' as \"HID_FUNCION\"";
           /* ,  case when usua_subrogado<>1 then 'Subrogante' else '' 
                    end as \"Subrogación\"";*/
        //Subrogacion
        $sql.= ", case when cargo_tipo = 1 then 'Jefe' else 'Normal' end  as \"Perfil\"
                , case when u.usua_codi
                          in (select usua_subrogado from usuarios_subrogacion 
                          where usua_visible=1) = true then ' (Subrogado)' else '' end 
                          || case when u.usua_codi
                          in (select usua_subrogante from usuarios_subrogacion 
                          where usua_visible=1) = true then ' (Subrogante)' else '' end 
                          AS \"Subrogación\"";
        $sql.=", u.usua_email AS \"Email\"
            , u.depe_nomb AS \"Área\"
            , u.usua_cargo AS \"Puesto\"
            , u.usua_cargo_cabecera AS \"Puesto Cabecera\"
            , case when u.usua_esta = 1
                   then '<span class=\"sin-envolver\" style=\"display:inline-block;padding:2px 10px;border-radius:10px;background:#e6f4ea;color:#1e7e34;font-weight:600\">Activo</span>'
                   else '<span class=\"sin-envolver\" style=\"display:inline-block;padding:2px 10px;border-radius:10px;background:#fdecea;color:#c5221f;font-weight:600\">Inactivo</span>'
              end AS \"SCR_Estado\"";
        if (isset($_SESSION["usua_codi"]) && $_SESSION["usua_codi"]==0) $sql .= ", u.inst_nombre as \"Institución\"";

        // Vigencia de la cuenta (fecha inicio / fin, en 'usuarios').
        $sql .= ", (select case when x.usua_vigencia_desde is null and x.usua_vigencia_hasta is null then 'Sin límite'
                                else coalesce(to_char(x.usua_vigencia_desde,'YYYY-MM-DD'),'…') || ' a '
                                  || coalesce(to_char(x.usua_vigencia_hasta,'YYYY-MM-DD'),'sin fin') end
                      from usuarios x where x.usua_codi = u.usua_codi) AS \"Vigencia\"";

        // Acciones por fila. La marca 'sin-envolver' hace que el paginador pinte la
        // celda tal cual (sin envolverla en el enlace de la fila).
        $sql .= ", '<span class=\"sin-envolver\">'
                   || '<input type=\"button\" class=\"botones\" value=\"Editar\" onclick=\"seleccionar_usuario(' || u.usua_codi || ');\">'
                   || '</span>' AS \"SCR_Acciones\"";


        $sql .= " from usuario u";
        if ($permiso!="0") $sql .= " left outer join permiso_usuario p on u.usua_codi=p.usua_codi and p.id_permiso=$permiso";

        $sql .= " where u.inst_codi>0 and u.usua_codi>0 and visible_sub=1 and u.inst_codi=".$_SESSION["inst_codi"];

        if ($estado!=2) $sql .= " and usua_esta=$estado";
        if ($nombre != ""){
            $sql .= buscar_datos_usuario($nombre);
//            $sql .= ' and (' . buscar_nombre_cedula($nombre);
//            $sql .= " or ((" . buscar_cadena($nombre,'usua_email').")
//                or (" . buscar_cadena($nombre,'usua_cargo_cabecera').")
//                    or (" . buscar_cadena($nombre,'usua_cargo')."))) ";
//
        }
        
        if ($dependencia > 0) {
            $sql .= " and u.depe_codi=$dependencia";
        } else {
            if (!empty($depe_codi_admin))
                $sql.= " and u.depe_codi in ($depe_codi_admin)";
        }
       
        if ($permiso!=0) $sql .= " and p.id_permiso is not null";
        
       
        if ($perfil!=2) $sql .= " and cargo_tipo=$perfil";        
        $sql .= " order by 7";
//echo $sql;
        break;
}
?>
