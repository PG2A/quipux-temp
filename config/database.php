<?php
/**
 * Configuración de Base de Datos para Quipux
 * 
 * @package    quipux
 * @author     2025 Casen Xu
 * @copyright  EXDUCERE ONLINE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Verificar que ADODB está disponible
$adodb_path = __DIR__ . '/../vendor/adodb/adodb-php/adodb.inc.php';
if (!file_exists($adodb_path)) {
     if (defined('LIB_PATH')) {
         $adodb_path = LIB_PATH . '/adodb/adodb.inc.php';
     } else {
         $adodb_path = __DIR__ . '/../lib/adodb/adodb.inc.php';
     }
}

if (!file_exists($adodb_path)) {
    die('ERROR: ADODB no encontrado en ' . $adodb_path);
}

// Cargar ADODB
require_once $adodb_path;

// ============================================
// CREAR CONEXIÓN A BD
// ============================================

try {
    // Crear conexión
    $db = ADONewConnection(DB_TYPE);
    
    // Conectar
    $db->Connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Configurar charset
    $db->Execute("SET NAMES utf8mb4");
    $db->Execute("SET CHARACTER SET utf8mb4");
    
    // Verificar conexión
    if (!$db->IsConnected()) {
        die('ERROR: No se pudo conectar a la base de datos');
    }
    
} catch (Exception $e) {
    die('ERROR de BD: ' . $e->getMessage());
}

// ============================================
// RETORNAR CONEXIÓN
// ============================================

return $db;

?>
