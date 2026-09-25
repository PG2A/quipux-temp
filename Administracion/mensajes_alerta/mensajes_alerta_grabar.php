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
 * @package    mensajes_alerta
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
if ($_SESSION["admin_institucion"] != 1 and $_SESSION["usua_codi"] != 0) {
    die("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');

$isUpdate = (isset($_POST["txt_bloq_codi"]) && $_POST["txt_bloq_codi"] > 0) ? true : false;

$fecha_inicio = limpiar_sql($_POST["txt_fecha_inicio"]);
$fecha_fin = limpiar_sql($_POST["txt_fecha_fin"]);
$estado = 0 + $_POST["txt_estado"];
$descripcion = limpiar_sql(base64_decode(base64_decode($_POST["txt_descripcion"])));
$mensaje = limpiar_sql(base64_decode(base64_decode($_POST["txt_mensaje"])),0);
$usua_acceso = limpiar_sql($_POST["txt_usua_acceso"]);
$tipo_mensaje = 0 + $_POST["txt_tipo_mensaje"];

if ($isUpdate) {
    $bloq_codi = 0 + $_POST["txt_bloq_codi"];
    $sql = "UPDATE bloqueo_sistema SET 
            fecha_inicio = '$fecha_inicio',
            fecha_fin = '$fecha_fin',
            estado = $estado,
            descripcion = '$descripcion',
            mensaje_usuario = '$mensaje',
            usua_acceso = '$usua_acceso',
            tipo_mensaje = $tipo_mensaje
            WHERE bloq_codi = $bloq_codi";
} else {
    $bloq_codi = $db->nextId("sec_bloqueo_sistema");
    $sql = "INSERT INTO bloqueo_sistema (bloq_codi, fecha_inicio, fecha_fin, estado, descripcion, mensaje_usuario, usua_acceso, tipo_mensaje) 
            VALUES ($bloq_codi, '$fecha_inicio', '$fecha_fin', $estado, '$descripcion', '$mensaje', '$usua_acceso', $tipo_mensaje)";
}

$ok = $db->conn->Execute($sql);
if (!$ok)
    echo "Existieron errores al momento de guardar los cambios.";
else
    echo "Los cambios se guardaron exitosamente.";
?>
<input type="hidden" name="txt_bloq_codi" id="txt_bloq_codi" value="<?=$bloq_codi?>">