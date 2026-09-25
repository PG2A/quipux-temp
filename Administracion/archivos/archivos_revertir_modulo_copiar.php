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
 * @package    archivos
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

include_once(dirname(__DIR__, 2).'/config.php');
include_once(dirname(__DIR__, 2).'/include/db/ConnectionHandler.php');

error_reporting(7);
$db = new ConnectionHandler(dirname(__DIR__, 2));
$db->conn->SetFetchMode(ADODB_FETCH_ASSOC);

$db_bodega = new ConnectionHandler(dirname(__DIR__, 2), "bodega");
$db_bodega->conn->SetFetchMode(ADODB_FETCH_ASSOC);

$sql = "select arch_codi, nombre from archivo where arch_codi>(select arch_codi from tmp_revertir) order by arch_codi asc limit 1";
$rs = $db_bodega->query($sql);

if (!$rs or $rs->EOF) {
    sleep(15);
    die ("Error - No se encuentran m&aacute;s archivos");
}

$archivo_codigo   = $rs->fields["ARCH_CODI"];
$archivo_nombre = $rs->fields["NOMBRE"];

//Validamos la extensión del archivo
$tmp = explode(".", $archivo_nombre);
$flag_firma = false;
$i = 1;
$archivo_extension = "";
do {
    $archivo_extension_tmp = strtoupper(trim($tmp[count($tmp)-$i]));
    $archivo_extension = ".".trim($tmp[count($tmp)-$i]) . $archivo_extension;
    if ($archivo_extension_tmp == "P7M") $flag_firma = true;
    ++$i;
} while ($archivo_extension_tmp=="P7M");


$archivo_path = __DIR__."/bodega/2013/reversa/$archivo_codigo$archivo_extension";

$rs_arch = $db_bodega->query("select func_recuperar_archivo($archivo_codigo) as archivo");
if (!$rs_arch or $rs_arch->EOF or $rs_arch->fields["ARCHIVO"]=="") {
    sleep(15);
    die ("Error - No se pudo recuperar el archivo $archivo_codigo");
}

$ok = file_put_contents($archivo_path, base64_decode($rs_arch->fields["ARCHIVO"]));
if (!$ok) {
    sleep(15);
    die ("Error - No se pudo grabar el archivo $archivo_codigo en la direcci&oacute;n &quot;$archivo_path&quot;");
}

$sql = "update tmp_revertir set arch_codi=$archivo_codigo";
$rs = $db_bodega->query($sql);

$sql = "insert into tmp_revertir_bodega (arch_codi, arch_path) values ($archivo_codigo,E'/2013/reversa/$archivo_codigo$archivo_extension')";
$rs = $db->query($sql);

?>
<table border="0" width="100%" cellpadding="0" cellspacing="2">
    <tr>
        <td width="30%" class="titulos2">Codigo Archivo:</td>
        <td width="70%" class="listado2"><?=$archivo_codigo?></td>
    </tr>
    <tr>
        <td class="titulos2">Nombre:</td>
        <td class="listado2"><?=$archivo_nombre?></td>
    </tr>
    <tr>
        <td class="titulos2">Path:</td>
        <td class="listado2"><?=$archivo_path?></td>
    </tr>
    <tr>
        <td class="titulos2">Fecha:</td>
        <td class="listado2"><?=date("Y-m-d H:i:s")?></td>
    </tr>
</table>
