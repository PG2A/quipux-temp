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
 * @package    anexos
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!$db->driver){ $db = $this->db; }	//Esto sirve para cuando se llama este archivo dentro de clases donde no se conoce $db.
//validacion de asociados en documentos externos

$vradi_nume = substr($radi_nume,19,1);//radicado
$vradi_nume_tmp = substr($radi_nume_tmp,19,1);//radicado padre
$editar=1;
if (($vradi_nume==0 and $radi_nume_deri!='')) //|| ($vradi_nume==1 and $vradi_nume_tmp==2))
    $editar=0;
if (!isset ($orderNo)) $orderNo = 2;
    
switch($db->driver)
{
    case 'postgres':
    	$sqlFecha = "substr(radi_fech_ofic::text,1,19)";
        
        $usuarioSel = 0+$_SESSION["usua_codi"];
        $from_usr_recorrido = " radi_nume_radi in (select distinct radi_nume_radi from hist_eventos ".
                              " where usua_codi_ori=$usuarioSel or usua_codi_dest=$usuarioSel) ";
        
        $isql = "select -- Asociacion de documentos
                radi_nume_text as \"No. Documento\"
                ,radi_cuentai as \"No. Referencia\"
                ,$sqlFecha as \"DAT_Fecha Documento\"
                ,radi_nume_radi as \"HID_RADI_NUME_RADI\"
                ,radi_asunto  as \"Asunto\"
                ,ver_usuarios(radi_usua_actu::text,',') AS \"Usuario Actual\"
                ,ver_usuarios(radi_usua_rem,',<br>') AS \"Remitente\"
                ,ver_usuarios(radi_usua_dest,',<br>') AS \"Destinatario\"
                ,trad_descr as \"Tipo de Documento\"";
        if ($editar==1)
            $isql.=",'Antecedente' AS \"SCR_Acción\",'seleccionar_documento(\"'|| radi_nume_radi ||'\",\"A\");' as \"HID_FUNCIONA\"";

            $isql.=",case when radi_nume_asoc is null then 'Consecuente' else '' end AS \"SCR_Acción.\"
                ,'seleccionar_documento(\"'|| radi_nume_radi ||'\",\"C\");' as \"HID_FUNCIONC\"
            from (
                select r.radi_nume_text, radi_cuentai, r.radi_fech_ofic, r.radi_nume_radi ,r.radi_asunto ,r.radi_usua_actu
                , r.radi_usua_rem, r.radi_usua_dest, t.trad_descr, r.radi_nume_asoc
                from (select * from radicado b where 
                    radi_nume_text||' '||coalesce(upper(radi_cuentai),'') like '%".strtoupper($txt_documento)."%'
                    and radi_inst_actu = " . $_SESSION["inst_codi"] . "
                    and esta_codi in (0,1,2,3,4,5,6) and radi_nume_radi<>$radi_nume) as r
                left outer join tiporad t on r.radi_tipo=t.trad_codigo
                order by ".($orderNo+1)." $orderTipo *LIMIT**OFFSET*
            ) as a order by ".($orderNo+1)." $orderTipo";

//echo $isql."<hr>";

	break;
}
?>
