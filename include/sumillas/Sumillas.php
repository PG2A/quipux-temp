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
 * Árbol de sumillas (operaciones) que clasifica los motivos por los que se
 * emite un documento al reasignarlo.
 *
 *   accion_categoria  -> los motivos (ramas)
 *   accion            -> las sumillas (hojas), con cate_codi apuntando al motivo
 *
 * El árbol tiene siempre dos niveles. Las sumillas sin motivo, y las de un motivo
 * desactivado, caen en una rama "Sin categoría": desaparecer del árbol las dejaría
 * fuera del alcance del usuario sin que nadie lo haya pedido.
 *
 * @package    sumillas
 * @author     2026 Marco Terán <marcosdanny14@gmail.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** Rama que recoge lo que no está clasificado. */
define("SUMILLAS_SIN_CATEGORIA", "Sin categor\xc3\xada");

/**
 * Deja un número de radicado listo para interpolarlo en SQL.
 *
 * Los radicados son de 20 dígitos y no caben en un entero de PHP: un "0 + $rad"
 * los convierte en float y acaban viajando a la base como 2.026000667001817E+19.
 * Por eso se limpian como cadena de dígitos en lugar de convertirlos a número.
 *
 * @return string Cadena de dígitos ("0" si no queda nada)
 */
function sumillas_radicado_sql($radicado) {
    $limpio = preg_replace('/[^0-9]/', '', (string)$radicado);
    return ($limpio === "") ? "0" : $limpio;
}

/**
 * Motivos de una institución.
 *
 * @param  object $db           ConnectionHandler
 * @param  int    $inst_codi    Institución
 * @param  bool   $solo_activos Excluye los motivos desactivados
 * @return array  Lista de array(codi, nombre, activo, orden)
 */
function sumillas_categorias($db, $inst_codi, $solo_activos = true) {
    $inst_codi = 0 + $inst_codi;

    $sql = "select cate_codi, cate_nombre, cate_activo, cate_orden
              from accion_categoria
             where inst_codi = $inst_codi";
    if ($solo_activos) $sql .= " and cate_activo = 1";
    $sql .= " order by cate_orden, cate_nombre";

    $rs   = $db->conn->Execute($sql);
    $cats = array();
    while ($rs and !$rs->EOF) {
        $cats[] = array(
            "codi"   => 0 + $rs->fields["CATE_CODI"],
            "nombre" => $rs->fields["CATE_NOMBRE"],
            "activo" => (0 + $rs->fields["CATE_ACTIVO"]) == 1,
            "orden"  => 0 + $rs->fields["CATE_ORDEN"]
        );
        $rs->MoveNext();
    }
    return $cats;
}

/**
 * Devuelve el árbol de sumillas activas agrupadas por motivo.
 *
 * @return array Lista de ramas: array(codi, nombre, sumillas => array(codi, nombre))
 *               Las ramas sin sumillas visibles no se incluyen.
 */
function sumillas_obtener_arbol($db, $inst_codi) {
    $inst_codi = 0 + $inst_codi;

    $ramas = array();
    foreach (sumillas_categorias($db, $inst_codi) as $cat) {
        $ramas[$cat["codi"]] = array(
            "codi"     => $cat["codi"],
            "nombre"   => $cat["nombre"],
            "sumillas" => array()
        );
    }

    $sql = "select accion_codi, accion_nombre, cate_codi
              from accion
             where inst_codi = $inst_codi
               and accion_activo = 1
             order by accion_nombre";
    $rs = $db->conn->Execute($sql);

    $sin_categoria = array();
    while ($rs and !$rs->EOF) {
        $cate = $rs->fields["CATE_CODI"];
        $cate = ($cate === null or $cate === "") ? 0 : 0 + $cate;

        $sumilla = array(
            "codi"   => 0 + $rs->fields["ACCION_CODI"],
            "nombre" => $rs->fields["ACCION_NOMBRE"]
        );

        if ($cate > 0 and isset($ramas[$cate])) $ramas[$cate]["sumillas"][] = $sumilla;
        else                                    $sin_categoria[]            = $sumilla;

        $rs->MoveNext();
    }

    // Una rama sin sumillas activas no aporta nada al árbol.
    $arbol = array();
    foreach ($ramas as $rama) {
        if (count($rama["sumillas"]) > 0) $arbol[] = $rama;
    }

    // Lo no clasificado va al final, para no estorbar a los motivos definidos.
    if (count($sin_categoria) > 0) {
        $arbol[] = array("codi" => 0, "nombre" => SUMILLAS_SIN_CATEGORIA, "sumillas" => $sin_categoria);
    }

    return $arbol;
}

/**
 * Cuenta las sumillas que el usuario puede llegar a marcar.
 *
 * @return int
 */
function sumillas_contar_seleccionables($db, $inst_codi) {
    $total = 0;
    foreach (sumillas_obtener_arbol($db, $inst_codi) as $rama) {
        $total += count($rama["sumillas"]);
    }
    return $total;
}

/**
 * Dibuja el árbol de selección.
 *
 * Las hojas se eligen con un clic: el primero añade "*<sumilla> " al comentario
 * y el segundo lo quita, que es como se venía guardando la sumilla elegida.
 *
 * No se usan checkbox a propósito. La pantalla de reasignación arranca con
 * onLoad="markAll(1)", que marca todos los controles del formulario para
 * preseleccionar los documentos; un checkbox aquí nacería marcado sin que nadie
 * lo hubiera elegido, y verificar_chk() lo contaría como documento seleccionado.
 *
 * @param  object $db
 * @param  int    $inst_codi
 * @param  string $id_caja_texto textarea donde se escribe la sumilla elegida
 * @return string HTML ("" si no hay nada que mostrar)
 */
function sumillas_dibujar_arbol($db, $inst_codi, $id_caja_texto = "observa") {
    $arbol = sumillas_obtener_arbol($db, $inst_codi);
    if (count($arbol) == 0) return "";

    // Los códigos elegidos viajan en este campo para quedar registrados como dato
    // junto al documento, no sólo como texto dentro del comentario.
    $html = "<input type='hidden' name='txt_sumillas' id='txt_sumillas' value=''>"
          . "<div class='sumillas-arbol' id='div_sumillas_arbol'"
          . " data-caja='".htmlspecialchars($id_caja_texto, ENT_QUOTES)."'>"
          . "<ul class='sumillas-nivel'>";

    foreach ($arbol as $rama) {
        // Las ramas arrancan abiertas: con pocos motivos el árbol entra completo
        // en pantalla y evita un clic extra en el caso habitual.
        $html .= "<li class='sumillas-rama abierta'>"
               . "<span class='sumillas-toggle' onclick='sumillas_alternar(this);'>"
               . htmlspecialchars($rama["nombre"])."</span>"
               . "<ul class='sumillas-nivel'>";

        foreach ($rama["sumillas"] as $sumilla) {
            $html .= "<li class='sumillas-hoja'>"
                   . "<a href='javascript:void(0);' class='sumillas-item' id='sumilla_".$sumilla["codi"]."'"
                   . " data-codi='".$sumilla["codi"]."'"
                   . " data-texto=\"".htmlspecialchars($sumilla["nombre"], ENT_QUOTES)."\""
                   . " onclick='sumillas_marcar(this);'>".htmlspecialchars($sumilla["nombre"])."</a>"
                   . "</li>";
        }

        $html .= "</ul></li>";
    }

    return $html."</ul></div>";
}

/**
 * Guarda, para el resto de la petición, las sumillas que el usuario eligió en el
 * árbol. Historico::insertarHistorico() las recoge de aquí al registrar el evento.
 *
 * No se usa $_SESSION a propósito: la selección vale sólo para esta transacción
 * y dejarla en la sesión la arrastraría a la siguiente.
 *
 * @param string $codigos Lista de accion_codi separados por coma (viene del POST)
 */
function sumillas_fijar_seleccion($codigos) {
    $limpios = array();
    foreach (explode(",", (string)$codigos) as $codi) {
        $codi = 0 + trim($codi);
        if ($codi > 0 and !in_array($codi, $limpios)) $limpios[] = $codi;
    }
    $GLOBALS["__sumillas_seleccion"]  = $limpios;
    $GLOBALS["__sumillas_registradas"] = array();
}

/** Sumillas elegidas en esta petición. */
function sumillas_seleccion() {
    return $GLOBALS["__sumillas_seleccion"] ?? array();
}

/**
 * Nombres de las sumillas elegidas, listos para mostrar.
 *
 * @return string "" si no se eligió ninguna
 */
function sumillas_texto_seleccion($db) {
    $codigos = sumillas_seleccion();
    if (count($codigos) == 0) return "";

    $rs = $db->conn->Execute("select accion_nombre from accion
                               where accion_codi in (".implode(",", $codigos).")
                               order by accion_nombre");
    $nombres = array();
    while ($rs and !$rs->EOF) {
        $nombres[] = $rs->fields["ACCION_NOMBRE"];
        $rs->MoveNext();
    }
    return implode(", ", $nombres);
}

/**
 * Registra las sumillas elegidas contra un documento y el evento de la hoja de
 * ruta en que se aplicaron.
 *
 * Se guarda copia del nombre y de la categoría porque el catálogo puede cambiar
 * después: la hoja de ruta debe seguir diciendo qué se sumilló aquel día.
 *
 * @param  object $db
 * @param  string $radicado
 * @param  int    $hist_codi Evento de hist_eventos (0 si no se pudo determinar)
 * @param  int    $usua_codi Quien aplicó la sumilla
 * @return int    Sumillas registradas
 */
function sumillas_registrar($db, $radicado, $hist_codi, $usua_codi) {
    $codigos = sumillas_seleccion();
    if (count($codigos) == 0) return 0;

    // Una reasignación puede generar varios eventos para el mismo documento
    // (p. ej. el traspaso previo de una bandeja compartida). La sumilla se
    // registra una sola vez, en el evento del usuario que la eligió.
    if (isset($GLOBALS["__sumillas_registradas"][$radicado])) return 0;
    $GLOBALS["__sumillas_registradas"][$radicado] = true;

    $radicado  = sumillas_radicado_sql($radicado);
    $hist_codi = 0 + $hist_codi;
    $usua_codi = 0 + $usua_codi;
    $total     = 0;

    $sql = "select a.accion_codi, a.accion_nombre, c.cate_nombre
              from accion a
              left join accion_categoria c on c.cate_codi = a.cate_codi
             where a.accion_codi in (".implode(",", $codigos).")";
    $rs = $db->conn->Execute($sql);

    while ($rs and !$rs->EOF) {
        $rasu_codi = $db->nextId("sec_radicado_sumilla");
        $nombre    = $db->conn->qstr($rs->fields["ACCION_NOMBRE"]);
        $categoria = ($rs->fields["CATE_NOMBRE"] === null)
                   ? "null" : $db->conn->qstr($rs->fields["CATE_NOMBRE"]);
        $codi      = 0 + $rs->fields["ACCION_CODI"];
        $hist_sql  = ($hist_codi > 0) ? $hist_codi : "null";

        $ok = $db->conn->Execute(
            "insert into radicado_sumilla
                    (rasu_codi, radi_nume_radi, hist_codi, accion_codi, rasu_nombre, rasu_categoria, usua_codi)
             values ($rasu_codi, $radicado, $hist_sql, $codi, $nombre, $categoria, $usua_codi)");
        if ($ok) ++$total;

        $rs->MoveNext();
    }

    return $total;
}

/**
 * hist_codi del evento que se acaba de insertar en esta conexión.
 *
 * currval() sobre la secuencia de hist_codi es exacto aunque otras sesiones estén
 * insertando a la vez. pg_get_serial_sequence() no basta: la secuencia deja de
 * estar "poseída" por la columna al restaurar un volcado, y entonces devuelve
 * null; por eso se lee también del DEFAULT de la columna. Si aun así no se puede
 * resolver, se recurre al último evento de ese documento y usuario.
 *
 * @return int 0 si no se pudo determinar
 */
function sumillas_ultimo_hist_codi($db, $radicado, $usua_ori) {
    $radicado = sumillas_radicado_sql($radicado);
    $usua_ori = 0 + $usua_ori;

    $sql = "select coalesce(
                     pg_get_serial_sequence('hist_eventos','hist_codi'),
                     substring(pg_get_expr(d.adbin, d.adrelid) from 'nextval\\(''([^'']+)''')
                   ) as seq
              from pg_attrdef d
              join pg_attribute a on a.attrelid = d.adrelid and a.attnum = d.adnum
             where d.adrelid = 'hist_eventos'::regclass and a.attname = 'hist_codi'";
    $rs  = $db->conn->Execute($sql);
    $seq = ($rs and !$rs->EOF) ? trim($rs->fields["SEQ"] ?? '') : '';

    if ($seq != '') {
        $rs = $db->conn->Execute("select currval(".$db->conn->qstr($seq).") as codi");
        if ($rs and !$rs->EOF and (0 + $rs->fields["CODI"]) > 0) return 0 + $rs->fields["CODI"];
    }

    $rs = $db->conn->Execute("select max(hist_codi) as codi from hist_eventos
                               where radi_nume_radi = $radicado and usua_codi_ori = $usua_ori");
    return ($rs and !$rs->EOF) ? 0 + $rs->fields["CODI"] : 0;
}

/**
 * Sumillas registradas en un documento, con el evento en que se aplicaron.
 *
 * @return array Lista de array(nombre, categoria, hist_codi, fecha, usua_codi)
 */
function sumillas_del_radicado($db, $radicado) {
    $radicado = sumillas_radicado_sql($radicado);
    $sql = "select rasu_nombre, rasu_categoria, hist_codi, rasu_fecha, usua_codi
              from radicado_sumilla
             where radi_nume_radi = $radicado
             order by rasu_fecha, rasu_nombre";
    $rs = $db->conn->Execute($sql);

    $lista = array();
    while ($rs and !$rs->EOF) {
        $lista[] = array(
            "nombre"    => $rs->fields["RASU_NOMBRE"],
            "categoria" => $rs->fields["RASU_CATEGORIA"],
            "hist_codi" => 0 + $rs->fields["HIST_CODI"],
            "fecha"     => $rs->fields["RASU_FECHA"],
            "usua_codi" => 0 + $rs->fields["USUA_CODI"]
        );
        $rs->MoveNext();
    }
    return $lista;
}

/**
 * Fragmento SQL que trae, para cada fila de hist_eventos, las sumillas que se
 * registraron en ese evento. Devuelve "null as sumillas" mientras la tabla no
 * exista, para no romper el recorrido en instalaciones sin este despliegue.
 *
 * @param  object $db
 * @param  string $alias_hist Alias de hist_eventos en la consulta destino
 * @return string
 */
function sumillas_columna_historico($db, $alias_hist = "h") {
    $rs = $db->conn->query("select to_regclass('radicado_sumilla') as tabla");
    if (!$rs or $rs->EOF or trim($rs->fields["TABLA"] ?? '') == '') return "null as sumillas";

    return "(select string_agg(s.rasu_nombre, ', ' order by s.rasu_nombre)
               from radicado_sumilla s
              where s.hist_codi = $alias_hist.hist_codi) as sumillas";
}

/**
 * Devuelve el bloque <script> con el comportamiento del árbol.
 * Se emite una sola vez por página.
 */
function sumillas_javascript() {
    return <<<'JS'
<script type="text/javascript">
    function sumillas_alternar(nodo) {
        var li = nodo.parentNode;
        li.className = (li.className.indexOf('abierta') >= 0)
                     ? li.className.replace('abierta', 'cerrada')
                     : li.className.replace('cerrada', 'abierta');
    }

    function sumillas_caja_texto() {
        var cont = document.getElementById('div_sumillas_arbol');
        var id   = cont ? cont.getAttribute('data-caja') : 'observa';
        return document.getElementById(id || 'observa');
    }

    function sumillas_seleccionada(item) {
        return item.className.indexOf('seleccionada') >= 0;
    }

    // Mantiene el campo que se envía al servidor con los códigos marcados, para
    // que la sumilla quede registrada como dato del documento.
    function sumillas_sincronizar() {
        var campo = document.getElementById('txt_sumillas');
        var cont  = document.getElementById('div_sumillas_arbol');
        if (!campo || !cont) return;

        var items = cont.getElementsByTagName('a');
        var codis = [];
        for (var i = 0; i < items.length; i++)
            if (items[i].className.indexOf('sumillas-item') >= 0 && sumillas_seleccionada(items[i]))
                codis.push(items[i].getAttribute('data-codi'));

        campo.value = codis.join(',');
    }

    function sumillas_pintar(item, activa) {
        item.className = activa
            ? (sumillas_seleccionada(item) ? item.className : item.className + ' seleccionada')
            : item.className.replace(/\s*seleccionada/g, '');
    }

    function sumillas_marcar(item) {
        var caja = sumillas_caja_texto();
        if (!caja) return;
        var texto = '*' + item.getAttribute('data-texto') + ' ';
        var activa = !sumillas_seleccionada(item);

        if (activa) {
            if (caja.value.indexOf(texto) < 0) caja.value += texto;
        } else {
            caja.value = caja.value.replace(texto, '');
        }
        sumillas_pintar(item, activa);

        // El contador recorta el comentario al máximo permitido; si el recorte
        // se comió la sumilla recién elegida, se deshace la marca para que el
        // árbol no diga una cosa y el comentario otra.
        if (typeof formEnvio_contador_caracteres == 'function') formEnvio_contador_caracteres();
        if (activa && caja.value.indexOf(texto) < 0) sumillas_pintar(item, false);

        sumillas_sincronizar();
    }

    function sumillas_limpiar() {
        var cont = document.getElementById('div_sumillas_arbol');
        if (!cont) return;
        var items = cont.getElementsByTagName('a');
        for (var i = 0; i < items.length; i++)
            if (items[i].className.indexOf('sumillas-item') >= 0) sumillas_pintar(items[i], false);
        sumillas_sincronizar();
    }
</script>
JS;
}
