<?php
/**
 * Autoloader para Quipux
 * 
 * Carga automáticamente clases y librerías externas
 * 
 * @package    quipux
 * @author     2025 Casen Xu
 * @copyright  EXDUCERE ONLINE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// ============================================
// DEFINIR RUTAS BASE
// ============================================

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__ . '/..');
}

if (!defined('APP_PATH')) {
    define('APP_PATH', BASE_PATH . '/app');
}

if (!defined('LIB_PATH')) {
    define('LIB_PATH', BASE_PATH . '/lib');
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', BASE_PATH . '/config');
}

if (!defined('TEMPLATES_PATH')) {
    define('TEMPLATES_PATH', BASE_PATH . '/templates');
}

if (!defined('CACHE_PATH')) {
    define('CACHE_PATH', BASE_PATH . '/cache');
}

//if (!defined('LOGS_PATH')) {
//    define('LOGS_PATH', BASE_PATH . '/logs');
//}

if (!defined('UPLOADS_PATH')) {
    define('UPLOADS_PATH', BASE_PATH . '/uploads');
}

// ============================================
// CARGAR MUSTACHE
// ============================================

// Buscar Mustache primero en la ruta legacy (lib/) y luego en Composer (vendor/)
$mustacheAutoloader = LIB_PATH . '/mustache/src/Mustache/Autoloader.php';
if (!file_exists($mustacheAutoloader)) {
    $mustacheAutoloader = BASE_PATH . '/vendor/mustache/mustache/src/Mustache/Autoloader.php';
}

if (file_exists($mustacheAutoloader)) {
    require_once $mustacheAutoloader;
    Mustache_Autoloader::register();
} else {
    // Mostrar error pero no detener
    error_log('ADVERTENCIA: Mustache no encontrado en lib/mustache/ ni vendor/mustache/');
}

// ============================================
// AUTOLOADER DE CLASES
// ============================================

spl_autoload_register(function ($class) {
    $namespaces = array(
        'App\\Components\\' => APP_PATH . '/Components/',
    );
    
    foreach ($namespaces as $namespace => $path) {
        if (strpos($class, $namespace) === 0) {
            $file = $path . str_replace('\\', '/', substr($class, strlen($namespace))) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }
    }
    
    return false;
}, true, true);

?>
