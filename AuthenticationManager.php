<?php
/**
 * AuthenticationManager - Gestor centralizado de autenticación
 * 
 * Parte de la solución mejorada de seguridad para Quipux
 * @package    quipux.security
 * @author     Security Team 2025
 * @license    GNU GPL v3 or later
 */

class AuthenticationManager {
    
    /**
     * Modo de autenticación: 'db', 'ldap', 'both'
     */
    private $authMode;
    
    /**
     * Instancia de la base de datos
     */
    private $db;
    
    /**
     * Configuración de LDAP
     */
    private $ldapConfig;
    
    /**
     * Constructor
     * 
     * @param object $db Instancia de ConnectionHandler
     * @param string $authMode Modo de autenticación
     * @param array $ldapConfig Configuración de LDAP
     */
    public function __construct($db, $authMode = 'db', $ldapConfig = array()) {
        $this->db = $db;
        $this->authMode = $authMode;
        $this->ldapConfig = $ldapConfig;
    }
    
    /**
     * Autenticar usuario
     * 
     * @param string $usuario Login del usuario
     * @param string $password Contraseña
     * @return array|false Datos del usuario si es exitoso, false en caso contrario
     */
    public function authenticate($usuario, $password) {
        // Validar inputs
        if (empty($usuario) || empty($password)) {
            return false;
        }
        
        // Limpiar usuario
        $usuario = trim($usuario);
        
        // Intentar autenticación según el modo configurado
        switch ($this->authMode) {
            case 'ldap':
                return $this->authenticateLDAP($usuario, $password);
            
            case 'both':
                // Intentar primero con LDAP, luego con BD
                $result = $this->authenticateLDAP($usuario, $password);
                if ($result) {
                    return $result;
                }
                return $this->authenticateDatabase($usuario, $password);
            
            case 'db':
            default:
                return $this->authenticateDatabase($usuario, $password);
        }
    }
    
    /**
     * Autenticar contra base de datos
     * 
     * @param string $usuario Login del usuario
     * @param string $password Contraseña
     * @return array|false Datos del usuario si es exitoso
     */
    private function authenticateDatabase($usuario, $password) {
        // Preparar login (agregar 'U' si es necesario)
        $login = strtoupper(trim($usuario));
        if (strpos($login, 'U') !== 0) {
            $login = 'U' . $login;
        }
        
        // Usar prepared statement para evitar inyección SQL
        $sql = "
            SELECT u.usua_codi, u.usua_login, u.usua_pasw, u.depe_codi, 
                   u.usua_tipo, u.usua_nomb, u.usua_email, u.usua_cedula
            FROM usuarios u
            WHERE UPPER(u.usua_login) = ?
              AND u.usua_esta = 1
            LIMIT 1
        ";
        
        $rs = $this->db->conn->Execute($sql, array($login));
        
        if (!$rs || $rs->EOF) {
            return false;
        }
        
        // Obtener hash de contraseña de la BD
        $hashDB = $rs->fields['USUA_PASW'];
        
        // Validar contraseña
        // Soportar tanto MD5 antiguo como bcrypt nuevo
        if ($this->validatePassword($password, $hashDB)) {
            return array(
                'usua_codi' => (int)$rs->fields['USUA_CODI'],
                'usua_login' => $rs->fields['USUA_LOGIN'],
                'depe_codi' => (int)$rs->fields['DEPE_CODI'],
                'tipo_usuario' => (int)$rs->fields['USUA_TIPO'],
                'usua_nomb' => $rs->fields['USUA_NOMB'],
                'usua_email' => $rs->fields['USUA_EMAIL'],
                'usua_cedula' => $rs->fields['USUA_CEDULA'],
                'auth_method' => 'database'
            );
        }
        
        return false;
    }
    
    /**
     * Autenticar contra LDAP
     * 
     * @param string $usuario Login del usuario
     * @param string $password Contraseña
     * @return array|false Datos del usuario si es exitoso
     */
    private function authenticateLDAP($usuario, $password) {
        // Validar que LDAP esté configurado
        if (empty($this->ldapConfig)) {
            return false;
        }
        
        // Validar extensión LDAP
        if (!extension_loaded('ldap')) {
            error_log('LDAP extension not loaded');
            return false;
        }
        
        try {
            // Conectar a LDAP
            $ldapConn = @ldap_connect(
                $this->ldapConfig['server'],
                $this->ldapConfig['port'] ?? 389
            );
            
            if (!$ldapConn) {
                error_log('LDAP connection failed');
                return false;
            }
            
            // Configurar opciones LDAP
            ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldapConn, LDAP_OPT_NETWORK_TIMEOUT, 5);
            
            // Bind como administrador
            $adminBind = @ldap_bind(
                $ldapConn,
                $this->ldapConfig['admin_dn'],
                $this->ldapConfig['admin_password']
            );
            
            if (!$adminBind) {
                error_log('LDAP admin bind failed');
                ldap_close($ldapConn);
                return false;
            }
            
            // Buscar usuario (sanitizar input)
            $filter = '(&(objectClass=ucPerson)(uid=' . ldap_escape($usuario, '', LDAP_ESCAPE_FILTER) . '))';
            $attributes = array('cn', 'mail', 'uid', 'uccedula');
            
            $search = @ldap_search($ldapConn, $this->ldapConfig['base_dn'], $filter, $attributes);
            
            if (!$search) {
                error_log('LDAP search failed');
                ldap_close($ldapConn);
                return false;
            }
            
            $entries = ldap_get_entries($ldapConn, $search);
            
            if ($entries['count'] === 0) {
                error_log('LDAP user not found: ' . $usuario);
                ldap_close($ldapConn);
                return false;
            }
            
            // Obtener DN del usuario
            $userDN = $entries[0]['dn'];
            
            // Intentar bind con credenciales del usuario
            $userBind = @ldap_bind($ldapConn, $userDN, $password);
            
            if (!$userBind) {
                error_log('LDAP user bind failed for: ' . $usuario);
                ldap_close($ldapConn);
                return false;
            }
            
            // Autenticación LDAP exitosa
            ldap_close($ldapConn);
            
            // Buscar o crear usuario en BD
            return $this->syncLDAPUser($usuario, $entries[0]);
            
        } catch (Exception $e) {
            error_log('LDAP authentication error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sincronizar usuario LDAP con base de datos
     * 
     * @param string $usuario Login del usuario
     * @param array $ldapEntry Entrada LDAP del usuario
     * @return array|false Datos del usuario
     */
    private function syncLDAPUser($usuario, $ldapEntry) {
        $login = strtoupper(trim($usuario));
        if (strpos($login, 'U') !== 0) {
            $login = 'U' . $login;
        }
        
        // Buscar usuario en BD
        $sql = "SELECT * FROM usuarios WHERE UPPER(usua_login) = ? LIMIT 1";
        $rs = $this->db->conn->Execute($sql, array($login));
        
        if (!$rs->EOF) {
            // Usuario existe, retornar datos
            return array(
                'usua_codi' => (int)$rs->fields['USUA_CODI'],
                'usua_login' => $rs->fields['USUA_LOGIN'],
                'depe_codi' => (int)$rs->fields['DEPE_CODI'],
                'tipo_usuario' => (int)$rs->fields['USUA_TIPO'],
                'usua_nomb' => $rs->fields['USUA_NOMB'],
                'usua_email' => $rs->fields['USUA_EMAIL'],
                'usua_cedula' => $rs->fields['USUA_CEDULA'],
                'auth_method' => 'ldap'
            );
        }
        
        // Usuario no existe, crear uno nuevo
        // (Esto depende de la política de la institución)
        return false;
    }
    
    /**
     * Validar contraseña
     * 
     * @param string $password Contraseña ingresada
     * @param string $hash Hash almacenado en BD
     * @return bool True si la contraseña es válida
     */
    private function validatePassword($password, $hash) {
        // Si el hash comienza con $2, es bcrypt
        if (strpos($hash, '$2') === 0) {
            return password_verify($password, $hash);
        }
        
        // Si no, asumir que es MD5 antiguo (26 o 32 caracteres)
        $md5_32 = md5($password);
        $md5_26 = substr($md5_32, 0, 26);
        
        return hash_equals($hash, $md5_26) || hash_equals($hash, $md5_32);
    }
    
    /**
     * Hash de contraseña usando bcrypt
     * 
     * @param string $password Contraseña a hashear
     * @return string Hash bcrypt
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, array('cost' => 12));
    }
    
    /**
     * Cambiar contraseña de usuario
     * 
     * @param int $usuaCodi Código del usuario
     * @param string $newPassword Nueva contraseña
     * @return bool True si se cambió correctamente
     */
    public function changePassword($usuaCodi, $newPassword) {
        // Validar contraseña
        if (strlen($newPassword) < 8) {
            return false;
        }
        
        // Hash de la nueva contraseña
        $passwordHash = self::hashPassword($newPassword);
        
        // Actualizar en BD
        $sql = "UPDATE usuarios SET usua_pasw = ? WHERE usua_codi = ?";
        
        return $this->db->conn->Execute($sql, array($passwordHash, $usuaCodi)) !== false;
    }
    
    /**
     * Validar si usuario requiere cambio de contraseña
     * 
     * @param int $usuaCodi Código del usuario
     * @return bool True si requiere cambio
     */
    public function requiresPasswordChange($usuaCodi) {
        $sql = "SELECT usua_nuevo FROM usuarios WHERE usua_codi = ? LIMIT 1";
        $rs = $this->db->conn->Execute($sql, array($usuaCodi));
        
        if ($rs && !$rs->EOF) {
            return (int)$rs->fields['USUA_NUEVO'] === 0;
        }
        
        return false;
    }
    
}
?>
