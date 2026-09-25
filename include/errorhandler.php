<?php
/**
 * Custom Error Handler for Quipux
 * 
 * Logs errors to a file and displays user-friendly messages in production.
 * Allows "Debug Mode" to show full errors for administrators/developers.
 */

// Configuration default (can be overridden before including this file)
if (!defined('QUIPUX_DEBUG_MODE')) {
    define('QUIPUX_DEBUG_MODE', false); 
}

// Set error reporting to all for logging purposes
error_reporting(E_ALL);


if (!function_exists('quipux_log_archivo')) {
    function quipux_log_archivo($message) {
        $ruta = dirname(__DIR__) . '/bodega/tmp/quipux_error.log';
        if (@filesize($ruta) > 2097152) @unlink($ruta);
        $url = $_SERVER['REQUEST_URI'] ?? (PHP_SAPI === 'cli' ? 'cli' : '');
        @file_put_contents($ruta, date('Y-m-d H:i:s') . " | $url | $message
", FILE_APPEND);
    }
}

// Main Error Handler Function
/*
function quipux_error_handler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return false;
    }

    $errorType = 'Unknown Error';
    switch ($errno) {
        case E_USER_ERROR: $errorType = 'Fatal Error'; break;
        case E_USER_WARNING: $errorType = 'Warning'; break;
        case E_USER_NOTICE: $errorType = 'Notice'; break;
        case E_WARNING: $errorType = 'PHP Warning'; break;
        case E_NOTICE: $errorType = 'PHP Notice'; break;
        case E_DEPRECATED: $errorType = 'Deprecated'; break;
        case E_USER_DEPRECATED: $errorType = 'User Deprecated'; break;
        default: $errorType = 'Error (' . $errno . ')'; break;
    }

    $message = "[$errorType] $errstr in $errfile on line $errline";

    // 1. Log the error
    // Log to standard PHP error log (usually apache/nginx error.log or php_errors.log)
    error_log("QUIPUX " . $message);
    if ($errno != E_NOTICE && $errno != E_DEPRECATED && $errno != E_USER_DEPRECATED) quipux_log_archivo($message);

    // 2. Display to User
    if ($errno == E_USER_ERROR || $errno == E_ERROR) {
        if (QUIPUX_DEBUG_MODE) {
            $style = "background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin: 10px; font-family: monospace; z-index: 99999; position: relative;";
            echo "<div style='$style'><strong>Quipux Debug:</strong> $message</div>";
        } else {
            echo "<div style='color: red; padding: 5px;'>Un error interno ha ocurrido. Por favor contacte al administrador. (Log ID: " . uniqid() . ")</div>";
            exit(1);
        }
    } elseif (QUIPUX_DEBUG_MODE) {
        $GLOBALS['__quipux_console'][] = "Quipux Debug: " . $message;
    }

    return true;
}


// Exception Handler
function quipux_exception_handler($exception) {
    $message = "Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . "\nStack trace: " . $exception->getTraceAsString();
    
    error_log("QUIPUX " . $message);
    quipux_log_archivo($message);

    if (QUIPUX_DEBUG_MODE) {
        echo "<div style='background-color: #ffeeba; color: #856404; padding: 10px; border: 1px solid #ffeeba; margin: 10px;'>";
        echo "<strong>Quipux Exception:</strong> <pre>" . htmlspecialchars($message) . "</pre>";
        echo "</div>";
    } else {
        echo "<div style='color: red; padding: 5px;'>Ha ocurrido un error inesperado al procesar su solicitud.</div>";
    }
}

 */

// Register Handlers
set_error_handler("quipux_error_handler");
set_exception_handler("quipux_exception_handler");

register_shutdown_function(function () {
    if (empty($GLOBALS['__quipux_console'])) return;
    if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') return;
    foreach (headers_list() as $h) {
        if (stripos($h, 'content-type:') === 0 && stripos($h, 'text/html') === false) return;
    }
    echo "\n<script>";
    foreach ($GLOBALS['__quipux_console'] as $m) {
        echo "if(window.console&&console.warn){console.warn(" . json_encode($m) . ");}";
    }
    echo "</script>\n";
});

// Fatal Error Handler (Shutdown function)
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== NULL && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
        $message = "[Fatal Shutdown] " . $error['message'] . " in " . $error['file'] . " on line " . $error['line'];
        error_log("QUIPUX " . $message);
        quipux_log_archivo($message);
        
        if (!QUIPUX_DEBUG_MODE) {
             echo "<div style='color: red; padding: 15px; text-align: center; font-family: sans-serif;'>
                    <h2>Sistema no disponible momentáneamente</h2>
                    <p>Ha ocurrido un error crítico. El equipo técnico ha sido notificado.</p>
                   </div>";
        } else {
             echo "<div style='color: red; border: 2px solid red; padding: 10px;'><strong>CRITICAL ERROR:</strong> $message</div>";
        }
    }
});
?>
