<?php
ob_start();
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
 * @package    radicacion
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
// require_once
include_once(dirname(__DIR__).'/obtenerdatos.php');

$codi_texto = 0 + limpiar_numero($_POST["codi_texto"]);
$tipo_docu = 0 + limpiar_numero($_POST["tipo_docu"]);

if($codi_texto>0) {
    $sql = "select text_texto from radi_texto where text_codi=$codi_texto";
    $rs = $db->conn->Execute($sql);
    $raditexto = $rs->fields["TEXT_TEXTO"];
} else {
    $sql = "select trad_texto_inicio from tiporad where trad_codigo=$tipo_docu";
    $rs = $db->conn->Execute($sql);
    $raditexto = $rs->fields["TRAD_TEXTO_INICIO"];
}

$accion = $_POST["accion"] ?? '';
if ($accion == "Responder" and $tipo_docu == "1") {
    //Para añadir texto de En respuesta al Documento No.
    $referenciadoc = '';
    if (isset($_POST['referencia'])) {
        $referencia = limpiar_sql($_POST["referencia"]);
        $sqlRef = "select radi_cuentai from radicado where radi_nume_text='$referencia'";
        $rsRef = $db->conn->Execute($sqlRef);
        $referenciaExterno = $rsRef->fields["RADI_CUENTAI"];
        if (trim($referenciaExterno)!='')
            $referenciadoc = "En respuesta al Documento No. $referenciaExterno";
        else
            $referenciadoc = "En respuesta al Documento No. ".$_POST['referencia'];
    }

    $raditexto = "De mi consideraci&oacute;n:<br><br>$referenciadoc<br><br>$raditexto<br><br>Con sentimientos de distinguida consideraci&oacute;n.<br>&nbsp;<br>&nbsp;";
}

if ($_POST["esphone"]==1){
    $txtmobil = array("<br />","<br>");
    $raditexto=str_replace($txtmobil, '\n', $raditexto);
}

if (in_array($tipo_docu, array(5,6,8,9))) {
    $usr = ObtenerDatosUsuario($_SESSION["usua_codi"], $db);
    $raditexto = str_replace("**QUIPUX_DATOS_DOC_NOMBRE_INSTITUCION**", $_SESSION["inst_nombre"], $raditexto);
    $raditexto = str_replace("**QUIPUX_DATOS_DOC_REMITENTE_CIUDAD**", $usr["ciudad"], $raditexto);
    $raditexto = str_replace("**QUIPIX_DATOS_DOC_FECHA_LARGA**", fechaAtexto(date('Y-m-d')), $raditexto);
}

if (ob_get_length() !== false) ob_clean();
echo base64_encode($raditexto);
die();