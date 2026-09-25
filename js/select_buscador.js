/*
 * select_buscador.js — convierte un <select> en un combo con buscador, sin
 * dependencias (no usa jQuery ni prototype, para no chocar con la página).
 *
 * El <select> original se mantiene oculto como fuente del valor y del onchange:
 * al elegir una opción se fija select.value y se dispara su evento 'change', de
 * modo que la lógica existente (cargarPuestos, aplicarPuesto, etc.) sigue igual.
 *
 * Uso:  mejorarSelect(document.getElementById('usr_depe'));
 *       observarSelect('div_cmb_puesto');   // re-mejora cuando el AJAX recarga el div
 */
(function (global) {
    'use strict';

    function norm(s) {
        s = (s || '').toString().toLowerCase();
        return s.normalize ? s.normalize('NFD').replace(/[̀-ͯ]/g, '') : s;
    }

    function mejorarSelect(sel) {
        if (!sel || sel.tagName !== 'SELECT') return;
        if (sel.getAttribute('data-sb') === '1') return;
        if (sel.disabled) return;
        sel.setAttribute('data-sb', '1');

        var wrap = document.createElement('span');
        wrap.className = 'sb-wrap';

        var input = document.createElement('input');
        input.type = 'text';
        input.className = 'sb-input ' + (sel.className || '');
        input.setAttribute('autocomplete', 'off');
        input.style.width = (sel.style.width || '350px');
        input.placeholder = 'Escriba para buscar…';

        var drop = document.createElement('div');
        drop.className = 'sb-drop';
        drop.style.display = 'none';

        // El select queda oculto justo antes del wrapper
        sel.parentNode.insertBefore(wrap, sel);
        sel.style.display = 'none';
        wrap.appendChild(sel);
        wrap.appendChild(input);
        wrap.appendChild(drop);

        function textoActual() {
            var o = sel.options[sel.selectedIndex];
            return o ? o.text : '';
        }
        function refrescarTexto() { input.value = textoActual(); }
        refrescarTexto();

        function construir(filtro) {
            drop.innerHTML = '';
            var f = norm(filtro);
            var vistos = 0;
            for (var i = 0; i < sel.options.length; i++) {
                var o = sel.options[i];
                if (f !== '' && norm(o.text).indexOf(f) < 0) continue;
                var item = document.createElement('div');
                item.className = 'sb-item' + (i === sel.selectedIndex ? ' sb-sel' : '');
                item.textContent = o.text;
                item.setAttribute('data-idx', i);
                drop.appendChild(item);
                vistos++;
            }
            if (vistos === 0) {
                var vac = document.createElement('div');
                vac.className = 'sb-vacio';
                vac.textContent = 'Sin coincidencias';
                drop.appendChild(vac);
            }
        }

        function abrir() {
            construir('');
            drop.style.display = 'block';
            input.select();
        }
        function cerrar() { drop.style.display = 'none'; }

        function elegir(idx) {
            sel.selectedIndex = idx;
            refrescarTexto();
            cerrar();
            // Dispara el onchange original del select (inline o addEventListener)
            var ev;
            if (typeof Event === 'function') { ev = new Event('change', { bubbles: true }); }
            else { ev = document.createEvent('HTMLEvents'); ev.initEvent('change', true, false); }
            sel.dispatchEvent(ev);
        }

        input.addEventListener('focus', abrir);
        input.addEventListener('click', abrir);
        input.addEventListener('input', function () { construir(input.value); drop.style.display = 'block'; });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                var primero = drop.querySelector('.sb-item');
                if (primero) { e.preventDefault(); elegir(parseInt(primero.getAttribute('data-idx'), 10)); }
            } else if (e.key === 'Escape') { cerrar(); refrescarTexto(); }
        });
        drop.addEventListener('mousedown', function (e) {
            var it = e.target;
            while (it && it !== drop && !it.classList.contains('sb-item')) it = it.parentNode;
            if (it && it.classList && it.classList.contains('sb-item')) {
                e.preventDefault();
                elegir(parseInt(it.getAttribute('data-idx'), 10));
            }
        });
        input.addEventListener('blur', function () { setTimeout(function () { cerrar(); refrescarTexto(); }, 150); });
    }

    // Observa un contenedor (p. ej. div_cmb_puesto) y mejora el <select> que
    // aparezca dentro cada vez que su contenido cambie (recarga AJAX).
    function observarSelect(idContenedor, idSelect) {
        var cont = document.getElementById(idContenedor);
        if (!cont) return;
        function aplicar() {
            var sel = idSelect ? document.getElementById(idSelect) : cont.querySelector('select');
            if (sel) mejorarSelect(sel);
        }
        aplicar();
        if (typeof MutationObserver !== 'undefined') {
            new MutationObserver(aplicar).observe(cont, { childList: true, subtree: true });
        }
    }

    global.mejorarSelect = mejorarSelect;
    global.observarSelect = observarSelect;
})(window);
