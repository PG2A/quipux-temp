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
**  Aplica la vigencia de las cuentas de usuario interno.                   **
**                                                                          **
**  Desactiva (usua_esta = 0) las cuentas activas cuya fecha fin            **
**  (usuarios.usua_vigencia_hasta) ya pasó. El trigger de 'usuarios'        **
**  replica el cambio en la tabla 'usuario'.                                **
**                                                                          **
**  Las cuentas cuya fecha inicio aún no llega no se tocan: mientras no     **
**  estén vigentes, usuario_vigente() impide usarlas (login y combo         **
**  "Usuario:"), y quedan disponibles solas al llegar la fecha.             **
**                                                                          **
**  No reasigna documentos: si la cuenta tenía documentos pendientes se     **
**  informa en la salida para que un administrador los reasigne.            **
**                                                                          **
**  Programar una vez al día (p. ej. 00:05):                                **
**    5 0 * * *  php /ruta/quipux/cron/procesar_vigencia_usuarios.php      **
**  Uso manual:  php cron/procesar_vigencia_usuarios.php [--dry-run]       **
******************************************************************************/

include_once(dirname(__DIR__) . '/include/db/ConnectionHandler.php');
include_once(dirname(__DIR__) . '/config.php');

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);

$dry_run = in_array('--dry-run', $argv ?? array(), true);

$db = new ConnectionHandler(dirname(__DIR__));
$db->conn->SetFetchMode(ADODB_FETCH_ASSOC);
if (!$db->conn->_connectionID) die("Error: No se pudo conectar con la BDD\n");

$log = function ($mensaje) {
    echo date('Y-m-d H:i:s') . "  $mensaje\n";
};

// Cuentas activas con la fecha fin ya vencida (antes de hoy)
$sql = "select u.usua_codi, u.usua_nomb, u.usua_apellido, u.usua_cargo, u.depe_codi,
               to_char(u.usua_vigencia_hasta, 'YYYY-MM-DD') as hasta,
               (select count(*) from radicado r
                 where r.radi_usua_actu = u.usua_codi and r.esta_codi in (1,2)) as pendientes
          from usuarios u
         where u.usua_esta = 1
           and u.usua_vigencia_hasta is not null
           and u.usua_vigencia_hasta < current_date
         order by u.usua_vigencia_hasta, u.usua_codi";

$rs = $db->conn->query($sql);
$cuentas = array();
while ($rs && !$rs->EOF) {
    $cuentas[] = $rs->fields;
    $rs->MoveNext();
}

$desactivadas = 0;
$errores = 0;

foreach ($cuentas as $c) {
    $codi = (int)$c['USUA_CODI'];
    $nombre = trim($c['USUA_NOMB'] . ' ' . $c['USUA_APELLIDO']);
    $pend = (int)$c['PENDIENTES'];
    $aviso = $pend > 0 ? "  ATENCIÓN: $pend documento(s) pendiente(s) sin reasignar" : "";

    if ($dry_run) {
        $log("[dry-run] Se desactivaría $codi $nombre ({$c['USUA_CARGO']}), fin de vigencia {$c['HASTA']}$aviso");
        continue;
    }

    $obs = $db->conn->qstr("Desactivado automáticamente: fin de vigencia " . $c['HASTA']);
    $ok = $db->conn->Execute(
        "update usuarios
            set usua_esta = 0,
                usua_codi_actualiza = 0,
                usua_fecha_actualiza = now(),
                usua_obs_actualiza = $obs
          where usua_codi = $codi and usua_esta = 1");

    if ($ok) {
        $desactivadas++;
        $log("Desactivada $codi $nombre ({$c['USUA_CARGO']}), fin de vigencia {$c['HASTA']}$aviso");
    } else {
        $errores++;
        $log("ERROR desactivando $codi: " . $db->conn->ErrorMsg());
    }
}

$log("Resumen: " . count($cuentas) . " cuenta(s) vencida(s), $desactivadas desactivada(s), $errores error(es)"
     . ($dry_run ? " [dry-run: no se modificó nada]" : "") . ".");
exit($errores > 0 ? 1 : 0);
