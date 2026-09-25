<?php
/**
 * session_orfeo_mejorado.php - Validación de Sesión Mejorada con SessionManager
 * 
 * This file is part of Quipux – Document Management System
 * 
 * Versión mejorada que:
 * - Integra SessionManager
 * - Mantiene compatibilidad con código existente
 * - Mejor validación de sesión
 * - Logging completo
 * 
 * @package    quipux
 * @author     Security Team 2025
 * @license    GNU GPL v3 or later
 */

// ============================================
// 1. INICIALIZAR SESIÓN
// ============================================

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// ============================================
// 2. FUNCIONES AUXILIARES
// ============================================

function obtener_ip_cliente_seguro() {
    $ip_parts = [];
    $ip_parts[] = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'unknown';
    $ip_parts[] = $_SERVER['HTTP_CLIENT_IP'] ?? 'unknown';
    $ip_parts[] = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return implode(' - ', $ip_parts);
}

function trim_seguro($valor) {
    return ($valor !== null && $valor !== false) ? trim($valor) : '';
}

function session_get($key, $default = null) {
    return $_SESSION[$key] ?? $default;
}

function server_get($key, $default = '') {
    return $_SERVER[$key] ?? $default;
}

function post_get($key, $default = '') {
    return $_POST[$key] ?? $default;
}

function get_get($key, $default = '') {
    return $_GET[$key] ?? $default;
}

function fields_get($rs, $field, $default = '') {
    if (!$rs || $rs->EOF) return $default;
    return $rs->fields[$field] ?? $default;
}

// ============================================
// 3. INICIALIZAR BASE DE DATOS
// ============================================

include_once(__DIR__ . '/include/db/ConnectionHandler.php');
include_once(__DIR__ . '/config.php');
include_once(__DIR__ . '/config_replicacion.php');

// error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

$db = new ConnectionHandler(__DIR__);
$db->conn->SetFetchMode(ADODB_FETCH_ASSOC);

if (!$db->conn->_connectionID) {
    die("<script>top.window.location='" . __DIR__ . "/paginasinConexion.php'</script>");
}

// ============================================
// 4. INCLUIR SESSIONMANAGER
// ============================================

require_once(__DIR__ . '/SessionManager.php');
$sessionManager = new SessionManager($db);

// ============================================
// 5. VARIABLES DE CONFIGURACIÓN
// ============================================

$recOrfeo = $recOrfeo ?? '';
$acceso = get_get('acceso', '');
$usua_codi = session_get("usua_codi", 0);

// ============================================
// 6. VALIDAR BLOQUEO DE USUARIO
// ============================================

if (session_get("session_dos_bloquear_usuario")) {
    die("<center><font color='red'>
        <br><br>Se están recibiendo muchas peticiones desde su cuenta de usuario.
        <br><br>Esto puede ser producido por un error en el navegador.
        <br><br>Por favor cierre su navegador y vuelva a ingresar al sistema.</font>
    </center>");
}

header("Cache-Control: Private");

// ============================================
// 7. PROTECCIÓN CONTRA DoS
// ============================================

$request_uri = server_get("REQUEST_URI");
if (session_get("session_dos_pagina") == $request_uri) {
    if ((time() - session_get("session_dos_hora", 0)) > 2) {
        $_SESSION["session_dos_num_accesos"] = 1;
        $_SESSION["session_dos_hora"] = time();
    } else {
        $usua_codi = session_get("usua_codi", 0);
        $session_dos_pagina = session_get("session_dos_pagina", "");
        
        if ($usua_codi != 0 && substr($session_dos_pagina, 0, 38) != "/Administracion/usuarios_dependencias/") {
            $_SESSION["session_dos_num_accesos"] = session_get("session_dos_num_accesos", 0) + 1;
        }
    }
    
    if (session_get("session_dos_num_accesos", 0) >= 10) {
        $_SESSION["session_dos_bloquear_usuario"] = true;
        $log_dos = [];
        $log_dos["fecha"] = $db->conn->sysTimeStamp;
        $log_dos["usua_codi"] = session_get("usua_codi", 0);
        $log_dos["pagina"] = $db->conn->qstr($request_uri);
        $log_dos["navegador"] = $db->conn->qstr(server_get("HTTP_USER_AGENT"));
        $log_dos["ip"] = $db->conn->qstr(obtener_ip_cliente_seguro());
        $log_dos["num_accesos"] = session_get("session_dos_num_accesos", 0);
        $db->conn->Replace("log_bloqueos_dos", $log_dos, "", false, false, false, false);
        die("<center><font color='red'>
            <br><br>Se están recibiendo muchas peticiones desde su cuenta de usuario.
            <br><br>Esto puede ser producido por un error en el navegador.
            <br><br>Por favor cierre su navegador y vuelva a ingresar al sistema.</font>
        </center>");
    }
} else {
    $_SESSION["session_dos_pagina"] = $request_uri;
}

// ============================================
// 8. PROCESAR LOGIN
// ============================================

$krd = '';
$drd = '';

if ($acceso == "login") {
    // Obtener credenciales
    $krd = "U" . post_get('krd', '');
    $drd = post_get('drd', '');
    
    // Limpiar
    $krd = strtoupper($krd);
    
    // Buscar usuario en BD
    $query = "SELECT * FROM usuarios 
              WHERE UPPER(usua_login) = UPPER('$krd') 
              AND usua_esta = 1 
              LIMIT 1";
    
    $rs = $db->conn->Execute($query);
    
    if ($rs && !$rs->EOF) {
        $usua_pasw = fields_get($rs, "USUA_PASW", '');
        
        // Validar contraseña
        if (substr($drd, 1, 26) == $usua_pasw) {
            
            // ============================================
            // 8.1 AUTENTICACIÓN EXITOSA
            // ============================================
            
            // Obtener datos del usuario
            $userData = array(
                'usua_codi' => fields_get($rs, 'USUA_CODI', 0),
                'usua_login' => fields_get($rs, 'USUA_LOGIN', ''),
                'depe_codi' => fields_get($rs, 'DEPE_CODI', 0),
                'tipo_usuario' => fields_get($rs, 'TIPO_USUARIO', 0),
                'usua_nomb' => fields_get($rs, 'USUA_NOMBRE', ''),
                'usua_email' => fields_get($rs, 'USUA_EMAIL', ''),
            );
            
            // Crear sesión con SessionManager
            $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            try {
                $sessionManager->createSession($userData, $clientIP, $userAgent);
                
                // Establecer variables de sesión compatibles
                $_SESSION["krd"] = $krd;
                $_SESSION["usua_codi"] = $userData['usua_codi'];
                $_SESSION["depe_codi"] = $userData['depe_codi'];
                $_SESSION["usua_nomb"] = $userData['usua_nomb'];
                $_SESSION["usua_email"] = $userData['usua_email'];
                $_SESSION["tipo_usuario"] = $userData['tipo_usuario'];
                $_SESSION["initiated"] = true;
                $_SESSION["hora_session"] = time();
                
                // Cargar permisos del usuario
                $query_permisos = "SELECT p.nombre, COUNT(pc.id_permiso) as permiso
                                   FROM permiso p 
                                   LEFT OUTER JOIN permiso_usuario pc 
                                   ON p.id_permiso = pc.id_permiso 
                                   AND pc.usua_codi = " . $userData['usua_codi'] . " 
                                   GROUP BY p.nombre";
                
                $rs_permisos = $db->conn->Execute($query_permisos);
                
                if ($rs_permisos) {
                    while (!$rs_permisos->EOF) {
                        $nom_perm = fields_get($rs_permisos, "NOMBRE", "");
                        $_SESSION[$nom_perm] = fields_get($rs_permisos, "PERMISO", 0);
                        $rs_permisos->MoveNext();
                    }
                }
                
                error_log("INFO: Login exitoso para usuario " . $krd . 
                          " desde IP " . $clientIP);
                
                // Redirigir a página principal
                header('Location: index_frames.php');
                exit;
                
            } catch (Exception $e) {
                error_log("ERROR: SessionManager exception: " . $e->getMessage());
                include(__DIR__ . '/paginaError.php');
                die();
            }
            
        } else {
            // Contraseña incorrecta
            error_log("WARNING: Contraseña incorrecta para usuario " . $krd);
            echo "<script>alert('Usuario o contraseña incorrectos');</script>";
        }
    } else {
        // Usuario no encontrado
        error_log("WARNING: Usuario no encontrado: " . $krd);
        echo "<script>alert('Usuario o contraseña incorrectos');</script>";
    }
}

// ============================================
// 9. VALIDAR SESIÓN EXISTENTE
// ============================================

else {
    
    // Obtener código de usuario
    $session_usua_codi = session_get("usua_codi", "");
    if (trim_seguro($session_usua_codi) == "") {
        $_SESSION["usua_codi"] = -99;
    }
    
    $usua_codi = (int)$_SESSION["usua_codi"];
    $sesion_id = $db->conn->qstr(session_id());
    
    // Validar sesión en BD
    $query = "SELECT * FROM usuarios_sesion 
              WHERE usua_codi = $usua_codi 
              AND usua_sesion = $sesion_id 
              LIMIT 1";
    
    $rs = $db->conn->Execute($query);
    
    if (!$rs || $rs->EOF) {
        // Sesión no válida
        error_log("ERROR: Sesión inválida para usuario " . session_get("krd", "UNKNOWN"));
        include(__DIR__ . '/paginaError.php');
        die();
    }
    
    // Validar con SessionManager
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    if (!$sessionManager->validateSession($clientIP, $userAgent)) {
        error_log("ERROR: SessionManager validation failed para usuario " . session_get("krd", "UNKNOWN"));
        include(__DIR__ . '/paginaError.php');
        die();
    }
    
    // Validar timeout
    $hora_session = session_get("hora_session", 0);
    if ((time() - $hora_session) > 1800) {  // 30 minutos
        error_log("ERROR: Sesión expirada para usuario " . session_get("krd", "UNKNOWN"));
        session_destroy();
        include(__DIR__ . '/paginaError.php');
        die();
    }
    
    // Actualizar hora de sesión
    $_SESSION["hora_session"] = time();
    
    // Validación exitosa
    $ValidacionKrd = "Si";
}

// ============================================
// 10. SESIÓN VÁLIDA
// ============================================

if (!isset($_SESSION['initiated'])) {
    $_SESSION['initiated'] = true;
}

?>
