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
function html_head ($flag_estilos=true, $flag_index=false) {
    try {
        $texto = "<head>
            <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
            <title>.:: Quipux - Sistema de Gesti&oacute;n Documental ::.</title>
            <link href='/estilos/orfeo.css' rel='stylesheet' type='text/css'>
            ";
        if ($flag_estilos) {
            $texto .= " <link href='/estilos/light_slate.css' rel='stylesheet' type='text/css'>
            <link href='/estilos/splitmenu.css' rel='stylesheet' type='text/css'>
            <link href='/estilos/template_css.css' rel='stylesheet' type='text/css'>
            <link href='/estilos/navbar.css' rel='stylesheet' type='text/css'>
            <link href='/estilos/mejoras.css?v=20' rel='stylesheet' type='text/css'>
            <link href='/estilos/skin.css?v=18' rel='stylesheet' type='text/css'>
            <link rel='shortcut icon' href='/imagenes/favicon.ico'>
            <link rel='stylesheet' type='text/css' href='/js/spiffyCal/spiffyCal_v2_1.css'>
            <link rel='stylesheet' type='text/css' href='/js/calendario_php/calendario_php.css'>";
        }

        $texto .= " <script type='text/JavaScript' src='/js/calendario_php/calendario_php.js'></script>
                <script type='text/JavaScript' src='/js/funciones_js.js'></script>
                <script type='text/JavaScript'>
                    //document.oncontextmenu = function(){return false} // Click derecho
              ";
//        if (!$flag_index) {
//            $texto .= "window.focus();
//                try {
//                    if (window.menubar.visible || window.toolbar.visible) { // si estan activas las barras llame a index para que se bloqueen
//                        if (detectarPhone()==0)
//                            window.location='index.php';
//                    }
//                } catch (e) {}
//                ";
//        }

        $texto .= " ns4 = (document.layers)? true:false;
                ie4 = (document.all)? true:false;
                document.onkeydown = keyDown;
                if (ns4) document.captureEvents(Event.KEYDOWN);

                function keyDown(e){
                    var tecla, res = true;
                    if (ns4) tecla = e.which;
                    else if (ie4) tecla = event.keyCode;
                    else {
                        var evt = arguments.length ? arguments[0] : window.event;
                        tecla = evt.which;
                    }
                    switch(tecla){
                      case 116:
                      case 117:
                      case 118:
//                      case 222:
                        res = false;
                        break;
                      default:
                        res = true;
                        break;
                    }
                    //alert(res+'tecla----'+tecla);
                    return res;
                }

                function fjs_verificar_plugin_navegador (nombre_plugin) {
                    var plugin = '';
                    try {
                        for (var a = 0; a < navigator.plugins.length; a++) {
                            plugin = navigator.plugins[a];
                            if (plugin.name.toLowerCase().indexOf(nombre_plugin.toLowerCase()) >= 0)
                                return true;
                        }
                        return false;
                    } catch(e) {
                        return false;
                    }
                }
     
            </script>
            <script type='text/JavaScript' src='/js/shortcut.js'></script>
            <script type='text/JavaScript' src='/js/websocket.js?v=<?=time()?>'></script>   
             
                    
        </head>";
        return $texto;
    }
    catch (Throwable $e) {
        var_dump($e);
    }

}

function html_head1 ($flag_estilos=true, $flag_index=false) {
    try {
        $texto = "<head>
            <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
            <title>.:: Quipux - Sistema de Gesti&oacute;n Documental ::.</title>
            <link href='".__DIR__."/estilos/orfeo.css' rel='stylesheet' type='text/css'>
            ";
        if ($flag_estilos) {
            $texto .= " <link href='/estilos/light_slate.css' rel='stylesheet' type='text/css'>
            <link href='../estilos/splitmenu.css' rel='stylesheet' type='text/css'>
            <link href='/estilos/template_css.css' rel='stylesheet' type='text/css'>
            <link href='/estilos/navbar.css' rel='stylesheet' type='text/css'>
            <link href='/estilos/mejoras.css?v=20' rel='stylesheet' type='text/css'>
            <link href='/estilos/skin.css?v=18' rel='stylesheet' type='text/css'>
            <link rel='shortcut icon' href='/imagenes/favicon.ico'>
            <link rel='stylesheet' type='text/css' href='".__DIR__."/js/spiffyCal/spiffyCal_v2_1.css'>
            <link rel='stylesheet' type='text/css' href='".__DIR__."/js/calendario_php/calendario_php.css'>";
        }

        $texto .= file_get_contents(__DIR__."/herramienta_monitoreo_quipux.js");
        $texto .= " <script type='text/JavaScript' src='".__DIR__."/js/calendario_php/calendario_php.js'></script>
                    
                    <script type='text/JavaScript'>
                    //document.oncontextmenu = function(){return false} // Click derecho
              ";
        if (!$flag_index) {
            $texto .= "window.focus();
                try {
                    if (window.menubar.visible || window.toolbar.visible) { // si estan activas las barras llame a index para que se bloqueen
                        if (detectarPhone()==0)
                            window.location='index.php';
                    }
                } catch (e) {}
                ";
        }

        $texto .= " ns4 = (document.layers)? true:false;
                ie4 = (document.all)? true:false;
                document.onkeydown = keyDown;
                if (ns4) document.captureEvents(Event.KEYDOWN);

                function keyDown(e){
                    var tecla, res = true;
                    if (ns4) tecla = e.which;
                    else if (ie4) tecla = event.keyCode;
                    else {
                        var evt = arguments.length ? arguments[0] : window.event;
                        tecla = evt.which;
                    }
                    switch(tecla){
                      case 116:
                      case 117:
                      case 118:
//                      case 222:
                        res = false;
                        break;
                      default:
                        res = true;
                        break;
                    }
                    //alert(res+'tecla----'+tecla);
                    return res;
                }

                function fjs_verificar_plugin_navegador (nombre_plugin) {
                    var plugin = '';
                    try {
                        for (var a = 0; a < navigator.plugins.length; a++) {
                            plugin = navigator.plugins[a];
                            if (plugin.name.toLowerCase().indexOf(nombre_plugin.toLowerCase()) >= 0)
                                return true;
                        }
                        return false;
                    } catch(e) {
                        return false;
                    }
                }
     
            </script>
            <script type='text/JavaScript' src='".__DIR__."/js/shortcut.js'></script>
            <script type='text/JavaScript' src='/js/websocket.js?v=<?=time()?>'></script>            
        </head>";
        return $texto;
    }
    catch (Throwable $e) {
        var_dump($e);
    }

}

// Imprime el encabezado en páginas como login.php y otras
function html_encabezado () {
    $rsw = 1;
    $rsw=base64_encode($rsw);
    $texto = "<div id='header'><div class='shad-r'><div class='shad-l'><div class='moduletable'>
                <table width='100%' cellpadding='0' cellspacing='0' >
                    <tr>
                        <td width='1%'></td>
						<td width='22%'><img alt='Escudo' src='../imagenes/2.png' height='60' width='190'></td>
                        <td width='52%' halign='center'><h1 align='center' ><font color='#002B5C'>Sistema Documental Universidad de Cuenca</font></h1></td>
                        <td  width='30%'><div id='nav-big'>
                           <ul><table align='right'>
                                <tr><td><li class='active_menu'><a href='' class='b6' onclick='ver_ayuda()'></a></li></td></tr>
                           </table></ul></div>
                        </td>
                    </tr>
                </table>
                </div></div></div></div>
                <script>
                    function ver_ayuda() {
                        windowprops = 'top=0,left=0,location=no,status=no, menubar=no,scrollbars=yes, resizable=yes,width=800,height=550';
                        preview = window.open('inf_soporte.php?rsw=$rsw' , 'ayuda', windowprops);
                    }
   
                </script>";
    return $texto;
}

// Imprime el pie de página en páginas como login.php y otras
function html_pie_pagina () {
    $texto = "<div id='footer'><div class='shad-r'><div class='shad-l'><div class='tabber' id='tab'><div class='tabbertab' title='Flushed Away'>
                <table width='100%'>
                    <tr>
                        <td align='center'>
                            <center>
                                <!--h3>Subsecretar&iacute;a de Gobierno Electr&oacute;nico
                                - Secretar&iacute;a Nacional de la Administraci&oacute;n P&uacute;blica - 2008</h3-->
                                (Basado en el sistema de gesti&oacute;n documental ORFEO <a href='http://www.orfeogpl.org'>www.orfeogpl.org</a>)
                            </center>
                        </td>
                    </tr>
                </table>
            </div></div></div></div></div>";
    return $texto;
}

// Valida el tipo de browser en login.php y otras
function html_validar_browser () {
    include(__DIR__.'/config.php');
    if (!isset($versionEstable) or trim($versionEstable)=='') $versionEstable = 17;
    $versionClienteFox = substr($_SERVER["HTTP_USER_AGENT"], strpos($_SERVER["HTTP_USER_AGENT"], 'Firefox/') + 8);
    $texto = "<script type='text/javascript' src='".__DIR__."/js/validar_browser.js'></script>
    <div id='check_browser'><div class='shad-1'><div class='shad-2'><div class='shad-3'><div class='shad-4'><div class='shad-5'>
    <table align='center' width='100%' cellpadding='0' cellspacing='0' class='mainbody'>
        <tr>
            <td align='center' width='100%'>
            </td>
        </tr>
        
    </table>
    </div></div></div></div></div></div>";
    return $texto;
}

function html_error($mensaje, $estilos=true) {
    $en_sesion = (session_status() === PHP_SESSION_ACTIVE) && (isset($_SESSION["usua_codi"]) || !empty($_SESSION["krd"]));

    $head = "<!DOCTYPE html>
<html lang='es'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1'>
<title>.:: Quipux - Sistema de Gesti&oacute;n Documental ::.</title>
<link rel='shortcut icon' href='/imagenes/favicon.ico' type='image/x-icon'>
<link rel='stylesheet' href='/estilos/soporte.css?v=4'>
</head>";

    if ($en_sesion) {
        return $head . "
<body>
<main class='soporte-main soporte-main--center'>
    <div class='glass soporte-card soporte-card--auth'>
        <div class='auth-badge'><svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'><rect x='3' y='11' width='18' height='10' rx='2'></rect><path d='M7 11V7a5 5 0 0 1 10 0v4'></path></svg></div>
        <div class='auth-msg'>$mensaje</div>
        <div class='auth-actions'>
            <button type='button' class='btn-regresar' onclick='regresarQuipux()'>Regresar</button>
        </div>
    </div>
</main>
<script>
function regresarQuipux(){
    if (window.opener && !window.opener.closed) { window.close(); return; }
    if (window.history.length > 1) { window.history.back(); return; }
    var w = (window.top && window.top !== window) ? window.top : window;
    w.location.href = '/index_frames.php';
}
</script>
</body>
</html>";
    }

    return $head . "
<body>
<header class='topbar'>
    <div class='topbar-in'><a class='brand' href='index.php'>Quipux UCUENCA</a><span class='topbar-sub'>Acceso al Sistema</span><a class='topbar-home' href='index.php' title='Regresar al inicio'><svg viewBox='0 0 24 24'><line x1='19' y1='12' x2='5' y2='12'/><polyline points='12 19 5 12 12 5'/></svg><span>Inicio</span></a></div>
</header>
<main class='soporte-main soporte-main--center'>
    <div class='glass soporte-card soporte-card--auth'>
        <div class='auth-badge'><svg viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='1.8' stroke-linecap='round' stroke-linejoin='round'><rect x='3' y='11' width='18' height='10' rx='2'></rect><path d='M7 11V7a5 5 0 0 1 10 0v4'></path></svg></div>
        <h1>Sistema de Gesti&oacute;n Documental &middot; QUIPUX</h1>
        <div class='auth-msg'>$mensaje</div>
    </div>
</main>
<footer class='soporte-foot'>Direcci&oacute;n de Tecnolog&iacute;as de la Informaci&oacute;n y Comunicaci&oacute;n &middot; DTIC</footer>
</body>
</html>";
}

function validar_telefono_movil() {
    // Verifica si estan accediendo desde un telefono
    $mobile_browser = false;
    //$_SERVER['HTTP_USER_AGENT'] -> el agente de usuario que está accediendo a la página.
    //preg_match -> Realizar una comparación de expresión regular
    if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone)/i',strtolower($_SERVER['HTTP_USER_AGENT']))){
        $mobile_browser = true;
    }
    //$_SERVER['HTTP_ACCEPT'] -> Indica los tipos MIME que el cliente puede recibir.
    if((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml')>0) or
            ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))){
        $mobile_browser = true;
    }
    $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));
    $mobile_agents = array(
            'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
            'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
            'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
            'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
            'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
            'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
            'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
            'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
            'wapr','webc','winw','winw','xda','xda-','bb');
    //buscar agentes en el array de agentes
    if(in_array($mobile_ua,$mobile_agents)){
        $mobile_browser = true;
    }
    //$_SERVER['ALL_HTTP'] -> Todas las cabeceras HTTP
    //strpos -> Primera aparicion de una cadena dentro de otra
    if(strpos(strtolower((string)($_SERVER['ALL_HTTP'] ?? '')),'OperaMini')!==false) {
        $mobile_browser = true;
    }
    if(strpos(strtolower((string)($_SERVER['HTTP_USER_AGENT'] ?? '')),'windows')!==false) {
        $mobile_browser = false;
    }

    return $mobile_browser;
}
//validar caja de texto
//tags html para imput text
//$nomCajaTexto= name o id
//$valorTexto= value
//$numeroCaracteresTexto, numero de caracteres (configurado en el config.php)
//$titulo='', title
//$size='30', tamaño predeterminado 30 por defecto
//$busqueda='0', si es 0 borra el contenido de la caja de texto
function cajaTextoValida($nomCajaTexto,$valorTexto,$numeroCaracteresTexto,$javascript='',$titulo='',$size='30',$busqueda='0'){
    $nomCajaTexto2 = '"'.$nomCajaTexto.'"';
    $html="<input type=text id='$nomCajaTexto' name='$nomCajaTexto' value='$valorTexto' onblur='evento_ver(event,this,$numeroCaracteresTexto,$nomCajaTexto2,1); numeroCarecteresDePara(this,$numeroCaracteresTexto,$nomCajaTexto2,1);' class='tex_area'  size='$size' title='$titulo' $javascript>";
    $html.='<div id="div_'.$nomCajaTexto.'" name=id="div_'.$nomCajaTexto.'" style="display:none"><font color="red">Se requiere mayor información para el criterio de búsqueda, por favor ingrese al menos '.$numeroCaracteresTexto.' caracteres</font></div>';
    return $html;
}

function dibujarDiv($ruta_raiz,$nom_div,$numeroCaracteresTexto){
    
     $html= '<div id="'.$nom_div.'" name="'.$nom_div.'" style="display:none">
                        Se requiere más información, ingrese al menos '.$numeroCaracteresTexto                        
                    .' caracteres </div>';
     return $html;
}



function count_inbox($inbox) {
    global $db, $CFG;

    if ($CFG->version_light) {
        return "-1";
    }

    $sql = "";
    $id_user = $_SESSION["usua_codi"];
    $inst_codi = (int)($_SESSION["inst_codi"] ?? 0);
    $and_inst = " AND r.radi_inst_actu = $inst_codi";

    $meses = (int)($GLOBALS['config_numero_meses'] ?? 3);
    $fecha_desde = date("Y-m-d", strtotime(date("Y-m-d") . ($meses < 3 ? " - 1 month" : " - 3 month")));
    $fecha_hasta = date("Y-m-d");

    switch ($inbox) {
        case 1: //En elaboración
            // "leidos" cuenta los NO leidos: el badge se arma como noLeidos/total (ver
            // el final de esta funcion) y asi lo hacen el resto de bandejas. Esta
            // consulta contaba los leidos (radi_leido <> 0) y mostraba el numero
            // invertido respecto de Recibidos, Informados y Tareas.
            $sql = "SELECT COUNT(*) AS total,
                        COUNT(*) FILTER (WHERE r.radi_leido = 0) AS leidos
                    FROM radicado r
                    WHERE r.esta_codi = 1
                        AND r.radi_usua_actu= $id_user $and_inst
                        AND r.radi_nume_radi NOT IN (SELECT t.radi_nume_radi FROM tarea t WHERE t.estado=1 AND t.usua_codi_ori=$id_user)";
            break;

        case 2: //Recibidos
            $sql = "SELECT COUNT(*) AS total,
                        count(case when radi_leido=0 then 1 else null end) as leidos
                    FROM radicado r
                    WHERE r.esta_codi = 2
                        AND r.radi_usua_actu = $id_user $and_inst
                        AND r.radi_nume_radi NOT IN (SELECT t.radi_nume_radi from tarea t WHERE t.estado = 1 AND usua_codi_ori = $id_user)";
            break;

        case 6:  //Eliminado
        case 84: //Eliminado - ciudadanos firma
            $sql = "SELECT COUNT(*) AS total,
                        0 as leidos
                    FROM radicado r
                    WHERE r.esta_codi = 7 AND r.radi_usua_actu = $id_user $and_inst";
            break;

        case 7:  //No enviado
        case 85: //No enviado - ciudadanos firma
            $sql = "SELECT COUNT(*) AS total,
                        0 as leidos
                    FROM radicado r
                    WHERE r.esta_codi = 3 AND r.radi_usua_actu = $id_user
                        AND r.radi_nume_radi = r.radi_nume_temp $and_inst";
            break;

        case 8: //Enviados
            $sql = "SELECT COUNT(*) AS total,
                        0 as leidos
                    FROM radicado r
                    WHERE r.esta_codi = 6
                        AND r.radi_nume_radi = r.radi_nume_temp AND r.radi_usua_actu = $id_user $and_inst";
            break;

        case 10: //Archivado
            $sql = "SELECT COUNT(*) AS total,
                        0 as leidos
                    FROM radicado r
                    WHERE r.esta_codi = 0 AND r.radi_usua_actu = $id_user $and_inst";
            break;

        case 12: //Reasignados
            $sql = "SELECT COUNT(DISTINCT h.radi_nume_radi) AS total,
                        0 as leidos
                    FROM hist_eventos h
                    WHERE h.sgd_ttr_codigo = 9 AND h.usua_codi_ori = $id_user
                        AND h.hist_fech::date BETWEEN '$fecha_desde' AND '$fecha_hasta'";
            break;

        case 13: //Informados
            $sql = "select count(1) as total, count(case when info_leido=0 then 1 else null end) as leidos from informados where usua_codi=$id_user";
            break;

        case 14: //compartida
            $sql = "select count(1) as total, count(case when radi_leido=0 then 1 else null end) as leidos from radicado r where esta_codi=2 and radi_usua_actu=".$_SESSION["usua_codi_jefe"].$and_inst." and radi_nume_radi not in (select radi_nume_radi from tarea where estado=1 and usua_codi_ori=".$_SESSION["usua_codi_jefe"].")";
            break;

        case 15: //Tareas Recibidas
            $sql = "select count(1) as total, count(case when leido=0 then 1 else null end) as leidos from tarea where estado=1 and $id_user=usua_codi_dest";
            break;

        case 16: //Tareas Enviadas
            $sql = "select count(1) as total, count(case when leido=0 then 1 else null end) as leidos from tarea where $id_user=usua_codi_ori";
            break;

        case 80:
            $sql = "select count(1) as total, 0 as leidos from radicado where radi_nume_radi::text like '%1' and esta_codi in (0,2)
                        and string_to_array(trim(both '-' from radi_usua_rem), '--') @> array['$id_user']";
            break;

        case 81: // Recibidos ciudadanos
            $sql = "select count(1) as total, 0 as leidos from radicado where radi_nume_radi::text like '%1' and esta_codi=6
                        and string_to_array(trim(both '-' from radi_usua_dest), '--') @> array['$id_user']";
            break;
        case 82: //Documentos en elaboración - ciudadanos firma
            $sql = "select count(1) as total, 0 as leidos from radicado where radi_usua_actu=$id_user and esta_codi=1";
            break;
        case 83: //Documentos recibidos - ciudadanos firma
            $sql = "select count(1) as total, 0 as leidos from radicado where radi_nume_radi::text like '%1' and esta_codi in (2,6)
                        and string_to_array(trim(both '-' from radi_usua_dest), '--') @> array['$id_user']";
            break;
        case 86: //Documentos enviados - ciudadanos firma
            $sql = "select count(1) as total, 0 as leidos from radicado where string_to_array(trim(both '-' from radi_usua_rem), '--') @> array['$id_user']
                        and ((radi_nume_radi::text like '%1' and esta_codi in (0,2)) or (radi_nume_radi::text like '%0' and esta_codi in (6)))";
            break;
        case 90: //Documentos firmados electrónicamente por ciudadanos
            $sql = "select count(1) as total, 0 as leidos from radicado where esta_codi=9 and radi_inst_actu=".$_SESSION["inst_codi"];
            break;
        case 99: //Bandeja Por Imprimir
            $sql = "select count(distinct b.radi_nume_radi) as total, 0 as leidos
                    from radicado b
                    join usuarios ur on split_part(b.radi_usua_rem,'-',2)::integer = ur.usua_codi
                        and ur.depe_codi = ".$_SESSION["depe_codi"]."
                    where b.esta_codi = 5 and b.radi_inst_actu = ".$_SESSION["inst_codi"];
            break;
        default:
            return "-1";
    }

    $txt_count = '';
    if ($sql != "") {
        $rs = $db->query($sql);
        if (!$rs || $rs->EOF) return "0";
        if ($rs->fields["LEIDOS"] > 0) {
            $txt_count .= $rs->fields["LEIDOS"] . "/";
        }
        $txt_count .= $rs->fields["TOTAL"];
    }

    return $txt_count;
}

function dibujar_loader_pantalla($mensaje = 'Cargando...', $logo = 'imagenes/escudo_blanco.png') {
    static $inyectado = false;
    if ($inyectado) { return; }
    $inyectado = true;

    $mensaje = htmlspecialchars($mensaje, ENT_QUOTES);
    $logo    = htmlspecialchars($logo, ENT_QUOTES);

    echo <<<HTML
<div id="quipux-loader" role="alert" aria-live="assertive" aria-busy="true">
  <div class="ql-box">
    <img src="$logo" alt="" class="ql-logo" onerror="this.style.display='none'">
    <div class="ql-spinner"></div>
    <div class="ql-text" id="quipux-loader-text">$mensaje</div>
  </div>
</div>
<style>
  #quipux-loader{position:fixed;top:0;left:0;right:0;bottom:0;z-index:99999;display:none;
    align-items:center;justify-content:center;background:rgba(0,29,63,.88);
    backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px)}
  #quipux-loader.ql-visible{display:flex}
  #quipux-loader .ql-box{text-align:center;color:#fff;font-family:Arial,Helvetica,sans-serif}
  #quipux-loader .ql-logo{width:90px;height:auto;margin-bottom:22px;animation:qlPulse 1.6s ease-in-out infinite}
  #quipux-loader .ql-spinner{width:54px;height:54px;margin:0 auto 16px;border-radius:50%;
    border:5px solid rgba(255,255,255,.25);border-top-color:#fff;animation:qlSpin .9s linear infinite}
  #quipux-loader .ql-text{font-size:17px;font-weight:600;letter-spacing:.3px}
  @keyframes qlSpin{to{transform:rotate(360deg)}}
  @keyframes qlPulse{0%,100%{opacity:1}50%{opacity:.45}}
</style>
<script>
  function bloquearPantalla(msg){
    var l=document.getElementById('quipux-loader'); if(!l){return;}
    if(msg){var t=document.getElementById('quipux-loader-text'); if(t){t.textContent=msg;}}
    l.classList.add('ql-visible');
  }
  function desbloquearPantalla(){
    var l=document.getElementById('quipux-loader'); if(l){l.classList.remove('ql-visible');}
  }
</script>
HTML;
}