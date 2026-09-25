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
 * @package    usuarios
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Los valores de config.php llegan vacíos cuando ese archivo se cargó dentro de
// ConnectionHandler; $CFG es el único global que expone, así que se toma de ahí.
global $CFG;
if (empty($nombre_servidor))     $nombre_servidor     = $CFG->nombre_servidor ?? "http://localhost/quipux";
if (empty($cuenta_mail_soporte)) $cuenta_mail_soporte = $CFG->cuenta_mail_soporte ?? "soporte@example.com";
$support_mail_account = $support_mail_account ?? $cuenta_mail_soporte;
$ruta_raiz = $ruta_raiz ?? dirname(__DIR__, 2);

$sql = "select usua_codi, usua_cedula, usua_email, usua_nombre, tipo_usuario
        from usuario
        where usua_esta=1 and usua_login like upper('$usr_login')
        order by tipo_usuario asc";

$rs_pass = $db->conn->query($sql);
if (!$rs_pass or $rs_pass->EOF)
    die(html_error("No se encontr&oacute; el usuario en el sistema."));

$usr_cedula = $rs_pass->fields["USUA_CEDULA"];
$usr_nombre = $rs_pass->fields["USUA_NOMBRE"];
$usr_tipo = $rs_pass->fields["TIPO_USUARIO"];
$cambio_pass_usr_codigo = $rs_pass->fields["USUA_CODI"];

$flag_ciudadano = true;
$usr_email = "";
while (!$rs_pass->EOF) {
    // Selecciono los mails de todas las cuentas; si es funcionario ya no se ponen las cuentas de los ciudadanos
    if ($rs_pass->fields["TIPO_USUARIO"]==1) $flag_ciudadano = false;
    if (trim($rs_pass->fields["USUA_EMAIL"])!="" and ($rs_pass->fields["TIPO_USUARIO"]==1 or $flag_ciudadano)) {
        $usr_email .= ",".trim($rs_pass->fields["USUA_EMAIL"]);
    }
    $rs_pass->MoveNext();
}
$usr_email = trim ($usr_email, ",");


if ($usr_email != "") {
    $clave = generar_password(30);
    $hash_clave = md5($clave);
    $sql = "update usuarios set usua_nuevo=1, usua_pasw='$hash_clave' where usua_cedula='$usr_cedula'";
    $ok2 = $db->query($sql);
    $sql = "update ciudadano set ciu_nuevo=1, ciu_pasw='$hash_clave' where ciu_cedula='$usr_cedula'";
    $db->query($sql);
    $direccion = "$nombre_servidor/usuarionuevo.php?krd=".base64_encode($usr_login)."&code=".base64_encode($clave);

    // Enviamos un mail de notificación
    $mail = "<!DOCTYPE html><title>Informaci&oacute;n Quipux</title>";
    $mail .= "<body><center><h1>QUIPUX</h1><br /><h2>Sistema de Gesti&oacute;n Documental</h2><br /><br /></center>";
    $mail .= "Estimado(a) $usr_nombre.<br /><br />";
    $mail .= "El Sistema de Gesti&oacute;n Documental Quipux le da la bienvenida. Su cuenta ha sido registrada con el usuario &quot;<b>".substr($usr_login,1)."</b>&quot;. <br /><br />";
    $mail .= "Para poder acceder al sistema deber&aacute; definir su contrase&ntilde;a ingresando a:<br />
              <a href='$direccion' target='_blank'>$direccion</a>";
    $mail .= "<br /><br />Saludos cordiales,<br /><br />Soporte Quipux.";
    $mail .= "<br /><br /><b>Nota: </b>Este mensaje fue enviado autom&aacute;ticamente por el sistema, por favor no lo responda.";
    $mail .= "<br />Si tiene alguna inquietud respecto a este mensaje, comun&iacute;quese con <a href='mailto:$cuenta_mail_soporte'>$cuenta_mail_soporte</a>";
    $mail .= "</body></html>";
    // Sin este correo el usuario nunca conoce el enlace para definir su contraseña
    // (la clave temporal es aleatoria y solo se guarda como hash). Estuvo
    // comentado desde abril de 2019.
    enviarMail($mail, "Quipux: Cambio de contraseña.", $usr_email, $usr_nombre, $ruta_raiz);
}
?>
