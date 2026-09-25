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
 * @package    include
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('QUIPUX_BASE')) {
    // Ajusta si tu config central ya define QUIPUX_BASE
    define('QUIPUX_BASE', dirname(__DIR__)); // asumiendo include/ está directamente bajo la raíz del proyecto
}

if (!function_exists('qx_log_boot')) {
    function qx_log_boot(array $opts = []) {
        static $booted = false;
        if ($booted) return;
        $booted = true;

        $dir   = $opts['dir']   ?? QUIPUX_BASE . '/logs';
        $file  = $opts['file']  ?? $dir . '/app.log';
        $limit = (int)($opts['rotate_mb'] ?? 10); // rota a 10MB por defecto

        // Crear carpeta y protegerla
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        // .htaccess para bloquear acceso web (si estás detrás de Apache)
        $htacc = $dir . '/.htaccess';
        if (!is_file($htacc)) {
            @file_put_contents($htacc, "Require all denied\n", LOCK_EX);
        }

        // Setear permisos suaves (no crítico si falla)
        @chmod($dir, 0775);

        // Rotación simple por tamaño
        if (is_file($file) && filesize($file) > ($limit * 1024 * 1024)) {
            $bak = $file . '.' . date('Ymd_His');
            @rename($file, $bak);
            // limpiar backups viejos (quedarse con últimos 7)
            $backups = glob($file . '.*');
            rsort($backups);
            foreach (array_slice($backups, 7) as $old) @unlink($old);
        }

        // Opcional: redirigir errores de ESTE request al log (no es php.ini global)
        if (($opts['capture_php_errors'] ?? true) === true) {
            @ini_set('log_errors', '1');
            @ini_set('display_errors', '0'); // evita ruido en pantalla
            @ini_set('error_log', $file);
        }

        // Handlers para warnings/notices/excepciones a mismo log
        set_error_handler(function($severity, $message, $filep, $line) use ($file) {
            if (!(error_reporting() & $severity)) return false;
            $payload = [
                'ts'   => date('c'),
                'type' => 'php_error',
                'sev'  => $severity,
                'msg'  => $message,
                'file' => $filep,
                'line' => $line,
            ];
            error_log(json_encode($payload, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL, 3, $file);
            return false; // que PHP siga su curso normal si aplica
        });

        set_exception_handler(function($e) use ($file) {
            $payload = [
                'ts'   => date('c'),
                'type' => 'php_exception',
                'msg'  => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace'=> $e->getTrace(),
            ];
            error_log(json_encode($payload, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL, 3, $file);
        });

        // Guardar ruta del log para qx_log
        $GLOBALS['__QX_LOG_FILE__'] = $file;
    }
}

if (!function_exists('qx_mask')) {
    function qx_mask($v) {
        if ($v === null) return null;
        $s = (string)$v;
        if (strlen($s) <= 4) return '****';
        return substr($s, 0, 2) . str_repeat('*', max(0, strlen($s)-4)) . substr($s, -2);
    }
}

if (!function_exists('qx_log')) {
    function qx_log($msg, array $ctx = []) {
        $file = $GLOBALS['__QX_LOG_FILE__'] ?? (QUIPUX_BASE . '/logs/app.log');

        // Nunca persistir contraseñas en claro
        foreach (['password','pass','userpass'] as $k) unset($ctx[$k]);
        if (isset($ctx['md5'])) $ctx['md5'] = qx_mask($ctx['md5']);

        $line = json_encode([
            'ts'  => date('c'),
            'pid' => getmypid(),
            'ip'  => $_SERVER['REMOTE_ADDR'] ?? 'cli',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'cli',
            'msg' => $msg,
            'ctx' => $ctx,
        ], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

        // Escribir con append seguro
        error_log($line . PHP_EOL, 3, $file);
    }
}

// Inicializa con defaults (puedes pasar opciones en el primer require del request)
qx_log_boot([
    // 'dir' => QUIPUX_BASE . '/logs',
    // 'file' => QUIPUX_BASE . '/logs/app.log',
    // 'rotate_mb' => 10,
    // 'capture_php_errors' => true,
]);
