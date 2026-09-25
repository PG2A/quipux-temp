<?php
/**
 * rec_session_FINAL_CORRECTO.php - Validación de Sesión en BD
 * 
 * This file is part of Quipux – Document Management System
 * 
 * Versión FINAL que:
 * - Valida sesión en tabla usuarios_sesion
 * - Valida variables de sesión PHP
 * - Mejor logging
 * - Compatible con login_FINAL_CORRECTO.php
 * 
 * @package    quipux.security
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
// 2. VALIDACIÓN BÁSICA: VARIABLES CRÍTICAS
// ============================================

$session_valida = true;
$razon_error = '';

// Verificar krd (usuario)
if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['krd'])) {
    $session_valida = true;
} elseif (!isset($_SESSION['krd']) || empty($_SESSION['krd'])) {
    $session_valida = false;
    $razon_error = "SESSION['krd'] no establecida";
    error_log("ERROR: $razon_error");
}

// Verificar usua_codi (código de usuario)
// [REQ-4] Validación estricta con excepción documentada para UADMINISTRADOR.
// Por restricción física heredada de la BD, el superusuario del sistema
// tiene usua_codi = 0. Se permite EXCLUSIVAMENTE para krd = 'UADMINISTRADOR'.
// Para cualquier otro usuario, usua_codi debe ser > 0.
// Bajo ningún concepto se permite sesión sin usua_codi establecido.
$es_admin_sistema = (($_SESSION['krd'] ?? '') === 'UADMINISTRADOR');
if (!isset($_SESSION['usua_codi'])) {
    $session_valida = false;
    $razon_error = "SESSION['usua_codi'] no establecida";
    error_log("ERROR: $razon_error");
} elseif (!$es_admin_sistema && $_SESSION['usua_codi'] <= 0) {
    $session_valida = false;
    $razon_error = "SESSION['usua_codi'] inválida para usuario " . ($_SESSION['krd'] ?? 'UNKNOWN');
    error_log("ERROR: $razon_error");
}

// Verificar que sesión está iniciada
if (!isset($_SESSION['initiated'])) {
    $session_valida = false;
    $razon_error = "Sesión no iniciada correctamente";
    error_log("ERROR: $razon_error");
}

// ============================================
// 3. SI SESIÓN NO ES VÁLIDA, MOSTRAR ERROR
// ============================================

if (!$session_valida) {
    // Log invalid
    $log_message = date('Y-m-d H:i:s') . " - INVALID - File: " . $_SERVER['PHP_SELF'] . " - Reason: " . $razon_error . "\n";
    file_put_contents(dirname(__DIR__) . '/debug_session.log', $log_message, FILE_APPEND);

    error_log("ERROR: Sesión inválida - $razon_error");
    
    // Incluir página de error
    if (file_exists(__DIR__ . '/paginaError.php')) {
        include(__DIR__ . '/paginaError.php');
    } else {
        echo "<h1>Error de Sesión</h1>";
        echo "<p>Su sesión ha expirado o es inválida.</p>";
        echo "<p><a href='index.php'>Volver al portal</a></p>";
    }
    
    die();
}

// ============================================
// 4. VALIDAR SESIÓN EN BASE DE DATOS
// ============================================

try {
    // Incluir clases necesarias
    include_once(__DIR__ . '/include/db/ConnectionHandler.php');
    
    // Inicializar base de datos
    $db = new ConnectionHandler(__DIR__);
    $db->conn->SetFetchMode(ADODB_FETCH_ASSOC);
    $GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;

    // Obtener datos de sesión de la BD
    $usua_codi = (int)$_SESSION['usua_codi'];
    $session_id = session_id();

    // La fila de usuarios_sesion pertenece a la PERSONA autenticada, no al cargo
    // bajo el que esté actuando: mientras un subrogante opera con la identidad
    // del puesto, usua_codi es la del titular, y validar contra ella expulsaría
    // al titular (usuarios_sesion admite una sola fila por usua_codi).
    // Las sesiones anteriores al despliegue no traen usua_codi_sesion; en ese
    // caso se recurre a usua_codi, que para ellas es equivalente.
    $usua_codi_sesion = (int)($_SESSION['usua_codi_sesion'] ?? $usua_codi);

    $query = "SELECT * FROM usuarios_sesion
              WHERE usua_codi = $usua_codi_sesion
              AND usua_sesion = '$session_id'
              LIMIT 1";
    
    $rs = $db->conn->Execute($query);
    
    if (!$rs || $rs->EOF) {
        error_log("ERROR: Sesión no encontrada en BD para usuario " . $_SESSION['krd'] . 
                  " con Session ID: " . $session_id);
        
        if (file_exists(__DIR__ . '/paginaError.php')) {
            include(__DIR__ . '/paginaError.php');
        } else {
            echo "<h1>Error de Sesión</h1>";
            echo "<p>Su sesión no es válida.</p>";
            echo "<p><a href='index.php'>Volver al portal</a></p>";
        }
        
        die();
    }
    
    // ============================================
    // 5. VALIDAR TIMEOUT DE SESIÓN (30 MINUTOS)
    // ============================================
    
    if (isset($_SESSION['hora_session'])) {
        $tiempo_transcurrido = time() - $_SESSION['hora_session'];
        $timeout_segundos = 1800;  // 30 minutos
        
        if ($tiempo_transcurrido > $timeout_segundos) {
            error_log("ERROR: Sesión expirada por timeout para usuario " . $_SESSION['krd']);
            
            // Eliminar sesión de BD
            $delete_query = "DELETE FROM usuarios_sesion
                            WHERE usua_codi = $usua_codi_sesion
                            AND usua_sesion = '$session_id'";
            $db->conn->Execute($delete_query);
            
            // Destruir sesión PHP
            session_destroy();
            
            if (file_exists(__DIR__ . '/paginaError.php')) {
                include(__DIR__ . '/paginaError.php');
            } else {
                echo "<h1>Sesión Expirada</h1>";
                echo "<p>Su sesión ha expirado por inactividad.</p>";
                echo "<p><a href='index.php'>Volver al portal</a></p>";
            }
            
            die();
        }
    }
    
    // ============================================
    // 6. ACTUALIZAR HORA DE SESIÓN
    // ============================================

    $_SESSION['hora_session'] = time();

    // ============================================
    // 6b. CAMBIO DE CONTRASEÑA OBLIGATORIO
    // ============================================
    // login.php marca forzar_cambio_clave cuando un ciudadano entra con su clave
    // inicial (cédula/documento). Hasta que la cambie, solo puede usar la pantalla
    // de cambio de contraseña o cerrar sesión.
    if (!empty($_SESSION['forzar_cambio_clave'])) {
        $script_actual = basename((string)($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['PHP_SELF'] ?? ''));
        $permitidos = array('cambiar_password.php', 'cambiar_password_grabar.php', 'cerrar_session.php');
        if (!in_array($script_actual, $permitidos)) {
            // Ruta web de la raíz del sistema, calculada a partir de la ruta física
            // del script que se está ejecutando (sirve tanto en / como en /quipux).
            $rel_script = str_replace('\\', '/', substr((string)realpath($_SERVER['SCRIPT_FILENAME']), strlen((string)realpath(__DIR__))));
            $script_name = (string)($_SERVER['SCRIPT_NAME'] ?? '');
            $web_root = (substr($script_name, -strlen($rel_script)) === $rel_script)
                      ? substr($script_name, 0, strlen($script_name) - strlen($rel_script)) : '';
            header("Location: $web_root/Administracion/usuarios/cambiar_password.php?forzar=1");
            die();
        }
    }
    
    // ============================================
    // 7. ESTABLECER VARIABLES GLOBALES (COMPATIBILIDAD)
    // ============================================
    
    // Estas variables se usan en el código legacy
    $krd = $_SESSION['krd'] ?? '';
    $usua_codi = $_SESSION['usua_codi'] ?? 0;
    $dependencia = $_SESSION['depe_codi'] ?? 0;
    $depe_codi = $_SESSION['depe_codi'] ?? 0;
    $usua_nomb = $_SESSION['usua_nomb'] ?? '';
    $usua_email = $_SESSION['usua_email'] ?? '';
    $tipo_usuario = $_SESSION['tipo_usuario'] ?? 0;

    if ((int)($_SESSION['inst_codi'] ?? 0) <= 0) {
        $usua_codi_inst = (int)($_SESSION['usua_codi'] ?? 0);
        $rs_inst = $db->conn->Execute(
            "select coalesce(nullif(u.inst_codi,0), d.inst_codi, 0) as inst_codi,
                    coalesce(nullif(u.inst_nombre,''), i.inst_nombre, '') as inst_nombre
               from usuarios u
               left join dependencia d on d.depe_codi = u.depe_codi
               left join institucion i on i.inst_codi = coalesce(nullif(u.inst_codi,0), d.inst_codi)
              where u.usua_codi = $usua_codi_inst");
        if ($rs_inst && !$rs_inst->EOF) {
            $_SESSION['inst_codi'] = (int)$rs_inst->fields['INST_CODI'];
            if (trim((string)($_SESSION['inst_nombre'] ?? '')) == '')
                $_SESSION['inst_nombre'] = (string)$rs_inst->fields['INST_NOMBRE'];
        }
    }

    $inst_codi = $_SESSION['inst_codi'] ?? 0;
    $inst_nombre = $_SESSION['inst_nombre'] ?? '';
    
    // ============================================
    // 8. LOGGING
    // ============================================
    
    // Log success
    $log_message = date('Y-m-d H:i:s') . " - SUCCESS - File: " . $_SERVER['PHP_SELF'] . " - Session: " . session_id() . " - KRD: " . ($_SESSION['krd'] ?? 'null') . "\n";
    file_put_contents(dirname(__DIR__) . '/debug_session.log', $log_message, FILE_APPEND);

    error_log("INFO: Sesión validada en BD para usuario " . $_SESSION['krd'] . 
              " desde IP " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    

} catch (Exception $e) {
    // Log exception
    $log_message = date('Y-m-d H:i:s') . " - EXCEPTION - File: " . $_SERVER['PHP_SELF'] . " - Message: " . $e->getMessage() . "\n";
    file_put_contents(dirname(__DIR__) . '/debug_session.log', $log_message, FILE_APPEND);


    error_log("ERROR: Exception en validación de sesión: " . $e->getMessage());
    
    if (file_exists(__DIR__ . '/paginaError.php')) {
        include(__DIR__ . '/paginaError.php');
    } else {
        echo "<h1>Error de Sesión</h1>";
        echo "<p>Error al validar sesión.</p>";
        echo "<p><a href='index.php'>Volver al portal</a></p>";
    }
    
    die();
}

?>
