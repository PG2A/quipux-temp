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
 * Finalización anticipada de una subrogación de puesto.
 *
 * Delega en Subrogacion::finalizar(), la misma rutina que usa
 * cron/procesar_subrogaciones.php al vencer el período, para que ambos caminos
 * no puedan divergir.
 *
 * @package    subrogacion
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
if ($_SESSION["usua_admin_sistema"] != 1) {
    echo html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
    die("");
}
require_once(dirname(__DIR__, 2)."/funciones.php");
require_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once(dirname(__DIR__, 2).'/include/subrogacion/Subrogacion.php');

$usr_subrogante = 0 + ($_GET['codigo_subrogante'] ?? 0);
$usr_subrogado  = 0 + ($_GET['codigo_subrogado'] ?? 0);

$subrogacion = new Subrogacion($db);
$mensaje = "";
$exito   = false;

if ($usr_subrogante == 0 || $usr_subrogado == 0) {
    $mensaje = "No se recibieron los datos de la subrogación a desactivar.";
} else {
    // Se ubica el registro vigente entre estas dos personas.
    $sql = "select usua_subrogacion_codi
              from usuarios_subrogacion
             where usua_subrogante = $usr_subrogante
               and usua_subrogado  = $usr_subrogado
               and estado in (" . Subrogacion::PROGRAMADA . "," . Subrogacion::ACTIVA . ")
             order by usua_fecha_inicio desc
             limit 1";
    $rs = $db->conn->query($sql);

    if (!$rs || $rs->EOF) {
        $mensaje = "No se encontró una subrogación vigente para estos usuarios.";
    } else {
        $subrogacion_codi = (int)$rs->fields['USUA_SUBROGACION_CODI'];

        $error = $subrogacion->finalizar(
            $subrogacion_codi,
            'Reasignado por desactivación de la subrogación de puesto',
            (int)$_SESSION['usua_codi']);

        if ($error === "") {
            $exito = true;
        } else {
            // A diferencia de la versión anterior, que informaba éxito siempre,
            // aquí se muestra el motivo real del fallo.
            $mensaje = $error;
        }
    }
}
?>

       <center>
        <table width="100%" border="1" align="center" class="t_bordeGris">
            <tr>
            <td width="100%" height="30" class="listado2">
           <?php if ($exito) { ?>
                <span class=etexto><center><B>Se ha desactivado la subrogación satisfactoriamente.<br/>
                Los documentos pendientes de trámite fueron devueltos al titular del puesto.</B></center></span>
           <?php } else { ?>
                <span class=etexto><center><B>No se pudo desactivar la subrogación.</B>
                <br /><small style="color:red;"><?php echo htmlspecialchars($mensaje); ?></small>
                </center></span>
           <?php } ?>
            </td>
           <td height="30" class="listado2">
                <input name="btn_accion" class="botones" value="Regresar" onclick="window.location='../usuarios/cuerpoUsuario.php';" type="button"/>
            </td></tr>
       </table>
    </center>
