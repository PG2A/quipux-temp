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
 * @package    cron
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
require_once(dirname(__DIR__)."/funciones.php");
include_once(dirname(__DIR__).'/obtenerdatos.php');
//p_register_globals($_POST);

//Consultar si el documento tiene registro en la tabla opciones de impresion
$radiNumeRadi = $_POST['nume_radi_temp'] ?? '';
$usuaCodi = $_POST['txt_usua_codi'] ?? '';
$usua_dest = ObtenerDatosUsuario($usuaCodi, $db);

$opcDireccion = $_POST['opcDireccion'] ?? '';
$opcCiudad = $_POST['opcCiudad'] ?? '';
$opcTelefono = $_POST['opcTelefono'] ?? '';
$rad_tipo_sobre = $_POST['rad_tipo_sobre'] ?? 'SO';
$observacionEdit = $_POST['observacionEdit'] ?? '';
$tipoGuardar = $_POST['tipoGuardar'] ?? 0;

$txt_texto_sobre = limpiar_sql(base64_decode(base64_decode($_POST['txt_texto_sobre'] ?? '')),0);

$OpcImpr = ObtenerDatosOpcImpresion($radiNumeRadi, $db);
$radicadoSobres=ObtenerDatosRadicado($radiNumeRadi, $db);

$opcImpresion= array();
$opcImpSobre= array();

if($OpcImpr['OPC_IMP_CODI'])
    $opcImpresion['OPC_IMP_CODI'] = $OpcImpr['OPC_IMP_CODI'];
else
    $opcImpresion['RADI_NUME_RADI'] = $radiNumeRadi;

$opcImpresion["OPC_IMP_TEXTO_SOBRE"] = $db->conn->qstr($txt_texto_sobre);
//$ok1 = $db->conn->Replace("OPCIONES_IMPRESION", $opcImpresion, "OPC_IMP_CODI", false,false);

$observacion_edicion = "";

if(trim($observacionEdit ?? '') != "")
    $observacion_edicion = "'<b>Cambios realizados desde Impresión de Sobre</b><br>".$observacionEdit."'";
//Guarda en opciones de impresion
$ok1 = $db->conn->Replace("OPCIONES_IMPRESION", $opcImpresion, "OPC_IMP_CODI", false,false);

if(trim($opcDireccion ?? '')!="" or trim($opcCiudad ?? '')!="" or trim($opcTelefono ?? '')!="")
{
    if(($_POST["tipoUsuario"] ?? 0) == 1)//funcionario
    {          
        //Si es funcionario los datos se guardan en opciones de impresion sobre
        //Guardar datos de direccion, ciudad y telefono en caso de haber sido ingresados
        $rsOpcImpSobre = ObtenerDatosOpcImpresionSobre($radiNumeRadi,$usuaCodi,$db);
        //var_dump($rsOpcImpSob);
        if(isset($rsOpcImpSobre["OPC_IMP_SOB_CODI"]))
            $opcImpSobre["OPC_IMP_SOB_CODI"] = $rsOpcImpSobre["OPC_IMP_SOB_CODI"];
        else
            $opcImpSobre['RADI_NUME_RADI'] = $radiNumeRadi;

        $opcImpSobre["USUA_CODI"] = $usuaCodi;
        
        if(trim($opcDireccion ?? '')!="")
            $opcImpSobre["OPC_IMP_SOB_DIRECCION"] = $db->conn->qstr(limpiar_sql(trim($opcDireccion)));
        //if(trim($opcCiudadTxt)!="")
            //$opcImpSobre["OPC_IMP_SOB_CIUDAD"] =$db->conn->qstr(limpiar_sql(trim($opcCiudadTxt)));
        if(trim($opcCiudad ?? '')!="")
            $opcImpSobre["OPC_IMP_SOB_CIUDAD"] =$db->conn->qstr(limpiar_sql(trim($opcCiudad)));
        if(trim($opcTelefono ?? '')!="")
            $opcImpSobre["OPC_IMP_SOB_TELEFONO"] =$db->conn->qstr(limpiar_sql(trim($opcTelefono)));
//        $opcImpSobre["OPC_IMP_TEXTO_SOBRE"] = $db->conn->qstr($txt_texto_sobre);        
        $ok1 = $db->conn->Replace("OPCIONES_IMPRESION_SOBRE", $opcImpSobre, "OPC_IMP_SOB_CODI", false,false);
        //Se añade insert para guardar en la tabla de ciudadano
        
    }
    else if($_POST["tipoUsuario"] == 2)//ciudadano
        {   //Actualiza registro en la tabla ciudadano
            $ciudadano= array();
           
            //Guardar datos de direccion, ciudad y telefono en caso de haber sido ingresados
            $ciudadano["CIU_CODIGO"] = $_POST['txt_usua_codi'] ?? 0;
            if(trim($opcDireccion ?? '')!="")
                $ciudadano["CIU_DIRECCION"] = $db->conn->qstr(limpiar_sql(trim($opcDireccion)));
            if(trim($opcCiudad ?? '')!="")
                $ciudadano["CIUDAD_CODI"] =$db->conn->qstr(limpiar_sql(trim($opcCiudad)));
            if(trim($opcTelefono ?? '')!="")
                $ciudadano["CIU_TELEFONO"] =$db->conn->qstr(limpiar_sql(trim($opcTelefono)));
            //Datos del usuario que modifico al funcionario la ultima ves.
            $ciudadano["USUA_CODI_ACTUALIZA"] = $_SESSION['usua_codi'];
            $ciudadano["CIU_FECHA_ACTUALIZA"] = "CURRENT_TIMESTAMP";
            $ciudadano["CIU_OBS_ACTUALIZA"] = $observacion_edicion;
           
             //if ($opcCiudad!=0){                  
                 if (($_SESSION['usua_perm_ciudadano'] ?? 0)==1){
                     //verificar si el ciudadano tiene permisos de edicion
                     if ($_SESSION['usua_perm_ciudadano']==1)
                     $ok1 = $db->conn->Replace("CIUDADANO", $ciudadano, "CIU_CODIGO", false,false);
             //   }
             }
            
            
        }
}
$registroOpcImpr = ObtenerDatosOpcImpresion($_POST['nume_radi_temp'], $db);

//Obtener datos de opciones de impresion en sobre si es funcionario público
if($_POST["tipoUsuario"] == 1)
    $rsOpcImpSob = ObtenerDatosOpcImpresionSobre($radiNumeRadi,$usuaCodi,$db);

//Opciones de Impresión
$titulo = $usua_dest['titulo'] ?? '';
$direccion = "";
$nombre = trim(($usua_dest['usua_nombre'] ?? '') . ' ' . ($usua_dest['usua_apellido'] ?? ''));
$ciudad = "";
$telefono = "";
$cargo = "";
$empresa = $usua_dest['institucion'] ?? '';

if(trim($usua_dest['direccion'] ?? '')!='')
    $direccion = $usua_dest['direccion'];

if(trim($usua_dest['ciudad'] ?? '')!='')
    $ciudad = $usua_dest['ciudad'];

if(trim($usua_dest['telefono'] ?? '')!='')
    $telefono = $usua_dest['telefono'];


if(($_POST["tipoUsuario"] ?? 0)==1){//Funcionario
    if(($radicadoSobres["radi_tipo"] ?? 0)==1){//Oficio
        $cargo = $usua_dest['cargo_cabecera'] ?? '';
    }else{
         if(trim($usua_dest['cargo'] ?? '')!='')
            $cargo = $usua_dest['cargo'] ?? '';
     }
}elseif(($_POST["tipoUsuario"] ?? 0)==2){//Ciudadano
    if(trim($usua_dest['cargo'] ?? '')!='')
        $cargo = $usua_dest['cargo'] ?? '';
}

if(isset($registroOpcImpr["OPC_IMP_CODI"]))
{
    if(($registroOpcImpr["TITULO_NATURAL"] ?? '')!="")
        $titulo = $registroOpcImpr["TITULO_NATURAL"].' ';

    if(($registroOpcImpr["FIRMANTES"] ?? '')!="")
        $nombre .= " ".$registroOpcImpr["FIRMANTES"];

    if(($registroOpcImpr["EXT_INSTITUCION"] ?? '')!="")
        $empresa = $empresa.' '.$registroOpcImpr["EXT_INSTITUCION"];

    if(($registroOpcImpr["DIRECCION"] ?? '')!="")
        $direccion = $registroOpcImpr["DIRECCION"];
    else if(trim($registroOpcImpr["DESTINO_DESTINATARIO"] ?? '')!="" and trim($direccion ?? '')=="")
        $direccion = $registroOpcImpr["DESTINO_DESTINATARIO"];

    if(($registroOpcImpr["CIUDAD"] ?? '')!="")
        $ciudad = $registroOpcImpr["CIUDAD"];

    if(($registroOpcImpr["TELEFONO"] ?? '')!="")
        $telefono = $registroOpcImpr["TELEFONO"];
}

//Opciones de impresion de sobre para funcionario
if(isset($rsOpcImpSob["OPC_IMP_SOB_CODI"]))
{
    if(($rsOpcImpSob["DIRECCION"] ?? '')!="")
        $direccion = $rsOpcImpSob["DIRECCION"];

    if(($rsOpcImpSob["CIUDAD"] ?? '')!="")
        $ciudad = $rsOpcImpSob["CIUDAD"];

    if(($rsOpcImpSob["TELEFONO"] ?? '')!="")
        $telefono = $rsOpcImpSob["TELEFONO"];
}
$tamano_papel = "commerical #10 envelope";
$orientacion_papel = "portrait";

$html = '<!DOCTYPE html>
        <html>
        <head>
        <title></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        </head>
        <body>
';

$html .= "<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;<br/>&nbsp;";

//Define tamaño Sobre
switch ($rad_tipo_sobre) {
    case "SM": // Sobre Mediano
        $ancho1='100px';
        $ancho2='400px';
        $html.="<table width='500px' align='center' cellpadding='0' cellspacing='0' >";
        break;
    case "SP": // sobre Pequeño
        $ancho1='96px';
        $ancho2='704px';
        $html.="<table width='800px' align='center' cellpadding='0' cellspacing='0'>";
        break;
    default: // Sobre Oficio
        $ancho1='230px';
        $ancho2='770px';
        $html.="<table width='1000px' align='center' cellpadding='0' cellspacing='0'>";
        break;
}

    
    $html .="<tr><td width='$ancho1'>&nbsp;&nbsp;</td><td width='$ancho2'><font size='3' style='line-height: 0.9em;'>";
    $html .= $txt_texto_sobre;
    $html .= '</font></td></tr>';
    $html .= "</table></body></html>";

    require_once(dirname(__DIR__).'/interconexion/generar_pdf.php');
    include(dirname(__DIR__).'/config.php');
    if (!isset($plantilla)) $plantilla = '';
    
    $html = preg_replace(':<a .*?>:is', "", $html);
    $html = str_replace("</a>", "", $html);
    file_put_contents(dirname(__DIR__)."/bodega/tmp/$radiNumeRadi"."Sobre.html", $html);
    $pdf = ws_generar_pdf($html, $plantilla, $servidor_pdf, "", "", "", 100,"R");
    $path = "/tmp/$radiNumeRadi"."Sobre.pdf";
    $path_archivo ="/tmp/$radiNumeRadi"."Sobre.pdf";
    sleep(2);
    $path_descarga = "../archivo_descargar.php?path_arch=$path&nomb_arch=Sobre.pdf";
    file_put_contents(dirname(__DIR__)."/bodega/$path_archivo", $pdf);
    ?>
    <div style="margin-top: 20px; text-align: center; color: green; font-weight: bold;">
        Comprobante Autorizado. Iniciando descarga...
    </div>
    
    <!-- Using img onerror hack because innerHTML injection ignores <script> tags in modern browsers -->
    <img src="quipux_trigger_download" onerror="
        window.location.href='<?=$path_descarga?>';
        setTimeout(function(){ window.history.back(); }, 2500);
    " style="display:none;">
<?php
/*
Cambio de dom
    //GENERACION DEL PDF
    require_once __DIR__."/js/dompdf/dompdf_config.inc.php";
    $dompdf = new DOMPDF();
    $dompdf->load_html($html);
    $dompdf->set_paper($tamano_papel, $orientacion_papel);
    $dompdf->set_base_path(getcwd());
    $dompdf->render();
    $pdf = $dompdf->output();
    file_put_contents(__DIR__."/bodega/tmp/sobre_$txt_usua_codi.pdf", $pdf);
    $path_descarga = __DIR__."/archivo_descargar.php?path_arch=/tmp/sobre_$txt_usua_codi.pdf&nomb_arch=sobre.pdf";
    if ($tipoGuardar == 1)//imprime, este parametro esta en el archivo accion_imprimir_sobre.php funcion imprimir_sobre()
    echo "<iframe name='ifr_descargar_archivo' id='ifr_descargar_archivo' style='display: none' src='$path_descarga'>
          Su navegador no soporta iframes, por favor actualicelo.</iframe>";
*/

/*
    require_once(__DIR__.'/interconexion/generar_pdf.php');
    $plantilla = "";
    //$plantilla = __DIR__."/bodega/plantillas/".$cod_estado.".pdf";
    $plantilla ="";
    $doc_pdf = $inicio . $cartaF;
    $pdf = ws_generar_pdf($doc_pdf, $plantilla, $servidor_pdf,'','','','','S');
    file_put_contents(__DIR__."/bodega/tmp/sobre_$txt_usua_codi.pdf", $pdf);
    $path_descarga = __DIR__."/archivo_descargar.php?path_arch=/tmp/sobre_$txt_usua_codi.pdf&nomb_arch=sobre.pdf";
    echo "<iframe name='ifr_descargar_archivo' id='ifr_descargar_archivo' style='display: none' src='$path_descarga'>
          Su navegador no soporta iframes, por favor actualicelo.</iframe>";
/* */
?>
