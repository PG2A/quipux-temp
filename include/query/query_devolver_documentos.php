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
 * @package    query
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


include_once(dirname(__DIR__) . "/local/localEcuador.php");
$descZonaHoraria = $descZonaHoraria ?? '';

switch($db->driver) {
    case 'postgres':
        //$whereFiltro = " and upper(radi_nume_text) like upper('%$txt_documento%')";
        $whereFiltro = " and (UPPER(radi_nume_text) like '%".trim(strtoupper($busqRadicados))."%'
                              or UPPER(radi_asunto) like '%".trim(strtoupper($busqRadicados))."%') ";

            if ($orderNo=='') $orderNo=4;
            $isql = "select -- Devolucion documentos
                    radi_nume_radi as \"CHK_CHKANULAR\"
                    ,ver_usuarios(radi_usua_rem,',') as \"De\"
                    ,ver_usuarios(radi_usua_dest,',') as \"Para\"
                    ,radi_asunto as \"Asunto\"
                    ,substr(radi_fech_radi::text,1,19) || '$descZonaHoraria' as \"DAT_Fecha Documento\"
                    ,radi_nume_radi as \"HID_RADI_NUME_RADI\"
                    ,radi_nume_text as \"Número Documento\"
                    ,trad_descr as \"Tipo Documento\"
                    from (
                        select b.radi_nume_radi, b.radi_usua_rem, b.radi_usua_dest, b.radi_asunto
                        , b.radi_fech_radi, 1, b.radi_nume_text, b.radi_cuentai, td.trad_descr
                        from (select * from radicado where esta_codi=6 and radi_nume_radi::text like '%1'
                        and radi_inst_actu = " . $_SESSION["inst_codi"] . " $whereFiltro ) as b
                        left outer join tiporad td on b.radi_tipo=td.trad_codigo
                    ) as a order by ".($orderNo+1)." $orderTipo";
        break;
    }
//echo $isql;
?>
