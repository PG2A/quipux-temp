<?php
class MembretePdfCompat
{
    private $buf;
    private $len;
    private $xref = array();
    private $trailer = array();
    private $visitados = array();
    private $usaXrefStream = false;
    private $hibrido = false;
    private $objstmCache = array();

    private function __construct($buf)
    {
        $this->buf = $buf;
        $this->len = strlen($buf);
    }

    public static function diagnosticar($ruta)
    {
        $r = array('compatible' => false, 'normalizable' => false, 'cifrado' => false, 'motivo' => '', 'version' => '');
        $buf = @file_get_contents($ruta);
        if ($buf === false || $buf === '') { $r['motivo'] = 'No se pudo leer el archivo.'; return $r; }
        $c = new self($buf);
        $r['version'] = $c->version();
        try {
            $c->leerEstructura();
        } catch (Exception $e) {
            $r['motivo'] = $e->getMessage();
            return $r;
        }
        if (isset($c->trailer['/Encrypt'])) {
            $r['cifrado'] = true;
            $r['motivo'] = 'El PDF est&aacute; cifrado o tiene restricciones de seguridad.';
            return $r;
        }
        if ($c->usaXrefStream || $c->hibrido) {
            $r['normalizable'] = true;
            $r['motivo'] = 'Usa tablas de referencias comprimidas (formato PDF 1.5 o superior).';
            return $r;
        }
        $r['compatible'] = true;
        return $r;
    }

    public static function normalizar($origen, $destino)
    {
        $buf = @file_get_contents($origen);
        if ($buf === false || $buf === '') return 'No se pudo leer el archivo.';
        $c = new self($buf);
        try {
            $c->leerEstructura();
            if (isset($c->trailer['/Encrypt'])) return 'El PDF est&aacute; cifrado o tiene restricciones de seguridad.';
            $salida = $c->reescribir();
        } catch (Exception $e) {
            return $e->getMessage();
        }
        if (@file_put_contents($destino, $salida) === false) return 'No se pudo escribir el archivo normalizado.';
        return true;
    }

    private function version()
    {
        return preg_match('/%PDF-(\d\.\d)/', substr($this->buf, 0, 1024), $m) ? $m[1] : '';
    }

    private function leerEstructura()
    {
        $cola = substr($this->buf, max(0, $this->len - 2048));
        $pos = strrpos($cola, 'startxref');
        if ($pos === false) throw new Exception('No se encontr&oacute; la marca startxref.');
        if (!preg_match('/startxref\s+(\d+)/', substr($cola, $pos), $m)) throw new Exception('La marca startxref no tiene desplazamiento.');
        $this->leerXref((int)$m[1]);
        if (!isset($this->trailer['/Root'])) throw new Exception('El PDF no tiene cat&aacute;logo (/Root).');
    }

    private function leerXref($off)
    {
        if ($off <= 0 || $off >= $this->len) throw new Exception('Desplazamiento de la tabla xref fuera del archivo.');
        if (isset($this->visitados[$off])) return;
        $this->visitados[$off] = true;
        $p = $this->ws($off);
        if (substr($this->buf, $p, 4) === 'xref') { $this->leerTabla($p + 4); return; }
        if (preg_match('/\G\d+\s+\d+\s+obj\b/', $this->buf, $m, 0, $p)) { $this->usaXrefStream = true; $this->leerXrefStream($p); return; }
        throw new Exception('No hay tabla xref en el desplazamiento ' . $off . '.');
    }

    private function leerTabla($p)
    {
        $b = $this->buf;
        while (true) {
            $p = $this->ws($p);
            if (substr($b, $p, 7) === 'trailer') { $p += 7; break; }
            if (!preg_match('/\G(\d+)\s+(\d+)/', $b, $m, 0, $p)) throw new Exception('Secci&oacute;n de la tabla xref inv&aacute;lida.');
            $ini = (int)$m[1];
            $cnt = (int)$m[2];
            $p += strlen($m[0]);
            for ($i = 0; $i < $cnt; $i++) {
                $p = $this->ws($p);
                if (!preg_match('/\G(\d{1,10})\s+(\d{1,5})\s+([nf])/', $b, $m, 0, $p)) throw new Exception('Entrada de la tabla xref inv&aacute;lida.');
                $p += strlen($m[0]);
                $num = $ini + $i;
                if (!isset($this->xref[$num])) $this->xref[$num] = $m[3] === 'n' ? array(1, (int)$m[1], (int)$m[2]) : array(0);
            }
        }
        $p = $this->ws($p);
        $e = $this->dict($p, $fin);
        $this->registrarTrailer($e);
        if (isset($e['/XRefStm'])) { $this->hibrido = true; $this->leerXref((int)$e['/XRefStm']['raw']); }
        if (isset($e['/Prev'])) $this->leerXref((int)$e['/Prev']['raw']);
    }

    private function leerXrefStream($p)
    {
        $o = $this->objetoEn($p);
        if ($o['datos'] === null || !$o['dict']) throw new Exception('El objeto xref no es un stream.');
        $d = $o['dict'];
        $datos = $this->decodificar($d, $o['datos']);
        $w = $this->enteros(isset($d['/W']) ? $d['/W']['raw'] : '');
        if (count($w) < 3) throw new Exception('Campo /W inv&aacute;lido en el xref stream.');
        $size = isset($d['/Size']) ? (int)$d['/Size']['raw'] : 0;
        $index = isset($d['/Index']) ? $this->enteros($d['/Index']['raw']) : array(0, $size);
        $ancho = array_sum($w);
        $pos = 0;
        $n = strlen($datos);
        for ($k = 0; $k + 1 < count($index); $k += 2) {
            $ini = $index[$k];
            $cnt = $index[$k + 1];
            for ($i = 0; $i < $cnt; $i++) {
                if ($pos + $ancho > $n) break 2;
                $f = array();
                foreach ($w as $j => $len) {
                    $v = 0;
                    for ($x = 0; $x < $len; $x++) $v = ($v << 8) | ord($datos[$pos++]);
                    $f[$j] = $v;
                }
                $tipo = $w[0] === 0 ? 1 : $f[0];
                $num = $ini + $i;
                if (isset($this->xref[$num])) continue;
                if ($tipo === 1) $this->xref[$num] = array(1, $f[1], $f[2]);
                elseif ($tipo === 2) $this->xref[$num] = array(2, $f[1], $f[2]);
                else $this->xref[$num] = array(0);
            }
        }
        $this->registrarTrailer($d);
        if (isset($d['/Prev'])) $this->leerXref((int)$d['/Prev']['raw']);
    }

    private function registrarTrailer($e)
    {
        foreach (array('/Root', '/Info', '/ID', '/Encrypt') as $k) {
            if (isset($e[$k]) && !isset($this->trailer[$k])) $this->trailer[$k] = trim($e[$k]['raw']);
        }
    }

    private function ws($p)
    {
        $n = $this->len;
        $b = $this->buf;
        while ($p < $n) {
            $ch = $b[$p];
            if ($ch === ' ' || $ch === "\n" || $ch === "\r" || $ch === "\t" || $ch === "\f" || $ch === "\0") { $p++; continue; }
            if ($ch === '%') { while ($p < $n && $b[$p] !== "\n" && $b[$p] !== "\r") $p++; continue; }
            break;
        }
        return $p;
    }

    private static function esDelim($ch)
    {
        return $ch === '' || strpos(" \n\r\t\f\0()<>[]{}/%", $ch) !== false;
    }

    private function saltarValor($p)
    {
        $n = $this->len;
        $b = $this->buf;
        if ($p >= $n) throw new Exception('Fin de archivo inesperado.');
        $ch = $b[$p];
        $sig = $p + 1 < $n ? $b[$p + 1] : '';
        if ($ch === '<' && $sig === '<') {
            $p += 2;
            while (true) {
                $p = $this->ws($p);
                if ($p >= $n) throw new Exception('Diccionario sin cerrar.');
                if ($b[$p] === '>' && ($p + 1 < $n) && $b[$p + 1] === '>') return $p + 2;
                $p = $this->saltarValor($p);
            }
        }
        if ($ch === '[') {
            $p++;
            while (true) {
                $p = $this->ws($p);
                if ($p >= $n) throw new Exception('Arreglo sin cerrar.');
                if ($b[$p] === ']') return $p + 1;
                $p = $this->saltarValor($p);
            }
        }
        if ($ch === '(') {
            $nivel = 0;
            while ($p < $n) {
                $c = $b[$p];
                if ($c === '\\') { $p += 2; continue; }
                if ($c === '(') $nivel++;
                elseif ($c === ')') { $nivel--; if ($nivel === 0) return $p + 1; }
                $p++;
            }
            throw new Exception('Cadena sin cerrar.');
        }
        if ($ch === '<') {
            $q = strpos($b, '>', $p);
            if ($q === false) throw new Exception('Cadena hexadecimal sin cerrar.');
            return $q + 1;
        }
        if ($ch === '/') {
            $p++;
            while ($p < $n && !self::esDelim($b[$p])) $p++;
            return $p;
        }
        if ($ch === ')' || $ch === '>' || $ch === ']' || $ch === '{' || $ch === '}') throw new Exception('Token inesperado en el desplazamiento ' . $p . '.');
        $q = $p;
        while ($q < $n && !self::esDelim($b[$q])) $q++;
        if ($q === $p) throw new Exception('Token vac&iacute;o en el desplazamiento ' . $p . '.');
        if (ctype_digit(substr($b, $p, $q - $p)) && preg_match('/\G\s+\d+\s+R(?![^\s()<>\[\]{}\/%])/', $b, $m, 0, $q)) $q += strlen($m[0]);
        return $q;
    }

    private function dict($p, &$fin)
    {
        $b = $this->buf;
        if (substr($b, $p, 2) !== '<<') throw new Exception('Se esperaba un diccionario en el desplazamiento ' . $p . '.');
        $p += 2;
        $e = array();
        while (true) {
            $p = $this->ws($p);
            if ($p >= $this->len) throw new Exception('Diccionario sin cerrar.');
            if ($b[$p] === '>' && ($p + 1 < $this->len) && $b[$p + 1] === '>') { $fin = $p + 2; return $e; }
            if ($b[$p] !== '/') throw new Exception('Clave de diccionario inv&aacute;lida en el desplazamiento ' . $p . '.');
            $kf = $this->saltarValor($p);
            $k = substr($b, $p, $kf - $p);
            $vi = $this->ws($kf);
            $vf = $this->saltarValor($vi);
            $e[$k] = array('raw' => substr($b, $vi, $vf - $vi), 'ini' => $vi, 'fin' => $vf);
            $p = $vf;
        }
    }

    private function enteros($raw)
    {
        preg_match_all('/\d+/', (string)$raw, $m);
        return array_map('intval', $m[0]);
    }

    private function entero($raw, $prof = 0)
    {
        $raw = trim((string)$raw);
        if (ctype_digit($raw)) return (int)$raw;
        if ($prof < 2 && preg_match('/^(\d+)\s+\d+\s+R$/', $raw, $m)) {
            $v = $this->valorObjeto((int)$m[1]);
            return $v === null ? null : $this->entero($v, $prof + 1);
        }
        return null;
    }

    private function valorObjeto($num)
    {
        if (!isset($this->xref[$num])) return null;
        $e = $this->xref[$num];
        if ($e[0] === 1) {
            try { $o = $this->objetoEn($e[1], $num); } catch (Exception $x) { return null; }
            return $o['valor'];
        }
        if ($e[0] === 2) return $this->desdeObjStm($e[1], $e[2], $num);
        return null;
    }

    private function objetoEn($p, $numEsperado = null)
    {
        $b = $this->buf;
        if ($p < 0 || $p >= $this->len) throw new Exception('Desplazamiento de objeto fuera del archivo.');
        $p = $this->ws($p);
        if (!preg_match('/\G(\d+)\s+(\d+)\s+obj\b/', $b, $m, 0, $p)) throw new Exception('No hay un objeto en el desplazamiento ' . $p . '.');
        $num = (int)$m[1];
        $gen = (int)$m[2];
        if ($numEsperado !== null && $num !== $numEsperado) throw new Exception('La tabla xref apunta al objeto ' . $num . ' en lugar del ' . $numEsperado . '.');
        $vi = $this->ws($p + strlen($m[0]));
        $vf = $this->saltarValor($vi);
        $o = array('num' => $num, 'gen' => $gen, 'vi' => $vi, 'valor' => substr($b, $vi, $vf - $vi), 'dict' => null, 'datos' => null);
        if (substr($b, $vi, 2) === '<<') $o['dict'] = $this->dict($vi, $tmp);
        $q = $this->ws($vf);
        if (substr($b, $q, 6) === 'stream') {
            $q += 6;
            if ($q < $this->len && $b[$q] === "\r") $q++;
            if ($q < $this->len && $b[$q] === "\n") $q++;
            $ini = $q;
            $len = ($o['dict'] && isset($o['dict']['/Length'])) ? $this->entero($o['dict']['/Length']['raw']) : null;
            $fin = null;
            if ($len !== null && $len >= 0 && $ini + $len <= $this->len) {
                $r = $this->ws($ini + $len);
                if (substr($b, $r, 9) === 'endstream') $fin = $ini + $len;
            }
            if ($fin === null) {
                $r = strpos($b, 'endstream', $ini);
                if ($r === false) throw new Exception('Stream sin marca endstream.');
                $fin = $r;
                if ($fin > $ini && $b[$fin - 1] === "\n") $fin--;
                if ($fin > $ini && $b[$fin - 1] === "\r") $fin--;
            }
            $o['datos'] = substr($b, $ini, $fin - $ini);
        }
        return $o;
    }

    private function desdeObjStm($stm, $idx, $num)
    {
        if (!isset($this->objstmCache[$stm])) {
            if (!isset($this->xref[$stm]) || $this->xref[$stm][0] !== 1) throw new Exception('No se localiz&oacute; el object stream ' . $stm . '.');
            $o = $this->objetoEn($this->xref[$stm][1], $stm);
            if ($o['datos'] === null || !$o['dict']) throw new Exception('El objeto ' . $stm . ' no es un object stream.');
            $datos = $this->decodificar($o['dict'], $o['datos']);
            $n = isset($o['dict']['/N']) ? (int)$o['dict']['/N']['raw'] : 0;
            $first = isset($o['dict']['/First']) ? $this->entero($o['dict']['/First']['raw']) : null;
            if ($first === null) throw new Exception('Object stream ' . $stm . ' sin /First.');
            $lista = $this->enteros(substr($datos, 0, $first));
            $pares = array();
            for ($i = 0; $i + 1 < count($lista) && count($pares) < $n; $i += 2) $pares[] = array($lista[$i], $lista[$i + 1]);
            $items = array();
            foreach ($pares as $k => $par) {
                $ini = $first + $par[1];
                $fin = isset($pares[$k + 1]) ? $first + $pares[$k + 1][1] : strlen($datos);
                $items[$k] = array($par[0], trim(substr($datos, $ini, max(0, $fin - $ini))));
            }
            $this->objstmCache[$stm] = $items;
        }
        $items = $this->objstmCache[$stm];
        if (isset($items[$idx]) && $items[$idx][0] === $num) return $items[$idx][1];
        foreach ($items as $it) if ($it[0] === $num) return $it[1];
        return null;
    }

    private function decodificar($d, $datos)
    {
        $filtros = array();
        if (isset($d['/Filter']) && preg_match_all('/\/([A-Za-z0-9]+)/', $d['/Filter']['raw'], $m)) $filtros = $m[1];
        foreach ($filtros as $f) {
            if ($f !== 'FlateDecode' && $f !== 'Fl') throw new Exception('Filtro ' . $f . ' no soportado en la estructura del PDF.');
            $out = @gzuncompress($datos);
            if ($out === false) $out = @gzinflate(substr($datos, 2));
            if ($out === false) $out = @gzinflate($datos);
            if ($out === false) throw new Exception('No se pudo descomprimir un stream de la estructura del PDF.');
            $datos = $out;
        }
        $parms = isset($d['/DecodeParms']) ? $d['/DecodeParms']['raw'] : '';
        if ($parms !== '' && preg_match('/\/Predictor\s+(\d+)/', $parms, $m)) {
            $pred = (int)$m[1];
            if ($pred >= 10) {
                $cols = preg_match('/\/Columns\s+(\d+)/', $parms, $mc) ? (int)$mc[1] : 1;
                $colores = preg_match('/\/Colors\s+(\d+)/', $parms, $mo) ? (int)$mo[1] : 1;
                $bpc = preg_match('/\/BitsPerComponent\s+(\d+)/', $parms, $mb) ? (int)$mb[1] : 8;
                $datos = $this->despredecirPng($datos, $cols, $colores, $bpc);
            } elseif ($pred > 1) {
                throw new Exception('Predictor ' . $pred . ' no soportado en la estructura del PDF.');
            }
        }
        return $datos;
    }

    private function despredecirPng($d, $cols, $colores, $bpc)
    {
        $bpp = max(1, (int)(($colores * $bpc + 7) / 8));
        $fila = (int)(($cols * $colores * $bpc + 7) / 8);
        $out = '';
        $prev = str_repeat("\0", $fila);
        $n = strlen($d);
        $p = 0;
        while ($p < $n) {
            $t = ord($d[$p++]);
            $act = substr($d, $p, $fila);
            $p += $fila;
            if (strlen($act) < $fila) $act = str_pad($act, $fila, "\0");
            $res = $act;
            for ($i = 0; $i < $fila; $i++) {
                $a = $i >= $bpp ? ord($res[$i - $bpp]) : 0;
                $bb = ord($prev[$i]);
                $c = $i >= $bpp ? ord($prev[$i - $bpp]) : 0;
                $x = ord($act[$i]);
                switch ($t) {
                    case 0: $v = $x; break;
                    case 1: $v = $x + $a; break;
                    case 2: $v = $x + $bb; break;
                    case 3: $v = $x + (int)(($a + $bb) / 2); break;
                    case 4:
                        $pp = $a + $bb - $c;
                        $pa = abs($pp - $a); $pb = abs($pp - $bb); $pc = abs($pp - $c);
                        $v = $x + (($pa <= $pb && $pa <= $pc) ? $a : ($pb <= $pc ? $bb : $c));
                        break;
                    default: throw new Exception('Predictor PNG desconocido en la estructura del PDF.');
                }
                $res[$i] = chr($v & 0xFF);
            }
            $out .= $res;
            $prev = $res;
        }
        return $out;
    }

    private function esAuxiliar($d)
    {
        $t = isset($d['/Type']) ? trim($d['/Type']['raw']) : '';
        return $t === '/XRef' || $t === '/ObjStm' || isset($d['/Linearized']);
    }

    private function reescribir()
    {
        ksort($this->xref);
        $objs = array();
        foreach ($this->xref as $num => $e) {
            if ($num === 0 || $e[0] === 0) continue;
            if ($e[0] === 1) {
                try { $o = $this->objetoEn($e[1], $num); } catch (Exception $x) { continue; }
                if ($o['dict'] && $this->esAuxiliar($o['dict'])) continue;
                $cuerpo = $o['valor'];
                if ($o['datos'] !== null) {
                    $len = strlen($o['datos']);
                    if ($o['dict'] && isset($o['dict']['/Length'])) {
                        $ri = $o['dict']['/Length']['ini'] - $o['vi'];
                        $rf = $o['dict']['/Length']['fin'] - $o['vi'];
                        $cuerpo = substr($cuerpo, 0, $ri) . $len . substr($cuerpo, $rf);
                    } else {
                        $cuerpo = rtrim(substr($cuerpo, 0, -2)) . ' /Length ' . $len . ' >>';
                    }
                    $cuerpo .= "\nstream\n" . $o['datos'] . "\nendstream";
                }
                $objs[$num] = array($o['gen'], $cuerpo);
            } else {
                $v = $this->desdeObjStm($e[1], $e[2], $num);
                if ($v === null || $v === '') continue;
                $objs[$num] = array(0, $v);
            }
        }
        $rootNum = (int)$this->trailer['/Root'];
        if (!isset($objs[$rootNum])) throw new Exception('No se pudo recuperar el cat&aacute;logo del PDF.');
        $out = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offs = array();
        foreach ($objs as $num => $o) {
            $offs[$num] = strlen($out);
            $out .= $num . ' ' . $o[0] . " obj\n" . $o[1] . "\nendobj\n";
        }
        $max = max(array_keys($objs));
        $xrefOff = strlen($out);
        $out .= "xref\n0 " . ($max + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) {
            $out .= isset($offs[$i]) ? sprintf("%010d %05d n \n", $offs[$i], $objs[$i][0]) : "0000000000 65535 f \n";
        }
        $tr = '<< /Size ' . ($max + 1) . ' /Root ' . $this->trailer['/Root'];
        if (isset($this->trailer['/Info'])) $tr .= ' /Info ' . $this->trailer['/Info'];
        if (isset($this->trailer['/ID'])) $tr .= ' /ID ' . $this->trailer['/ID'];
        $tr .= ' >>';
        $out .= "trailer\n" . $tr . "\nstartxref\n" . $xrefOff . "\n%%EOF\n";
        return $out;
    }
}

function membrete_pdf_probar_motor($ruta)
{
    $raiz = dirname(__DIR__, 2);
    if (!class_exists('TCPDF')) require_once $raiz . '/html_a_pdf/tcpdf/tcpdf.php';
    if (!class_exists('FPDI')) require_once $raiz . '/html_a_pdf/fpdi/fpdi.php';
    if (!class_exists('MembretePdfSonda')) {
        class MembretePdfSonda extends fpdi_pdf_parser
        {
            function error($msg) { throw new RuntimeException((string)$msg); }
        }
    }
    set_error_handler(function () { return true; });
    try {
        $fpdi = new FPDI();
        $sonda = new MembretePdfSonda($ruta, $fpdi);
        if ($sonda->getPageCount() < 1) throw new RuntimeException('El PDF no tiene p&aacute;ginas.');
        $sonda->setPageno(1);
        $sonda->getPageBoxes(1);
        $sonda->getContent();
        $sonda->closeFile();
        return null;
    } catch (Throwable $e) {
        return $e->getMessage();
    } finally {
        restore_error_handler();
    }
}

function membrete_pdf_mensaje_incompatible($detalle)
{
    return 'El PDF no es compatible con el generador de documentos (' . trim(strip_tags((string)$detalle), ' .') . '). '
         . 'Vuelva a exportarlo como <b>PDF 1.4 (compatible con Acrobat 5)</b>, sin cifrado ni restricciones, y c&aacute;rguelo de nuevo.';
}

function membrete_pdf_preparar($ruta)
{
    $r = array('ruta' => $ruta, 'normalizado' => false, 'error' => null, 'motivo' => '');
    $diag = MembretePdfCompat::diagnosticar($ruta);
    if (!$diag['compatible'] && !$diag['normalizable']) { $r['error'] = membrete_pdf_mensaje_incompatible($diag['motivo']); return $r; }
    if ($diag['compatible']) {
        $err = membrete_pdf_probar_motor($ruta);
        if ($err === null) return $r;
        $diag['motivo'] = $err;
    }
    $tmp = tempnam(sys_get_temp_dir(), 'MEMB');
    $res = MembretePdfCompat::normalizar($ruta, $tmp);
    if ($res !== true) { @unlink($tmp); $r['error'] = membrete_pdf_mensaje_incompatible($res); return $r; }
    $err = membrete_pdf_probar_motor($tmp);
    if ($err !== null) { @unlink($tmp); $r['error'] = membrete_pdf_mensaje_incompatible($err); return $r; }
    $r['ruta'] = $tmp;
    $r['normalizado'] = true;
    $r['motivo'] = $diag['motivo'];
    return $r;
}
