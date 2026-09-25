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

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__).'/include/tx/Historico.php');
require_once(dirname(__DIR__)."/funciones.php");

p_register_globals(array());

    $hist = new Historico($db);
    $record = array();
    $where = array();
    
    $txtRadicado = limpiar_numero($_POST['txtRadicado']);
    $record["RADI_NUME_RADI"] = $txtRadicado;

    $txtCodigo = limpiar_numero($_POST['txtCodigo']);
    $record["TRD_CODI"] = $txtCodigo;
    
    $record["FECHA"] = $db->conn->sysTimeStamp;
    $record["USUA_CODI"] = $_SESSION['usua_codi'];
    $record["DEPE_CODI"] = $_SESSION['depe_codi'];

    $where[]="RADI_NUME_RADI";
    $where[]="DEPE_CODI";
    
    $ok = $db->conn->Replace("TRD_RADICADO", $record, $where, false,false,true,false);
    $hist->insertarHistorico($txtRadicado, $_SESSION['usua_codi'], $_SESSION['usua_codi'], "Incluir documento en $descTRD", 32, $txtCodigo);

echo "<script>opener.regresar();window.close();</script>";
?>

