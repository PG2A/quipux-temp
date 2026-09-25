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
 * @package    anexos
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//Tipos de anexos permitidos
$rs = $db->conn->query("select anex_tipo_ext from anexos_tipo where anex_tipo_estado=1");
$extenciones_archivos = "";
while(!$rs->EOF) {
    $extenciones_archivos .= strtolower(trim($rs->fields["ANEX_TIPO_EXT"])) . ",";
    $rs->MoveNext();
}

$parametros_post = "";
if (isset ($_POST["asocImgRad"])) $parametros_post = "&asocImgRad=".$_POST["asocImgRad"];
$radi_nume = $radi_nume ?? "";
$chk_asociar_imagen = $chk_asociar_imagen ?? "";
?>
<script type="text/javascript">
    var ruta_raiz = '..';
    var radi_nume = '<?=$radi_nume?>';
    var extenciones_archivos = '<?=$extenciones_archivos?>';
    var timer_id_anexos_esperar_carga_archivo = 0;
    var chk_asociar_imagen = '<?=$chk_asociar_imagen?>';
    var flag_anexos_estado_carga_archivos = false;
    var parametros_post = '<?=$parametros_post?>';

    // Extensiones que el navegador puede mostrar en la vista previa antes de subir el archivo
    var extenciones_vista_previa = ['pdf','jpg','jpeg','png','gif','bmp','webp'];
    var url_vista_previa_local = '';

    // Validamos que no se esten subiendo archivos y el usuario intente salir de la página
    window.onbeforeunload = function () {
        if (flag_anexos_estado_carga_archivos) {
            return "Aún no se han cargado todos los archivos. \nSi sale de esta página sus datos se perderán.";
        }
        return;
    }

    function anexos_cargar_div_lista_anexos () {
        div = 'div_anexos_lista_archivos';
        nuevoAjax(div, 'POST', ruta_raiz+'/anexos/anexos_lista.php', 'radi_nume=' + radi_nume + parametros_post + '&chk_asociar_imagen='+chk_asociar_imagen, 'fjs_popup_crear_divs();');
        return;
    }

    function anexos_cargar_div_nuevo_anexo () {
        nuevoAjax('div_anexos_cargar_nuevo_archivo', 'POST', ruta_raiz+'/anexos/anexos_nuevo.php', 'chk_asociar_imagen=' + chk_asociar_imagen + parametros_post);
        return;
    }

    function anexos_validar_tipo_archivo(id_archivo) {
        var nombre_archivo = document.getElementById('fil_archivo_nuevo_'+id_archivo).value.toLowerCase();
        var lista_extensiones = extenciones_archivos.split(',');
        nombre_archivo = nombre_archivo.replace(/.p7m/g, "");
        var tmp = nombre_archivo.split('.');
        var extension = tmp[tmp.length-1];

        for (i = 0; i < lista_extensiones.length; i++) {
            if (lista_extensiones[i].toLowerCase() == extension) {
                if (parseInt(id_archivo)<9)
                    document.getElementById('tr_archivo_nuevo_'+(parseInt(id_archivo)+1).toString()).style.display = '';

                document.getElementById('fil_archivo_nuevo_'+id_archivo).style.display = 'none';
                document.getElementById('img_archivo_nuevo_borrar_'+id_archivo).style.display = '';
                document.getElementById('lbl_archivo_nuevo_'+id_archivo).innerHTML = document.getElementById('fil_archivo_nuevo_'+id_archivo).value;
                fjs_anexos_mostrar_icono_vista_previa(id_archivo, extension);
                return true;
            }
        }
        alert ('No está permitido anexar archivos con extensión "'+extension+'".\nConsulte con su administrador del sistema.');
        document.getElementById('fil_archivo_nuevo_'+id_archivo).value = '';
        return false;
    }

    function fjs_anexos_borrar_archivo_nuevo(id_archivo) {
        document.getElementById('lbl_archivo_nuevo_'+id_archivo).innerHTML = '';
        document.getElementById('fil_archivo_nuevo_'+id_archivo).value = '';
        document.getElementById('fil_archivo_nuevo_'+id_archivo).style.display = ''
        document.getElementById('img_archivo_nuevo_borrar_'+id_archivo).style.display = 'none';
        fjs_anexos_ocultar_icono_vista_previa(id_archivo);
        return;
    }

    // Muestra el icono de vista previa sólo si el navegador puede representar el archivo seleccionado
    function fjs_anexos_mostrar_icono_vista_previa(id_archivo, extension) {
        var img_vista_previa = document.getElementById('lnk_archivo_nuevo_previsualizar_'+id_archivo);
        if (!img_vista_previa) return;

        var campo_archivo = document.getElementById('fil_archivo_nuevo_'+id_archivo);
        var nombre_original = campo_archivo.value.toLowerCase();
        var flag_previsualizable = false;

        // Los archivos firmados (.p7m) están cifrados, sólo se pueden ver una vez procesados en el servidor
        if (nombre_original.indexOf('.p7m') == -1 && fjs_anexos_obtener_archivo_seleccionado(id_archivo) != null) {
            for (var j = 0; j < extenciones_vista_previa.length; j++) {
                if (extenciones_vista_previa[j] == extension) {
                    flag_previsualizable = true;
                    break;
                }
            }
        }

        img_vista_previa.style.display = flag_previsualizable ? '' : 'none';
        return;
    }

    function fjs_anexos_ocultar_icono_vista_previa(id_archivo) {
        var img_vista_previa = document.getElementById('lnk_archivo_nuevo_previsualizar_'+id_archivo);
        if (img_vista_previa) img_vista_previa.style.display = 'none';
        return;
    }

    // Obtiene el archivo seleccionado en el campo. Retorna null si el navegador no soporta el API de archivos
    function fjs_anexos_obtener_archivo_seleccionado(id_archivo) {
        var campo_archivo = document.getElementById('fil_archivo_nuevo_'+id_archivo);
        if (!campo_archivo || !campo_archivo.files || campo_archivo.files.length == 0) return null;
        if (typeof window.URL == 'undefined' || typeof window.URL.createObjectURL != 'function') return null;
        return campo_archivo.files[0];
    }

    // Vista previa del archivo ANTES de enviarlo: se lee desde el equipo del usuario, no se sube al servidor
    function fjs_anexos_previsualizar_archivo_nuevo(id_archivo) {
        var archivo = fjs_anexos_obtener_archivo_seleccionado(id_archivo);
        if (archivo == null) {
            alert('Su navegador no permite mostrar la vista previa del archivo antes de subirlo.');
            return;
        }

        fjs_anexos_liberar_vista_previa();
        url_vista_previa_local = window.URL.createObjectURL(archivo);

        var nombre_archivo = archivo.name.toLowerCase();
        var contenido = '';
        if (nombre_archivo.lastIndexOf('.pdf') == (nombre_archivo.length-4)) {
            contenido = '<iframe id="ifr_anexos_vista_previa_local" src="' + url_vista_previa_local + '" ' +
                        'style="width:100%; height:100%; display:block; border:0; overflow:auto;">' +
                        'Su navegador no soporta iframes, por favor actualicelo.</iframe>';
        } else {
            contenido = '<img id="img_anexos_vista_previa_local" src="' + url_vista_previa_local + '" alt="Vista previa" ' +
                        'style="width:100%; height:100%; display:block; object-fit:contain; object-position:center;">';
        }

        // El contenido va como hijo directo del área de trabajo del popup: un contenedor de alto automático
        // (p.ej. <center>) impide que height:100% se resuelva y la vista previa queda diminuta.
        fjs_popup_activar('Vista Previa: ' + archivo.name, '', '');
        document.getElementById('div_popup_pantalla_tabajo').innerHTML = contenido;
        return;
    }

    function fjs_anexos_liberar_vista_previa() {
        if (url_vista_previa_local != '') {
            try { window.URL.revokeObjectURL(url_vista_previa_local); } catch (e) {}
            url_vista_previa_local = '';
        }
        return;
    }

    function fjs_anexos_validar_chk_asociar_imagen(id_archivo) {
        var i=0;
        for ( i=0 ; i<10 ; ++i ) {
            if (i != id_archivo)
                document.getElementById('chk_asociar_imagen_'+i).checked = false;
        }
        return true;
    }

    function anexos_cargar_archivo_nuevo() {
        var nombre_archivo = '';
        var i = 0;
        var flag_validar_archivos = false;
        for (i=0 ; i<10 ; ++i) {
            if (trim(document.getElementById('fil_archivo_nuevo_'+i.toString()).value) != '') {
                nombre_archivo += '<br><b>&quot;' + document.getElementById('fil_archivo_nuevo_'+i.toString()).value+'&quot;</b>';
                if (anexos_validar_tipo_archivo(i.toString())) flag_validar_archivos = true;
            }
        }

        if (flag_validar_archivos) {
            document.getElementById('lbl_nombre_archivo_nuevo').innerHTML = nombre_archivo;
            document.getElementById('div_anexos_cargar_nuevo_archivo').style.display = 'none';
            document.getElementById('div_anexos_cargar_nuevo_archivo_estado').style.display = '';
            document.getElementById('txt_radi_nume').value = radi_nume;
            document.getElementById('frm_anexos_cargar_nuevo_archivo').action = ruta_raiz+'/anexos/anexos_grabar.php';
            document.getElementById('frm_anexos_cargar_nuevo_archivo').submit();
            flag_anexos_estado_carga_archivos = true;
        } else {
            alert ('Por favor seleccione los archivos que desea subir.');
        }
        return;
    }

    function anexos_cargar_archivo_nuevo_finalizar() {
        flag_anexos_estado_carga_archivos = false;
        fjs_anexos_liberar_vista_previa();
        document.getElementById('lbl_nombre_archivo_nuevo').innerHTML ='';
        document.getElementById('div_anexos_cargar_nuevo_archivo').style.display = '';
        document.getElementById('div_anexos_cargar_nuevo_archivo_estado').style.display = 'none';
        anexos_cargar_div_lista_anexos ();
        anexos_cargar_div_nuevo_anexo ();
        return;
    }


    function anexos_descargar_archivo(radicado, anex_codigo, arch_tipo, tipo_descarga) {
        path_descarga = ruta_raiz+'/anexos/anexos_descargar_archivo.php?radi_nume='+radicado+'&anex_codigo=' + anex_codigo + '&arch_tipo=' + arch_tipo;
        if (tipo_descarga && tipo_descarga=='embeded') {
            path_descarga += '&tipo_descarga=embeded';
            if (fjs_verificar_plugin_navegador ('acrobat')) path_descarga += '_ar';
            fjs_popup_activar ('Vista Previa', '', '');
            document.getElementById('div_popup_pantalla_tabajo').innerHTML = '<iframe name="ifr_anexos_mostrar_archivo" id="ifr_anexos_mostrar_archivo" ' +
                'style="width:100%; height:100%; display:block; border:0; overflow:auto;" src="' + path_descarga + '">' +
                'Su navegador no soporta iframes, por favor actualicelo.</iframe>';
        } else {
            path_descarga += '&tipo_descarga=download';
            document.getElementById('ifr_descargar_archivo').src=path_descarga;
        }
        return;
    }

    // Descarga todos los anexos del documento comprimidos en un ZIP
    function anexos_descargar_zip(radicado) {
        var ifr_descarga = document.getElementById('ifr_descargar_archivo');
        if (!ifr_descarga) {
            alert('No se pudo iniciar la descarga en esta pantalla.');
            return;
        }
        ifr_descarga.src = ruta_raiz+'/anexos/anexos_descargar_zip.php?radi_nume='+radicado;
        return;
    }

    function anexos_verificar_firma(radicado, anex_codigo) {
        var url = ruta_raiz+'/anexos/anexos_verificar_firma.php';
        var parametros = 'radi_nume=' + radicado + '&anex_codigo=' + anex_codigo;
        fjs_popup_activar ('Verificación de Firma Electrónica', url, parametros);
        return;
    }

    function fjs_anexos_acciones(radicado, anexo, accion){
        var funcion_ejecutar = '';
        var parametros = '';
        switch (accion) {
            case '1':
                if (!confirm('Está seguro de borrar este archivo anexo?')) return;
                document.getElementById('tr_anexo_'+anexo).style.display = 'none';
                document.getElementById('tr_anexo_detalle_'+anexo).style.display = 'none';
                break;
            case '2':
                try {
                    modificar_opcion_mostrar('imagen_'+anexo, 1);
                } catch (e) {}
                break;
            case '3':
                modificar_opcion_mostrar('imagen_'+anexo, 1);
                break;
            case '4':
//                    alert ('Este documento deberá ser incluido en el archivo de la institución.');
                modificar_opcion_mostrar('medio_'+anexo, 1);
                break;
            case '5':
                modificar_opcion_mostrar('medio_'+anexo, 1);
                break;
            case '6':
                modificar_opcion_mostrar('descripcion_'+anexo, 1);
                parametros = '&txt_descripcion='+document.getElementById('txt_descripcion_'+anexo).value;
                break;
            default:
                return;
        }

        if (accion==2)
            funcion_ejecutar = 'anexos_cargar_div_lista_anexos ();';

        nuevoAjax('div_anexos_acciones', 'POST', ruta_raiz + '/anexos/anexos_acciones.php', 'radi_nume='+radicado+'&anexo='+anexo+'&accion='+accion+parametros + parametros_post, funcion_ejecutar);
        return;
    }

    function modificar_opcion_mostrar(opcion, mostrar) {
        try {
            document.getElementById("span_"+opcion).innerHTML=document.getElementById("txt_"+opcion).options[document.getElementById("txt_"+opcion).selectedIndex].text;
        } catch (e) {
            document.getElementById("span_"+opcion).innerHTML=document.getElementById("txt_"+opcion).value;
        }
        if (mostrar==2) { // Mostrar combo para editar opcion
            document.getElementById("img_"+opcion).style.display = 'none';
            document.getElementById("span_"+opcion).style.display = 'none';
            document.getElementById("txt_"+opcion).style.display = '';
            try {
                document.getElementById("img_guardar_"+opcion).style.display = '';
            } catch (e) {}
        } else { // Ocultar combo para editar opcion
            document.getElementById("img_"+opcion).style.display = '';
            document.getElementById("span_"+opcion).style.display = '';
            document.getElementById("txt_"+opcion).style.display = 'none';
            try {
                document.getElementById("img_guardar_"+opcion).style.display = 'none';
            } catch (e) {}
        }
    }

    function fjs_anexos_mostrar_detalle_archivo (anex_codigo) {
        if (document.getElementById('tr_anexo_detalle_'+anex_codigo).style.display=='none') {
            document.getElementById('tr_anexo_detalle_'+anex_codigo).style.display='';
            document.getElementById('img_anexos_ocultar_detalle_'+anex_codigo).style.display='';
            document.getElementById('img_anexos_mostrar_detalle_'+anex_codigo).style.display='none';
        } else {
            document.getElementById('tr_anexo_detalle_'+anex_codigo).style.display='none';
            document.getElementById('img_anexos_ocultar_detalle_'+anex_codigo).style.display='none';
            document.getElementById('img_anexos_mostrar_detalle_'+anex_codigo).style.display='';
        }
        return;
    }

    // Al cerrar el popup se libera la vista previa local para no dejar el archivo en memoria
    if (typeof(fjs_popup_cerrar) == 'function' && typeof(fjs_popup_cerrar_original_anexos) == 'undefined') {
        var fjs_popup_cerrar_original_anexos = fjs_popup_cerrar;
        fjs_popup_cerrar = function () {
            fjs_anexos_liberar_vista_previa();
            return fjs_popup_cerrar_original_anexos();
        };
    }

    if(typeof(fjs_radicado_descargar_archivo) != 'function')  {
        function fjs_radicado_descargar_archivo(radicado, anex_codigo, arch_tipo, tipo_descarga) {
            path_descarga = ruta_raiz+'/anexos/anexos_descargar_archivo.php?radi_nume='+radicado+'&anex_codigo=' + anex_codigo + '&arch_tipo=' + arch_tipo + '&tipo_descarga=' + tipo_descarga;
            if (tipo_descarga=='embeded')
                document.getElementById('ifr_mostrar_archivo').src=path_descarga;
            else
                document.getElementById('ifr_descargar_archivo').src=path_descarga;
            return;
        }
    }
</script>
