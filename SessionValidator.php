<?php
/**
 * SessionValidator.php - Validador Centralizado de Sesiones
 * 
 * This file is part of Quipux – Document Management System
 * 
 * Clase que valida sesiones de forma centralizada, reemplazando
 * la lógica dispersa de session_orfeo.php
 * 
 * Uso:
 * require_once(__DIR__ . '/SessionValidator.php');
 * $validator = new SessionValidator();
 * if (!$validator->isLoggedIn()) {
 *     die("No autorizado");
 * }
 * 
 * @package    quipux.security
 * @author     Security Team 2025
 * @license    GNU GPL v3 or later
 */

class SessionValidator {
    
    private $db = null;
    private $sessionManager = null;
    private $user = null;
    private $isValid = false;
    private $errorMessage = '';
    
    // ============================================
    // CONSTRUCTOR
    // ============================================
    
    public function __construct($db = null) {
        // Inicializar sesión si no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $this->db = $db;
        
        // Intentar usar SessionManager si está disponible
        if (file_exists(__DIR__ . '/SessionManager.php')) {
            require_once(__DIR__ . '/SessionManager.php');
            try {
                $this->sessionManager = new SessionManager($db);
            } catch (Exception $e) {
                error_log("WARNING: No se pudo inicializar SessionManager: " . $e->getMessage());
            }
        }
        
        // Validar sesión
        $this->validate();
    }
    
    // ============================================
    // VALIDACIÓN PRINCIPAL
    // ============================================
    
    private function validate() {
        
        // Paso 1: Validar variables críticas
        if (!$this->validateCriticalVariables()) {
            $this->isValid = false;
            return;
        }
        
        // Paso 2: Validar timeout
        if (!$this->validateTimeout()) {
            $this->isValid = false;
            return;
        }
        
        // Paso 3: Validar sesión en BD (si DB disponible)
        if ($this->db && !$this->validateSessionInDB()) {
            $this->isValid = false;
            return;
        }
        
        // Paso 4: Validar con SessionManager (si disponible)
        if ($this->sessionManager && !$this->validateWithSessionManager()) {
            $this->isValid = false;
            return;
        }
        
        // Paso 5: Actualizar hora de sesión
        $this->updateSessionTime();
        
        // Sesión válida
        $this->isValid = true;
        $this->user = $this->getCurrentUserData();
        
        error_log("INFO: Sesión validada para usuario " . ($_SESSION['krd'] ?? 'UNKNOWN') . 
                  " desde IP " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
    
    // ============================================
    // VALIDACIÓN DE VARIABLES CRÍTICAS
    // ============================================
    
    private function validateCriticalVariables() {
        
        // Verificar krd
        if (!isset($_SESSION['krd']) || empty($_SESSION['krd'])) {
            $this->errorMessage = "SESSION['krd'] no establecida";
            error_log("ERROR: " . $this->errorMessage);
            return false;
        }
        
        // Verificar usua_codi
        // [REQ-4] Validación estricta con excepción documentada para UADMINISTRADOR.
        // Por restricción física heredada de la BD, el superusuario del sistema
        // tiene usua_codi = 0. Se permite EXCLUSIVAMENTE para krd = 'UADMINISTRADOR'.
        // Para cualquier otro usuario, usua_codi debe ser > 0.
        $esAdminSistema = (($_SESSION['krd'] ?? '') === 'UADMINISTRADOR');
        if (!isset($_SESSION['usua_codi'])) {
            $this->errorMessage = "SESSION['usua_codi'] no establecida";
            error_log("ERROR: " . $this->errorMessage);
            return false;
        } elseif (!$esAdminSistema && $_SESSION['usua_codi'] <= 0) {
            $this->errorMessage = "SESSION['usua_codi'] inválida para usuario " . ($_SESSION['krd'] ?? 'UNKNOWN');
            error_log("ERROR: " . $this->errorMessage);
            return false;
        }
        
        // Verificar que sesión está iniciada
        if (!isset($_SESSION['initiated']) && !isset($_SESSION['session_initiated'])) {
            $this->errorMessage = "Sesión no iniciada correctamente";
            error_log("ERROR: " . $this->errorMessage);
            return false;
        }
        
        return true;
    }
    
    // ============================================
    // VALIDACIÓN DE TIMEOUT
    // ============================================
    
    private function validateTimeout() {
        
        if (!isset($_SESSION['hora_session'])) {
            $this->errorMessage = "Hora de sesión no establecida";
            error_log("ERROR: " . $this->errorMessage);
            return false;
        }
        
        $tiempo_transcurrido = time() - $_SESSION['hora_session'];
        $timeout_segundos = 1800;  // 30 minutos
        
        if ($tiempo_transcurrido > $timeout_segundos) {
            $this->errorMessage = "Sesión expirada por timeout";
            error_log("ERROR: " . $this->errorMessage . " para usuario " . $_SESSION['krd']);
            session_destroy();
            return false;
        }
        
        return true;
    }
    
    // ============================================
    // VALIDACIÓN EN BASE DE DATOS
    // ============================================
    
    private function validateSessionInDB() {
        
        if (!$this->db) {
            return true;  // No validar si no hay DB
        }
        
        try {
            $usua_codi = (int)$_SESSION['usua_codi'];
            $sesion_id = $this->db->conn->qstr(session_id());
            
            $query = "SELECT * FROM usuarios_sesion 
                      WHERE usua_codi = $usua_codi 
                      AND usua_sesion = $sesion_id 
                      LIMIT 1";
            
            $rs = $this->db->conn->Execute($query);
            
            if (!$rs || $rs->EOF) {
                $this->errorMessage = "Sesión no encontrada en BD";
                error_log("ERROR: " . $this->errorMessage . " para usuario " . $_SESSION['krd']);
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("WARNING: Error validando sesión en BD: " . $e->getMessage());
            return true;  // Permitir si hay error en BD
        }
    }
    
    // ============================================
    // VALIDACIÓN CON SESSIONMANAGER
    // ============================================
    
    private function validateWithSessionManager() {
        
        if (!$this->sessionManager) {
            return true;  // No validar si no hay SessionManager
        }
        
        try {
            $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            if (!$this->sessionManager->validateSession($clientIP, $userAgent)) {
                $this->errorMessage = "SessionManager validation failed";
                error_log("WARNING: " . $this->errorMessage . " para usuario " . $_SESSION['krd']);
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("WARNING: Error en SessionManager: " . $e->getMessage());
            return true;  // Permitir si hay error en SessionManager
        }
    }
    
    // ============================================
    // ACTUALIZAR HORA DE SESIÓN
    // ============================================
    
    private function updateSessionTime() {
        $_SESSION['hora_session'] = time();
    }
    
    // ============================================
    // MÉTODOS PÚBLICOS
    // ============================================
    
    /**
     * Verificar si la sesión es válida
     */
    public function isLoggedIn() {
        return $this->isValid;
    }
    
    /**
     * Obtener datos del usuario actual
     */
    public function getCurrentUser() {
        return $this->user;
    }
    
    /**
     * Obtener mensaje de error
     */
    public function getErrorMessage() {
        return $this->errorMessage;
    }
    
    /**
     * Obtener datos del usuario de sesión
     */
    private function getCurrentUserData() {
        return array(
            'krd' => $_SESSION['krd'] ?? null,
            'usua_codi' => $_SESSION['usua_codi'] ?? null,
            'usua_nomb' => $_SESSION['usua_nomb'] ?? null,
            'usua_email' => $_SESSION['usua_email'] ?? null,
            'depe_codi' => $_SESSION['depe_codi'] ?? null,
            'tipo_usuario' => $_SESSION['tipo_usuario'] ?? null,
        );
    }
    
    /**
     * Verificar si el usuario tiene un rol específico
     */
    public function hasRole($role) {
        $tipo_usuario = $_SESSION['tipo_usuario'] ?? null;
        return $tipo_usuario == $role;
    }
    
    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public function hasPermission($permission) {
        return isset($_SESSION[$permission]) && $_SESSION[$permission] > 0;
    }
    
    /**
     * Destruir sesión
     */
    public function destroySession() {
        error_log("INFO: Sesión destruida para usuario " . ($_SESSION['krd'] ?? 'UNKNOWN'));
        session_destroy();
    }
    
    /**
     * Registrar actividad del usuario
     */
    public function logActivity($action, $description = '') {
        if (!$this->db) {
            return;
        }
        
        try {
            $recordSet = array();
            $recordSet["FECHA"] = $this->db->conn->sysTimeStamp;
            $recordSet["USUARIO"] = $this->db->conn->qstr($_SESSION['krd'] ?? 'UNKNOWN');
            $recordSet["ACCION"] = $this->db->conn->qstr($action);
            $recordSet["DESCRIPCION"] = $this->db->conn->qstr($description);
            $recordSet["IP"] = $this->db->conn->qstr($_SERVER['REMOTE_ADDR'] ?? 'unknown');
            
            $this->db->conn->Replace("LOG_ACTIVIDAD", $recordSet, "", false, false, false, false);
        } catch (Exception $e) {
            error_log("WARNING: Error registrando actividad: " . $e->getMessage());
        }
    }
}

?>
