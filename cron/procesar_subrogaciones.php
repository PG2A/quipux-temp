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
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*****************************************************************************
**  Aplica la vigencia de las subrogaciones de puesto.                      **
**                                                                          **
**  - Activa las PROGRAMADAS cuyo período ya comenzó.                       **
**  - Finaliza las ACTIVAS cuyo período terminó, devolviendo al titular los **
**    documentos que quedaron pendientes.                                   **
**                                                                          **
**  Hasta este rediseño, usua_fecha_inicio / usua_fecha_fin se almacenaban  **
**  pero nada las aplicaba: la subrogación empezaba al grabarla y sólo      **
**  terminaba con un clic manual del administrador.                         **
**                                                                          **
**  Programar para que se ejecute cada 5 minutos.                           **
******************************************************************************/

include_once(dirname(__DIR__) . '/include/db/ConnectionHandler.php');
include_once(dirname(__DIR__) . '/config.php');
include_once(dirname(__DIR__) . '/include/subrogacion/Subrogacion.php');

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$db = new ConnectionHandler(dirname(__DIR__));
$db->conn->SetFetchMode(ADODB_FETCH_ASSOC);
if (!$db->conn->_connectionID) die("Error: No se pudo conectar con la BDD\n");

// Tx::reasignar() se apoya en la sesión para autorizar y para registrar el
// histórico. Al correr sin navegador, se declara un contexto de sistema:
// usua_codi 0 es el super administrador en este sistema, y es la autoría
// correcta para una acción automática.
$_SESSION["usua_codi"]           = 0;
$_SESSION["usua_codi_jefe"]      = 0;
$_SESSION["usua_admin_sistema"]  = 1;

$subrogacion = new Subrogacion($db);

$log = function ($mensaje) {
    echo date('Y-m-d H:i:s') . "  $mensaje\n";
};

$activadas = 0;
$finalizadas = 0;
$errores = 0;

// -----------------------------------------------------------------------------
// 1. Activar las subrogaciones programadas que ya entraron en vigencia
// -----------------------------------------------------------------------------
$sql = "select usua_subrogacion_codi, usua_subrogado, usua_subrogante
          from usuarios_subrogacion
         where estado = " . Subrogacion::PROGRAMADA . "
           and usua_fecha_inicio <= now()
           and usua_fecha_fin    >  now()
         order by usua_fecha_inicio";

$rs = $db->conn->query($sql);
$porActivar = array();
while ($rs && !$rs->EOF) {
    $porActivar[] = $rs->fields;
    $rs->MoveNext();
}

foreach ($porActivar as $fila) {
    $codi  = (int)$fila['USUA_SUBROGACION_CODI'];
    $error = $subrogacion->activar($codi);
    if ($error === "") {
        $activadas++;
        $log("Subrogación $codi ACTIVADA (subrogante {$fila['USUA_SUBROGANTE']} sobre el cargo de {$fila['USUA_SUBROGADO']})");
        notificarSubrogacion($db, $codi, 'activacion');
    } else {
        $errores++;
        $log("ERROR activando la subrogación $codi: $error");
    }
}

// -----------------------------------------------------------------------------
// 2. Finalizar las subrogaciones vigentes cuyo período ya terminó
// -----------------------------------------------------------------------------
// Se incluyen también las PROGRAMADAS que nunca llegaron a activarse y cuyo
// período ya venció, para que no queden colgadas indefinidamente.
$sql = "select usua_subrogacion_codi, usua_subrogado, usua_subrogante, estado
          from usuarios_subrogacion
         where estado in (" . Subrogacion::PROGRAMADA . "," . Subrogacion::ACTIVA . ")
           and usua_fecha_fin <= now()
         order by usua_fecha_fin";

$rs = $db->conn->query($sql);
$porFinalizar = array();
while ($rs && !$rs->EOF) {
    $porFinalizar[] = $rs->fields;
    $rs->MoveNext();
}

foreach ($porFinalizar as $fila) {
    $codi  = (int)$fila['USUA_SUBROGACION_CODI'];
    $error = $subrogacion->finalizar($codi, 'Reasignado por finalización automática de la subrogación de puesto', 0);
    if ($error === "") {
        $finalizadas++;
        $log("Subrogación $codi FINALIZADA (documentos pendientes devueltos a {$fila['USUA_SUBROGADO']})");
        notificarSubrogacion($db, $codi, 'finalizacion');
    } else {
        $errores++;
        $log("ERROR finalizando la subrogación $codi: $error");
    }
}

$log("Resumen: $activadas activada(s), $finalizadas finalizada(s), $errores error(es).");
exit($errores > 0 ? 1 : 0);


/**
 * Avisa por correo al titular y al subrogante del cambio de estado.
 * Un fallo de correo no debe invalidar el cambio ya aplicado en la base.
 */
function notificarSubrogacion($db, $subrogacion_codi, $evento)
{
    include_once(dirname(__DIR__) . '/funciones.php');

    $subrogacion_codi = (int)$subrogacion_codi;
    $sql = "select s.usua_fecha_inicio, s.usua_fecha_fin
                 , tit.usua_nombre as titular_nombre, tit.usua_email as titular_email
                 , tit.usua_cargo  as titular_cargo
                 , sub.usua_nombre as subrogante_nombre, sub.usua_email as subrogante_email
              from usuarios_subrogacion s
              join usuario tit on tit.usua_codi = s.usua_subrogado
              join usuario sub on sub.usua_codi = s.usua_subrogante
             where s.usua_subrogacion_codi = $subrogacion_codi";

    $rs = $db->conn->query($sql);
    if (!$rs || $rs->EOF) return;

    $f = $rs->fields;
    $desde = substr($f['USUA_FECHA_INICIO'], 0, 16);
    $hasta = substr($f['USUA_FECHA_FIN'], 0, 16);

    if ($evento == 'activacion') {
        $asunto = "Quipux: Inicio de Subrogacion de Puesto.";
        $cuerpo = "Se ha iniciado la subrogaci&oacute;n del puesto <b>{$f['TITULAR_CARGO']}</b> "
                . "de <b>{$f['TITULAR_NOMBRE']}</b>, a cargo de <b>{$f['SUBROGANTE_NOMBRE']}</b>, "
                . "para el per&iacute;odo desde $desde hasta $hasta.";
    } else {
        $asunto = "Quipux: Fin de Subrogacion de Puesto.";
        $cuerpo = "Ha finalizado la subrogaci&oacute;n del puesto <b>{$f['TITULAR_CARGO']}</b> "
                . "de <b>{$f['TITULAR_NOMBRE']}</b>, que estuvo a cargo de "
                . "<b>{$f['SUBROGANTE_NOMBRE']}</b> desde $desde hasta $hasta.<br />&nbsp;<br />"
                . "Los documentos que quedaron pendientes de tr&aacute;mite fueron devueltos "
                . "autom&aacute;ticamente a la bandeja del titular del puesto.";
    }

    $mail = "<!DOCTYPE html><title>Subrogacion de Puesto - Quipux</title>"
          . "<body><center><h1>QUIPUX</h1><br /><h2>Sistema de Gesti&oacute;n Documental</h2></center>"
          . "Estimado(a),<br /><br />$cuerpo"
          . "<br /><br />Saludos cordiales,<br />Soporte Quipux."
          . "<br /><br /><b>Nota: </b>Este mensaje fue enviado autom&aacute;ticamente por el sistema, "
          . "por favor no lo responda.</body></html>";

    $raiz = dirname(__DIR__);
    if (!empty($f['TITULAR_EMAIL']))
        enviarMail($mail, $asunto, $f['TITULAR_EMAIL'], $f['TITULAR_NOMBRE'], $raiz);
    if (!empty($f['SUBROGANTE_EMAIL']))
        enviarMail($mail, $asunto, $f['SUBROGANTE_EMAIL'], $f['SUBROGANTE_NOMBRE'], $raiz);
}
