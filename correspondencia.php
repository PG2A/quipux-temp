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
require_once(__DIR__.'/lib/outputcomponents.php');

global $CFG;

if (isset($CFG->replicacion) && $CFG->replicacion && !($CFG->config_db_replica_cuerpo_paginador === "")) {
    $db = new ConnectionHandler(__DIR__, $CFG->config_db_replica_cuerpo_paginador);
}

$carpeta = isset($_REQUEST['carpeta']) ? (int)$_REQUEST['carpeta'] : 0;
if (isset($_SESSION['tipo_usuario']) && $_SESSION['tipo_usuario'] == 2) {
    $carpeta = 80;
}
$num = 1;


function create_sidebar_section($id, $name, $items, $icon = ''){
    $html  = '';

    $html .= html_writer::start_tag('div', array('id' => $id, 'class' => 'sidebar-section'));

    // Header section
    $html .= html_writer::start_tag('button', array('id' => $id, 'class' => 'section-header', 'aria-expanded' => 'true', 'aria-controls' => $id.'-items'));
    $html .= html_writer::start_tag('div', array('class' => 'section-header-content'));
    if(!empty($icon)) {
        $html .= html_writer::tag('div', '<i class="'.$icon.'"></i>', array('class' => 'section-header-icon'));
    } else {
        $html .= html_writer::tag('div', '<i class="fas fa-envelope"></i>', array('class' => 'section-header-icon'));
    }
    $html .= html_writer::tag('div', $name, array('class' => 'section-header-title'));
    $html .= html_writer::end_tag('div');
    $html .= html_writer::tag('div', '<i class="fas fa-chevron-down"></i>', array('class' => 'section-toggle'));
    $html .= html_writer::end_tag('button');

    // Sections items
    $html .= html_writer::start_tag('div', array('id' => $id.'-items' , 'class' => 'section-items'));
    $html .= $items;
    $html .= html_writer::end_tag('div');

    $html .= html_writer::end_tag('div');

    return $html;
}

// $badge: contador para enlaces sin carpeta (depth 0), p. ej. solicitudes pendientes;
// se pinta con el mismo estilo que el de las bandejas.
function create_sidebar_item($depth, $name, $description, $link = '', $icon = '', $badge = null) {
    $html  = '';

    $description = isset($description) ? str_replace("*usuario*", $_SESSION["usua_nomb"], $description) : '';

    if($depth != 0) {
        $link = "cuerpo.php?carpeta=$depth&nomcarpeta=$name";
        $count = count_inbox($depth);
    }

    $html .= html_writer::start_tag('a', array(
            'onclick' => "llamaCuerpo('$link')",
            'class' => 'section-item',
            'title' => $description,
            'data-section' => $name,
            'data-item' => $name
    ));

    // Add icon to item
    if(!empty($icon)) {
        $html .= html_writer::start_tag('div', array('class' => 'section-header-content'));
        $html .= html_writer::tag('div', '<i class="'.$icon.'"></i>', array('class' => 'section-header-icon'));
        $html .= html_writer::tag('div', $name, array('class' => 'section-header-title'));
        $html .= html_writer::end_tag('div');
    } else {
        $html .= html_writer::tag('span', $name);
    }

    // El badge lleva id: cuerpo.php llama a cambiar_contador() (js/correspondencia.js)
    // despues de cada recarga del listado para refrescarlo sin recargar el menu. Si el
    // id no existe la actualizacion se pierde en silencio y el numero queda congelado
    // con el valor que tenia al cargar el frame izquierdo.
    if($depth != 0 and $count >= 0) {
        $html .= html_writer::tag('span', $count, array(
                'class' => 'section-item-badge',
                'id' => 'spam_carpeta_'.$depth
        ));
    } elseif ($depth == 0 and $badge !== null) {
        $html .= html_writer::tag('span', (int)$badge, array(
                'class' => 'section-item-badge',
                'id' => 'spam_' . preg_replace('/[^a-z0-9]+/i', '_', strtolower(strip_tags($name)))
        ));
    }

    $html .= html_writer::end_tag('a');

    return $html;
}

function print_html_head() {
    $html  = '';
    $html .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//ES" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
    $html .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="es" lang="es">';
    $html .= html_writer::start_tag('head');

    $html .= html_writer::tag('link', '', array('rel' => 'stylesheet', 'href' => '/estilos/orfeo.css', 'type' => 'text/css'));
    $html .= html_writer::tag('link', '', array('rel' => 'stylesheet', 'href' => '/style/correspondencia.css?v=3', 'type' => 'text/css'));
    $html .= html_writer::tag('link', '', array('rel' => 'stylesheet', 'href' => '/style/fontawesome/css/all.min.css', 'type' => 'text/css'));

    $html .= html_writer::tag('script', '', array('src' => '/js/calendario_php/calendario_php.js', 'type' => 'text/javascript'));
    $html .= html_writer::tag('script', '', array('src' => '/js/funciones_js.js', 'type' => 'text/javascript'));
    $html .= html_writer::tag('script', '', array('src' => '/js/shortcut.js', 'type' => 'text/javascript'));
    $html .= html_writer::tag('script', '', array('src' => '/js/websocket.js', 'type' => 'text/javascript'));
    $html .= html_writer::tag('script', '', array('src' => '/js/ajax_raw.js', 'type' => 'text/javascript'));
    $html .= html_writer::tag('script', '', array('src' => '/js/correspondencia.js', 'type' => 'text/javascript'));

    $html .= html_writer::end_tag('head');

    return $html;
}

function print_menu_sidebar() {
    global $db, $CFG;

    $html = '';
    $html .= html_writer::start_tag('body');

    if ($_SESSION["tipo_usuario"]==2) {
        if ($_SESSION["inst_codi"] == 1) {
            // New document
            if($_SESSION["usua_prad_tp1"] == 1) {
                $html .= create_sidebar_item(0, "Nuevo", "Crear memorandos, oficios, circulares, etc.", "radicacion/NEW.php?&ent=1&accion=Nuevo&carpeta=0", 'fa fa-plus');
            }
            // Menu signature external person
            $item_signature_external_person  = create_sidebar_item(82, "En Elaboraci&oacute;n", "Documentos que se est&aacute;n elaborando.");
            $item_signature_external_person .= create_sidebar_item(83, "Recibidos", "Documentos recibidos por el ciudadano desde cualquier Instituci&oacute;n P&uacute;blica");
            $item_signature_external_person .= create_sidebar_item(84, "Eliminados", "Documentos que se encontraban en elaboración y que han sido eliminados");
            $item_signature_external_person .= create_sidebar_item(85, "No Enviados", "Documentos que no se pudieron firmar electr&oacute;nicamente y que están pendientes para su firma");
            $item_signature_external_person .= create_sidebar_item(86, "Enviados", "Documentos enviados por el ciudadano a cualquier Instituci&oacute;n P&uacute;blica");
            $html .=  create_sidebar_section("bandejas_ciudadanos_firma", "Bandejas", $item_signature_external_person);

            // Menu signature external person admin
            $item_signature_external_person_admin  = create_sidebar_item(0, "Cambiar Contrase&ntilde;a", "Permite al usuario cambiar la clave de ingreso al sistema", "Administracion/usuarios/cambiar_password.php");
            $item_signature_external_person_admin .= create_sidebar_item(0, "Respaldos", "Solicitud de Respaldos", "backup/respaldo_menu.php");
            echo create_sidebar_section("administracion", "Administraci&oacute;n", $item_signature_external_person_admin);
        } else {
            // Menu externa person
            $items_external_person  = create_sidebar_item(80, "Enviados", "Documentos enviados por el ciudadano a cualquier Instituci&oacute;n P&uacute;blica");
            $items_external_person .= create_sidebar_item(81, "Recibidos", "Documentos recibidos por el ciudadano desde cualquier Instituci&oacute;n P&uacute;blica");
            $html .= create_sidebar_section("bandejas_ciudadanos", "Bandejas", $items_external_person);

            // Menu external person admin
            $items_external_person_admin  = create_sidebar_item(0, "Cambiar Contrase&ntilde;a", "Permite al usuario cambiar la clave de ingreso al sistema", "Administracion/usuarios/cambiar_password.php");
            $items_external_person_admin .= create_sidebar_item(0, "Editar Datos Personales", "Permite al usuario modificar sus datos registrados en el sistema", "Administracion/ciudadanos/adm_ciudadano.php");
            $items_external_person_admin .= create_sidebar_item(0, "Generar/Firmar Documentos", "Solicitud para que el usuario pueda firmar electr&oacute;nicamente documentos", "Administracion/ciudadanos_solicitud/adm_solicitud_ciu.php");
            $html .=  create_sidebar_section("administracion", "Administraci&oacute;n", $items_external_person_admin);
        }
    } else {
        if ($_SESSION["depe_codi"]!=0) {
            // New document
            if($_SESSION["usua_prad_tp1"] == 1) {
                $html .= create_sidebar_item(0, "Nuevo", "Crear memorandos, oficios, circulares, etc.", "radicacion/NEW.php?&ent=1&accion=Nuevo&carpeta=0", 'fa fa-plus');
            }

            // Menu inbox
            $inbox_group = "1,2,8,15,16";
            if (isset($_SESSION["firma_digital"]) && $_SESSION["firma_digital"] == 1) {
                $inbox_group .= ",7";
            }

            if (isset($_SESSION["usua_codi_jefe"]) && $_SESSION["usua_codi_jefe"] != 0) {
                $inbox_group .= ",14";
            }

            // Bandejas de consulta de subrogación (sólo lectura). Cada una se
            // muestra a quien tiene documentos en ella: la 17 al titular del
            // puesto y la 18 al subrogante. Sirven durante el período y también
            // después de finalizado, por eso no se filtra por estado.
            //
            // No aparecen mientras se actúa bajo un contexto de subrogación: son
            // el registro personal de cada usuario, no del puesto que ocupa
            // temporalmente. En ese contexto la identidad activa es la del cargo,
            // así que mostrarlas daría el histórico del titular a quien lo cubre.
            if (empty($_SESSION["subrogacion_codi"])) {
                $usua_codi_actual = (int)$_SESSION["usua_codi"];
                $rsSubr = $db->conn->query(
                    "select count(case when s.usua_subrogado  = $usua_codi_actual then 1 end) as como_titular
                          , count(case when s.usua_subrogante = $usua_codi_actual then 1 end) as como_subrogante
                       from radicado_subrogacion rs
                       join usuarios_subrogacion s on s.usua_subrogacion_codi = rs.usua_subrogacion_codi
                      where rs.tipo = 'T'
                        and (s.usua_subrogado = $usua_codi_actual or s.usua_subrogante = $usua_codi_actual)");
                if ($rsSubr && !$rsSubr->EOF) {
                    if ((int)$rsSubr->fields["COMO_TITULAR"]    > 0) $inbox_group .= ",17";
                    if ((int)$rsSubr->fields["COMO_SUBROGANTE"] > 0) $inbox_group .= ",18";
                }
            }

            $items_inbox = '';
            $sql = "select * from carpeta where carp_codi in ($inbox_group) order by carp_orden asc";
            $rs = $db->conn->query($sql);
            while($rs && !$rs->EOF) {
                $items_inbox .= create_sidebar_item($rs->fields["CARP_CODI"], $rs->fields["CARP_NOMBRE"], $rs->fields["CARP_DESCRIPCION"]);
                $rs->MoveNext();
            }
            $html .=  create_sidebar_section("bandejas", "Bandejas", $items_inbox);

            // Get another inbox
            $items_other_inbox = '';
            // 14, 17 y 18 nunca van en "Otras Bandejas": o ya se listaron arriba,
            // o este usuario no tiene registros en ellas y no debe verlas.
            $inbox_group .= ",14,17,18";
            $sql = "select * from carpeta where carp_codi not in ($inbox_group) order by carp_orden asc";
            $rs = $db->conn->query($sql);
            while($rs && !$rs->EOF) {
                $items_other_inbox .= create_sidebar_item($rs->fields["CARP_CODI"], $rs->fields["CARP_NOMBRE"], $rs->fields["CARP_DESCRIPCION"]);
                $rs->MoveNext();
            }
            $html .=  create_sidebar_section("otras_bandejas", "Otras Bandejas", $items_other_inbox);

            // Menu rooting (Radicación)
            $items_rooting = '';
            if($_SESSION["usua_prad_tp2"] == 1 or $_SESSION["usua_perm_digitalizar"] == 1 or $_SESSION["perm_tramitar_docs_ciudadano"] == 1) {
                if($_SESSION["usua_prad_tp2"] == 1) {
                    $items_rooting .= create_sidebar_item(0, "Registrar", "Registro de documentos externos", "radicacion/NEW.php?&ent=2&accion=Nuevo");
                    $items_rooting .= create_sidebar_item(0, "Comprobante", "Imprimir Comprobantes", "uploadFiles/cargar_doc_digitalizado.php?imprimir=si");
                }
                if($_SESSION["usua_perm_digitalizar"] == 1) {
                    $items_rooting .= create_sidebar_item(0, "Cargar Doc. Digitalizado", "Asociar imagen digitalizada del Documento", "uploadFiles/cargar_doc_digitalizado.php");
                    $items_rooting .= create_sidebar_item(0, "Cargar Anexos al Doc.", "Cargar nuevos anexos al documento", "uploadFiles/cargar_doc_digitalizado.php?tipo_archivo=anex");
                }
                if($_SESSION["usua_prad_tp2"] == 1) {
                    $items_rooting .= create_sidebar_item(0, "Devoluci&oacute;n", "Registrar devoluciones de documentos", "devolver_documentos.php");
                }
                if($_SESSION["perm_tramitar_docs_ciudadano"] == 1) {
                    $items_rooting .= create_sidebar_item(90, "Docs. Ciudadanos", "Documentos recibidos, firmados electr&oacute;nicamente por ciudadanos");
                }
                $html .= create_sidebar_section("registro_externos", "Bandeja de Entrada", $items_rooting, 'fa fa-inboxes');
            }

        }

        // Menu default
        $descTRDpl = "Carpetas Virtuales";
        $items_admin = create_sidebar_item(0, "Administraci&oacute;n", "Opciones de administraci&oacute;n del sistema", "Administracion/formAdministracion.php");
        // Aprobadores de ciudadanos (RQT-7): acceso directo con el número de pendientes
        if (($_SESSION["perm_aprobar_ciudadano"] ?? 0) == 1 || ($_SESSION["usua_admin_sistema"] ?? 0) == 1) {
            include_once(__DIR__.'/include/ciudadanos/SolicitudCiudadano.php');
            if (SolicitudCiudadano::disponible($db)) {
                $sol_ciu = new SolicitudCiudadano($db);
                // Se muestra como una bandeja más: nombre a la izquierda y el número de
                // pendientes en el badge de la derecha.
                $items_admin .= create_sidebar_item(0, "Solicitudes de ciudadanos",
                    "Ciudadanos registrados desde la b&uacute;squeda de destinatarios pendientes de aprobaci&oacute;n",
                    "Administracion/ciudadanos_solicitud/aprobacion_ciudadanos.php", '', $sol_ciu->contarPendientes());
            }
        }
        if ($_SESSION["depe_codi"]!=0) { //Si no tiene definida el area no puede realizar acciones
            if ($_SESSION["usua_perm_trd"]==1) {
                $items_admin .= create_sidebar_item(0, $descTRDpl, "Administraci&oacute;n de $descTRDpl", "tipo_documental/menu_trd.php");
            }
            if($_SESSION["usua_admin_archivo"]==1 or $_SESSION["usua_perm_archivo"]==1) {
                $items_admin .= create_sidebar_item(0, "Archivo F&iacute;sico", "Archivo F&iacute;sico", "archivo/menu_archivo.php");
            }
        }
        $html .= create_sidebar_section("administracion", "Administraci&oacute;n", $items_admin, 'fa fa-cogs');

        // Menu Other inbox
        $items_others = create_sidebar_item(0, "B&uacute;squeda Avanzada", "B&uacute;squeda de documentos", "busquedaN/busqueda.php");
        $items_others .= create_sidebar_item(0, "Seguimiento de documentos", "B&uacute;squeda de documentos para hacer seguimiento", "busqueda/busqueda_tramites.php");
        if (isset($_SESSION['perm_buscar_doc_adscritas']) && $_SESSION['perm_buscar_doc_adscritas'] == 2) {
            $items_others .= create_sidebar_item(0, "B&uacute;squeda de documentos", "B&uacute;squeda de documentos de Instituciones Adscritas", "busqueda/busqueda_adscritas.php");
        }

        if ($_SESSION["depe_codi"]!=0) { //Si no tiene definida el area no puede realizar acciones
            // Carpetas virtuales
            $items_others .= create_sidebar_item(0, $descTRDpl, "Consultar documentos por $descTRDpl", "tipo_documental/consultar_trd.php");

            if($_SESSION["usua_perm_impresion"] == 1) { // Bandeja por imprimir
                $items_others .= create_sidebar_item(99, "Por Imprimir", "Documentos para Imprimir");
            }

            $items_others .= create_sidebar_item(0, "Reportes", "Reportes", "$CFG->nombre_servidor_reportes/reportes_new/reportes.php?id_sess=".session_id());
        }
        $html .= create_sidebar_section("otros", "Otros", $items_others, 'fa fa-inboxes');

        // Menu Signature
        // $item_signature = create_sidebar_item(200, "Firmar Documento", "Opción para firmar documentos");
        // $html .=  create_sidebar_section("firma", "Firmar Documentos", $item_signature, 'fa fa-signature');
    }

//    include(__DIR__.'/menu/alertas_documentos_vencidos.php');

    return $html;
}



function crear_grupo_bandeja($id, $nombre, $items, $icon = '') {
    echo create_sidebar_section($id, $nombre, $items, $icon);
}

function crear_item_bandeja ($carpeta, $nombre, $descripcion, $destino="", $icon = '') {
    return create_sidebar_item($carpeta, $nombre, $descripcion, $destino, $icon);
}

echo print_html_head();

echo print_menu_sidebar();


//<!--<body onload="recargar_estadisticas(); init_menu();">-->
//<!--<center>-->
//<!--    <div id="div_bloquear_menu" style="width: 100%; height: 0%; z-index: 1000; position: fixed; top: 0; left: 0;"></div>-->
//<!--    <br>-->
//<!--    <table width="160px" border="0" cellpadding="0" cellspacing="3">-->
//
//<!--    </table>-->
//<!--    <br>-->
//<!--    <div id="div_estadisticas_menu" style="width: 160px;"></div>-->

//    include(__DIR__.'/menu/alertas_documentos_vencidos.php');
?>