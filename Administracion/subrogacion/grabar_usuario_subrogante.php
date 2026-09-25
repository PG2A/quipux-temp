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
 * Registro de una subrogación de puesto.
 *
 * A diferencia de la versión anterior, aquí NO se crea ni se reactiva ningún
 * usuario: la subrogación es un contexto de actuación con vigencia sobre la
 * cuenta real del subrogante. Tampoco se copian permisos ni se traspasan
 * documentos en bloque:
 *
 *  - los permisos del cargo se obtienen al asumir la identidad del puesto
 *    (reiniciar_session.php recarga permiso_usuario del usuario activo)
 *  - los documentos que lleguen durante el período se desvían uno a uno en los
 *    puntos de entrega de Tx.php, generando además la copia de lectura del titular
 *  - la activación y la finalización las aplica cron/procesar_subrogaciones.php
 *
 * @package    subrogacion
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php");
require_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/config.php');
include_once(dirname(__DIR__, 2).'/include/subrogacion/Subrogacion.php');

if ($_SESSION["usua_admin_sistema"] != 1) {
    echo html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
    die("");
}

$subrogacion = new Subrogacion($db);

$usr_subrogante = 0 + ($_POST['usr_subrogante'] ?? 0);
$usr_subrogado  = 0 + ($_POST['usr_subrogado'] ?? 0);

$txt_fecha_desde = trim($_POST['txt_fecha_desde'] ?? '');
$txt_fecha_hasta = trim($_POST['txt_fecha_hasta'] ?? '');
$txt_hora_desde  = !empty($_POST['txt_hora_desde']) ? $_POST['txt_hora_desde'] : '00:00';
$txt_hora_hasta  = !empty($_POST['txt_hora_hasta']) ? $_POST['txt_hora_hasta'] : '23:59';

$desde = $txt_fecha_desde . " " . $txt_hora_desde . ":00";
$hasta = $txt_fecha_hasta . " " . $txt_hora_hasta . ":00";

$mensaje = "";
$exito   = false;
$programada = false;

// -----------------------------------------------------------------------------
// Validaciones
// -----------------------------------------------------------------------------
$datosSubrogado  = null;
$datosSubrogante = null;

if ($usr_subrogante <= 0 || $usr_subrogado <= 0) {
    $mensaje = "Debe seleccionar el servidor público subrogado y el subrogante.";
} elseif ($usr_subrogante == $usr_subrogado) {
    $mensaje = "El funcionario subrogante no puede ser el mismo que el subrogado.";
} elseif ($txt_fecha_desde == '' || $txt_fecha_hasta == '') {
    $mensaje = "Debe indicar el período de la subrogación.";
} elseif (strtotime($hasta) <= strtotime($desde)) {
    $mensaje = "La fecha de fin debe ser posterior a la fecha de inicio.";
} else {
    $rsSubrogado = $db->conn->query(
        "select usua_codi, usua_nombre, usua_cargo, usua_email, depe_codi, cargo_tipo, inst_codi
           from usuario where usua_codi = $usr_subrogado");
    $datosSubrogado = ($rsSubrogado && !$rsSubrogado->EOF) ? $rsSubrogado->fields : null;

    $rsSubrogante = $db->conn->query(
        "select usua_codi, usua_nombre, usua_cargo, usua_email, usua_esta
           from usuario where usua_codi = $usr_subrogante");
    $datosSubrogante = ($rsSubrogante && !$rsSubrogante->EOF) ? $rsSubrogante->fields : null;

    if (!$datosSubrogado || !$datosSubrogante) {
        $mensaje = "No se pudieron recuperar los datos de los funcionarios seleccionados.";
    } elseif ((int)$datosSubrogante['USUA_ESTA'] != 1) {
        $mensaje = "El funcionario subrogante debe estar activo.";
    } elseif (!$subrogacion->cargoPermiteSubrogacion($datosSubrogado['CARGO_TIPO'], $datosSubrogado['DEPE_CODI'])) {
        // Requisito: la subrogación sólo se habilita para el nivel jerárquico
        // definido en subrogacion_cargo_permitido.
        $mensaje = "El puesto de " . $datosSubrogado['USUA_NOMBRE']
                 . " no corresponde a un nivel jerárquico habilitado para subrogación.";
    } else {
        // Una misma persona no puede tener dos subrogaciones solapadas, ni como
        // titular ni como subrogante.
        $sqlSolape = "select usua_subrogacion_codi
                        from usuarios_subrogacion
                       where estado in (" . Subrogacion::PROGRAMADA . "," . Subrogacion::ACTIVA . ")
                         and (usua_subrogado = $usr_subrogado or usua_subrogante = $usr_subrogante)
                         and usua_fecha_inicio <= " . $db->conn->qstr($hasta) . "::timestamp
                         and usua_fecha_fin    >= " . $db->conn->qstr($desde) . "::timestamp
                       limit 1";
        $rsSolape = $db->conn->query($sqlSolape);

        if ($rsSolape && !$rsSolape->EOF) {
            $mensaje = "Ya existe una subrogación registrada que se cruza con este período.";
        } else {
            // -----------------------------------------------------------------
            // Registro
            // -----------------------------------------------------------------
            // Si el período ya comenzó entra vigente de inmediato; si empieza en
            // el futuro queda PROGRAMADA y el cron la activará en su fecha.
            $programada = (strtotime($desde) > time());
            $estado  = $programada ? Subrogacion::PROGRAMADA : Subrogacion::ACTIVA;
            $visible = $programada ? 0 : 1;

            $rec = array();
            $rec['USUA_SUBROGADO']          = $usr_subrogado;
            $rec['USUA_SUBROGANTE']         = $usr_subrogante;
            $rec['USUA_FECHA_INICIO']       = $db->conn->qstr($desde);
            $rec['USUA_FECHA_FIN']          = $db->conn->qstr($hasta);
            $rec['USUA_VISIBLE']            = $visible;
            $rec['ESTADO']                  = $estado;
            $rec['USUA_OBSERVACION']        = $db->conn->qstr("Subrogacion de Puesto");
            $rec['USUA_FECHA_ACTUALIZACION']= $db->conn->sysTimeStamp;
            $rec['USUA_CODI_ACTUALIZA']     = (int)$_SESSION['usua_codi'];
            if (!$programada) $rec['FECHA_ACTIVACION'] = $db->conn->sysTimeStamp;

            // RETURNING evita depender de Insert_ID(), que en PostgreSQL vía
            // ADOdb no siempre resuelve la secuencia correcta.
            $sqlInsert = "INSERT INTO USUARIOS_SUBROGACION (" . implode(", ", array_keys($rec)) . ")"
                       . " VALUES (" . implode(", ", array_values($rec)) . ")"
                       . " RETURNING usua_subrogacion_codi";

            $db->conn->BeginTrans();
            $ok = $db->conn->Execute($sqlInsert);

            if (!$ok || $ok->EOF) {
                $mensaje = $db->conn->ErrorMsg();
                $db->conn->RollbackTrans();
                error_log("Error insert USUARIOS_SUBROGACION: $mensaje | SQL: $sqlInsert");
            } else {
                $subrogacion_codi = (int)$ok->fields['USUA_SUBROGACION_CODI'];

                if (!$programada) {
                    // El contexto sólo aparece en el combo "Usuario:" cuando el
                    // subrogante vuelve a autenticarse.
                    $subrogacion->invalidarSesion($usr_subrogante);
                    $subrogacion->auditar($subrogacion_codi, (int)$_SESSION['usua_codi'], $usr_subrogado, 'ACTIVACION');
                }

                $db->conn->CommitTrans();
                $exito = true;
            }
        }
    }
}

// -----------------------------------------------------------------------------
// Notificación por correo
// -----------------------------------------------------------------------------
if ($exito) {
    global $cuenta_mail_soporte;
    if (empty($cuenta_mail_soporte)) $cuenta_mail_soporte = "sgd@ucuenca.edu.ec";

    $rsInst = $db->conn->query("select inst_nombre from institucion where inst_codi = " . (0 + $datosSubrogado['INST_CODI']));
    $instNombre = ($rsInst && !$rsInst->EOF) ? $rsInst->fields['INST_NOMBRE'] : '';

    $estadoTexto = $programada
        ? "quedó <b>programada</b> y se activará automáticamente al iniciar el período"
        : "se encuentra <b>vigente</b>";

    $mail  = "<!DOCTYPE html><title>Subrogacion de Puesto - Quipux</title>";
    $mail .= "<body><center><h1>QUIPUX</h1><br /><h2>Sistema de Gesti&oacute;n Documental</h2><br /><br /></center>";
    $mail .= "Estimado(a). <br /><br />";
    $mail .= "Se ha registrado una Subrogaci&oacute;n de Puesto en la Instituci&oacute;n <b>$instNombre</b>, ";
    $mail .= "que $estadoTexto, para el per&iacute;odo desde: $desde hasta: $hasta<br>&nbsp;<br>";
    $mail .= "<b>Subrogante: </b>" . $datosSubrogante['USUA_NOMBRE'] . "<br>&nbsp;<br>";
    $mail .= "<b>Puesto subrogado: </b>" . $datosSubrogado['USUA_CARGO'] . " (" . $datosSubrogado['USUA_NOMBRE'] . ")";
    $mail .= "<br /><br />Durante el per&iacute;odo, el subrogante podr&aacute; seleccionar el puesto en el ";
    $mail .= "men&uacute; <b>Usuario:</b> de la parte superior para acceder a las bandejas y funciones del cargo.";
    $mail .= "<br /><br />Saludos cordiales,<br /><br />Soporte Quipux.";
    $mail .= "<br /><br /><b>Nota: </b>Este mensaje fue enviado autom&aacute;ticamente por el sistema, por favor no lo responda.";
    $mail .= "<br />Si tiene alguna inquietud respecto a este mensaje, comun&iacute;quese con <a href='mailto:$cuenta_mail_soporte'>$cuenta_mail_soporte</a>";
    $mail .= "</body></html>";

    $raiz = dirname(__DIR__, 2);
    if (!empty($datosSubrogante['USUA_EMAIL']))
        enviarMail($mail, "Quipux: Subrogacion de Puesto.", $datosSubrogante['USUA_EMAIL'], $datosSubrogante['USUA_NOMBRE'], $raiz);
    if (!empty($datosSubrogado['USUA_EMAIL']))
        enviarMail($mail, "Quipux: Subrogacion de Puesto.", $datosSubrogado['USUA_EMAIL'], $datosSubrogado['USUA_NOMBRE'], $raiz);

    $rsAdmin = $db->conn->query("select usua_email from usuarios where usua_login = 'UADMINISTRADOR'");
    if ($rsAdmin && !$rsAdmin->EOF && !empty($rsAdmin->fields['USUA_EMAIL']))
        enviarMail($mail, "Quipux: Subrogacion de Puesto.", $rsAdmin->fields['USUA_EMAIL'], "Administrador Quipux", $raiz);
}
?>

<!DOCTYPE html>
    <?php echo html_head(); //Imprime el head definido para el sistema ?>
<body>
    <form name="frmConfirmaCreacion" action="../usuarios/mnuUsuarios.php" method="post">
    <center>
        <br /><br /><br />
        <table width="45%" border="2" align="center" class="t_bordeGris">
            <tr>
            <td width="100%" height="30" class="listado2">
           <?php if ($exito) { ?>
                <span class=etexto><center><B>
                La subrogación del puesto de <?php echo htmlspecialchars($datosSubrogado['USUA_NOMBRE']); ?>
                a favor de <?php echo htmlspecialchars($datosSubrogante['USUA_NOMBRE']); ?>
                se registró correctamente.</B><br /><br />
                <?php if ($programada) { ?>
                    Quedó <b>programada</b>: se activará automáticamente el <?php echo htmlspecialchars($desde); ?>.
                <?php } else { ?>
                    Se encuentra <b>vigente</b>. El subrogante deberá volver a iniciar sesión para ver el
                    puesto en el menú <b>Usuario:</b>.
                <?php } ?>
                </center></span>
           <?php } else { ?>
                <span class=etexto><center><B>No se registró la subrogación.</B>
                <br /><br /><small style="color:red;"><?php echo htmlspecialchars($mensaje); ?></small>
                </center></span>
           <?php } ?>
            </td>
            </tr>
            <tr>
            <td height="30" class="listado2">
                <center><input class="botones" type="submit" name="Submit" value="Aceptar"></center>
            </td>
            </tr>
        </table>
    </center>
    </form>
</body>
</html>
