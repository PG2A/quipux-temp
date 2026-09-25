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
 * @package    usuarios
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// En modo solicitud (alta de ciudadano desde la búsqueda de destinatarios, RQT-7)
// la página que incluye este archivo ya validó al usuario y no dibuja el menú
// principal, así que no se exige el permiso 16 aquí.
if (empty($modo_solicitud) && ($_SESSION["usua_admin_sistema"] ?? 0) != 1){
    if(($_SESSION["usua_perm_ciudadano"] ?? 0) != 1) {
        die(html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina."));
    }
}
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
require_once(dirname(__DIR__, 2).'/lib/outputcomponents.php');

/**
 * Generates and returns the HTML string for the <head> section of a webpage.
 *
 * The generated HTML includes:
 * - A DOCTYPE declaration for XHTML 1.0 Transitional.
 * - Opening and closing <head> tags.
 * - Multiple <link> tags to reference stylesheets, including local and external CSS files.
 * - Multiple <script> tags to include JavaScript files for additional functionality.
 * - Metadata for language specification.
 *
 * @return string The complete HTML markup for the <head> section.
 */
function print_html_head() {
    $html  = '';
    $html .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//ES" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
    $html .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">';
    $html .= html_writer::start_tag('head');

    $html .= html_writer::tag('link', '', array('rel' => 'shortcut icon', 'href' => '/imagenes/favicon.ico'));
    $html .= html_writer::tag('link', '', array('rel' => 'stylesheet', 'href' => '/estilos/light_slate.css', 'type' => 'text/css'));
    $html .= html_writer::tag('link', '', array('rel' => 'stylesheet', 'href' => '/estilos/splitmenu.css', 'type' => 'text/css'));
    $html .= html_writer::tag('link', '', array('rel' => 'stylesheet', 'href' => '/estilos/template_css.css', 'type' => 'text/css'));
    $html .= html_writer::tag('link', '', array('rel' => 'stylesheet', 'href' => '/estilos/navbar.css', 'type' => 'text/css'));
    $html .= html_writer::tag('link', '', array('rel' => 'stylesheet', 'href' => '/js/spiffyCal/spiffyCal_v2_1.css', 'type' => 'text/css'));
    $html .= html_writer::tag('link', '', array('rel' => 'stylesheet', 'href' => '/js/calendario_php/calendario_php.css', 'type' => 'text/css'));
    $html .= html_writer::tag('link', '', array('rel' => 'stylesheet', 'href' => '/style/mnuUsuariosH.css?v=3', 'type' => 'text/css'));
    $html .= html_writer::tag('link', '', array('rel' => 'stylesheet', 'href' => '/style/fontawesome/css/all.min.css', 'type' => 'text/css'));
    $html .= html_writer::tag('link', '', array('rel' => 'stylesheet', 'href' => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap'));

    $html .= html_writer::tag('script', '', array('src' => '/js/calendario_php/calendario_php.js', 'type' => 'text/javascript'));
    $html .= html_writer::tag('script', '', array('src' => '/js/funciones_js.js', 'type' => 'text/javascript'));
    $html .= html_writer::tag('script', '', array('src' => '/js/shortcut.js', 'type' => 'text/javascript'));
    $html .= html_writer::tag('script', '', array('src' => '/js/websocket.js', 'type' => 'text/javascript'));
    $html .= html_writer::tag('script', '', array('src' => '/js/mnuUsuariosH.js', 'type' => 'text/javascript'));

    $html .= html_writer::end_tag('head');

    return $html;
}

/**
 * Generates and returns the HTML markup for a navigation menu with multiple options.
 *
 * The menu includes links for returning to the previous menu, searching for users, and creating a new user. Each menu item is styled and structured with appropriate HTML tags and classes.
 *
 * @return string The generated HTML string representing the navigation menu.
 */
function print_menu() {

    $html  = '';
    $html .= html_writer::start_tag('div',array('style' => 'padding: 20px; text-align: left;'));
    $html .= html_writer::start_tag('nav', array('class' => 'menu-container', 'role' => 'navigation', 'aria-label' => 'Menú de usuarios'));
    $html .= html_writer::start_tag('div',array('class' => 'menu-buttons'));

    $html .= html_writer::start_tag('a', array('href'=>'/Administracion/usuarios/mnuUsuarios.php', 'class' => 'menu-item', 'title' => 'Regresar al menú anterior'));
    $html .= html_writer::tag('div', '<i class="fa-solid fa-arrow-left"></i>', array('class' => 'menu-btn'));
    $html .= html_writer::tag('span', 'Regresar', array('class' => 'menu-label'));
    $html .= html_writer::end_tag('a');

    $html .= html_writer::start_tag('a', array('href'=>'/Administracion/usuarios/cuerpoUsuario.php?accion=2', 'class' => 'menu-item', 'title' => 'Consultar usuario'));
    $html .= html_writer::tag('div', '<i class="fa-solid fa-user-magnifying-glass"></i>', array('class' => 'menu-btn'));
    $html .= html_writer::tag('span', 'Buscar', array('class' => 'menu-label'));
    $html .= html_writer::end_tag('a');

    $html .= html_writer::start_tag('a', array('href'=>'/Administracion/usuarios/adm_usuario.php?accion=1', 'class' => 'menu-item', 'title' => 'Crear nuevo usuario'));
    $html .= html_writer::tag('div', '<i class="fa-solid fa-user-plus"></i>', array('class' => 'menu-btn'));
    $html .= html_writer::tag('span', 'Crear', array('class' => 'menu-label'));
    $html .= html_writer::end_tag('a');

    $html .= html_writer::end_tag('div');
    $html .= html_writer::end_tag('nav');
    $html .= html_writer::end_tag('div');

    return $html;
}

function print_tabs_menu_user($usr_codigo = 0, $tiene_subrogacion = 0, $usr_perfil = 0, $usr_depe = 0, $menu = '') {

    $html  = '';

    $html .= html_writer::tag('button','<span>Información del usuario</span>',array('class'=>'tab-button', 'data-tab' => 'div_informacion_usr', 'type' => 'button'));
    $html .= html_writer::tag('button','<span>Permisos</span>',array('class'=>'tab-button', 'data-tab' => 'div_permisos_desp', 'type' => 'button'));

    if ($usr_codigo>0) {
        $html .= html_writer::tag('button','<span>Solicitud de Respaldos</span>',array('class'=>'tab-button', 'data-tab' => 'div_backup', 'type' => 'button'));
    }

    $html .= html_writer::tag('button','<span>Modificaciones</span>',array('class'=>'tab-button', 'data-tab' => 'div_recorrido', 'type' => 'button'));

    if ($usr_codigo>0 and $usr_perfil==1) {
        if ($tiene_subrogacion==0) {
            $url = "../subrogacion/buscar_usuario_nuevo_subr.php?accion=2&usr_subrogado=$usr_codigo&depe_codi_get=$usr_depe&cargo_tipo_get=$usr_perfil";
            $html .= html_writer::tag('a','<span>Subrogación</span>', array('href'=> $url,'class'=>'tab-button-link', 'title' => 'Subrogación'));
        } else {
            $url = "../subrogacion/buscar_usuario_nuevo_subr_des.php?accion=2&usr_subrogado=$usr_codigo&depe_codi_get=$usr_depe&cargo_tipo_get=$usr_perfil";
            $html .= html_writer::tag('a','<span>Desactivar Subrogación</span>', array('href'=> $url,'class'=>'tab-button-link', 'title' => 'Desactivar Subrogación', 'style' => 'background-color: #d9534f; color: white;'));
        }
    }

    return $html;
}

function print_tabs_external_user() {
    $html  = '';

    $isAdmin = isset($_SESSION["usua_admin_sistema"]) && $_SESSION["usua_admin_sistema"] == 1;
    $isCitizen = isset($_SESSION["usua_perm_ciudadano"]) && $_SESSION["usua_perm_ciudadano"] == 1;

    // Modo solicitud (RQT-7): la página ya validó al usuario; sin permiso 16 no se
    // dibuja el menú (sus opciones exigen ese permiso) pero tampoco se bloquea.
    if (!$isAdmin && !$isCitizen && !empty($GLOBALS['modo_solicitud'])) return '';
    if (!$isAdmin && !$isCitizen) {
        die(html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina."));
    }

    $html .= html_writer::start_tag('div',array('style' => 'padding: 20px; text-align: left;'));
    $html .= html_writer::start_tag('nav', array('class' => 'menu-container', 'role' => 'navigation', 'aria-label' => 'Menú de usuarios'));
    $html .= html_writer::start_tag('div',array('class' => 'menu-buttons'));

    $html .= html_writer::start_tag('a', array('href'=>'../formAdministracion.php', 'class' => 'menu-item', 'title' => 'Regresar al menú anterior'));
    $html .= html_writer::tag('div', '<i class="fa-solid fa-arrow-left"></i>', array('class' => 'menu-btn'));
    $html .= html_writer::tag('span', 'Regresar', array('class' => 'menu-label'));
    $html .= html_writer::end_tag('a');

    if($_SESSION["perm_validar_ciudadano"]==1 || $_SESSION["usua_codi"]==0) {
        $html .= html_writer::start_tag('a', array('href'=>'adm_usuario_ext_combinar.php', 'class' => 'menu-item', 'title' => 'Combinar usuarios'));
        $html .= html_writer::tag('div', '<i class="fa-solid fa-users"></i>', array('class' => 'menu-btn'));
        $html .= html_writer::tag('span', 'Combinar', array('class' => 'menu-label'));
        $html .= html_writer::end_tag('a');
    }

    //    if (!isset($cerrar)) $cerrar="No";
    $cerrar="No";
    $url = "adm_usuario_ext.php?cerrar=$cerrar&accion=1";
    $html .= html_writer::start_tag('a', array('href'=> $url, 'class' => 'menu-item', 'title' => 'Crear usuario externo'));
    $html .= html_writer::tag('div', '<i class="fa-solid fa-user-plus"></i>', array('class' => 'menu-btn'));
    $html .= html_writer::tag('span', 'Crear', array('class' => 'menu-label'));
    $html .= html_writer::end_tag('a');

    $html .= html_writer::start_tag('a', array('href'=>'cuerpoUsuario_ext.php?cerrar=$cerrar&accion=2', 'class' => 'menu-item', 'title' => 'Editar usuario externo'));
    $html .= html_writer::tag('div', '<i class="fa-solid fa-user-pen"></i>', array('class' => 'menu-btn'));
    $html .= html_writer::tag('span', 'Editar', array('class' => 'menu-label'));
    $html .= html_writer::end_tag('a');

    if($_SESSION["perm_validar_ciudadano"]==1 || $_SESSION["usua_codi"]==0) {
        $html .= html_writer::start_tag('a', array('href'=>'adm_ciudadano_confirmar.php', 'class' => 'menu-item', 'title' => 'Confirmar usuario externo'));
        $html .= html_writer::tag('div', '<i class="fa-solid fa-user-check"></i>', array('class' => 'menu-btn'));
        $html .= html_writer::tag('span', 'Confirmar', array('class' => 'menu-label'));
        $html .= html_writer::end_tag('a');
    }

    $html .= html_writer::start_tag('a', array('href'=>'../ciudadanos_solicitud/cuerpoSolicitud_ext.php', 'class' => 'menu-item', 'title' => 'Solicitudes de firma electrónica de ciudadanos'));
    $html .= html_writer::tag('div', '<i class="fa-solid fa-file-lines"></i>', array('class' => 'menu-btn'));
    $html .= html_writer::tag('span', 'Solicitudes', array('class' => 'menu-label'));
    $html .= html_writer::end_tag('a');

    // Solicitudes de alta de ciudadanos desde la búsqueda de destinatarios (RQT-7)
    if(($_SESSION["perm_aprobar_ciudadano"] ?? 0)==1 || ($_SESSION["usua_admin_sistema"] ?? 0)==1) {
        $html .= html_writer::start_tag('a', array('href'=>'../ciudadanos_solicitud/aprobacion_ciudadanos.php', 'class' => 'menu-item', 'title' => 'Aprobar solicitudes de nuevos ciudadanos'));
        $html .= html_writer::tag('div', '<i class="fa-solid fa-user-clock"></i>', array('class' => 'menu-btn'));
        $html .= html_writer::tag('span', 'Aprobaciones', array('class' => 'menu-label'));
        $html .= html_writer::end_tag('a');
    }

    $html .= html_writer::end_tag('div');
    $html .= html_writer::end_tag('nav');
    $html .= html_writer::end_tag('div');

    return $html;
}

function print_tabs_menu_external_user($usr_codigo=0, $menu='') {
    $html  = '';

    $html .= html_writer::tag('button','<span>Información ciudadano</span>',array('class'=>'tab-button-ext', 'data-tab' => 'div_informacion_ext', 'type' => 'button'));
    $html .= html_writer::tag('button','<span>Modificaciones</span>',array('class'=>'tab-button-ext', 'data-tab' => 'div_historico_ext', 'type' => 'button'));

    return $html;
}

echo print_html_head();

function graficarMenu($usr_codigo=0,$tiene_subrogacion=0,$usr_perfil=0,$usr_depe=0) {
    echo print_menu();
}

function graficarTabsMenuUsr($usr_codigo=0,$tiene_subrogacion=0,$usr_perfil=0,$usr_depe=0,$menu='') {
    echo print_tabs_menu_user($usr_codigo, $tiene_subrogacion, $usr_perfil, $usr_depe, $menu);
}

function graficarTabsCiud() {
    echo print_tabs_external_user();
}

function graficarTabsMenuCiud($usr_codigo=0, $menu='') {
    echo print_tabs_menu_external_user($usr_codigo, $menu);
}