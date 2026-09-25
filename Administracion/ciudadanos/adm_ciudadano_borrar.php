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
 * @package    ciudadanos
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php'); //para traer funciones p_get y p_post
require_once(dirname(__DIR__, 2).'/funciones_interfaz.php'); //para traer funciones p_get y p_post

include_once("../ciudadanos/util_ciudadano.php");
$ciud = New Ciudadano($db);

if($_SESSION["usua_admin_sistema"]!=1 and $_SESSION["usua_perm_ciudadano"]!=1) {
    echo html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
    die("");
}
$ciu_codigo = 0 +limpiar_numero($_POST["ciu_codigo"]);

$rs = $db->conn->query("select * from ciudadano_tmp where ciu_codigo=$ciu_codigo and ciu_estado=1");
if (!$rs or $rs->EOF) {
    echo html_error("No se encont&oacute; el usuario en el sistema.");
    die("");
}

$usr_nombre = $rs->fields["CIU_NOMBRE"]." ".$rs->fields["CIU_APELLIDO"];
$usr_email = $rs->fields["CIU_EMAIL"];
$sql = "select i.inst_nombre
            ,c.ciu_nombre || ' ' || c.ciu_apellido as ciudadano_nombre
            ,c.ciu_email as ciudadano_email
        from ciudadano c left outer join institucion i on coalesce(c.inst_codi,0)=i.inst_codi
        where ciu_codigo=$ciu_codigo";

$rs = $db->conn->query($sql);
if (!$rs or $rs->EOF) {
    echo html_error("No se encont&oacute; el usuario en el sistema.");
    die("");
}

$usr_institucion = $rs->fields["INST_NOMBRE"];
$usr_nombreOri = $rs->fields["CIUDADANO_NOMBRE"];
$usr_emailOri = $rs->fields["CIUDADANO_EMAIL"];

$sql="select * from ciudadano_tmp where ciu_codigo = $ciu_codigo";
$rs_old=$db->conn->Execute($sql);
//actualizar ciudadano estado

$sqlUp="update ciudadano_tmp set ciu_estado=0 where ciu_codigo=$ciu_codigo";

$db->conn->query($sqlUp);
$rs_new=$db->conn->Execute($sql);
//$ciud->grabar_log_tabla('LOG_USR_CIUDADANOS',$rs_old, $rs_new, $_SESSION['usua_codi'],4);

$mail = "<!DOCTYPE html><title>Informaci&oacute;n Quipux</title>";
$mail .= "<body><center><h1>QUIPUX</h1><br /><h2>Sistema de Gesti&oacute;n Documental</h2><br /><br /></center>";
$mail .= "Estimado(a) $usr_nombreOri.<br /><br />";
$mail .= "Los cambios solicitados a la informaci&oacute;n de su usuario han sido rechazados.
          Por favor comuníquese con $cuenta_mail_soporte solicitando la aprobación de su solicitud de cambio de información.";
$mail .= "<br /><br />Saludos cordiales,<br /><br />Soporte Quipux.";
$mail .= "<br /><br /><b>Nota: </b>Este mensaje fue enviado autom&aacute;ticamente por el sistema, por favor no lo responda.";
$mail .= "<br />Si tiene alguna inquietud respecto a este mensaje, comun&iacute;quese con <a href='mailto:$cuenta_mail_soporte'>$cuenta_mail_soporte</a>";
$mail .= "</body></html>";
if ($usr_emailOri)
enviarMail($mail, "Quipux: Actualización de datos.", $usr_emailOri, $usr_nombreOri, dirname(__DIR__, 2));

?>

<!DOCTYPE html>
    <script>
        window.location='adm_ciudadano_confirmar.php';
    </script>
    <body>
    </body>
</html>
