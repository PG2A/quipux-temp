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
 * @package    tareas
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__).'/funciones.php');
include_once(dirname(__DIR__).'/obtenerdatos.php');
include_once(dirname(__DIR__).'/include/tx/Tx.php');
include_once(dirname(__DIR__).'/seguridad_documentos.php'); // Valida estados de los documentos y otras reglas dependiendo de la transacción realizada

$codTx = limpiar_sql($_POST['codTx']);
$comentario = limpiar_sql($_POST['txt_comentario']);
$fecha_max_tram = limpiar_sql($_POST['txt_fecha_tarea']);
$usua_codi_dest = $_POST['txt_usua_codi'];
$radicados = explode(",",$_POST['txt_radicados']);

$radicadosSel = array ();
$whereFiltro = "0";
$mensaje_error = "";

foreach ($radicados as $radi_nume) {
    if ((int)$radi_nume != 0) {
        $flag = validar_transacciones($codTx, $radi_nume, $db);
        if ($flag == "") {
            $whereFiltro .= ",$radi_nume";
            $radicadosSel[] = $radi_nume;
        } else
            $mensaje_error .= $flag;
    }
}

if ($whereFiltro === "0") {	//Si no se escogio ningun radicado
    die ("No hay documentos seleccionados.");
}

$tx = new Tx($db);

switch ($codTx)
{
    case 30:  //Eliminar Documentos
        $nombTx = "Asignar Tareas ";
        echo $tx->asignarTareas($radicadosSel, $usua_codi_dest, $fecha_max_tram, $comentario);
        break;
}

?>