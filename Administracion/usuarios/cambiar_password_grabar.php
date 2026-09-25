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

include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/funciones.php');

if (isset($_POST["krd"])) {
    include_once(dirname(__DIR__, 2).'/include/db/ConnectionHandler.php');
    $db = new ConnectionHandler(dirname(__DIR__, 2));
    $krd = limpiar_sql(trim($_POST["krd"]));
    $accion_aceptar = "window.location='../../login.php'";
    $flag = false;
} else {
    session_start();
    include_once(dirname(__DIR__, 2).'/rec_session.php');
    $krd = $_SESSION["krd"];
    $forzado = !empty($_SESSION["forzar_cambio_clave"]);
    if ($forzado)
        $accion_aceptar = "window.location='../../index_frames.php'"; // primer ingreso: entrar al sistema
    elseif (substr($krd,0,1)=="U")
        $accion_aceptar = "window.location='../../Administracion/formAdministracion.php'";
    else
        $accion_aceptar = "window.location='../../cuerpo.php?carpeta=81&adodb_next_page=1'";
    $flag = true;
}
$pass_old = limpiar_sql(trim($_POST["contraold"]));
$pass_new = limpiar_sql(trim($_POST["contradrd"]));
$pass_ver = limpiar_sql(trim($_POST["contraver"]));


$isql = "select usua_pasw, usua_cedula from usuario where USUA_LOGIN = upper('$krd')";
$rs = $db->query($isql);
if ($rs->EOF) {
    echo html_error("Su usuario no fue encontrado. Por favor comun&iacute;quese con su administrador del sistema.");
    die ("");
}

$usr_pass = $rs->fields["USUA_PASW"] ?? $rs->fields["usua_pasw"];
$usr_cedula = $rs->fields["USUA_CEDULA"] ?? $rs->fields["usua_cedula"];

$md5_32 = $pass_old;
$md5_26 = substr($pass_old, 0, 26);
$md5_26_bug = substr($pass_old, 1, 26); // Support legacy passwords corrupted by the old index-1 bug

if ($pass_new == $pass_ver and $pass_new != "" and ($usr_pass === $md5_32 || $usr_pass === $md5_26 || $usr_pass === $md5_26_bug)) {
    // Save the FULL 32 character hash to prevent further truncations
    $isql = "update usuarios set usua_pasw='$pass_new' where usua_cedula='$usr_cedula'";
    $ok2 = $db->query($isql);
    $isql = "update ciudadano set ciu_pasw='$pass_new' where ciu_cedula='$usr_cedula'";
    $ok2 = $db->query($isql);
    $mensaje = "Su contrase&ntilde;a ha sido cambiada exitosamente.";
    if ($flag) $_SESSION["forzar_cambio_clave"] = 0; // ya no usa la clave inicial
} else {
    $mensaje = "No se pudo cambiar su contrase&ntilde;a.";
    if (!empty($forzado)) $accion_aceptar = "window.location='cambiar_password.php?forzar=1'";
}

?>

<!DOCTYPE html>
    <?php echo html_head(); /*Imprime el head definido para el sistema*/?>

    <body  id="page-bg" class="f-default light_slate">
    <div id="wrapper">
    <?php  if (!$flag) echo html_encabezado(); /*Imprime el encabezado del sistema si es nuevo usuario*/ ?>
    <div id="mainbody">
	<div class="shad-1">
	<div class="shad-2">
	<div class="shad-3">
	<div class="shad-4">
	<div class="shad-5">

        <form name='formulario' class="moduletable">
        <center>
            <br /><br />
            <table align="center" cellpadding="0" cellspacing="0" class="mainbody" border=0>
                <tr>
                    <td align="center">
                        <h3><?=$mensaje?></h3>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" align="center">
                        <br />
                        <input type='button' value='Aceptar' class='botones' name='btn_aceptar' onClick="<?=$accion_aceptar?>">
                    </td>
                </tr>
                <tr>
                    <td>&nbsp;</td>
                </tr>
            </table>
        </center>
        </form>
    </div>
    </div>
    </div>
    </div>
    </div>
    </div>
    <?php  if (!$flag) echo html_pie_pagina(); /*Imprime el pie de pagina del sistema si es nuevo usuario*/ ?>
    </div>

    </body>
</html>
