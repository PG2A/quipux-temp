<?php
require_once(__DIR__ . '/membrete_pdf.php');

function membrete_csrf_token() {
    if (empty($_SESSION['membrete_csrf'])) {
        $_SESSION['membrete_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['membrete_csrf'];
}

function membrete_csrf_valido() {
    $recibido = (string)($_POST['csrf_token'] ?? '');
    $guardado = (string)($_SESSION['membrete_csrf'] ?? '');
    return $recibido !== '' && $guardado !== '' && hash_equals($guardado, $recibido);
}

function membrete_requerir_post_csrf($ajax = false) {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && membrete_csrf_valido()) return;
    $mensaje = 'La solicitud no es v&aacute;lida o expir&oacute;. Recargue la pantalla e intente nuevamente.';
    if ($ajax) die('ERROR|' . $mensaje);
    echo html_error($mensaje);
    die('');
}

function membrete_ejecutar($db, $sql) {
    $resultado = $db->conn->Execute($sql);
    if ($resultado === false) {
        throw new RuntimeException('No se pudo completar la operaci&oacute;n en la base de datos.');
    }
    return $resultado;
}

function membrete_dir() {
    return dirname(__DIR__, 2) . '/bodega/plantillas/membretes';
}

function membrete_ruta($archivo) {
    return membrete_dir() . '/' . basename((string)$archivo);
}

function membrete_es_pdf($ruta) {
    if (!is_file($ruta)) return false;
    $fh = fopen($ruta, 'rb');
    if (!$fh) return false;
    $cab = fread($fh, 1024);
    fclose($fh);
    return strpos(ltrim((string)$cab), '%PDF-') === 0;
}

function membrete_h($texto) {
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

function membrete_kb($bytes) {
    if ($bytes <= 0) return '';
    if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1, ',', '.') . ' KB';
    return number_format($bytes / (1024 * 1024), 2, ',', '.') . ' MB';
}

function membrete_obtener($db, $id) {
    $id = (int)$id;
    $inst = (int)($_SESSION['inst_codi'] ?? 0);
    $rs = $db->conn->Execute("select m.*, (select count(*) from membrete_asignacion a where a.memb_codi=m.memb_codi and a.masi_estado=1) as n_asignadas
                              from membrete m where m.memb_codi=$id and m.inst_codi=$inst");
    if (!$rs || $rs->EOF) return null;
    $f = $rs->fields;
    $m = array(
        'memb_codi'        => (int)$f['MEMB_CODI'],
        'inst_codi'        => (int)$f['INST_CODI'],
        'memb_nombre'      => (string)$f['MEMB_NOMBRE'],
        'memb_descripcion' => (string)($f['MEMB_DESCRIPCION'] ?? ''),
        'memb_archivo'     => (string)$f['MEMB_ARCHIVO'],
        'memb_defecto'     => (int)$f['MEMB_DEFECTO'],
        'memb_estado'      => (int)$f['MEMB_ESTADO'],
        'n_asignadas'      => (int)$f['N_ASIGNADAS'],
    );
    $m['ruta'] = membrete_ruta($m['memb_archivo']);
    $m['existe'] = is_file($m['ruta']);
    $m['tamanio'] = $m['existe'] ? filesize($m['ruta']) : 0;
    $m['fecha'] = $m['existe'] ? date('Y-m-d H:i', filemtime($m['ruta'])) : '';
    return $m;
}

function membrete_defecto($db) {
    $inst = (int)($_SESSION['inst_codi'] ?? 0);
    $rs = $db->conn->Execute("select memb_codi, memb_nombre from membrete where inst_codi=$inst and memb_defecto=1 and memb_estado=1");
    if (!$rs || $rs->EOF) return null;
    return array('memb_codi' => (int)$rs->fields['MEMB_CODI'], 'memb_nombre' => (string)$rs->fields['MEMB_NOMBRE']);
}

function membrete_lista_activas($db) {
    $inst = (int)($_SESSION['inst_codi'] ?? 0);
    $rs = $db->conn->Execute("select memb_codi, memb_nombre, memb_defecto from membrete where inst_codi=$inst and memb_estado=1 order by memb_defecto desc, memb_nombre");
    $lista = array();
    while ($rs && !$rs->EOF) {
        $lista[] = array('memb_codi' => (int)$rs->fields['MEMB_CODI'], 'memb_nombre' => (string)$rs->fields['MEMB_NOMBRE'], 'memb_defecto' => (int)$rs->fields['MEMB_DEFECTO']);
        $rs->MoveNext();
    }
    return $lista;
}

function membrete_incompatibles($db) {
    $inst = (int)($_SESSION['inst_codi'] ?? 0);
    $rs = $db->conn->Execute("select memb_codi, memb_nombre, memb_archivo from membrete where inst_codi=$inst and memb_estado=1 order by memb_nombre");
    $lista = array();
    while ($rs && !$rs->EOF) {
        $ruta = membrete_ruta($rs->fields['MEMB_ARCHIVO']);
        if (is_file($ruta)) {
            $diag = MembretePdfCompat::diagnosticar($ruta);
            if (!$diag['compatible']) $lista[] = array('memb_codi' => (int)$rs->fields['MEMB_CODI'], 'memb_nombre' => (string)$rs->fields['MEMB_NOMBRE'], 'motivo' => $diag['motivo']);
        }
        $rs->MoveNext();
    }
    return $lista;
}

function membrete_filtro_areas($db, $alias = 'd') {
    $inst = (int)($_SESSION['inst_codi'] ?? 0);
    $where = " $alias.depe_estado=1 and $alias.inst_codi=$inst ";
    $admin = obtenerAreasAdmin($_SESSION['usua_codi'], $inst, $_SESSION['usua_admin_sistema'], $db);
    if (!empty($admin)) $where .= " and $alias.depe_codi in ($admin) ";
    return $where;
}

function membrete_areas($db) {
    $where = membrete_filtro_areas($db);
    $rs = $db->conn->Execute("select d.depe_codi, d.depe_nomb from dependencia d where $where order by d.depe_nomb");
    $lista = array();
    while ($rs && !$rs->EOF) {
        $lista[] = array('depe_codi' => (int)$rs->fields['DEPE_CODI'], 'depe_nomb' => (string)$rs->fields['DEPE_NOMB']);
        $rs->MoveNext();
    }
    return $lista;
}

function membrete_area_en_alcance($db, $depe) {
    $depe = (int)$depe;
    $where = membrete_filtro_areas($db);
    $nombre = $db->conn->GetOne("select d.depe_nomb from dependencia d where $where and d.depe_codi=$depe");
    return $nombre === false ? null : (string)$nombre;
}

function membrete_tipos_documento($db) {
    $inst = (int)($_SESSION['inst_codi'] ?? 0);
    $rs = $db->conn->Execute("select trad_codigo, trad_descr from tiporad where trad_tipo='S' and trad_estado=1 and trad_inst_codi in (0,$inst) order by trad_descr");
    $lista = array();
    while ($rs && !$rs->EOF) {
        $lista[] = array('trad_codigo' => (int)$rs->fields['TRAD_CODIGO'], 'trad_descr' => (string)$rs->fields['TRAD_DESCR']);
        $rs->MoveNext();
    }
    return $lista;
}

function membrete_tipo_nombre($db, $tipo) {
    $tipo = (int)$tipo;
    if ($tipo <= 0) return 'todos los tipos de documento';
    $inst = (int)($_SESSION['inst_codi'] ?? 0);
    $n = $db->conn->GetOne("select trad_descr from tiporad where trad_codigo=$tipo and trad_tipo='S' and trad_estado=1 and trad_inst_codi in (0,$inst)");
    return ($n === false || $n === null) ? null : (string)$n;
}

function membrete_areas_uso($db, $memb, $tipo, $txt = '') {
    $memb = (int)$memb;
    $tipo = (int)$tipo;
    $where = membrete_filtro_areas($db);
    $txt = trim(limpiar_sql($txt));
    if ($txt != '') {
        $t = strtoupper($txt);
        $patron = $db->conn->qstr('%' . $t . '%');
        $where .= " and (upper(d.depe_nomb) like $patron or upper(coalesce(d.dep_sigla,'')) like $patron)";
    }
    $cond_tipo = $tipo > 0 ? "a.trad_codigo=$tipo" : "a.trad_codigo is null";
    $inst = (int)($_SESSION['inst_codi'] ?? 0);
    $base = "from membrete_asignacion a join membrete m on m.memb_codi=a.memb_codi and m.memb_estado=1 and m.inst_codi=$inst where a.depe_codi=d.depe_codi and a.masi_estado=1 and a.masi_uso='documento'";
    $sql = "select d.depe_codi, d.depe_nomb, coalesce(d.dep_sigla,'') as sigla,
                   (select a.memb_codi $base and $cond_tipo limit 1) as memb_tipo,
                   (select m.memb_nombre $base and $cond_tipo limit 1) as hoja_tipo,
                   (select m.memb_nombre $base and a.trad_codigo is null limit 1) as hoja_general
            from dependencia d where $where order by d.depe_nomb";
    $rs = $db->conn->Execute($sql);
    $defecto = membrete_defecto($db);
    $lista = array();
    while ($rs && !$rs->EOF) {
        $f = $rs->fields;
        $memb_tipo = (int)($f['MEMB_TIPO'] ?? 0);
        if (!empty($f['HOJA_TIPO'])) $usa = (string)$f['HOJA_TIPO'];
        elseif ($tipo > 0 && !empty($f['HOJA_GENERAL'])) $usa = $f['HOJA_GENERAL'] . ' (general del área)';
        elseif ($defecto) $usa = $defecto['memb_nombre'] . ' (por defecto)';
        else $usa = 'archivo de Administración de Áreas';
        $lista[] = array(
            'depe_codi' => (int)$f['DEPE_CODI'],
            'depe_nomb' => (string)$f['DEPE_NOMB'],
            'sigla'     => (string)$f['SIGLA'],
            'marcada'   => $memb_tipo == $memb,
            'usa'       => $usa,
        );
        $rs->MoveNext();
    }
    return $lista;
}

function membrete_tipos_uso($db, $memb) {
    $memb = (int)$memb;
    $inst = (int)($_SESSION['inst_codi'] ?? 0);
    $rs = $db->conn->Execute("select t.trad_codigo, t.trad_descr, a.memb_codi, m.memb_nombre
                              from tiporad t
                              left join membrete_asignacion a on a.trad_codigo=t.trad_codigo and a.depe_codi is null and a.masi_estado=1 and a.masi_uso='documento'
                              left join membrete m on m.memb_codi=a.memb_codi and m.memb_estado=1 and m.inst_codi=$inst
                              where t.trad_tipo='S' and t.trad_estado=1 and t.trad_inst_codi in (0,$inst)
                              order by t.trad_descr");
    $defecto = membrete_defecto($db);
    $lista = array();
    while ($rs && !$rs->EOF) {
        $f = $rs->fields;
        $actual = !empty($f['MEMB_NOMBRE']) ? (int)$f['MEMB_CODI'] : 0;
        if ($actual > 0) $usa = (string)$f['MEMB_NOMBRE'];
        elseif ($defecto) $usa = $defecto['memb_nombre'] . ' (por defecto)';
        else $usa = 'archivo de Administración de Áreas';
        $lista[] = array(
            'trad_codigo' => (int)$f['TRAD_CODIGO'],
            'trad_descr'  => (string)$f['TRAD_DESCR'],
            'marcada'     => $actual == $memb,
            'usa'         => $usa,
        );
        $rs->MoveNext();
    }
    return $lista;
}

function membrete_asignaciones_por_tipo($db) {
    $inst = (int)($_SESSION['inst_codi'] ?? 0);
    $rs = $db->conn->Execute("select t.trad_descr, m.memb_nombre
                              from membrete_asignacion a
                              join membrete m on m.memb_codi=a.memb_codi and m.memb_estado=1 and m.inst_codi=$inst
                              join tiporad t on t.trad_codigo=a.trad_codigo
                              where a.depe_codi is null and a.masi_estado=1 and a.masi_uso='documento'
                              order by t.trad_descr");
    $lista = array();
    while ($rs && !$rs->EOF) {
        $lista[] = array('trad_descr' => (string)$rs->fields['TRAD_DESCR'], 'memb_nombre' => (string)$rs->fields['MEMB_NOMBRE']);
        $rs->MoveNext();
    }
    return $lista;
}

function membrete_log($db, $memb_codi, $accion, $detalle) {
    $usuario = (int)($_SESSION['usua_codi'] ?? 0);
    membrete_ejecutar($db, "insert into log_membrete (memb_codi, usua_codi, log_accion, log_detalle) values (" . ($memb_codi === null ? "null" : (int)$memb_codi) . ", $usuario, " . $db->conn->qstr($accion) . ", " . $db->conn->qstr(substr((string)$detalle, 0, 500)) . ")");
}

function membrete_pagina_resultado($mensaje, $destino) {
    echo "<!DOCTYPE html>" . html_head();
    echo "<body><center><br><table width='60%' class='borde_tab'><tr><td class='listado2' align='center'><br>$mensaje<br><br>";
    echo "<input type='button' name='btn_aceptar' value='Aceptar' class='botones' onClick=\"window.location='$destino'\"><br><br></td></tr></table></center></body></html>";
    die('');
}

function membrete_texto_tokens() {
    return array(
        '**QUIPUX_DATOS_DOC_NOMBRE_INSTITUCION**' => 'Nombre de la instituci&oacute;n',
        '**QUIPUX_DATOS_DOC_REMITENTE_CIUDAD**'   => 'Ciudad del remitente',
        '**QUIPIX_DATOS_DOC_FECHA_LARGA**'        => 'Fecha del d&iacute;a en letras',
    );
}

function membrete_textos_tipos($db) {
    $inst = (int)($_SESSION['inst_codi'] ?? 0);
    $rs = $db->conn->Execute("select trad_codigo, trad_descr, trad_abreviatura, coalesce(trad_texto_inicio,'') as trad_texto_inicio
                              from tiporad where trad_tipo='S' and trad_estado=1 and trad_inst_codi in (0,$inst) order by trad_descr");
    $lista = array();
    while ($rs && !$rs->EOF) {
        $lista[] = array(
            'trad_codigo'      => (int)$rs->fields['TRAD_CODIGO'],
            'trad_descr'       => (string)$rs->fields['TRAD_DESCR'],
            'trad_abreviatura' => (string)($rs->fields['TRAD_ABREVIATURA'] ?? ''),
            'texto'            => (string)$rs->fields['TRAD_TEXTO_INICIO'],
        );
        $rs->MoveNext();
    }
    return $lista;
}

function membrete_texto_tipo($db, $tipo) {
    $tipo = (int)$tipo;
    foreach (membrete_textos_tipos($db) as $t) {
        if ($t['trad_codigo'] === $tipo) return $t;
    }
    return null;
}

function membrete_texto_plano($html, $max = 140) {
    $txt = html_entity_decode(strip_tags(str_replace(array('<br>', '<br />', '<br/>', '</p>', '</div>'), ' ', (string)$html)), ENT_QUOTES, 'UTF-8');
    $txt = trim(preg_replace('/\s+/u', ' ', $txt));
    if (mb_strlen($txt) > $max) $txt = mb_substr($txt, 0, $max) . '&hellip;';
    return $txt;
}

function membrete_texto_limpio($html) {
    $html = str_replace("\r", '', (string)$html);
    $html = preg_replace('#<\s*(script|iframe|object|embed|form)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
    $html = preg_replace('#<\s*(script|iframe|object|embed|form)[^>]*/?>#i', '', $html);
    $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);
    $html = preg_replace('#(href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>\s]*\2#i', '$1=$2#$2', $html);
    return trim($html);
}

function membrete_texto_resolver_tokens($db, $html) {
    $usr = ObtenerDatosUsuario($_SESSION['usua_codi'] ?? 0, $db);
    $html = str_replace('**QUIPUX_DATOS_DOC_NOMBRE_INSTITUCION**', (string)($_SESSION['inst_nombre'] ?? ''), (string)$html);
    $html = str_replace('**QUIPUX_DATOS_DOC_REMITENTE_CIUDAD**', (string)($usr['ciudad'] ?? ''), $html);
    $html = str_replace('**QUIPIX_DATOS_DOC_FECHA_LARGA**', fechaAtexto(date('Y-m-d')), $html);
    return $html;
}

function membrete_texto_tokens_amigables($html) {
    foreach (membrete_texto_tokens() as $token => $descr) {
        $html = str_replace($token, '[' . html_entity_decode($descr, ENT_QUOTES, 'UTF-8') . ']', (string)$html);
    }
    return $html;
}
