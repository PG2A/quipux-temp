<?php
/**
 * Configuración General de Quipux
 * 
 * @package    quipux
 * @author     2025 Casen Xu
 * @copyright  EXDUCERE ONLINE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// ============================================
// MODO DEBUG
// ============================================

// Cambiar a false en producción
define('DEBUG_MODE', true);

// ============================================
// CONFIGURACIÓN DE BASE DE DATOS
// ============================================

// ACTUALIZAR CON TUS VALORES
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'quipux');
define('DB_TYPE', 'mysql');

// ============================================
// CONFIGURACIÓN DE SESIÓN
// ============================================

// Timeout de sesión (30 minutos = 1800 segundos)
define('SESSION_TIMEOUT', 1800);

// Nombre de la cookie de sesión
define('SESSION_NAME', 'QUIPUX_SESSION');

// ============================================
// CONFIGURACIÓN DE SEGURIDAD
// ============================================

// Habilitar HTTPS solo (comentar en desarrollo)
// define('FORCE_HTTPS', true);

// Habilitar HSTS (HTTP Strict Transport Security)
// define('ENABLE_HSTS', true);

// ============================================
// CONFIGURACIÓN DE LOGGING
// ============================================

// Nivel de logging (0=OFF, 1=ERROR, 2=WARNING, 3=INFO, 4=DEBUG)
define('LOG_LEVEL', 3);

// Archivo de log
define('LOG_FILE', LOGS_PATH . '/quipux.log');

// ============================================
// CONFIGURACIÓN DE CACHÉ
// ============================================

// Habilitar caché
define('CACHE_ENABLED', true);

// TTL de caché en segundos (1 hora = 3600)
define('CACHE_TTL', 3600);

// ============================================
// CONFIGURACIÓN DE CORREO
// ============================================

// SMTP Host
define('SMTP_HOST', 'smtp.gmail.com');

// SMTP Port
define('SMTP_PORT', 587);

// SMTP User
define('SMTP_USER', 'tu_email@gmail.com');

// SMTP Password
define('SMTP_PASS', 'tu_contraseña');

// From Email
define('FROM_EMAIL', 'noreply@quipux.local');

// From Name
define('FROM_NAME', 'Quipux');

// ============================================
// CONFIGURACIÓN DE APLICACIÓN
// ============================================

// Nombre de la aplicación
define('APP_NAME', 'Quipux');

// Versión de la aplicación
define('APP_VERSION', '2.0.0');

// URL base de la aplicación
define('APP_URL', 'http://localhost/quipux');

// Zona horaria
define('TIMEZONE', 'America/Guayaquil');

// Idioma por defecto
define('DEFAULT_LANGUAGE', 'es');

// ============================================
// CONFIGURACIÓN DE PAGINACIÓN
// ============================================

// Items por página
define('ITEMS_PER_PAGE', 20);

// ============================================
// CONFIGURACIÓN DE ARCHIVOS
// ============================================

// Directorio de uploads
define('UPLOAD_DIR', BASE_PATH . '/uploads');

// Tamaño máximo de archivo (10 MB)
define('MAX_FILE_SIZE', 10485760);

// Extensiones permitidas
define('ALLOWED_EXTENSIONS', 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,zip,rar');

// ============================================
// CONFIGURACIÓN DE LDAP
// ============================================

// Host LDAP
define('LDAP_HOST', 'ldap.example.com');

// Puerto LDAP
define('LDAP_PORT', 389);

// Base DN
define('LDAP_BASE_DN', 'dc=example,dc=com');

// Habilitar LDAP
define('LDAP_ENABLED', false);

?>
