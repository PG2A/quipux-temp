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
 * @package    tbasicas
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
if($_SESSION["usua_codi"]!=0) {
    die("Usted no tiene los permisos suficientes para acceder a esta p&aacute;gina.");
}


$anio = 0+date("Y");
$mes =  0+date("m");
if ($mes == 12) ++$anio;
$mensaje = "";


function grabar_log ($sentencia, $tabla, $flag) {
    global $db;
    $flag_log = 1;
    if (!$flag) $flag_log = 0;
    $fecha = $db->conn->sysTimeStamp;
    $sentencia = $db->conn->qstr($sentencia);
    $tabla = $db->conn->qstr($tabla);
    $usr = $_SESSION["usua_codi"];
    $sql = "insert into log (fecha, usua_codi, tabla, sentencia, tipo) values ($fecha,$usr,$tabla,$sentencia,$flag_log)";
    $db->query($sql);
    return;
}

// Inicializamos la secuencia del número de radicado
$flag = $db->query("select pg_catalog.setval('sec_radi_nume_radi', 1, true);");
if (!$flag) $mensaje .= "Error - Al modificar secuencia sec_radi_nume_radi <br>";
grabar_log ("select pg_catalog.setval('sec_radi_nume_radi', 1, true);", "SECUENCIA", $flag);


// Actualizamos las secuencias para los numeros temporales de los documentos
$sql = "update radicado_sec_temp set secuencia=0 where secuencia>0";
$flag = $db->query($sql);
grabar_log ($sql, "RADICADO_SEC_TEMP", $flag);
if (!$flag) $mensaje .= "Error - No se pudo inicializar secuencias en tabla RADICADO_SEC_TEMP";


// Actualizamos las secuencias para el texto de los documentos
$sql = "update formato_numeracion set fn_contador=0, fn_formato=E'inst-dep-anio-secuencial-tipodoc'";
$flag = $db->query($sql);
grabar_log ($sql, "FORMATO_NUMERACION", $flag);
if (!$flag) $mensaje .= "Error - No se pudo inicializar secuencias en tabla FORMATO_NUMERACION";

//Desactivacion de usuarios administradores
//$rs_adm = $db->query("select usua_codi, usua_login from usuarios where usua_login like 'UADM%' and usua_codi<>0");
//while ($rs_adm && !$rs_adm->EOF) {
//    $sql = "update usuarios set usua_login='l'||usua_codi::text
//                 , usua_cedula=substr(usua_cedula,1,10)||'-'||usua_codi::text
//                 , usua_esta=0
//            where usua_codi=".$rs_adm->fields["USUA_CODI"];
//    $flag = $db->query($sql);
//    grabar_log ($sql, "USUARIOS", $flag);
//    if (!$flag) $mensaje .= "Error - No se pudo desactivar el usuario ".$rs_adm->fields["USUA_LOGIN"];
//    $rs_adm->MoveNext();
//}

if ($mensaje == "") $mensaje = "OK";

echo $mensaje;
?>