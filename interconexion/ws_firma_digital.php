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
 * @package    interconexion
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

include_once("../config.php");

function grabar_archivos_firmados($usuario, $nombre_doc, $archivo,$datos_firmante,$fecha,$institucion,$cargo) {
	//echo "Retorna";
        $ruta_raiz = "..";
        include_once(__DIR__.'/funciones.php');
        include_once(__DIR__.'/obtenerdatos.php');
        include_once(__DIR__.'/include/db/ConnectionHandler.php');
        include_once(__DIR__.'/include/tx/Tx.php');
        include_once(__DIR__.'/include/tx/Firma_Digital.php');
        $db = new ConnectionHandler(__DIR__);
    	$db_bodega = new ConnectionHandler(__DIR__, "bodega");
    	$tx = new Tx($db);
//return $archivo;
        $radicado = ObtenerDatosRadicado($nombre_doc, $db);
	$usr = ObtenerDatosUsuario(str_replace("-", "",$radicado["usua_rem"]), $db);      
	$archivo5=md5($archivo);
	//$nombre_doc = limpiar_numero(trim($nombre_doc));

        //$fecha_doc = date('Y-m-d');
        $arch64 = base64_encode($archivo);
        $archivo5 = md5($arch64);
        //Grabar registro archivo
        $fechadia = substr($fecha,0,2);
        $fechames = substr($fecha,3,2);
        $fechaanio = substr($fecha,6,4);
        $fechahora = substr($fecha,11,8);
        //COMPUESTA
        $fecha = "$fechaanio-$fechames-$fechadia $fechahora (GMT-5)";
        $nombre = $datos_firmante;
//$db->conn->Execute("insert into log_paginas_visitadas (pagina) values('nombredoc: $nombre_doc')");
//$db->conn->Execute("insert into log_paginas_visitadas (pagina) values('archivo_firmado64: $arch64')");
        $datos_firmante = "<table><tr><th>Cédula</th><th>Nombre</th><th>Institución</th><th>Cargo</th><th>Fecha</th></tr>";
        $datos_firmante.= "<tr><td>$usuario</td><td>$nombre</td><td>$institucion</td><td>$cargo</td><td>$fecha</td></tr></table>";
 $rs_archivo = $db_bodega->query("select func_grabar_archivo(E'$nombre_doc.pdf', E'$arch64') as arch_codi");

    if (!$rs_archivo or $rs_archivo->EOF or (0+$rs_archivo->fields["ARCH_CODI"])==0)
        return 0;
    $arch_codi_firma = 0+$rs_archivo->fields["ARCH_CODI"];

//$db->conn->Execute("insert into log_paginas_visitadas (pagina) values('fecha_doc: $fecha')");
	$sql="update radicado set radi_fech_firma='$fecha',radi_tipo_archivo=1, radi_nomb_usua_firma = '$datos_firmante', arch_codi = $arch_codi_firma, arch_codi_firma=$arch_codi_firma where radi_nume_temp = $nombre_doc and (esta_codi=4 or esta_codi=3 or radi_nume_radi=$nombre_doc)";

	$ok = $db->conn->Execute($sql);
//$usr = ObtenerDatosUsuario(str_replace("-", "",$radicado["usua_rem"]), $db);
//$db->conn->Execute("insert into log_paginas_visitadas (pagina) values('$sql')");
$tx->insertarHistorico($nombre_doc, $usr["usua_codi"],  $usr["usua_codi"], "Documento Firmado Electrónicamente", 40);
$respFirma = $tx->envioElectronicoDocumento($nombre_doc,  $usr["usua_codi"]);
if (!$ok)
          return 0;
        else
        return 1;

}
//Pongo old por edicion
function grabar_archivos_firmados_old($usuario, $radi_nume, $archivo) {

    $radi_nume = trim($radi_nume);
    if (strlen($radi_nume)!=20) return "0a";

    $ruta_raiz = "..";
    include_once(__DIR__.'/obtenerdatos.php');
    include_once(__DIR__.'/include/db/ConnectionHandler.php');
    $db = new ConnectionHandler(__DIR__);
    include_once(__DIR__.'/include/tx/Tx.php');
    include_once(__DIR__.'/include/tx/Firma_Digital.php');
    $tx = new Tx($db);

//    $usr = ObtenerDatosUsuario($usuario, $db, "C");
    $radicado = ObtenerDatosRadicado($radi_nume, $db);
    $usr = ObtenerDatosUsuario(str_replace("-", "",$radicado["usua_rem"]), $db);
    if (substr($usr["cedula"], 0, 10) != $usuario) return "0b";


    if ($radicado["estado"] != 3) return "0c"; // Validamos que el documento este en un estado válido

    if (trim($radicado["radi_path"])=="") {
        $radi_path = "/".substr(trim($radi_nume),0,4)."/".substr(trim($radi_nume),4,6)."/$radi_nume.pdf.p7m";
        $path_arch = __DIR__."/bodega".$radi_path;
    } else {
        $radi_path = trim($radicado["radi_path"]);
        while (strtoupper(substr($radi_path,-4)) == ".P7M") {
            $radi_path = substr($radi_path,0,-4);
        }
        $radi_path .= ".p7m";
        $path_arch = __DIR__."/bodega".$radi_path;
    }

    $ok = file_put_contents($path_arch, base64_decode($archivo));
    if (!$ok) return "0d";

    $firma = verificaFirma("$path_arch",$ruta_raiz);
	//if ($firma["flag"]!=1) return "0";

    $persona = $firma["datos_firma"];

    $fecha = $db->conn->sysTimeStamp;
    $sql = "update radicado set radi_fech_firma=$fecha, radi_path='$radi_path', radi_tipo_archivo=1, radi_nomb_usua_firma='$persona' ".
           "where radi_nume_temp=$radi_nume and (esta_codi=4 or esta_codi=3 or radi_nume_radi=$radi_nume)";
    $ok = $db->conn->Execute($sql);

    if (!$ok) return "0e";
//	Registramos el histórico
    $tx->insertarHistorico($radi_nume, $usr["usua_codi"],  $usr["usua_codi"], "Documento Firmado Electrónicamente", 40);	//Firma Digital
//	Enviamos el documento a los usuarios
    $respFirma = $tx->envioElectronicoDocumento($radi_nume,  $usr["usua_codi"]);
    return "1";
}



    // Averiguar ruta servidor

    ini_set("soap.wsdl_cache_enabled", "0");
    $sServer = new SoapServer("$nombre_servidor/interconexion/firma.wsdl");
    //  $sServer = new SoapServer($ruta_servidor);
    $sServer->addFunction("grabar_archivos_firmados");
    $sServer->handle();
?>
