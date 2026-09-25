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
 * Descarga en un único archivo ZIP todos los archivos anexos de un documento.
 *
 * @package    anexos
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__).'/funciones.php');
include_once(dirname(__DIR__).'/obtenerdatos.php');
include_once(dirname(__DIR__).'/seguridad/obtener_nivel_seguridad.php');

function anexos_zip_error($mensaje) {
    while (ob_get_level()) ob_end_clean();
    die("<script>alert('".str_replace("'", "\\'", $mensaje)."');</script>");
}

if (!class_exists('ZipArchive'))
    anexos_zip_error("El servidor no tiene habilitada la extensión ZIP de PHP.\\nInforme a su administrador del sistema.");

$radi_nume = trim(limpiar_numero($_GET["radi_nume"] ?? ''));
if ($radi_nume == "")
    anexos_zip_error("Lo sentimos, no se encontró el documento solicitado.");

$nivel_seguridad_documento = obtener_nivel_seguridad_documento($db, $radi_nume);
if ($nivel_seguridad_documento < 2)
    anexos_zip_error("Usted no tiene los permisos suficientes para descargar estos archivos.");

$datos_radicado = ObtenerDatosRadicado($radi_nume, $db);
$radi_nume_temp = 0 + ($datos_radicado["radi_nume_temp"] ?? 0);

// Los mismos anexos que se listan en pantalla (documento y su temporal)
$sql = "select anex_codigo, anex_nombre, anex_path, arch_codi, anex_numero
        from anexos
        where anex_radi_nume in ($radi_nume,$radi_nume_temp) and anex_borrado='N'
        order by anex_fecha asc";
$rs = $db->query($sql);
if (!$rs or $rs->EOF)
    anexos_zip_error("El documento no tiene archivos anexos para descargar.");

$db_bodega = new ConnectionHandler(dirname(__DIR__), "bodega");
$dir_tmp = dirname(__DIR__)."/bodega/tmp";
if (!is_dir($dir_tmp)) mkdir($dir_tmp, 0777, true);

$zip_path = $dir_tmp."/anexos_".$radi_nume."_".getmypid()."_".time().".zip";
$zip = new ZipArchive();
if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true)
    anexos_zip_error("No se pudo crear el archivo comprimido en el servidor.");

$archivos_temporales = array();  // recuperados de la bodega, se eliminan al terminar
$nombres_usados = array();       // evita que dos anexos con el mismo nombre se pisen dentro del ZIP
$archivos_agregados = 0;
$archivos_omitidos = array();

while (!$rs->EOF) {
    $anex_codigo = $rs->fields["ANEX_CODIGO"];
    $anex_nombre = trim($rs->fields["ANEX_NOMBRE"]);
    $anex_path   = trim((string)$rs->fields["ANEX_PATH"]);
    $arch_codi   = 0 + $rs->fields["ARCH_CODI"];

    // Nombre dentro del ZIP: sin rutas y sin caracteres inválidos
    $nombre_zip = preg_replace('/[\\\\\/:*?"<>|]/', "_", $anex_nombre);
    if ($nombre_zip == "") $nombre_zip = $anex_codigo;
    if (isset($nombres_usados[strtolower($nombre_zip)])) {
        $punto = strrpos($nombre_zip, ".");
        $base  = ($punto === false) ? $nombre_zip : substr($nombre_zip, 0, $punto);
        $ext   = ($punto === false) ? "" : substr($nombre_zip, $punto);
        $nombre_zip = $base."_".(0 + $rs->fields["ANEX_NUMERO"]).$ext;
    }
    $nombres_usados[strtolower($nombre_zip)] = true;

    $archivo_origen = "";
    if ($anex_path != "" and is_file(dirname(__DIR__)."/bodega".$anex_path)) {
        // Repositorio en disco
        $archivo_origen = dirname(__DIR__)."/bodega".$anex_path;
    } elseif ($arch_codi > 0) {
        // Bodega en base de datos (documentos anteriores a la migración a disco)
        $rs_bodega = $db_bodega->query("select func_recuperar_archivo($arch_codi) as archivo");
        if ($rs_bodega and !$rs_bodega->EOF and $rs_bodega->fields["ARCHIVO"] != '') {
            $archivo_origen = $dir_tmp."/zip_".$arch_codi."_".getmypid().".tmp";
            file_put_contents($archivo_origen, base64_decode($rs_bodega->fields["ARCHIVO"]));
            $archivos_temporales[] = $archivo_origen;
        }
    }

    if ($archivo_origen != "" and $zip->addFile($archivo_origen, $nombre_zip)) {
        ++$archivos_agregados;
        grabar_log_descargar_zip($db, $radi_nume, $anex_codigo);
    } else {
        $archivos_omitidos[] = $anex_nombre;
    }

    $rs->MoveNext();
}

// Deja constancia dentro del ZIP de lo que no se pudo incluir, en lugar de omitirlo en silencio
if (count($archivos_omitidos) > 0)
    $zip->addFromString("ARCHIVOS_NO_INCLUIDOS.txt",
        "No se pudieron recuperar del repositorio los siguientes archivos:\r\n\r\n- ".implode("\r\n- ", $archivos_omitidos)."\r\n");

$zip->close();

if ($archivos_agregados == 0) {
    @unlink($zip_path);
    foreach ($archivos_temporales as $tmp) @unlink($tmp);
    anexos_zip_error("No se pudo recuperar ningún archivo anexo del repositorio.");
}

$nombre_descarga = trim((string)($datos_radicado["radi_nume_text"] ?? ''));
if ($nombre_descarga == "") $nombre_descarga = $radi_nume;
$nombre_descarga = preg_replace('/[\\\\\/:*?"<>|\s]/', "_", $nombre_descarga)."_anexos.zip";

while (ob_get_level()) ob_end_clean();
header("Content-Disposition: attachment; filename=\"$nombre_descarga\"");
header("Content-Type: application/zip");
header("Content-Length: ".filesize($zip_path));
header("Content-Transfer-Encoding: binary");
readfile($zip_path);

@unlink($zip_path);
foreach ($archivos_temporales as $tmp) @unlink($tmp);


// Registra la descarga en la bitácora, igual que anexos_descargar_archivo.php
function grabar_log_descargar_zip($db, $radi_nume, $anex_codigo) {
    $usua_codi = (int)$_SESSION["usua_codi"];
    $sql = "INSERT INTO log_archivo_descarga (usua_codi, fecha, radi_nume_radi, anex_codigo, arch_tipo, tipo_descarga)
            VALUES ($usua_codi, current_timestamp, '$radi_nume', ".$db->conn->qstr($anex_codigo).", 0, ".$db->conn->qstr("zip").")";
    $db->conn->Execute($sql);
}
