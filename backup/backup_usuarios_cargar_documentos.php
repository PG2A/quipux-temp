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

include_once(dirname(__DIR__).'/config.php');
include_once(dirname(__DIR__).'/include/db/ConnectionHandler.php');
include_once(dirname(__DIR__)."/funciones.php");

$db = new ConnectionHandler(dirname(__DIR__));
$db->conn->SetFetchMode(ADODB_FETCH_ASSOC);

$respaldo = limpiar_sql($_POST["txt_resp_codi"]);
$sql = "select usua_codi from respaldo_usuario where resp_codi=$respaldo";
$rs = $db->query($sql);
if (!$rs) die ("OK");
$usr = $rs->fields["USUA_CODI"];
// Borramos los registros si falló un ingreso anterior
$sql = "delete from respaldo_usuario_radicado where resp_codi=$respaldo";
$db->query($sql);

//Datos de solicitud de respaldo
$where_fecha_documento = "";
$fecha_documento = "radi_fech_ofic";
$resp_soli_codi = "";

//Se consulta fecha de inicio y fin de la solicitud
$sql = "select * from respaldo_solicitud where resp_codi = $respaldo";
$rs = $db->query($sql);

if($rs){
    $fecha_inicio_doc = $rs->fields["FECHA_INICIO_DOC"];
    $fecha_fin_doc = $rs->fields["FECHA_FIN_DOC"];
    $resp_soli_codi = $rs->fields["RESP_SOLI_CODI"];
    $estado_solicitud = $rs->fields["ESTADO_SOLICITUD"];

    //Consulta por fecha de documentos
    if($fecha_inicio_doc != "" and $fecha_fin_doc != ""){
        $fecha_documento = "(case when radi_nume_temp::text like '%0' then radi_fech_ofic else radi_fech_radi end)";
        $where_fecha_documento = " and ($fecha_documento::date between '$fecha_inicio_doc' and '$fecha_fin_doc')";
    }
}

// ENVIADOS
$sql = "select radi_nume_radi, $fecha_documento as fecha from radicado
        where radi_usua_rem like '%-$usr-%'
        and radi_nume_radi::text like '%0' and esta_codi in (0,3,6)
        $where_fecha_documento
        union -- REASIGNADOS (Reasignados por mi)
        select radi_nume_radi, $fecha_documento as fecha from radicado
        where esta_codi in (0,2,3,6)
        and radi_nume_radi in (select radi_nume_radi from hist_eventos where sgd_ttr_codigo=9 and usua_codi_ori=$usr)
        $where_fecha_documento
        order by fecha asc";
cargar_lista_documentos($sql, 1);

// RECIBIDOS
$sql = "select radi_nume_radi, $fecha_documento as fecha from radicado
        where (radi_usua_dest like '%-$usr-%' or radi_cca like '%-$usr-%')
        and radi_nume_radi::text like '%1' and esta_codi in (0,2)
        $where_fecha_documento
        union --REASIGNADOS (Reasignados a mi)
        select radi_nume_radi, $fecha_documento as fecha from radicado
        where esta_codi in (0,2,3,6)
        and radi_nume_radi in (select radi_nume_radi from hist_eventos where (sgd_ttr_codigo=9 or sgd_ttr_codigo=10) and usua_codi_dest=$usr)
        $where_fecha_documento
        union --INFORMADOS (Informados a mi)
        select radi_nume_radi, $fecha_documento as fecha from radicado
        where esta_codi in (0,2,3,6)
        and radi_nume_radi in (select radi_nume_radi from hist_eventos where sgd_ttr_codigo=8 and usua_codi_dest=$usr)
        $where_fecha_documento
        order by fecha asc";
cargar_lista_documentos($sql, 2);

$sql = "update respaldo_usuario set fecha_inicio=".$db->conn->sysTimeStamp." where resp_codi=$respaldo";
$db->query($sql);

//Se actualiza solicitud de respaldo
if($resp_soli_codi!=""){

    $estado_respaldo = 9;
    $cantidad_documentos = "select count(radi_nume_radi) from respaldo_usuario_radicado where coalesce(fila,'')='' and num_error<3 and resp_codi = $respaldo";
    $sql = "update respaldo_solicitud set fecha_inicio_ejec=".$db->conn->sysTimeStamp.",
            estado_respaldo  = $estado_respaldo
            where resp_codi=$respaldo";
    $db->query($sql);

    //Se inserta el histórico
    $usua_codi = 0; //$_SESSION["usua_codi"];
    $fecha_accion = $db->conn->sysTimeStamp;
    $accion = 75;    
    $sql = "INSERT INTO respaldo_hist_eventos(resp_soli_codi, usua_codi, fecha, accion, estado_solicitud, estado_respaldo)
    VALUES ($resp_soli_codi, $usua_codi, $fecha_accion, $accion, $estado_solicitud, $estado_respaldo)";
    $db->query($sql);
}

// Creamos la estructura de directorios
if (!is_dir(dirname(__DIR__)."/bodega/respaldos")) {
    if (!mkdir($concurrentDirectory = dirname(__DIR__) . "/bodega/respaldos") && !is_dir($concurrentDirectory)) {
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
    }
}
$path = dirname(__DIR__)."/bodega/respaldos/respaldo_$respaldo";
if (is_dir($path)) exec("rm -rf $path");
mkdir ($path);
mkdir ("$path/archivos");
mkdir ("$path/documentos");

function cargar_lista_documentos($sql, $tipo) {
    global $db;
    global $respaldo;
    $rs = $db->query($sql);
    if (!$rs) die("OK");
    while (!$rs->EOF) {
        $sql = "insert into respaldo_usuario_radicado (resp_codi, radi_nume_radi, tipo)
                values ($respaldo, ".$rs->fields["RADI_NUME_RADI"].", $tipo)";
        $db->query($sql);
        $rs->MoveNext();
    }
}


die("OK");

?>