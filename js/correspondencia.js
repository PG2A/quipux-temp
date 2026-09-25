var carpeta = 2;

function bloquear_menu (accion) {
    try {
        if (accion == 1) {
            document.getElementById('div_bloquear_menu').style.height = '100%';
            timerID = setTimeout("bloquear_menu(0)", 2500);
        } else {
            document.getElementById('div_bloquear_menu').style.height = '0%';
            clearTimeout(timerID);
        }
    } catch (e) {}
}

function cambioMenu(valor) {

    try {
        document.getElementById('menu_tr' + carpeta).style.cssText = 'background-color:#FFFFFF; font-size: 10px;';
        document.getElementById('menu_tr' + valor).style.cssText = 'background-color:#a8bac6; font-size: 10px;';
        bloquear_menu(1);
        carpeta = valor;
    } catch (e) {}
    //document.location='correspondencia.php?carpeta='+valor;
}

function cambiar_contador(bandeja, valor) {
    try {
        var badge = document.getElementById("spam_carpeta_" + bandeja);
        // Sin badge no hay nada que refrescar: las bandejas cuyo contador es -1 no lo
        // dibujan (ver create_sidebar_item en correspondencia.php).
        if (badge) {
            if (valor.toString() == '-1' || trim(valor.toString()) == '')
                badge.style.display = 'none';
            else {
                // Sin parentesis, para que coincida con el badge que dibuja el menu.
                badge.innerHTML = valor;
                badge.style.display = '';
            }
        }
        bloquear_menu(0);
    } catch (e) { }
}

var ultimoRefrescoContadores = 0;
function refrescar_contadores(forzar) {
    try {
        var ahora = new Date().getTime();
        if (!forzar && ahora - ultimoRefrescoContadores < 20000) return;
        ultimoRefrescoContadores = ahora;
        var badges = document.getElementsByTagName('span');
        var ids = [];
        for (var i = 0; i < badges.length; i++) {
            if (badges[i].id && badges[i].id.indexOf('spam_carpeta_') === 0)
                ids.push(badges[i].id.substring(13));
        }
        if (ids.length === 0) return;
        var req = new XMLHttpRequest();
        req.open('GET', './contadores_bandejas.php?carpetas=' + ids.join(',') + '&t=' + new Date().getTime(), true);
        req.onreadystatechange = function () {
            if (req.readyState != 4 || req.status != 200) return;
            var datos;
            try { datos = JSON.parse(req.responseText); } catch (e) { return; }
            for (var carpeta in datos) {
                if (datos.hasOwnProperty(carpeta)) cambiar_contador(carpeta, datos[carpeta]);
            }
        };
        req.send(null);
    } catch (e) { }
}

function cambiar_fondo(fila, fondo) {
    if (fila.id=='menu_tr'+carpeta) return;
    color = "#e3e8ec";
    if (fondo == 0)
        color = "#ffffff";
    fila.style.cssText = 'background-color:'+color;
}

function llamaCuerpo(parametros){
    try{ window.top.bloquearPantalla('Cargando...'); }catch(e){}
    try {
        top.frames['mainFrame'].location.href=parametros;
    } catch (e) {
        window.top.frames['mainFrame'].location.href=parametros;
    }
}

var menuTimerId = 0;
function recargar_estadisticas() {
    clearTimeout(menuTimerId);

    if ('<?= $existe_estadisticas ?>' !== 'Si') return;

    nuevoAjax('div_estadisticas_menu', 'GET', './bodega/estadisticas_menu.html', '');
    menuTimerId = setTimeout(recargar_estadisticas, 300000);
}

function init_menu() {
    // Pintar la bandeja de recibidos
    if ('<?=$_SESSION["inst_codi"]?>'=='0') {
        cambioMenu('3'); // Bandeja recibidos de ciudadanos
    } else {
        cambioMenu('4'); // Bandeja recibidos funcionarios y ciudadanos con firma
    }
    // comprimir la bandeja "Otras Bandejas"
    mostrar_ocultar_grupo_carpetas("tr_menu_otras_bandejas");
}

function mostrar_ocultar_grupo_carpetas(id) {
    try {
        var grupo = document.getElementById(id);
        if (grupo.style.display == 'none') {
            grupo.style.display = '';
            document.getElementById(id+'_img_exp').style.display = '';
            document.getElementById(id+'_img_com').style.display = 'none';
        } else {
            grupo.style.display = 'none';
            document.getElementById(id+'_img_exp').style.display = 'none';
            document.getElementById(id+'_img_com').style.display = '';
        }
    } catch(e) {}
    return;
}

document.addEventListener('DOMContentLoaded', function() {
    // Obtener todos los botones de sección
    const sectionHeaders = document.querySelectorAll('.section-header');

    // Obtener todos los enlaces de items
    const sectionItems = document.querySelectorAll('.section-item');

    // Manejar click en botones de sección (expandir/contraer)
    sectionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const itemsId = this.getAttribute('aria-controls');
            const items = document.getElementById(itemsId);

            this.classList.toggle('active');
            items.classList.toggle('collapsed');

            // Actualizar atributo aria-expanded
            const isExpanded = this.classList.contains('active');
            this.setAttribute('aria-expanded', isExpanded);
        });
    });

    // Manejar click en enlaces de items
    // sectionItems.forEach(item => {
    //     item.addEventListener('click', function(e) {
    //         // Prevenir navegación por defecto para demo
    //         e.preventDefault();
    //
    //         // Remover active de todos los items
    //         sectionItems.forEach(i => i.classList.remove('active'));
    //
    //         // Agregar active al item seleccionado
    //         this.classList.add('active');
    //
    //         // Obtener datos del enlace
    //         const section = this.getAttribute('data-section');
    //         const itemName = this.getAttribute('data-item');
    //         const title = this.querySelector('span').textContent;
    //
    //         // Actualizar contenido
    //         document.getElementById('contentTitle').textContent = title;
    //         document.getElementById('contentSubtitle').textContent =
    //             `Contenido de: ${title}`;
    //
    //         // Log para demostración
    //         console.log('Navegando a:', {
    //             url: this.href,
    //             section: section,
    //             item: itemName
    //         });
    //     });
    // });

    // Seleccionar primer item por defecto
    const firstItem = document.querySelector('.section-item');
    if (firstItem) {
        firstItem.classList.add('active');
    }

    // Verificar si hay parámetros en la URL y cargar el item correspondiente
    const params = new URLSearchParams(window.location.search);
    const section = params.get('section');
    const item = params.get('item');

    if (section && item) {
        const targetItem = document.querySelector(
            `.section-item[data-section="${section}"][data-item="${item}"]`
        );
        if (targetItem) {
            targetItem.click();
        }
    }
});