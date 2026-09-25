<?php
/**
 * Configuración de Mustache.php para Quipux
 * 
 * @package    quipux
 * @author     2025 Casen Xu
 * @copyright  EXDUCERE ONLINE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Verificar que Mustache está disponible
if (!class_exists('Mustache_Engine')) {
    die('ERROR: Mustache_Engine no encontrada. Verifica que Mustache está en lib/mustache/');
}

// Crear directorio de caché si no existe
if (!is_dir(CACHE_PATH . '/mustache')) {
    @mkdir(CACHE_PATH . '/mustache', 0755, true);
}

// ============================================
// CREAR INSTANCIA DE MUSTACHE
// ============================================

$mustache = new Mustache_Engine(array(
    // Directorios de templates
    'template_dirs' => array(
        TEMPLATES_PATH,
    ),
    
    // Loader de partials
    'partials_loader' => new Mustache_Loader_FilesystemLoader(
        TEMPLATES_PATH . '/partials',
        array(
            'extension' => '.mustache'
        )
    ),
    
    // Directorio de caché
    'cache' => CACHE_PATH . '/mustache',
    
    // Función de escape (seguridad)
    'escape' => 'htmlspecialchars',
    
    // Charset
    'charset' => 'UTF-8',
    
    // Modo estricto
    'strict' => false,
    
    // Pragmas
    'pragmas' => array(Mustache_Engine::PRAGMA_FILTERS),
));

// ============================================
// RETORNAR INSTANCIA
// ============================================

return $mustache;

?>
