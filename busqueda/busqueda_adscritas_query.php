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
 * @package    busqueda
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

switch($db->driver)
{
    case 'postgres':

        $adsc = New Adscritas($db);
        if($cmb_institucion == 0)
            $cmb_institucion = $adsc->institucionesAnidadas($_SESSION["inst_codi"],'DESC');
        
        $sqlFecha = "substr(radi_fech_ofic::text,1,19)";
        $where = "r.radi_inst_actu in (" . $cmb_institucion . ")";
        $from = "";
        if (trim($txt_nume_documento) != ""){
           if (strlen($txt_nume_documento)>=4)
            $where .= " and upper(r.radi_nume_text) like upper('%".trim($txt_nume_documento)."%')";
           //$where .= " and upper(r.radi_nume_text) like upper('%".trim($txt_nume_documento)."%')";
        }
        if (trim($txt_nume_referencia) != ""){
            if (strlen($txt_nume_referencia)>=4)
            $where .= " and upper(r.radi_cuentai) like upper('%".trim($txt_nume_referencia)."%')";
            }
        if (trim($txt_texto) != "") {
            if (strlen($txt_texto)>=4)
            //Añadido para buscar en el campo radi_resumen donde se guarda la información de notas
            $where .= " and (" . buscar_cadena($txt_texto,"coalesce(r.radi_asunto,'')||' '||coalesce(r.radi_resumen,'')").") ";
        }

        //Armar where para busqueda por De (remitente)
        if(trim($txt_usua_remitente) != "") {
            $where .= " and r.radi_nume_temp in (select radi_nume_radi from usuarios_radicado where radi_usua_tipo=1 ";
            $tmp1 = explode(" ",limpiar_sql($txt_usua_remitente));
            $campo_tsearch = "(((((COALESCE(usua_nombre, '' )) || ' ' ) ||
                                 (COALESCE(usua_apellido, '' ))) || ' ' ) ||
                                 (COALESCE(usua_institucion, '' )))";
            foreach($tmp1 as $tmp) {
                if(trim($tmp) != "")
                    $where .= " and " . buscar_cadena_tsearch ($tmp, $campo_tsearch);
            }
            $where .= ")";
        }

        //Armar where para busqueda por Para (destinatario)
        if(trim($txt_usua_destinatario) != "") {
            $where .= " and r.radi_nume_temp in (select radi_nume_radi from usuarios_radicado where radi_usua_tipo in (2,3) ";
            $tmp1 = explode(" ",limpiar_sql($txt_usua_destinatario));
            $campo_tsearch = "(((((COALESCE(usua_nombre, '' )) || ' ' ) ||
                                 (COALESCE(usua_apellido, '' ))) || ' ' ) ||
                                 (COALESCE(usua_institucion, '' )))";
            foreach($tmp1 as $tmp) {
                if(trim($tmp) != "")
                    $where .= " and " . buscar_cadena_tsearch($tmp, $campo_tsearch);
            }
            $where .= ")";
        }

        $where .= " and (r.esta_codi!=6 or r.radi_nume_radi::text not like '%1')
                   and r.radi_fech_ofic::date >= '$txt_fecha_desde'::date
                   and r.radi_fech_ofic::date <= '$txt_fecha_hasta'::date";
        //VER USUARIOS SUBROGACION
        $sbrgado='<b>(Subrogado)</b>';
        $sbrgate='<b>(Subrogante)</b>';

        $isql = "select -- Busqueda Avanzada
            case when (select 1 from radicado r2 where a.radi_nume_temp=r2.radi_nume_radi and a.radi_nume_radi<>r2.radi_nume_radi and r2.radi_inst_actu in (" . $cmb_institucion . ")) is null then '' else '&nbsp;&nbsp;<b>&rarr;</b>&nbsp;' end || radi_nume_text as \"No. Documento\"
            ,'<img src=\"$ruta_raiz/iconos/popup.png\" border=0>' as \"SCR_ \"
            ,'mostrar_documento(\"'||radi_nume_radi||'\",\"'||radi_nume_text||'\")' as \"HID_POPUP\"
            ,$sqlFecha || '$descZonaHoraria' as \"DAT_Fecha Documento\"
            ,radi_nume_radi as \"HID_RADI_NUME_RADI\"
            ,radi_cuentai as \"No. de Referencia\"
            ,radi_asunto  as \"Asunto\"
            ,inst_nombre as \"Institución\"
            ,usua_nombre || case when radi_usua_actu
            in (select usua_subrogado from usuarios_subrogacion
            where usua_visible=1) = true then ' $sbrgado' else '' end
            || case when radi_usua_actu
            in (select usua_subrogante from usuarios_subrogacion
            where usua_visible=1) = true then ' $sbrgate' else '' end as \"Usuario Actual\"
            ,depe_nomb as \"Área Actual\"
            ,ver_usuarios(radi_usua_rem,',') as \"De\"
            ,ver_usuarios(radi_usua_dest,',') as \"Para\"
            ,trad_descr as \"Tipo de Documento\"
            , esta_desc as \"Estado\"
            ,CASE WHEN radi_fech_firma is not null THEN 'SI' ELSE 'NO' END as \"Firma Digital\"
            from (
                select b.radi_nume_text, 'popup1', 'popup2', b.radi_fech_ofic, b.radi_nume_radi, b.radi_cuentai, b.radi_asunto
                    , coalesce(u.usua_nomb,'') || ' ' || coalesce(u.usua_apellido,'') as usua_nombre,b.radi_usua_actu, d.depe_nomb
                    , b.radi_usua_rem, b.radi_usua_dest, t.trad_descr, b.radi_fech_firma, b.radi_nume_temp, b.esta_desc, i.inst_nombre
                from (
                    select r.radi_nume_text, r.radi_fech_ofic, r.radi_nume_radi, r.radi_cuentai, r.radi_asunto, r.radi_usua_actu,
                        r.radi_usua_rem, r.radi_usua_dest, r.radi_fech_firma, r.radi_tipo, r.radi_fech_radi as \"fecha_orden\", r.radi_nume_temp, es.esta_desc, r.radi_inst_actu
                    from radicado r left outer join estado es on r.esta_codi=es.esta_codi
                        $from
                    where $where
            ) as b
            left outer join usuarios u on b.radi_usua_actu=u.usua_codi
            left outer join dependencia d on u.depe_codi=d.depe_codi
            left outer join tiporad t on b.radi_tipo=t.trad_codigo
            left outer join institucion i on b.radi_inst_actu=i.inst_codi
            order by " . ($orderNo+1) . " $orderTipo, b.fecha_orden asc *LIMIT**OFFSET*
        ) as a";
//    echo $isql;
	break;
}
?>
