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
 * @package    tbasicas
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
if($_SESSION["usua_codi"]!=0) {
    die("Usted no tiene los permisos suficientes para acceder a esta p&aacute;gina.");
}

$inst_origen  = 0 + limpiar_numero($_POST["txt_inst_origen"]);
$inst_destino = 0 + limpiar_numero($_POST["txt_inst_destino"]);
$depe_origen  = 0 + limpiar_numero($_POST["txt_depe_origen"]);

if ($inst_origen==0 or $inst_destino==0 or $inst_origen==$inst_destino)
    die ("Error-Por favor verifique las instituciones seleccionadas");

if ($depe_origen == 0) {
    $lista_areas = "select depe_codi from dependencia where inst_codi=$inst_origen";
} else {
    $lista_areas = "$depe_origen";

    $flag_detener = false;
    $i=0;
    while (!$flag_detener && $i<1000) {
        ++$i;
        $sql = "select depe_codi from dependencia where depe_codi_padre in ($lista_areas) and depe_codi not in ($lista_areas)";
        $rs = $db->query($sql);
        if (!$rs or $rs->EOF) {
            $flag_detener = true;
        } else {
            while(!$rs->EOF) {
                $lista_areas .= ",".$rs->fields["DEPE_CODI"];
                $rs->MoveNext();
            }
        }
    }
}


$db->conn->BeginTrans();

$record = array();

$sql = "select depe_codi, fn_tiporad from formato_numeracion
        where depe_codi in (select depe_codi from dependencia where depe_codi in ($lista_areas))
            and coalesce (depe_numeracion,depe_codi) not in (select depe_codi from dependencia where depe_codi in ($lista_areas))";
// Consulto las areas creadas en la institucion origen
$rs = $db->query($sql);

$contador = 0;
while($rs && !$rs->EOF) {
    $record["depe_codi"] = $rs->fields["DEPE_CODI"];
    $record["fn_tiporad"] = $rs->fields["FN_TIPORAD"];
    $record["depe_numeracion"] = $rs->fields["DEPE_CODI"];
    $ok = $db->conn->Replace("formato_numeracion", $record, array("depe_codi","fn_tiporad"), false,false,true,false);
    if ($ok != 1) {
        $db->conn->RollbackTrans();
        die ("Error-No se pudo desasociar numeracion de los documentos ".trim($rs->fields["DEPE_CODI"])." - ".$rs->fields["FN_TIPORAD"]);
    }
    ++$contador;
    $rs->MoveNext();
}

unset ($record);
$record = array();

$sql = "select depe_codi from dependencia
        where depe_codi in (select depe_codi from dependencia where depe_codi in ($lista_areas))
            and coalesce (dep_central,depe_codi) not in (select depe_codi from dependencia where depe_codi in ($lista_areas))";
// Consulto las areas creadas en la institucion origen
$rs = $db->query($sql);

$contador = 0;
while($rs && !$rs->EOF) {
    $record["depe_codi"] = $rs->fields["DEPE_CODI"];
    $record["dep_central"] = $rs->fields["DEPE_CODI"];
    $ok = $db->conn->Replace("dependencia", $record, "depe_codi", false,false,true,false);
    if ($ok != 1) {
        $db->conn->RollbackTrans();
        die ("Error-No se pudo desasociar el archivo f&iacute;sico del &aacute;rea ".$rs->fields["DEPE_CODI"]);
    }
    ++$contador;
    $rs->MoveNext();
}

unset ($record);
$record = array();


$sql = "select depe_codi from dependencia
        where depe_codi in (select depe_codi from dependencia where depe_codi in ($lista_areas))
            and coalesce (depe_plantilla,depe_codi) not in (select depe_codi from dependencia where depe_codi in ($lista_areas))";
// Consulto las areas creadas en la institucion origen
$rs = $db->query($sql);

$contador = 0;
while($rs && !$rs->EOF) {
    $record["depe_codi"] = $rs->fields["DEPE_CODI"];
    $record["depe_plantilla"] = $rs->fields["DEPE_CODI"];
    $ok = $db->conn->Replace("dependencia", $record, "depe_codi", false,false,true,false);
    if ($ok != 1) {
        $db->conn->RollbackTrans();
        die ("Error-No se pudo desasociar la plantilla del &aacute;rea ".trim($rs->fields["DEPE_CODI"]));
    }
    ++$contador;
    $rs->MoveNext();
}


$db->conn->CommitTrans();

if ($contador==0)
    die ("Finalizado");
else
    die ("$contador");

?>