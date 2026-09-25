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
    die ("Usted no tiene los permisos suficientes para acceder a esta p&aacute;gina.");
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

// Creamos la carpeta bodega
$dir_bodega = dirname(__DIR__, 2)."/bodega/$anio";

if (!is_dir($dir_bodega)) {
    $flag = mkdir($dir_bodega, 0755);
    grabar_log ("mkdir(\"$dir_bodega\", 0755);", "BODEGA", $flag);
    if (!$flag) die ("Error - No se pudo crear la bodega");
}

// Consulto las areas creadas en el sistema
$rs = $db->query("select depe_codi from dependencia");
// Creamos la estructura de la bodega
while(!$rs->EOF) {
    $carpeta = substr("000000".trim($rs->fields["DEPE_CODI"]),-6);
    $dir_carpeta = "$dir_bodega/$carpeta";

    if (!is_dir($dir_carpeta)) {
        $flag = mkdir("$dir_carpeta", 0755);
        if (!$flag) $mensaje .= "Error - Al crear la carpeta $dir_carpeta <br>";
        grabar_log ("mkdir(\"$dir_carpeta\", 0755);", "BODEGA", $flag);

        $dir_carpeta .= "/docs";
        if (!is_dir($dir_carpeta)) {
            $flag = mkdir("$dir_carpeta", 0755);
            if (!$flag) $mensaje .= "Error - Al crear la carpeta $dir_carpeta <br>";
            grabar_log ("mkdir(\"$dir_carpeta\", 0755);", "BODEGA", $flag);
        }
    }
    $rs->MoveNext();
}

if ($mensaje == "") $mensaje = "OK";

echo $mensaje;
?>