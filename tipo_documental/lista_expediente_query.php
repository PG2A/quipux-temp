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
 * @package    tipo_documental
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


if (!$db->driver){	$db = $this->db; }	//Esto sirve para cuando se llama este archivo dentro de clases donde no se conoce $db.

switch($db->driver)
{
    case 'postgres':

//    	$sqlFecha = $db->conn->SQLDate("Y-m-d H:i A","R.RADI_FECH_RADI");
    	if (!isset($orderNo)) $orderNo=3;
        //$sqlFecha = "substr(r.radi_fech_ofic::text,1,19)";
        $where_fecha_documento = "";
        $fecha_documento = "(case when radi_nume_temp::text like '%0' then radi_fech_ofic else radi_fech_radi end)";
        if($fecha_inicio != "" and $fecha_fin != "")
            $where_fecha_documento = " and ($fecha_documento::date between '$fecha_inicio' and '$fecha_fin')";

         if ($txt_reporte!=1){
            $isql = "select -- Carpetas Virtuales
                        ver_usuarios(radi_usua_rem,',') as \"De\"
                        ,ver_usuarios(radi_usua_dest,',') as \"Para\"
                        ,radi_asunto as \"Asunto\"
                        ,substr(fecha::text,1,10) || '" . (isset($descZonaHoraria) ? $descZonaHoraria : '') . "' as \"SCR_Fecha Documento\"
                        ,'mostrar_documento(\"'||radi_nume_radi||'\",\"'||radi_nume_text||'\")' as \"HID_RADI_NUME_RADI\"
                        ,radi_nume_text as \"No. Documento\"
                        ,usuario_actual as \"Usuario Actual\"
                        ,esta_desc as \"Estado\"
                    from (
                        select r.radi_usua_rem, r.radi_usua_dest, r.radi_asunto, $fecha_documento as fecha, r.radi_nume_radi
                            , r.radi_nume_text, coalesce(u.usua_nomb,'')||' '||coalesce(u.usua_apellido,'') as usuario_actual, e.esta_desc
                        from
                            (select radi_nume_radi from trd_radicado where trd_codi=$codexp) as cv
                            left outer join radicado r on cv.radi_nume_radi=r.radi_nume_radi
                            left outer join usuarios u on r.radi_usua_actu=u.usua_codi
                            left outer join estado e on r.esta_codi=e.esta_codi
                        where r.esta_codi in (0,1,2,3,6) $where_fecha_documento
                        order by ".($orderNo+1)." $orderTipo
                    ) as a order by ".($orderNo+1)." $orderTipo";               
         }else{
              $isql1 = "select -- Carpetas Virtuales
                        ver_usuarios(radi_usua_rem,',') as \"De\"
                        ,ver_usuarios(radi_usua_dest,',') as \"Para\"
                        ,radi_asunto as \"Asunto\"
                        ,substr(fecha::text,1,10) || '" . (isset($descZonaHoraria) ? $descZonaHoraria : '') . "' as \"Fecha Documento\"
                        ,radi_nume_text as \"No. Documento\"
                        ,usuario_actual as \"Usuario Actual\"
                        ,esta_desc as \"Estado\"
                    from (
                        select r.radi_usua_rem, r.radi_usua_dest, r.radi_asunto, $fecha_documento as fecha, r.radi_nume_radi
                            , r.radi_nume_text, coalesce(u.usua_nomb,'')||' '||coalesce(u.usua_apellido,'') as usuario_actual, e.esta_desc
                        from
                            (select radi_nume_radi from trd_radicado where trd_codi=$codexp) as cv
                            left outer join radicado r on cv.radi_nume_radi=r.radi_nume_radi
                            left outer join usuarios u on r.radi_usua_actu=u.usua_codi
                            left outer join estado e on r.esta_codi=e.esta_codi
                        where r.esta_codi in (0,1,2,3,6) $where_fecha_documento
                        order by ".($orderNo+1)." $orderTipo
                    ) as a order by ".($orderNo+1)." $orderTipo";              
         }
         break;
}
?>
