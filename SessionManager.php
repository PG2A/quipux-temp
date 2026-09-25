<?php
/**
 * SessionManager - Gestor seguro de sesiones
 * 
 * Parte de la solución mejorada de seguridad para Quipux
 * @package    quipux.security
 * @author     Security Team 2025
 * @license    GNU GPL v3 or later
 */

class SessionManager {
    
    /**
     * Tiempo de vida de la sesión en segundos (30 minutos)
     */
    private const SESSION_LIFETIME = 1800;
    
    /**
     * Tiempo de inactividad máximo en segundos (15 minutos)
     */
    private const INACTIVITY_TIMEOUT = 900;
    
    /**
     * Instancia de la base de datos
     */
    private $db;
    
    /**
     * Constructor
     * 
     * @param object $db Instancia de ConnectionHandler
     */
    public function __construct($db) {
        $this->db = $db;
        $this->configurarSesion();
    }
    
    /**
     * Configurar opciones seguras de sesión
     */
    private function configurarSesion() {
        // Solo si la sesión no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            // Configurar opciones de seguridad
            ini_set('session.cookie_httponly', 1);      // Prevenir acceso desde JavaScript
            ini_set('session.use_strict_mode', 1);      // Modo estricto
            ini_set('session.cookie_secure', 1);        // Solo HTTPS
            ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
            ini_set('session.use_only_cookies', 1);     // No usar URL rewriting
            ini_set('session.cookie_lifetime', self::SESSION_LIFETIME);
            
            session_start();
        }
    }
    
    /**
     * Crear una nueva sesión segura para un usuario
     * 
     * @param array $userData Datos del usuario
     * @param string $ip Dirección IP del cliente
     * @param string $userAgent User-Agent del navegador
     * @return bool True si la sesión se creó correctamente
     */
    public function createSession($userData, $ip, $userAgent) {
        // Regenerar ID de sesión para prevenir Session Fixation
        session_regenerate_id(true);
        
        // Generar token de sesión seguro
        $sessionToken = bin2hex(random_bytes(32));
        
        // Almacenar datos de usuario en sesión
        $_SESSION['usua_codi'] = $userData['usua_codi'];
        $_SESSION['krd'] = $userData['usua_login'];
        $_SESSION['user'] = $userData['usua_login'];
        $_SESSION['depe_codi'] = $userData['depe_codi'];
        $_SESSION['tipo_usuario'] = $userData['tipo_usuario'];
        $_SESSION['usua_nomb'] = $userData['usua_nomb'];
        $_SESSION['usua_email'] = $userData['usua_email'];
        
        // Datos de seguridad
        $_SESSION['session_token'] = $sessionToken;
        $_SESSION['session_ip'] = $ip;
        $_SESSION['session_user_agent'] = hash('sha256', $userAgent);
        $_SESSION['session_created'] = time();
        $_SESSION['session_last_activity'] = time();
        $_SESSION['session_initiated'] = true;
        
        // Registrar sesión en base de datos
        $recordSet = array(
            'usua_codi' => $userData['usua_codi'],
            'usua_sesion' => $this->db->conn->qstr(session_id()),
            'session_token' => $this->db->conn->qstr($sessionToken),
            'ip_cliente' => $this->db->conn->qstr($ip),
            'user_agent' => $this->db->conn->qstr($userAgent),
            'usua_fech_sesion' => $this->db->conn->sysTimeStamp,
            'usua_intentos' => 0
        );
        
        return $this->db->conn->Replace(
            'usuarios_sesion',
            $recordSet,
            'usua_codi',
            false,
            false,
            false,
            false
        );
    }
    
    /**
     * Validar sesión activa
     * 
     * @param string $ip Dirección IP del cliente
     * @param string $userAgent User-Agent del navegador
     * @return bool True si la sesión es válida
     */
    public function validateSession($ip, $userAgent) {
        // Verificar que la sesión esté iniciada
        if (!isset($_SESSION['session_initiated'])) {
            return false;
        }
        
        // Verificar que los datos requeridos existan
        if (!isset($_SESSION['usua_codi']) || !isset($_SESSION['session_ip'])) {
            return false;
        }
        
        // Validar IP (con flexibilidad para proxies)
        if (!$this->validateIP($ip)) {
            return false;
        }
        
        // Validar User-Agent
        $currentUserAgentHash = hash('sha256', $userAgent);
        if ($_SESSION['session_user_agent'] !== $currentUserAgentHash) {
            return false;
        }
        
        // Validar tiempo de vida de la sesión
        if ((time() - $_SESSION['session_created']) > self::SESSION_LIFETIME) {
            $this->destroySession();
            return false;
        }
        
        // Validar inactividad
        if ((time() - $_SESSION['session_last_activity']) > self::INACTIVITY_TIMEOUT) {
            $this->destroySession();
            return false;
        }
        
        // Actualizar último acceso
        $_SESSION['session_last_activity'] = time();
        
        return true;
    }
    
    /**
     * Validar dirección IP con flexibilidad para proxies
     * 
     * @param string $ip IP actual del cliente
     * @return bool True si la IP es válida
     */
    private function validateIP($ip) {
        $sessionIP = $_SESSION['session_ip'];
        
        // IP exacta
        if ($ip === $sessionIP) {
            return true;
        }
        
        // Para proxies, validar al menos el primer octeto
        $sessionIPParts = explode('.', $sessionIP);
        $currentIPParts = explode('.', $ip);
        
        if (count($sessionIPParts) >= 3 && count($currentIPParts) >= 3) {
            // Validar primeros 3 octetos
            return ($sessionIPParts[0] === $currentIPParts[0] &&
                    $sessionIPParts[1] === $currentIPParts[1] &&
                    $sessionIPParts[2] === $currentIPParts[2]);
        }
        
        return false;
    }
    
    /**
     * Obtener la IP real del cliente, respetando encabezados de proxy
     *
     * Prioriza X-Forwarded-For (primera IP = cliente real),
     * luego X-Real-IP, y finalmente REMOTE_ADDR como fallback.
     * Compatible con PHP 8.3 (null coalescing operator).
     *
     * @return string Dirección IP del cliente
     */
    public static function getClientIP(): string {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;

        if ($forwarded !== null) {
            $ips = explode(',', $forwarded);
            return trim($ips[0]);
        }

        return ($_SERVER['HTTP_X_REAL_IP'] ?? null)
            ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }
    
    /**
     * Obtener datos de la sesión
     * 
     * @param string $key Clave de la sesión
     * @param mixed $default Valor por defecto
     * @return mixed Valor de la sesión
     */
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Establecer datos en la sesión
     * 
     * @param string $key Clave de la sesión
     * @param mixed $value Valor a establecer
     */
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    /**
     * Verificar si existe una clave en la sesión
     * 
     * @param string $key Clave a verificar
     * @return bool True si existe
     */
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    /**
     * Eliminar una clave de la sesión
     * 
     * @param string $key Clave a eliminar
     */
    public function remove($key) {
        unset($_SESSION[$key]);
    }
    
    /**
     * Destruir la sesión
     */
    public function destroySession() {
        // Obtener datos para registrar en base de datos
        // [REQ-3] Se incluye usua_codi = 0 (ADMINISTRADOR) en el cierre.
        // La única condición es que usua_codi esté definida en la sesión.
        $usua_codi = $_SESSION['usua_codi'] ?? null;
        
        // Registrar cierre de sesión en la base de datos
        // [REQ-1] Se escribe el marcador FIN en usua_sesion, siguiendo el
        // patrón heredado del sistema: 'FIN  <Y-m-d H:i:s>'.
        // Esto permite que las consultas 'usua_sesion NOT LIKE FIN%' usadas
        // por reportes y cron identifiquen correctamente sesiones cerradas.
        // [REQ-2] Se elimina la referencia a la columna inexistente fecha_cierre.
        if ($usua_codi !== null) {
            $finMarker = 'FIN  ' . date('Y-m-d H:i:s');
            $recordSet = array(
                'usua_codi' => $usua_codi,
                'usua_sesion' => $this->db->conn->qstr($finMarker)
            );
            
            $this->db->conn->Replace(
                'usuarios_sesion',
                $recordSet,
                'usua_codi',
                false,
                false,
                false,
                false
            );
        }
        
        // Limpiar sesión
        $_SESSION = array();
        
        // Eliminar cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        // Destruir sesión
        session_destroy();
    }
    
    /**
     * Obtener tiempo restante de sesión
     * 
     * @return int Segundos restantes
     */
    public function getRemainingTime() {
        if (!isset($_SESSION['session_created'])) {
            return 0;
        }
        
        $elapsed = time() - $_SESSION['session_created'];
        $remaining = self::SESSION_LIFETIME - $elapsed;
        
        return max(0, $remaining);
    }
    
    /**
     * Obtener información de la sesión
     * 
     * @return array Información de la sesión
     */
    public function getSessionInfo() {
        return array(
            'usua_codi' => $this->get('usua_codi', 0),
            'usuario' => $this->get('krd', ''),
            'nombre' => $this->get('usua_nomb', ''),
            'email' => $this->get('usua_email', ''),
            'departamento' => $this->get('depe_codi', 0),
            'tipo_usuario' => $this->get('tipo_usuario', 0),
            'sesion_id' => session_id(),
            'tiempo_creacion' => $this->get('session_created', 0),
            'ultimo_acceso' => $this->get('session_last_activity', 0),
            'tiempo_restante' => $this->getRemainingTime()
        );
    }
    
}
?>
