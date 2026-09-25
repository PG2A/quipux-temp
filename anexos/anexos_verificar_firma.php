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
include_once(dirname(__DIR__).'/include/tx/Firma_Digital.php');

$db_bodega = new ConnectionHandler(dirname(__DIR__),"bodega");

$anex_codigo = trim(limpiar_sql($_POST["anex_codigo"]));
$radi_nume   = trim(limpiar_numero($_POST["radi_nume"]));

if ($anex_codigo != "") {
    $rs_archivo = $db->query("select arch_codi, arch_codi_firma, anex_nombre, anex_path from anexos where anex_codigo='$anex_codigo'");
    if (!$rs_archivo or $rs_archivo->EOF)
        die(imprimir_resultado_firma("Lo sentimos, no se encontr&oacute; el archivo solicitado."));
    $arch_codi = 0+$rs_archivo->fields["ARCH_CODI"];
    $arch_codi_firma = 0+$rs_archivo->fields["ARCH_CODI_FIRMA"];
    $arch_path = trim($rs_archivo->fields["ANEX_PATH"]);
    $arch_nombre = trim($rs_archivo->fields["ANEX_NOMBRE"]);
} else {
    $rs_archivo = $db->query("select arch_codi, arch_codi_firma, radi_nume_temp, radi_path, radi_nume_text, radi_nomb_usua_firma  from radicado where radi_nume_radi=$radi_nume");
    if (!$rs_archivo or $rs_archivo->EOF)
        die(imprimir_resultado_firma("Lo sentimos, no se encontr&oacute; el archivo solicitado"));
    $arch_codi = 0+$rs_archivo->fields["ARCH_CODI"];
    $arch_codi_firma = 0+$rs_archivo->fields["ARCH_CODI_FIRMA"];
    $arch_path = trim($rs_archivo->fields["RADI_PATH"]);
    // $arch_nombre = trim($rs_archivo->fields["RADI_NUME_TEXT"]).".pdf.p7m";
    $firma["mensaje"]="Archivo verificado exitosamente";
    $firma["datos_firma"]=$rs_archivo->fields["RADI_NOMB_USUA_FIRMA"];
    if ($arch_codi<>$arch_codi_firma)
    	$arch_nombre = trim($rs_archivo->fields["RADI_NUME_TEXT"]).".pdf.p7m";
    else
        $arch_nombre = trim($rs_archivo->fields["RADI_NUME_TEXT"]).".pdf";
}

// Recuperamos el archivo de la BDD o de la bodega
$archivo_base64 = "";
if ($arch_codi_firma > 0) {
    $rs_bodega = $db_bodega->query("select func_recuperar_archivo($arch_codi_firma) as archivo");
    $archivo_base64 = $rs_bodega->fields["ARCHIVO"];
} elseif ($arch_path != "" and is_file(dirname(__DIR__)."/bodega$arch_path")) {
    $archivo_base64 = base64_encode(file_get_contents(dirname(__DIR__)."/bodega$arch_path"));
}

if ($archivo_base64 == "")
    die(imprimir_resultado_firma("Lo sentimos, no se encontr&oacute; el archivo solicitado."));

// LLAmamos al WS para verificar la firma
if ($arch_codi <> $arch_codi_firma)
$firma = verificar_firma_archivo($archivo_base64);

// Si se pudo verificar el archivo y no habia sido verificado antes, se guarda el archivo verificado
if (($firma["flag"] ?? 0) == 1 and $arch_codi_firma != 0 and $arch_codi==0) {
    $arch_nombre = str_ireplace(".p7m", "", $arch_nombre);

    $rs_archivo = $db_bodega->query("select func_grabar_archivo(E'$arch_nombre', E'".$firma["archivo"]."') as arch_codi");
    $arch_codi = ($rs_archivo && !$rs_archivo->EOF) ? (0+$rs_archivo->fields["ARCH_CODI"]) : 0;
    if ($arch_codi != 0) {
        if (isset ($grabar_archivo)) unset ($grabar_archivo);
        if ($anex_codigo != "") {
            $sql = "update anexos
                    set arch_codi=$arch_codi
                        , anex_datos_firma=".$db->conn->qstr($firma["datos_firma"])."
                        , anex_fecha_firma=".$db->conn->sysTimeStamp."
                    where arch_codi_firma=$arch_codi_firma and arch_codi=0";
            $db->query($sql);
        } else {
            $sql = "update radicado
                    set arch_codi=$arch_codi
                        , radi_nomb_usua_firma=".$db->conn->qstr($firma["datos_firma"])."
                        , radi_fech_firma=".$db->conn->sysTimeStamp."
                    where arch_codi_firma=$arch_codi_firma and arch_codi=0";
            $db->query($sql);
        }
    }
}

$botones_descarga = "";
if ($arch_codi <> $arch_codi_firma)
$botones_descarga = "<a onclick='fjs_radicado_descargar_archivo(\"$radi_nume\", \"$anex_codigo\", 1, \"download\");' href='javascript:;' class='vinculos'>Descargar Archivo Firmado</a>";

if ($arch_codi <> $arch_codi_firma){
	if ($firma["flag"] == 1) {
    		$botones_descarga .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <a onclick='fjs_radicado_descargar_archivo(\"$radi_nume\", \"$anex_codigo\", 0, \"download\");' href='javascript:;' class='vinculos'>Descargar Archivo Verificado</a>";
    		echo imprimir_resultado_firma ($firma["mensaje"], $botones_descarga, $firma["datos_firma"]);
	} else {
    		echo imprimir_resultado_firma ("Lo sentimos, no se pudo verificar la firma electr&oacute;nica del archivo.", $botones_descarga);
	}
}
else{
	$botones_descarga .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			   <a onclick='fjs_radicado_descargar_archivo(\"$radi_nume\",\"$anex_codigo\",0,\"download\");' href='javascript:;' class='vinculos'>Descargar Archivo Verificado</a>";
	echo imprimir_resultado_firma ($firma["mensaje"], $botones_descarga, $firma["datos_firma"]);
}

function imprimir_resultado_firma ($mensaje, $botones_descarga, $datos_firma="") {
    $texto = "<br><br>
              <center>
                <font color='red' face='Arial' size='3'>$mensaje</font>
                ".str_replace("<table>","<br><br><table border='2' cellspace='2' cellpad='2' width='98%' class='t_bordeGris'>", $datos_firma)."
                <br><br>$botones_descarga<br><br><br>
                <input type='button' onClick='fjs_popup_cerrar();' name='cerrar' value='Cerrar Ventana' class='botones_largo'/>
              </center>";
    return $texto;
}

?>
