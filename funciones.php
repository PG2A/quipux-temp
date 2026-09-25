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
 * @package    quipux
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */



if (!class_exists('coding_exception')) {
    class coding_exception extends Exception {}
}

if (!function_exists('debugging')) {
    function debugging($message, $level = null, $backtrace = null) {
        error_log("DEBUGGING: " . $message);
    }
}

if (!function_exists('enviar_mail_intento_ataque')) {
    function enviar_mail_intento_ataque($title, $body) {
        error_log("INTENTO ATAQUE: $title - $body");
    }
}



function linkFirmaEc($centrado='center'){
//echo="<script>function firmaEC(){window.open('http://www.firmadigital.gob.ec/descargar-firmaec/','popup', 'with=700,height=800,scrollbars=1')}</script>";
$html="";
$html.="<$centrado>";
$html.="<table>";
$html.="<tr><td><br><center><b><font size='2'><i>Para continuar, asegúrese de tener instalado la aplicación FirmaEC</i></font></b></center></td></tr>";
$html.="</table>";
$html.="</$centrado>";
return $html;
}





/*******************************************************************************
** Limpia cadenas numéricas, elimina cualquier caracter de otro tipo          **
*******************************************************************************/
function limpiar_numero($numero) {
    $numero = trim($numero ?? '');
    $flag_punto = false;
    $cadena = "";
    if (ltrim($numero, "0123456789.-") != "") {
        enviar_mail_intento_ataque("QUIPUX: NOTIFICACION INTENTO DE ATAQUE - TEXTO INTRODUCIDO EN CAMPO NUMERICO", $cadena);
    }
    for ($i=0; $i<strlen($numero); $i++){
        $num = substr($numero,$i,1);
        if (strpos("0123456789.-", $num) !== false) {
            if ($num == "-" and $i > 0) $num = ""; //Para numeros negativos el guion debe estar al inicio
            if ($num == ".") { // Para decimales
                if ($flag_punto) $num=""; //Controla que haya un solo punto
                if ($i == 0) $num = "0.";
                $flag_punto = true;
            }
            $cadena .= $num;
        }
    }
    return $cadena;
}


/*******************************************************************************
** Limpia las cadenas de ataques SQL INJECTION, CSS y HTML INJECTION          **
** Si $html==1 no limpia la cadena de código html                             **
*******************************************************************************/
function limpiar_sql($cadena, $validar_html=1) {
    // Limpiamos apóstrofes para ataques SQL Injection
    if (is_array($cadena)) return;
    $cadena = (string)$cadena; // Cast to string to avoid deprecated null warning
    $cadena = str_replace("\\", "", $cadena);
    $cadena = str_replace("'", "′", $cadena);
    $cadena = str_replace("”", '"', $cadena);
    $cadena = str_replace("“", '"', $cadena);

    $cadena_tmp = str_replace(array("&NBSP;", "\n", " "), "", strtoupper($cadena));
    $cadena_tmp = str_replace(array("&AMP;","&LT;"), array("&","<"), $cadena_tmp);
    if (strpos($cadena_tmp, "<SCRIPT") !== false) {
//        enviar_mail_intento_ataque("QUIPUX: NOTIFICACION INTENTO DE ATAQUE - CSS y HTML", $cadena);
        $cadena = str_ireplace(array("javascript","script"), array("jaiba_street","street"), $cadena);
    }

    if ($validar_html) {
        $cadena = str_replace(array("<"), array("&lt;"), $cadena);
//        $cadena = strip_tags($cadena);
//        $cadena = htmlspecialchars($cadena);
    }
    return trim($cadena);

}


/*******************************************************************************
** Registra las variables que se reciben por $GET, $_POST y $_SESSION y las   **
** limpia contra ataques SQL Injection.                                       **
** Permite dehabilitar la variable REGISTER_GLOBALS del archivo PHP.INI       **
*******************************************************************************/
function validar_register_globals() {
    // Lista de variables que se van a limpiar; $_POST se sobrepone a $_GET y $_SESSION a todas las demás
    $variables = array("_GET", "_POST", "_SESSION");
    foreach ($variables as $tipo_variable) {
        global $$tipo_variable;
        if (is_array($$tipo_variable)) {
            foreach ($$tipo_variable as $key => $value) {
                $key = trim(limpiar_sql($key));
                if ($key != "" && $key != "ruta_raiz") {
                    global $$key;
                    $$key = limpiar_sql($value);
                    if ($key == "orderNo") $$key = (int)$value;
                    if ($key == "adodb_next_page") $$key = (int)$value;
                    if ($key == "orderTipo" && strtolower($value)!="desc") $$key = "asc";
                }
            }
        }
    }
    return;
}
/*******************************************************************************
** ATENCIÓN: Ejecuto automáticamente la función; se llama al archivo desde    **
** SESSION_ORFEO.PHP para que se ejecute en todas las páginas                 **
*******************************************************************************/
validar_register_globals();



function buscar_cadena($cadena, $campo) {
//Arma el query para buscar una cadena separada por espacios en un campo de la bdd quitando las tildes y eñes

    $resp = "";
    $cadena = limpiar_sql($cadena);
    //$cadena = str_replace('á','A',str_replace('é','E',str_replace('í','I',str_replace('ó','O',str_replace('ú','U',str_replace('ñ','N',$cadena))))));
    //$cadena = str_replace('Á','A',str_replace('É','E',str_replace('Í','I',str_replace('Ó','O',str_replace('Ú','U',str_replace('Ñ','N',$cadena))))));

    $arr_buscar = explode(" ", $cadena);
    $glue = '';
    foreach ($arr_buscar as $tmp) {
        if ($tmp != "" && strlen($tmp)>=3) {
            $resp .= " $glue translate(UPPER($campo),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
		      LIKE translate(upper('%" . trim($tmp) . "%'),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN') ";
            $glue = 'and';
        }
    }
    $resp = (empty($resp)) ? 'true' : $resp;
    return $resp;
}

function buscar_cadena_tsearch($cadena, $campo, $idioma="es") {
//Arma el query para buscar una cadena separada por espacios en un campo de la bdd quitando las tildes y eñes

    $resp = "";
    $cadena = limpiar_sql($cadena);
    //$cadena = str_replace('á','A',str_replace('é','E',str_replace('í','I',str_replace('ó','O',str_replace('ú','U',str_replace('ñ','N',$cadena))))));
    //$cadena = str_replace('Á','A',str_replace('É','E',str_replace('Í','I',str_replace('Ó','O',str_replace('Ú','U',str_replace('Ñ','N',$cadena))))));

    $arr_buscar = explode(" ", $cadena);
    $glue = '';
    foreach ($arr_buscar as $tmp) {
        if ($tmp != "" && strlen($tmp)>=3) {
            $resp .= " $glue to_tsvector('$idioma',
                                          upper($campo))
                             @@ to_tsquery('$idioma', upper('%" . trim($tmp) . "%')) ";
            $glue = 'and';
        }
    }
    $resp = (empty($resp)) ? 'true' : $resp;
    return $resp;
}

function buscar_nombre_cedula($cadena, $buscarInstitucion = 'N') {
    $filtro = '((' . buscar_cadena($cadena, "usua_nombre") . ') or (' . buscar_cadena($cadena, "usua_cedula") . ')';
    if($buscarInstitucion == 'S')
        $filtro .= ' or (' . buscar_cadena($cadena, "inst_nombre") . ')';
    $filtro .= ')';
    return $filtro;
}

function str_limpiar_tildes($cadena) {
//Arma el query para buscar una cadena separada por espacios en un campo de la bdd quitando las tildes y eñes

    $resp = "";
    $cadena = limpiar_sql($cadena);
    $mayusculas = array ("Á", "É", "Í", "Ó", "Ú", "À", "È", "Ì", "Ò", "Ù", "Ä", "Ë", "Ï", "Ö", "Ü", "Â", "Ê", "Î", "Ô", "Û", "Ã", "Õ", "Ñ");
    $minusculas = array ("á", "é", "í", "ó", "ú", "à", "è", "ì", "ò", "ù", "ä", "ë", "ï", "ö", "ü", "â", "ê", "î", "ô", "û", "ã", "õ", "ñ");
    $limpiar_may = array ("A", "E", "I", "O", "U", "A", "U", "I", "O", "U", "A", "E", "I", "O", "U", "A", "E", "I", "O", "U", "A", "O", "N");
    $limpiar_min = array ("a", "e", "i", "o", "u", "a", "e", "i", "o", "u", "a", "e", "i", "o", "u", "a", "e", "i", "o", "u", "a", "o", "n");
    $cadena = str_replace($minusculas, $limpiar_min, $cadena);
    $cadena = str_replace($mayusculas, $limpiar_may, $cadena);
    return $cadena;
}


function buscar_datos_usuario($cadena) {
    //Arma el query para buscar una cadena separada por espacios en los datos del usuario usuario->usua_datos

    $resp = "";
    $cadena = strtoupper(str_limpiar_tildes($cadena));

    $arr_buscar = explode(" ", $cadena);
    foreach ($arr_buscar as $tmp) {
        if (trim($tmp) != "" && strlen($tmp)>=3) {
            $resp .= " and usua_datos like '%" . trim($tmp) . "%'";
        }
    }
    return $resp;
}

function buscar_nombre_cedula_solicitud($cadena) {
    return '((' . buscar_cadena($cadena, "ciu_nombre") . ') or (' . buscar_cadena($cadena, "ciu_cedula") . '))';
}

function buscar_2campos($cadena, $campo1, $campo2) {
    return '((' . buscar_cadena($cadena, $campo1) . ') or (' . buscar_cadena($cadena, $campo2) . '))';
}


function p_register_globals($list = null) {
    return;
}

// Envía un email al destinaratio especificado.
// El $mensaje debe estar en HTML
// $destinatario es el email
// $nombre_dest es el nombre del destinatario
function enviarMail($mensaje, $asunto, $destinatario_ori, $nombre_dest="", $ruta_raiz=".") {
    include($ruta_raiz.'/config.php');
    // Puente del entorno local de desarrollo. /local/ está en .gitignore y no se
    // despliega, así que en producción esto no existe y el envío sigue por mail().
    if (is_file(__DIR__.'/local/mail/Mailer.php')) include_once(__DIR__.'/local/mail/Mailer.php');
    $tmp = explode(",", $destinatario_ori);
    foreach ($tmp as $destinatario) {
        $destinatario = trim($destinatario);
        if ($destinatario != "" and strpos($destinatario, "@") and strpos($destinatario, ".", strpos($destinatario, "@"))) {
            // Estructura de la descripcion de email.
            $email = $destinatario; //recipient
            //$email = $para; //recipient
            $subject = $asunto;

            $despedida = "<br /><br />Saludos cordiales,<br /><br />Soporte Quipux.";
            $despedida .= "<br /><br /><b>Nota: </b>Este mensaje fue enviado autom&aacute;ticamente por el sistema, por favor no lo responda.";
            $despedida .= "<br />Si tiene alguna inquietud respecto a este mensaje, comun&iacute;quese con <a href='mailto:$cuenta_mail_soporte'>$cuenta_mail_soporte</a>";

            $mail_body = str_replace("**SISTEMA**", "<a href='$nombre_servidor' target='_blank'>$nombre_servidor</a>", $mensaje);
            $mail_body = str_replace("**DESPEDIDA**", $despedida, $mail_body);

            if (function_exists('quipux_enviar_correo')) {
                quipux_enviar_correo($email, $subject, $mail_body, $nombre_dest, $cuenta_mail_envio);
            } else {
                $header = 'MIME-Version: 1.0' . "\r\n";
                $header .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                //$header .= "To: $nombre_dest <" . $destinatario . ">" . "\r\n";
                $header .= "From: Quipux <$cuenta_mail_envio>" . "\r\n";

                ini_set('sendmail_from', "$cuenta_mail_envio"); //Suggested by "Some Guy"
                mail($email, $subject, $mail_body, $header); //mail command :)
            }
        }
    }
//echo "<hr>Para: $destinatario_ori<br><br>Asunto: $asunto<br><br>$mail_body<hr>";
}

// Genera un password randómico con un numero determinado de caracteres
function generar_password($num=8) {
    $chars = "abcdefghijkmnopqrstuvwxyz023456789";
    $pass = "";
    for ($i = 0; $i < $num; ++$i) {
        $pass .= substr($chars, rand(0, 33), 1);
    }
    return $pass;
}

/**
 *  ObtenerPath
 * */
function ObtenerPath($archivo) {
    $archivo = str_replace(".p7m", "", $archivo);
    return $path_arch = __DIR__."/bodega" . $archivo;
}

/* Muestra las áreas hijas de un área.
 * Si tiene permiso de bandeja de entrada y $tipo="T" muestra todas las áreas de la institución
 */

function buscar_areas_dependientes($area, $tipo="T") {
    global $db;

    $areas = $area;
    // En caso que tenga permiso de "Bandeja de entrada" consulta todas las áreas
    if ($_SESSION["ver_todos_docu"] == 1 and $tipo == "T") {
        $sql = "select depe_codi from dependencia where depe_codi<>$area and depe_estado=1 and inst_codi=" . $_SESSION["inst_codi"];
        $rs = $db->conn->Execute($sql);
        while (!$rs->EOF) {
            $areas .= "," . $rs->fields["DEPE_CODI"];
            $rs->MoveNext();
        }
    } else {
        $areas .= buscar_areas_dependientes_rec($area);
    }
    return $areas;
}

// función recursiva que busca las áreas hijas de un área
function buscar_areas_dependientes_rec($codigo) {
    global $db;
    $areas = "";
    $sql = "select depe_codi from dependencia where depe_codi_padre=$codigo and depe_codi<>$codigo and depe_estado=1";
    $rs = $db->conn->Execute($sql);
    while (!$rs->EOF) {
        $areas .= "," . $rs->fields["DEPE_CODI"];
        $areas .= buscar_areas_dependientes_rec($rs->fields["DEPE_CODI"]);
        $rs->MoveNext();
    }
    return $areas;
}

/**
 * Guardar firma en la base de datos
 * */
function GrabarFirma($db, $firDigCodi, $usua_codi, &$file, $nombre, $extension, $ruta_raiz) {
    if ($ruta_raiz == '')
        $ruta_raiz = "..";

    //Respuesta si hubo un error al anexar el archivo
    $ok = 0; //Error
    //Nombre del archivo
    $tmp = explode("\\", strtolower($nombre));
    $nomb_arch1 = $tmp[count($tmp) - 1];
    $tmp = explode("/", strtolower($nomb_arch1));
    $nomb_arch = $tmp[count($tmp) - 1];
    //Extension del archivo
    $tmp = explode(".", $nomb_arch);
    $flag_firma = false;

    if ($tmp[count($tmp) - 1] == "p7m") {
        $tmp = explode(".", str_replace(".p7m", "", $nomb_arch));
        $flag_firma = true;
    }

    $tmp_ext = $tmp[count($tmp) - 1];
    $ext_arch = substr($nomb_arch, strpos($nomb_arch, $tmp_ext));

    //$tipo_arch = $rs[0]["arch_tip_codi"];
    //$anex_nombre = str_replace(" ","_",$nomb_arch);

    unset($recordSet);
    if ($firDigCodi != '')
        $recordSet["FIR_DIG_CODI"] = $firDigCodi;
    $recordSet["USUA_CODI"] = $usua_codi;
    $recordSet["FIR_DIG_CUERPO"] = $db->conn->qstr(limpiar_sql(base64_encode(file_get_contents($file))));
    //$recordSet["FIR_DIG_NOMBRE"] = $db->conn->qstr(limpiar_sql($anex_nombre));
    $recordSet["FIR_DIG_EXT"] = $db->conn->qstr(limpiar_sql($extension));

    $ok = $db->conn->Replace("FIRMA_DIGITALIZADA", $recordSet, "FIR_DIG_CODI", false, false); //true al final para ver la cadena del insert

    if ($ok) { //Si inserto correctamente
        $ok = 0;
    } else
        $ok = 1;
    return $ok;
}

//Divide las cadenas en varias líneas según un ancho determinado
function dividir_cadenas($cadena, $separador="<br>", $tamanio=60) {
    $resultado = "";
    $pos = 0;
    while (true) {
        $cadena = trim($cadena);
        if (strlen($cadena) <= $tamanio or $pos === false) {
            $resultado .= $cadena;
            return $resultado;
        }
        $pos = strpos($cadena, " ", $tamanio);
        $resultado .= substr($cadena, 0, $pos) . $separador;
        $cadena = substr($cadena, $pos);
    }
}

function validar_mail($email) {
    //autor:                teya
    //fecha:                20110418
    //motivo:               funcion que valida la existencia del mail, pero solo se puede hacer que valide el dominio
    //1. verifica si el mail tiene el formato correcto
    if (trim($email) == "")
        return true; // porque cuando se ingresa por primera vez a la pag viene en blanco
//        if (!preg_match("/^[_\.0-9a-z\-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,6}$/i",$email)) {
//            return false; //echo 'direccion no tiene el formato adecuado';
//        }
    //2. verifica si existe el dominio
    //2.1 separamos en caso de venir mas de 1 direccion de correo
    $cadena = str_replace(";",",",$email);
    $correosc = explode(",", $cadena );

    //$correospc = split(";", $email);
    $bandera = 0;

    foreach ($correosc as $email1) {

        list ( $Username, $dominio ) = explode("@", $email1);
        $MXHost = '';

        if (checkdnsrr($dominio, 'A') || checkdnsrr($dominio, 'MX') || checkdnsrr($dominio, 'NS')
                || checkdnsrr($dominio, 'SOA') || checkdnsrr($dominio, 'PTR') || checkdnsrr($dominio, 'CNAME')
                || checkdnsrr($dominio, 'AAAA') || checkdnsrr($dominio, 'A6') || checkdnsrr($dominio, 'SRV')
                || checkdnsrr($dominio, 'NAPTR') || checkdnsrr($dominio, 'TXT') || checkdnsrr($dominio, 'ANY')
        ) {
            $bandera = 0;
//            if ( !(getmxrr ($dominio, $MXHost)))  {
//                return false; //echo 'no mailbox';
//            }
        } else {
            $bandera = 1; // no hay dominio
            break;
        }
        // return true; // email ok
    }
    if ($bandera == 0 ) return true;
    else return false;
}

function fechaAtexto($fecha){
    $dia = substr($fecha, 8, 2);
    $mes = substr($fecha, 5, 2);
    $anio = substr($fecha, 0, 4);

    /**
     * Creamos un array con los meses disponibles.
     * Agregamos un valor cualquiera al comienzo del array para que los números coincidan
     * con el valor tradicional del mes. El valor "Error" resultará útil
     **/
    $meses = array('Error', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');

    /**
     * Si el número ingresado está entre 1 y 12 asignar la parte entera.
     * De lo contrario asignar "0"
     **/
    $num_limpio = $mes >= 1 && $mes <= 12 ? intval($mes) : 0;
    $fechaletras = "a los $dia día(s) del mes de $meses[$num_limpio] de ".anio($anio).".";
    return $fechaletras;
    //return $meses[$num_limpio];
    //return $dia;
}
function anio($anio){
    $anio = (int)$anio;
    if ($anio < 2000 || $anio > 2099) return (string)$anio;
    $unidades = array('', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve',
        'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve',
        'veinte', 'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve');
    $decenas = array(3 => 'treinta', 4 => 'cuarenta', 5 => 'cincuenta', 6 => 'sesenta', 7 => 'setenta', 8 => 'ochenta', 9 => 'noventa');
    $resto = $anio - 2000;
    if ($resto == 0) return "dos mil";
    if ($resto < 30) return "dos mil " . $unidades[$resto];
    $d = intdiv($resto, 10);
    $u = $resto % 10;
    return "dos mil " . $decenas[$d] . ($u > 0 ? " y " . $unidades[$u] : "");
}
//crea el combo para las pantallas de solicitud de firma de ciudadano
//$solfirma: recibe del select de cada pantalla
function combo_firma_ciudadano($sol_firma,$db){
    $sqlCmbCiu = "select descripcion, tipo_cert_codi from tipo_certificado where estado = 1 and tipo_cert_codi not in (0)";
    $rsCmbCiu = $db->conn->query($sqlCmbCiu);
    $usr_firma  = $rsCmbCiu->GetMenu2('sol_firma',$sol_firma,"",false,"","Class='select' id='sol_firma'");
    return $usr_firma;
}

function reemplaza_caracteres_html($texto){

    // Cambiamos letras con tildes a formato html
    $origen  = array ("á", "é", "í", "ó", "ú", "à", "è", "ì", "ò", "ù", "ä", "ë", "ï", "ö", "ü"
                    , "â", "ê", "î", "ô", "û", "ã", "õ", "ñ"
                    , "Á", "É", "Í", "Ó", "Ú", "À", "È", "Ì", "Ò", "Ù", "Ä", "Ë", "Ï", "Ö", "Ü"
                    , "Â", "Ê", "Î", "Ô", "Û", "Ã", "Õ", "Ñ"
                    , "ç", "Ç", "°", "º", "ª", "½", "¿", "·", "~"
                    , "©", "®", "™");
    $destino = array ("&aacute;", "&eacute;", "&iacute;", "&oacute;", "&uacute;"
                    , "&agrave;", "&egrave;", "&igrave;", "&ograve;", "&ugrave;"
                    , "&auml;", "&euml;", "&iuml;", "&ouml;", "&uuml;"
                    , "&acirc;", "&ecirc;", "&icirc;", "&ocirc;", "&ucirc;"
                    , "&atilde;", "&otilde;", "&ntilde;"
                    , "&Aacute;", "&Eacute;", "&Iacute;", "&Oacute;", "&Uacute;"
                    , "&Agrave;", "&Egrave;", "&Igrave;", "&Ograve;", "&Ugrave;"
                    , "&Auml;", "&Euml;", "&Iuml;", "&Ouml;", "&Uuml;"
                    , "&Acirc;", "&Ecirc;", "&Icirc;", "&Ocirc;", "&Ucirc;"
                    , "&Atilde;", "&Otilde;", "&Ntilde;"
                    , "&ccedil;", "&Ccedil;", "&deg;", "&ordm;", "&ordf;", "&frac12;", "&iquest;", "&middot;", "&sim;"
                    , "&copy;", "&reg;", "&trade;");
    $texto = str_ireplace($origen, $destino, $texto);

    return $texto;

}

function get_mime_tipe($archivo) {

    $tmp = explode(".",$archivo);
    $ext = $tmp[count($tmp)-1];
    switch( $ext ) {
      case "pdf": return "application/pdf"; break;
      case "exe": return "application/octet-stream"; break;
      case "zip": return "application/zip"; break;
      case "doc": return "application/msword"; break;
      case "xls": return "application/vnd.ms-excel"; break;
      case "ppt": return "application/vnd.ms-powerpoint"; break;
      case "gif": return "image/gif"; break;
      case "png": return "image/png"; break;
      case "jpeg":
      case "jpg": return "image/jpg"; break;
      case "bmp": return "image/bmp"; break;
      case "webp": return "image/webp"; break;
      case "tif":
      case "tiff": return "image/tiff"; break;
      case "mp3": return "audio/mpeg"; break;
      case "wav": return "audio/x-wav"; break;
      case "mpeg":
      case "mpg":
      case "mpe": return "video/mpeg"; break;
      case "mov": return "video/quicktime"; break;
      case "avi": return "video/x-msvideo"; break;

      case "php":
      case "htm":
      case "html":
      case "txt": return "text/plain"; break;

      default: return "application/force-download";
    }
}

function verificar_dispositivo_movil() {
    return preg_match( '/ipod|iphone|ipad|android|opera mini|blackberry|palm os|windows ce|Bada|Windows Phone|Symbian/i', $_SERVER['HTTP_USER_AGENT'] );
}

function verificar_navegador_firefox() {
    return preg_match( '/Firebird|Firefox/i', $_SERVER['HTTP_USER_AGENT'] );
}





// Funciones del calendario
//include_once __DIR__."/js/calendario_php/calendario_php.php";
//include_once (__DIR__ . "/js/calendario_php/calendario_php.php");
// Funciones auxiliares para dependencias (movidas de area_ajax_grabar.php)

function pertenece($inst_codi,$usua_codi,$db,$tipo){
    $sql="select depe_codi_tmp from usuario_dependencia 
    where usua_codi = $usua_codi and inst_codi = $inst_codi";
    //echo $sql;
    $rs=$db->conn->query($sql);
    
     if (!$rs->EOF){
             if ($tipo==1)
                 return 1;
             else
                 return $rs->fields['DEPE_CODI_TMP'];
             }
             return 0;
}

function countPadre($depe_codi,$db,$tipo=0){
    $sql="select count(depe_codi) as contador from dependencia where depe_codi_padre = $depe_codi"; 
    //echo $sql;
    $rs=$db->conn->query($sql);
     if (!$rs->EOF){
           if ($tipo==1){
             $contador = $rs->fields['CONTADOR'];
                if ($contador>=1)//si tiene hijos
                return 1;
           }else{
               return $rs->fields['CONTADOR'];
           }
     }else//si no ecisten registros
     return 0;
}

function obtenerCodigos($usr_codigo,$depe_codigo,$db,$tipo=0){    
    $sql="select depe_codi_tmp from usuario_dependencia where usua_codi = $usr_codigo";
    //echo $sql;
    $rs=$db->conn->query($sql);
     if (!$rs->EOF){
         
             $depe_codi_tmp = $rs->fields["DEPE_CODI_TMP"];            
             $depe_codigos = explode(",",$depe_codi_tmp);             
          if ($tipo==1){//para guardar   
         // $resultado = substr_count($depe_codi_tmp, $depe_codigo); 
         
            for($i=0;$i<sizeof($depe_codigos);$i++){ 
                if ($i!=0){                            
                    
                    if ($depe_codigos[$i]==$depe_codigo){                        
                        return 1;
                        break;
                    }//i
                 }//if
            }//for      
          }else{//recorro las dependencias en el campo
                     for($i=0;$i<sizeof($depe_codigos);$i++){ 
                         if ($i!=0){ 
                            if ($depe_codigos[$i]!=$depe_codigo)//elimino del campo la dependencia
                                if ($i==1)//si es el primer registro para el campo
                                 $depe_codigo_up=",".$depe_codigos[$i];
                                else//sigo concatenando
                                $depe_codigo_up=$depe_codigo_up.",".$depe_codigos[$i];
                         }
                    }
            
                  
            return $depe_codigo_up;
          }
         }
     
     else return 0;//si no hay registros
}

function obtenerDependenciaUso($usr_codigo,$depe_codigo,$db){    
    $sql="select depe_codi_tmp from usuario_dependencia where usua_codi = $usr_codigo";
    
    $rs=$db->conn->query($sql);
     if (!$rs->EOF){
         
             $depe_codi_tmp = $rs->fields["DEPE_CODI_TMP"];
            
             $depe_codigos = explode(",",$depe_codi_tmp);
           
            for($i=0;$i<sizeof($depe_codigos);$i++){ 
                if ($i!=0){  
                    //echo $depe_codigos[$i]."==".$depe_codigo;
                    if ($depe_codigos[$i]==$depe_codigo){
                        return 1;
                        break;
                    }                   
                }
         }
     }
     else return 0;
}

function opcionGrabar($depe_codi,$depe_codi_padre,$admin_areas,$existeAdmin,$existe){
    
    if ($admin_areas=='')
        $admin_areas=0;
    if ($existeAdmin=='')
        $existeAdmin = 0;
    
    if ($admin_areas==0){//administra areas
            if ($existe==0)//administra el area                
                return 1;
            else                
                return 0;
        }else{//no administra           
            
            if ($existeAdmin==1){
                if ($existe==0)                
                    return 1;
                else                
                    return 0;
            }else
                return 0;
        }        
}

include_once __DIR__ . "/js/calendario_php/calendario_php.php";
?>
