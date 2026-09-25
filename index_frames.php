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

session_start();
include_once(__DIR__.'/rec_session.php');
include_once(__DIR__.'/funciones_interfaz.php');

?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>.:: Quipux - Sistema de Gestión Documental ::.</title>
    <link rel="icon" href="imagenes/favicon.ico" />

    <style>
        html, body { height: 100%; margin: 0; }
        .app {
            height: 100%;
            display: grid;
            grid-template-rows: 97px 1fr;
            grid-template-columns: 280px 1fr;
            grid-template-areas:
        "header header"
        "sidebar main";
        }
        header { grid-area: header; border-bottom: 1px solid #ccc; }
        aside  { grid-area: sidebar; border-right: 1px solid #ccc; }
        main   { grid-area: main; }

        iframe { width: 100%; height: 100%; border: 0; }
        .swal2-container { z-index: 100000 !important; }
        .swal2-popup, .swal2-popup .swal2-title, .swal2-popup .swal2-html-container, .swal2-popup .swal2-styled { font-family: Arial, Helvetica, sans-serif; }
    </style>
</head>

<body>
<?php dibujar_loader_pantalla('Cerrando sesión...'); ?>
<div class="app">
    <header>
        <iframe name="topFrame" src="f_top.php" title="Barra superior" scrolling="no"></iframe>
    </header>

    <aside>
        <iframe name="leftFrame" src="correspondencia.php" title="Menú lateral"></iframe>
    </aside>

    <main>
        <iframe name="mainFrame" src="cuerpo.php" title="Contenido principal" onload="try{ if(window.desbloquearPantalla) desbloquearPantalla(); }catch(e){}"></iframe>
    </main>
</div>

<div id="div_session"></div>
<script src="js/sweetalert2.all.min.js"></script>
<script>
function confirmarSalir(){
    if (typeof Swal === 'undefined'){
        if (confirm('¿Está seguro de Cerrar la Sesión?')){
            if (window.bloquearPantalla){ bloquearPantalla('Cerrando sesión...'); }
            window.location.href = 'cerrar_session.php?accion=cerrar';
        }
        return;
    }
    Swal.fire({
        title: '¿Cerrar sesión?',
        text: '¿Está seguro de que desea salir del sistema?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, cerrar sesión',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#002B5C',
        cancelButtonColor: '#d33',
        reverseButtons: true
    }).then(function(r){
        if (r.isConfirmed){
            if (window.bloquearPantalla){ bloquearPantalla('Cerrando sesión...'); }
            window.location.href = 'cerrar_session.php?accion=cerrar';
        }
    });
}
</script>
</body>
</html>