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
 * @package    anexos
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__).'/funciones.php');

$db_bodega = new ConnectionHandler(dirname(__DIR__),"bodega");

$radi_nume = trim(limpiar_numero($_GET["radi_nume"]));
$anex_codigo = trim(limpiar_sql($_GET["anex_codigo"]));
$arch_tipo = 0 + $_GET["arch_tipo"];
$tipo_descarga = substr(trim(limpiar_sql($_GET["tipo_descarga"])),0,10); //download, embeded

grabar_log_descargar_archivo ($db, $radi_nume, $anex_codigo, $arch_tipo, $tipo_descarga);

if ($anex_codigo == "" and $radi_nume == "")
    die("<script>alert('Lo sentimos, no se encontró el archivo solicitado.');</script>");

if ($anex_codigo != "") {
    $rs_arch = $db->query("select arch_codi, arch_codi_firma, anex_nombre, anex_path, anex_radi_nume from anexos where anex_codigo='$anex_codigo'");
    if (!$rs_arch or $rs_arch->EOF)
        die("<script>alert('Lo sentimos, no se encontró el archivo solicitado.');</script>");

    // Formateamos el nombre del archivo
    $arch_nombre = str_replace(" ", "_", strtolower($rs_arch->fields["ANEX_NOMBRE"]));
    $arch_path   = trim($rs_arch->fields["ANEX_PATH"]);
    $arch_codi   = 0+$rs_arch->fields["ARCH_CODI_FIRMA"];
    $radi_nume   = $rs_arch->fields["ANEX_RADI_NUME"];

    //obtenemos la extensión del archivo
    $tmp = explode(".", $arch_nombre);
    $i = 0;
    $arch_ext = "";
    do {
        ++$i;
        $arch_ext = ".".trim($tmp[count($tmp)-$i]) . $arch_ext;
    } while (strtolower(trim($tmp[count($tmp)-$i]))=="p7m");

    if ($arch_tipo==0) {
        $arch_nombre = str_ireplace(".p7m", "", $arch_nombre);
        $arch_path   = str_ireplace(".p7m", "", $arch_path);
        $arch_ext    = str_ireplace(".p7m", "", $arch_ext);
        $arch_codi   = 0 + $rs_arch->fields["ARCH_CODI"];
    }
} else {
    // Verificamos si exite el anexo
    $rs_arch = $db->query("select arch_codi, arch_codi_firma, radi_nume_text, radi_path from radicado where radi_nume_radi=$radi_nume");
    if (!$rs_arch or $rs_arch->EOF)
        die("<script>alert('Lo sentimos, no se encontró el archivo solicitado.');</script>");

    // Formateamos el nombre del archivo
    $arch_path   = trim((string)($rs_arch->fields["RADI_PATH"] ?? ''));
    $arch_ext = trim((string)substr ($arch_path, 0+strpos($arch_path, ".")));
    if ($arch_ext == "") $arch_ext = ".pdf.p7m";
    $arch_nombre     = str_replace(" ", "_", $rs_arch->fields["RADI_NUME_TEXT"]).$arch_ext;
    $arch_codi_firma = 0+$rs_arch->fields["ARCH_CODI_FIRMA"];
    $arch_codi       = 0+$rs_arch->fields["ARCH_CODI_FIRMA"];
    if ($arch_tipo==0) {
        $arch_nombre = str_ireplace(".p7m", "", $arch_nombre);
        $arch_path   = str_ireplace(".p7m", "", $arch_path);
        $arch_ext    = str_ireplace(".p7m", "", $arch_ext);
        $arch_codi   = 0 + $rs_arch->fields["ARCH_CODI"];
    }
    if ($arch_path=="" and $arch_codi==0 and $arch_codi_firma==0) {
        include_once(dirname(__DIR__)."/plantillas/generar_documento.php");
        $doc = New GenerarDocumento($db);
        $arch_path = $doc->GenerarPDF($radi_nume);
        if (substr($arch_path, 0 , 1) != "/") {
            $arch_codi = $arch_path;
            $arch_path = "";
        }
    }
}

$mime = get_mime_tipe($arch_nombre);

if ($arch_codi > 0) {
    $arch_path = dirname(__DIR__)."/bodega/tmp/$arch_codi$arch_ext";
    if (!is_file($arch_path)) {
        $rs_bodega = $db_bodega->query("select func_recuperar_archivo($arch_codi) as archivo");
        if (!$rs_bodega or $rs_bodega->EOF or $rs_bodega->fields["ARCHIVO"]=='')
            die("<script>alert('Lo sentimos, no se pudo descargar el archivo solicitado.');</script>");
        file_put_contents($arch_path, base64_decode($rs_bodega->fields["ARCHIVO"]));
        $tamanio = strlen($rs_bodega->fields["ARCHIVO"])/8*6;
    } else {
        $tamanio = filesize($arch_path);
    }
} elseif ($arch_path != "") {
    $arch_path = dirname(__DIR__)."/bodega$arch_path";
    if (!is_file($arch_path)) {
        error_log("DOWNLOAD ERROR - is_file failed for: " . $arch_path);
        die("<script>alert('Lo sentimos, no se pudo descargar el archivo: " . addslashes($arch_path) . "');</script>");
    }
    $tamanio = filesize($arch_path);
} else {
    include_once(dirname(__DIR__)."/interconexion/generar_pdf.php");
    quipux_log_pdf('anexos_descargar_archivo', "radicado=$radi_nume anexo=$anex_codigo tipo=$arch_tipo sin arch_codi ni radi_path y la generacion al vuelo no devolvio archivo");
    die("<script>alert('El documento a&uacute;n no tiene un PDF generado y no se pudo generar en este momento. Vuelva a intentarlo; si persiste, informe a la DTIC indicando el n&uacute;mero del documento.');</script>");
}

if (substr($tipo_descarga,0,7) == "embeded") {
    //echo "<hr>$arch_nombre<hr>"; die("");
//    if ($arch_codi > 0) {
//        if (!verificar_navegador_firefox() or verificar_dispositivo_movil() or $tipo_descarga=="embeded_ar") {
//            $url = __DIR__."/bodega/tmp/$radi_nume$anex_codigo$arch_ext";
//            file_put_contents($url, base64_decode($rs_bodega->fields["ARCHIVO"]));
//        } else {
//            $url = "data:$mime;base64," . $rs_bodega->fields["ARCHIVO"];
//        }
//    } else {
        $url = "./anexos_descargar_archivo.php?radi_nume=$radi_nume&anex_codigo=$anex_codigo&arch_tipo=$arch_tipo&tipo_descarga=download";
//    }
    // Extensión completa: substr(-3) no distingue ".jpeg" ni ".webp"
    $arch_extension_ver = (strrpos($arch_nombre, ".") === false) ? "" : strtolower(substr($arch_nombre, 1+strrpos($arch_nombre, ".")));
    switch ($arch_extension_ver) {
        case "pdf":
            if (substr($tipo_descarga, -2) == "ar") //Si tiene el plugin de Acrobat Reader
                echo "<style>html,body{height:100%; margin:0; padding:0;}</style>
                      <embed src='$url' type='text/html; charset=UTF-8' width='100%' height='100%'></embed>";
            else
                include dirname(__DIR__)."/js/pdf_js/visor_pdf.php";
            break;
        case "png":
        case "jpg":
        case "jpeg":
        case "gif":
        case "tif":
        case "bmp":
        case "webp":
            // La imagen ocupa todo el alto del popup manteniendo su proporción
            echo "<style>html,body{height:100%; margin:0; padding:0; background-color:#FFFFFF;}</style>
                  <img src='$url' alt='No se puede mostrar la imagen del documento'
                       style='width:100%; height:100%; display:block; object-fit:contain; object-position:center;'>";
            break;
        case "txt":
//            if ($arch_codi > 0) {
//                echo base64_decode($rs_bodega->fields["ARCHIVO"]);
//            } else {
                readfile($arch_path);
//            }
            break;
        default:
            echo "<center><br><br>Lo sentimos, no se puede mostrar la imagen del documento.
                        <br>Por favor haga click sobre el botón descargar</center>";
            break;
    }
} else {
    header("Content-Disposition: attachment; filename=$arch_nombre");
    header("Content-Length: $tamanio");
    header("Content-Type: $mime");
    header("Content-Transfer-Encoding: binary");

//    if ($arch_codi > 0) {
//        echo base64_decode($rs_bodega->fields["ARCHIVO"]);
//    } else {
	ob_clean();
        flush();
        readfile($arch_path);
//    }
}

// Registramos el usuario que descargo el archivo en un log
function grabar_log_descargar_archivo ($db, $radi_nume, $anex_codigo, $arch_tipo=0, $tipo_descarga="") {
    $usua_codi = (int)$_SESSION["usua_codi"];
    $radi_nume_radi = $radi_nume != "" ? "'$radi_nume'" : "null";
    $anex_codigo_val = $anex_codigo != "" ? $db->conn->qstr($anex_codigo) : "null";
    $arch_tipo = (int)$arch_tipo;
    $tipo_descarga_val = trim((string)$tipo_descarga) == "" ? $db->conn->qstr("download") : $db->conn->qstr($tipo_descarga);

    $sql = "INSERT INTO log_archivo_descarga (usua_codi, fecha, radi_nume_radi, anex_codigo, arch_tipo, tipo_descarga) 
            VALUES ($usua_codi, current_timestamp, $radi_nume_radi, $anex_codigo_val, $arch_tipo, $tipo_descarga_val)";
    
    $db->conn->Execute($sql);
}

?>
