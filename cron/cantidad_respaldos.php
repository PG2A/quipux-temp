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
 * @package    cron
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

include_once(dirname(__DIR__).'/include/db/ConnectionHandler.php');
include_once(dirname(__DIR__).'/config.php');
error_reporting(7);
$db = new ConnectionHandler(dirname(__DIR__));
$db->conn->SetFetchMode(ADODB_FETCH_ASSOC);

$sql = "select resp_soli_codi, usua_codi_solicita, fecha_inicio_doc, fecha_fin_doc
from respaldo_solicitud where estado_solicitud=3 and num_documentos is null";
$rs = $db->query($sql);

if($rs){
    $resp_soli_codi = $rs->fields["RESP_SOLI_CODI"];
    $fecha_inicio_doc = $rs->fields["FECHA_INICIO_DOC"];
    $fecha_fin_doc = $rs->fields["FECHA_FIN_DOC"];
    $usuario_solicita = $rs->fields["USUA_CODI_SOLICITA"];
}

if($resp_soli_codi != ""){
    //Se actualiza los datos    
    $sql_actualiza = "update respaldo_solicitud set num_documentos = -1 where resp_soli_codi = $resp_soli_codi";
    $db->query($sql_actualiza);
    $cantidad_doc = ConsultarCantidadDocumentos($fecha_inicio_doc, $fecha_fin_doc,$usuario_solicita, $db);
    $sql_actualiza = "update respaldo_solicitud set num_documentos = $cantidad_doc where resp_soli_codi = $resp_soli_codi";
    $db->query($sql_actualiza);
}

function ConsultarCantidadDocumentos($fecha_inicio_doc, $fecha_fin_doc,$usuario_solicita, $db){

    $valor1 = 0;
    $valor2 = 0;
    $valor3 = 0;
    $valor4 = 0;
    $valor5 = 0;
    $where_fecha_documento = "";

    if($fecha_inicio_doc != "" and $fecha_fin_doc != ""){
        $fecha_documento = "(case when radi_nume_temp::text like '%0' then radi_fech_ofic else radi_fech_radi end)";
        $where_fecha_documento = " and ($fecha_documento::date between '$fecha_inicio_doc' and '$fecha_fin_doc')";
    }

    // ENVIADOS
    $sql = "select count(radi_nume_radi) as cant
            from radicado
            where radi_usua_rem like '%-$usuario_solicita-%'
            and radi_nume_radi::text like '%0' and esta_codi in (0,3,6)
            $where_fecha_documento";
    $rs = $db->query($sql);
    //echo "<br> --q1 " . $sql;
    if ($rs)
        $valor1 = $rs->fields["CANT"];

    $sql = "select count(radi_nume_radi) as cant
            from radicado
            where esta_codi in (0,2,3,6)
            and radi_nume_radi in (select radi_nume_radi from hist_eventos where (sgd_ttr_codigo=9 or sgd_ttr_codigo=10) and usua_codi_ori=$usuario_solicita)
            $where_fecha_documento";
    $rs = $db->query($sql);
    //echo "<br> --q2 " . $sql;

    if ($rs)
        $valor2 = $rs->fields["CANT"];

    $sql = "select count(radi_nume_radi) as cant
            from radicado
            where (radi_usua_dest like '%-$usuario_solicita-%' or radi_cca like '%-$usuario_solicita-%')
            and radi_nume_radi::text like '%1' and esta_codi in (0,2)
            $where_fecha_documento";
    $rs = $db->query($sql);
    //echo "<br> --q3 " . $sql;
    if ($rs)
        $valor3 = $rs->fields["CANT"];

    $sql = "select count(radi_nume_radi) as cant
            from radicado
            where esta_codi in (0,2,3,6)
            and radi_nume_radi in (select radi_nume_radi from hist_eventos where (sgd_ttr_codigo=9 or sgd_ttr_codigo=10) and usua_codi_dest=$usuario_solicita)
            $where_fecha_documento";
    $rs = $db->query($sql);
    //echo "<br> --q4 " . $sql;
    if ($rs)
        $valor4 = $rs->fields["CANT"];

    $sql = "select count(radi_nume_radi) as cant
            from radicado
            where esta_codi in (0,2,3,6)
            and radi_nume_radi in (select radi_nume_radi from hist_eventos where sgd_ttr_codigo=8 and usua_codi_dest=$usuario_solicita)
            $where_fecha_documento";
    $rs = $db->query($sql);
    //echo "<br> --q5 " . $sql;
    if ($rs)
        $valor5 = $rs->fields["CANT"];

    $total = $valor1 + $valor2 + $valor3+ $valor4 + $valor5;

    return $total;
}

?>
