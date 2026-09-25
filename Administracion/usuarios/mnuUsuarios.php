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

session_start();

include_once(dirname(__DIR__, 2).'/rec_session.php');

if($_SESSION["usua_admin_sistema"]!=1) {
    die("");
}
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
require_once(dirname(__DIR__, 2).'/lib/outputcomponents.php');

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
    $html .= html_writer::tag('link', '', array('rel' => 'stylesheet', 'href' => '/style/mnuUsuarios.css?v=3', 'type' => 'text/css'));
    $html .= html_writer::tag('link', '', array('rel' => 'stylesheet', 'href' => '/js/spiffyCal/spiffyCal_v2_1.css', 'type' => 'text/css'));
    $html .= html_writer::tag('link', '', array('rel' => 'stylesheet', 'href' => '/js/calendario_php/calendario_php.css', 'type' => 'text/css'));
    $html .= html_writer::tag('link', '', array('rel' => 'stylesheet', 'href' => '/style/fontawesome/css/all.min.css', 'type' => 'text/css'));

    $html .= html_writer::tag('script', '', array('src' => '/js/calendario_php/calendario_php.js', 'type' => 'text/javascript'));
    $html .= html_writer::tag('script', '', array('src' => '/js/funciones_js.js', 'type' => 'text/javascript'));
    $html .= html_writer::tag('script', '', array('src' => '/js/shortcut.js', 'type' => 'text/javascript'));
    $html .= html_writer::tag('script', '', array('src' => '/js/websocket.js', 'type' => 'text/javascript'));

    $html .= html_writer::end_tag('head');

    return $html;
}

function print_admin_menu_user(){
    $html  = '';
    $html .= html_writer::start_tag('body');
    $html .= html_writer::start_tag('div', array('class' => 'container'));

    // Menu Header
    $html .= html_writer::start_tag('div', array('class' => 'admin-header'));
    $html .= html_writer::tag('h1','<i class="fas fa-users-cog"></i> Administración de Usuarios y Permisos');
    $html .= html_writer::tag('p','Gestiona usuarios, permisos, subrogaciones y reportes del sistema');
    $html .= html_writer::end_tag('div');

    // Menu items
    $html .= html_writer::start_tag('div', array('class' => 'admin-menu-container'));

    $html .= html_writer::start_tag('div', array('class' => 'menu-item'));
    $html .= html_writer::tag('a','1. Usuarios', array('href'=>'cuerpoUsuario.php?accion=2', 'class' => 'menu-item', 'title' => 'Administración de Usuarios'));
    $html .= html_writer::end_tag('div');

    $html .= html_writer::start_tag('div', array('class' => 'menu-item'));
    $html .= html_writer::tag('a','2. Crear Subrogación de Puesto', array('href'=>'../subrogacion/buscar_usuario_nuevo_subr.php?accion=3', 'class' => 'menu-item', 'title' => 'Crear Subrogación de Puesto'));
    $html .= html_writer::end_tag('div');

    $html .= html_writer::start_tag('div', array('class' => 'menu-item'));
    $html .= html_writer::tag('a','3. Desactivar Subrogación de Puesto', array('href'=>'../subrogacion/buscar_usuario_nuevo_subr.php?accion=3', 'class' => 'menu-item', 'title' => 'Desactivar Subrogación de Puesto'));
    $html .= html_writer::end_tag('div');

    $html .= html_writer::start_tag('div', array('class' => 'menu-item'));
    $html .= html_writer::tag('a','4. Usuarios Sin Área', array('href'=>'listadoUsuariosinarea.php?accion=4', 'class' => 'menu-item', 'title' => 'Usuarios sin Área'));
    $html .= html_writer::end_tag('div');

    $html .= html_writer::start_tag('div', array('class' => 'menu-item'));
    $html .= html_writer::tag('a','5. Reporte Usuarios', array('href'=>'reporte_usuarios_01.php', 'class' => 'menu-item', 'title' => 'Reporte Usuarios'));
    $html .= html_writer::end_tag('div');

    if ($_SESSION["usua_perm_backup"]==1) {
        $html .= html_writer::start_tag('div', array('class' => 'menu-item'));
        $html .= html_writer::tag('a','6. Respaldos de documentos de usuarios', array('href'=>'../../backup/backup_usuarios_menu.php', 'class' => 'menu-item', 'title' => 'Reporte Usuarios'));
        $html .= html_writer::end_tag('div');
    }

    $html .= html_writer::start_tag('div', array('class' => 'menu-item'));
    $html .= html_writer::tag('a','8. Permisos a Usuarios', array('href'=>'../dependencias/permiso_areas_usuarios.php?accion=5&des_activar=3', 'class' => 'menu-item', 'title' => 'Reporte Usuarios'));
    $html .= html_writer::end_tag('div');

    $html .= html_writer::end_tag('div'); // End menu items

    // Footer
    $html .= html_writer::start_tag('div', array('class' => 'admin-footer-menu'));
    $html .= html_writer::tag('a','Regresar', array('href'=>'../formAdministracion.php', 'class' => 'btn-footer-menu', 'title' => 'Reporte Usuarios'));
    $html .= html_writer::end_tag('div');

    $html .= html_writer::end_tag('div');
    $html .= html_writer::end_tag('body');

    return $html;
}

echo print_html_head();

echo print_admin_menu_user();