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
include_once(dirname(__DIR__, 2)."/include/tx/Historico.php");
if($_SESSION["usua_codi"]!=0) {
    die("Usted no tiene los permisos suficientes para acceder a esta p&aacute;gina.");
}

$hist = new Historico($db);

$inst_origen  = 0 + limpiar_numero($_POST["txt_inst_origen"]);
$inst_destino = 0 + limpiar_numero($_POST["txt_inst_destino"]);
$depe_origen  = 0 + limpiar_numero($_POST["txt_depe_origen"]);
$observacion  = limpiar_sql($_POST["txt_observacion"]);

if ($inst_origen==0 or $inst_destino==0 or $inst_origen==$inst_destino)
    die ("Error-Por favor verifique las instituciones seleccionadas");

if ($depe_origen == 0) {
    $sql = "select radi_nume_radi from radicado where radi_inst_actu=$inst_origen limit 1000";

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
    $sql = "select radi_nume_radi from radicado
            where radi_usua_actu in (select usua_codi from usuarios where depe_codi in ($lista_areas))
            and radi_inst_actu=$inst_origen limit 1000";
}


$db->conn->BeginTrans();

$record = array();

$rs = $db->query($sql);

$contador = 0;
while($rs && !$rs->EOF) {
    $record["radi_nume_radi"] = trim($rs->fields["RADI_NUME_RADI"]);
    $record["radi_inst_actu"] = $inst_destino;
    $ok = $db->conn->Replace("radicado", $record, "radi_nume_radi", false,false,true,false);
    if ($ok != 1) {
        $db->conn->RollbackTrans();
        die ("Error-No se pudo mover el documento No. ".trim($rs->fields["RADI_NUME_RADI"]));
    }
    ++$contador;
    $hist->insertarHistorico($rs->fields["RADI_NUME_RADI"], $_SESSION["usua_codi"], $_SESSION["usua_codi"], $observacion, 10);
    $rs->MoveNext();
}

$db->conn->CommitTrans();

if ($contador==0)
    die ("Finalizado");
else
    die ("$contador");

?>