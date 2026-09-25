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

$dotenvPath = __DIR__ . '/.env';
if (is_readable($dotenvPath)) {
    $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) continue;
        [$key, $value] = $parts;
        $key = trim($key);
        $value = trim($value);
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Crear directorio de logs si no existe
$log_dir = '/data/sites/quipux-ucuenca.exducereonline.com/logs/quipux';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}

// Configuración de errores detallada
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', $log_dir . '/php-errors.log');

// Configuración de zona horaria
date_default_timezone_set('America/Guayaquil');

// Función de logging personalizada para Quipux
function quipux_log($message, $level = 'INFO') {
    $log_dir = '/data/sites/quipux-ucuenca.exducereonline.com/logs/quipux';
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user = $_SESSION['krd'] ?? 'anonymous';
    $script = $_SERVER['SCRIPT_NAME'] ?? 'unknown';
    
    $log_entry = "[$timestamp] [$level] [IP:$ip] [User:$user] [Script:$script] $message" . PHP_EOL;
    
    @file_put_contents($log_dir . '/application.log', $log_entry, FILE_APPEND | LOCK_EX);
}

// Handler de errores personalizado
function quipux_error_handler($errno, $errstr, $errfile, $errline) {
    $error_types = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_COMPILE_ERROR => 'COMPILE_ERROR',
        E_COMPILE_WARNING => 'COMPILE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED'
    ];
    
    $error_type = $error_types[$errno] ?? 'UNKNOWN';
    $message = "PHP $error_type: $errstr in $errfile on line $errline";
    
    quipux_log($message, 'ERROR');
    
    // No interrumpir la ejecución para errores menores
    if ($errno == E_ERROR || $errno == E_CORE_ERROR || $errno == E_COMPILE_ERROR) {
        return false;
    }
    
    return true;
}

// Registrar handler de errores
set_error_handler('quipux_error_handler');

// Handler de excepciones no capturadas
function quipux_exception_handler($exception) {
    $message = "Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    quipux_log($message, 'EXCEPTION');
}

set_exception_handler('quipux_exception_handler');

// Log de inicio de script
if (isset($_SERVER['REQUEST_URI'])) {
    quipux_log("Request started: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'], 'INFO');
}


