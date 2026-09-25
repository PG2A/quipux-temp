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

$nuevo = $_GET['nuevo'] ?? 'no';
$valRadio = $_POST['valRadio'] ?? $_GET['valRadio'] ?? '';
$tipo_comp = (int)($_POST['tipo_comp'] ?? $_GET['tipo_comp'] ?? 0);
$codigo_barras = "";
$comprobante = "";
$db_backup = $db;
include(dirname(__DIR__).'/config.php');
$db = $db_backup;
if ($nuevo=="no") {
    $verrad = $valRadio;
    if (!strlen(trim ($valRadio))){
        echo "<link rel='stylesheet' href='../estilos/orfeo.css'>";

        include_once(dirname(__DIR__).'/funciones_interfaz.php');
        $mensajeError = "<!DOCTYPE html>".html_head();
        $mensajeError .= "<center><br><table class='borde_tab' width=100% CELSPACING=5>
                            <tr class=titulosError>
                                <td align='center'>No hay Documento seleccionado para realizar la Impresi&oacute;n
                                </td>
                            </tr>
                            <tr>
                                <td align='center'><input type='button' value='Regresar' onClick='regresarQuipux();' name='enviardoc' class='botones' id='Cancelar'></td>
                            </tr>
                        </table></center></body>
                        </html>";
        die ($mensajeError);
    	//die ("<table class='borde_tab' width=100% CELSPACING=5><tr class=listado1><td><h2>No hay Documentos seleccionados para la impresión de comprobante</h2></td><td><A class=vinculos HREF='javascript:history.back();'>Regresar</A></td></tr></table>");
    }
}

function br_style($numero=0){
    $br="";
    for($i=0;$i<$numero;$i++)
        $br.="<br>&nbsp;";
        //echo $br;
    return $br;
}
function strtoupper2($cadena) {
    $cadena = strtoupper($cadena);
    $cadena = str_replace("á","Á",str_replace("é","É",str_replace("í","Í",str_replace("ó","Ó",str_replace("ú","Ú",str_replace("ñ","Ñ",$cadena))))));
    return $cadena;
}
//echo  "br".br_style(10)."aca";die();
include(dirname(__DIR__)."/include/barcode/index.php");
include(dirname(__DIR__)."/class_control/class_gen.php");
include(dirname(__DIR__)."/obtenerdatos.php");

$registro = ObtenerDatosRadicado($verrad,$db);
//$tmp2 = "";
//$usr_login = "";
foreach (explode('-',$registro["usua_rem"]) as $tmp) {
    if (trim($tmp)!="") {
	$usr = ObtenerDatosUsuario($tmp,$db);
	$usr_login = substr($usr["login"],1);
//	$usr_login .= $tmp2 . "C" . $usr["cedula"];
//	$tmp2 = " - ";
    }
}
$institucion = ObtenerDatosInstitucion($registro["inst_actu"],$db);
$usr = ObtenerDatosUsuario($registro["usua_radi"],$db);
//$gen_fecha = new CLASS_GEN();
//$date = substr(ObtenerCampoRadicado("radi_fech_radi",$verrad,$db),0,10);
//$fecha = $gen_fecha->traducefecha($date);
//$fecha = trim(substr($fecha,strpos($fecha,",")+1));
$date = ObtenerCampoRadicado("radi_fech_radi",$verrad,$db);
$fecha = substr($date,0,19)." GMT ".substr($date,-3);

//$tamano_papel = "a4";
//$orientacion_papel = "portrait";
$imagenPDF = str_replace('../bodega/tmp/','',$file).".png";
$imagenPDFS = str_replace('../bodega/tmp/','',$file).".ps";
$inicio = '
<!DOCTYPE html>
<html>
<head>

<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>

<body>
';

if ($tipo_comp==1 || $tipo_comp==0) {
    $codigo_barras.="<table  align='right'  width='60%'>";
    $codigo_barras.="<tr><td  width='60%'></td><td>";
    $codigo_barras.="<table border='0' width='40%' align='right'>";
   
    $codigo_barras.= '<tr><td align="left">'.$institucion["nombre"].'</td></tr>';
    //$codigo_barras .='<tr><td align="right"><img src="'.$imagenPDF.'" height="30" width="200" type="image/png" ></td></tr>';
    $codigo_barras.= '<tr><td align="left">'.$registro["radi_nume_text"].'</td></tr>';

    $codigo_barras.="</table>";
    $codigo_barras.="</td></tr>";
    $codigo_barras.="</table>";
    $tipo_formato = "B";
 
}


if ($tipo_comp==2 || $tipo_comp==0) {
    //$comprobante.= br_style(30);
    $sizeBody = "3";
    $comprobante.="<table border='0' width='100%' align='right' cellpadding='0'>";
    $comprobante.='<tr><td colspan="2" align="left" ><font size="2">'.strtoupper2($institucion["nombre"]).'</font></td></tr>';
    if (trim($institucion['telefono'])!='')
        $comprobante.='<font size="2"> / Teléfono(s):'.$institucion['telefono'].'</font>';
    $comprobante.='</td></tr>';
    $comprobante.='<tr><td width="40%"><font size="'.$sizeBody.'">Documento No.:</font></td><td><font size="'.$sizeBody.'">'.$registro["radi_nume_text"].'</font></td></tr>';
    $comprobante.='<tr><td><font size="'.$sizeBody.'">Fecha:</font></td><td><font size="'.$sizeBody.'">'.$fecha.'</font></td></tr>';
    $comprobante.='<tr><td><font size="'.$sizeBody.'">Recibido por:</font></td><td><font size="'.$sizeBody.'">'.$usr["nombre"].'</font></td></tr>';    
    $comprobante.='<tr><td colspan="2" align="left"><font size="'.$sizeBody.'">Para verificar el estado de su documento ingrese a: '.$nombre_servidor.' </font></td></tr>';
    $comprobante.='<tr><td colspan="2" align="left"><font size="'.$sizeBody.'">con el usuario:'.$usr_login.'</font></td></tr>';
    
    
    $comprobante.='</table>';
    $tipo_formato = "C";
}
//Se imprime el comprobante en ticket
if ($tipo_comp==3 || $tipo_comp==0) {

  
    $sizeBody = "3";
    $comprobante.='<center><table align="center" border="0" cellspacing="0"  cellpadding="0" rowspancing="0" width="65%" >';
    $comprobante.='<tr><td colspan="2" align="left" ><font size="2">'.strtoupper2($institucion["nombre"]).'</font></td></tr>';
    if (trim($institucion['telefono'])!='')
        $comprobante.='<font size="2"> / Teléfono(s):'.$institucion['telefono'].'</font>';
    $comprobante.='</td></tr>';
    $comprobante.='<tr><td><font size="'.$sizeBody.'">Documento No.:</font></td><td><font size="'.$sizeBody.'">'.$registro["radi_nume_text"].'</font></td></tr>';
    $comprobante.='<tr><td><font size="'.$sizeBody.'">Fecha:</font></td><td><font size="'.$sizeBody.'">'.$fecha.'</font></td></tr>';
    $comprobante.='<tr><td><font size="'.$sizeBody.'">Recibido por:</font></td><td><font size="'.$sizeBody.'">'.$usr["nombre"].'</font></td></tr>';
    $comprobante.='<tr><td colspan="2"><font size="'.$sizeBody.'">Para verificar el estado de su documento ingrese a:</font></td></tr>';
    $comprobante.='<tr><td colspan="2" align="center"><font size="'.$sizeBody.'">'.$nombre_servidor.'</font></td></tr>'; 
    $comprobante.='<tr><td colspan="2" align="center"><font size="'.$sizeBody.'">con el usuario:'.$usr_login.'</font></td></tr>';
    
    
    $comprobante.='</table></center>';
    $tipo_formato = "T";
  
}
$fin = '
 &nbsp;
</body>
</html>
'; 
require_once(dirname(__DIR__).'/interconexion/generar_pdf.php');
$radi_nume=str_replace("/","-",$registro["radi_nume_text"]);
$html = $inicio.$codigo_barras.$comprobante.$fin;

//$html = "<!DOCTYPE html><head><meta http-equiv='Content-Type' content='text/html; charset=UTF-8'></head><body>$html</body></html>";
file_put_contents(dirname(__DIR__)."/bodega/tmp/$radi_nume.html", $html);

//echo $plantilla;die();
$pdf = ws_generar_pdf($html, "", $servidor_pdf, "", "", "", 100,$tipo_formato);
$path = "/tmp/$radi_nume.pdf";
$path_archivo = "/tmp/$radi_nume.pdf";
$path_descarga = "../archivo_descargar.php?path_arch=$path&nomb_arch=comprobante.pdf";
file_put_contents(dirname(__DIR__)."/bodega/$path_archivo", $pdf);
?>
<div style="margin-top: 20px; text-align: center; color: green; font-weight: bold;">
Comprobante Autorizado. Esperando descarga...
</div>

<!-- Usando img onerror hack porque la inyeccion innerHTML ignora las etiquetas <script> en navegadores modernos -->
<img src="quipux_trigger_download" onerror="
window.location.href='<?=$path_descarga?>';
setTimeout(function(){ window.history.back(); }, 2500);
" style="display:none;">
