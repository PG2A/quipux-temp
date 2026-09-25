<?php
/**
 * RateLimiter - Protección contra ataques de fuerza bruta
 * 
 * Parte de la solución mejorada de seguridad para Quipux
 * @package    quipux.security
 * @author     Security Team 2025
 * @license    GNU GPL v3 or later
 */

class RateLimiter {
    
    /**
     * Máximo número de intentos permitidos
     */
    private const MAX_ATTEMPTS = 5;
    
    /**
     * Tiempo de bloqueo en segundos (15 minutos)
     */
    private const LOCKOUT_TIME = 900;
    
    /**
     * Ventana de tiempo para contar intentos (5 minutos)
     */
    private const ATTEMPT_WINDOW = 300;
    
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
    }
    
    /**
     * Verificar si una IP está bloqueada
     * 
     * @param string $ip Dirección IP del cliente
     * @return bool True si está bloqueada
     */
    public function isIPBlocked($ip) {
        $query = "
            SELECT COUNT(*) as intentos, 
                   MAX(fecha_intento) as ultimo_intento
            FROM login_attempts
            WHERE ip_cliente = ?
              AND fecha_intento > DATE_SUB(NOW(), INTERVAL ? SECOND)
            LIMIT 1
        ";
        
        $rs = $this->db->conn->Execute($query, array($ip, self::ATTEMPT_WINDOW));
        
        if (!$rs || $rs->EOF) {
            return false;
        }
        
        $intentos = (int)$rs->fields['intentos'];
        
        if ($intentos >= self::MAX_ATTEMPTS) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar si un usuario está bloqueado
     * 
     * @param string $usuario Login del usuario
     * @return bool True si está bloqueado
     */
    public function isUserBlocked($usuario) {
        $query = "
            SELECT COUNT(*) as intentos
            FROM login_attempts
            WHERE usuario = ?
              AND fecha_intento > DATE_SUB(NOW(), INTERVAL ? SECOND)
            LIMIT 1
        ";
        
        $rs = $this->db->conn->Execute($query, array($usuario, self::ATTEMPT_WINDOW));
        
        if (!$rs || $rs->EOF) {
            return false;
        }
        
        $intentos = (int)$rs->fields['intentos'];
        
        if ($intentos >= self::MAX_ATTEMPTS) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Registrar un intento de login fallido
     * 
     * @param string $usuario Login del usuario
     * @param string $ip Dirección IP del cliente
     * @param string $razon Razón del fallo
     * @return bool True si se registró correctamente
     */
    public function recordFailedAttempt($usuario, $ip, $razon = 'contraseña_incorrecta') {
        $recordSet = array(
            'usuario' => $this->db->conn->qstr($usuario),
            'ip_cliente' => $this->db->conn->qstr($ip),
            'razon_fallo' => $this->db->conn->qstr($razon),
            'fecha_intento' => $this->db->conn->sysTimeStamp,
            'user_agent' => $this->db->conn->qstr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')
        );
        
        return $this->db->conn->Replace(
            'login_attempts',
            $recordSet,
            '',
            false,
            false,
            false,
            false
        );
    }
    
    /**
     * Registrar un intento de login exitoso
     * 
     * @param string $usuario Login del usuario
     * @param string $ip Dirección IP del cliente
     * @return bool True si se registró correctamente
     */
    public function recordSuccessfulAttempt($usuario, $ip) {
        // Limpiar intentos fallidos previos
        $query = "
            DELETE FROM login_attempts
            WHERE usuario = ?
              AND ip_cliente = ?
              AND fecha_intento > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ";
        
        $this->db->conn->Execute($query, array($usuario, $ip, self::ATTEMPT_WINDOW));
        
        // Registrar login exitoso
        $recordSet = array(
            'usuario' => $this->db->conn->qstr($usuario),
            'ip_cliente' => $this->db->conn->qstr($ip),
            'razon_fallo' => $this->db->conn->qstr('login_exitoso'),
            'fecha_intento' => $this->db->conn->sysTimeStamp,
            'user_agent' => $this->db->conn->qstr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown')
        );
        
        return $this->db->conn->Replace(
            'login_attempts',
            $recordSet,
            '',
            false,
            false,
            false,
            false
        );
    }
    
    /**
     * Obtener número de intentos fallidos para un usuario
     * 
     * @param string $usuario Login del usuario
     * @return int Número de intentos fallidos
     */
    public function getFailedAttempts($usuario) {
        $query = "
            SELECT COUNT(*) as intentos
            FROM login_attempts
            WHERE usuario = ?
              AND razon_fallo != 'login_exitoso'
              AND fecha_intento > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ";
        
        $rs = $this->db->conn->Execute($query, array($usuario, self::ATTEMPT_WINDOW));
        
        if (!$rs || $rs->EOF) {
            return 0;
        }
        
        return (int)$rs->fields['intentos'];
    }
    
    /**
     * Obtener tiempo restante de bloqueo
     * 
     * @param string $ip Dirección IP del cliente
     * @return int Segundos restantes de bloqueo (0 si no está bloqueado)
     */
    public function getRemainingLockoutTime($ip) {
        $query = "
            SELECT MAX(fecha_intento) as ultimo_intento
            FROM login_attempts
            WHERE ip_cliente = ?
              AND fecha_intento > DATE_SUB(NOW(), INTERVAL ? SECOND)
            LIMIT 1
        ";
        
        $rs = $this->db->conn->Execute($query, array($ip, self::LOCKOUT_TIME));
        
        if (!$rs || $rs->EOF) {
            return 0;
        }
        
        $ultimoIntento = strtotime($rs->fields['ultimo_intento']);
        $tiempoRestante = self::LOCKOUT_TIME - (time() - $ultimoIntento);
        
        return max(0, $tiempoRestante);
    }
    
    /**
     * Limpiar intentos fallidos antiguos (ejecutar periódicamente)
     * 
     * @param int $diasAntiguedad Días de antigüedad mínima
     * @return bool True si se ejecutó correctamente
     */
    public function cleanupOldAttempts($diasAntiguedad = 30) {
        $query = "
            DELETE FROM login_attempts
            WHERE fecha_intento < DATE_SUB(NOW(), INTERVAL ? DAY)
        ";
        
        return $this->db->conn->Execute($query, array($diasAntiguedad));
    }
    
    /**
     * Obtener estadísticas de intentos de login
     * 
     * @return array Estadísticas
     */
    public function getStatistics() {
        $query = "
            SELECT 
                COUNT(*) as total_intentos,
                SUM(CASE WHEN razon_fallo = 'login_exitoso' THEN 1 ELSE 0 END) as exitosos,
                SUM(CASE WHEN razon_fallo != 'login_exitoso' THEN 1 ELSE 0 END) as fallidos,
                COUNT(DISTINCT ip_cliente) as ips_unicas,
                COUNT(DISTINCT usuario) as usuarios_unicos
            FROM login_attempts
            WHERE fecha_intento > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ";
        
        $rs = $this->db->conn->Execute($query);
        
        if (!$rs || $rs->EOF) {
            return array(
                'total_intentos' => 0,
                'exitosos' => 0,
                'fallidos' => 0,
                'ips_unicas' => 0,
                'usuarios_unicos' => 0
            );
        }
        
        return array(
            'total_intentos' => (int)$rs->fields['total_intentos'],
            'exitosos' => (int)$rs->fields['exitosos'],
            'fallidos' => (int)$rs->fields['fallidos'],
            'ips_unicas' => (int)$rs->fields['ips_unicas'],
            'usuarios_unicos' => (int)$rs->fields['usuarios_unicos']
        );
    }
    
}
?>
