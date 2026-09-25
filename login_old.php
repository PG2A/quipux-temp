<?php
/**
 * login_FINAL_CORRECTO.php - Autenticación Segura con Sesión en BD
 * 
 * This file is part of Quipux – Document Management System
 * 
 * Versión FINAL que:
 * - Autentica con LDAP
 * - Inserta sesión en tabla usuarios_sesion
 * - Establece variables de sesión correctamente
 * - Redirige a index_frames.php
 * 
 * @package    quipux.security
 * @author     Security Team 2025
 * @license    GNU GPL v3 or later
 */

// ============================================
// INICIALIZAR SESIÓN Y CONFIGURACIÓN
// ============================================

session_start();
error_reporting(0);

// Incluir configuración
include_once "config.php";
include_once "login_ldap.php";

// ============================================
// VALIDAR BLOQUEO DEL SISTEMA
// ============================================

$txt_administrador = (int)($_GET["txt_administrador"] ?? 0);

if ($activar_bloqueo_sistema && $txt_administrador != 1) {
    if (is_file("./bodega/mensaje_bloqueo_sistema.html")) {
        include_once(__DIR__ . '/funciones_interfaz.php');
        $mensaje = file_get_contents("./bodega/mensaje_bloqueo_sistema.html");
        die(html_error($mensaje));
    }
}

// ============================================
// PROCESAR FORMULARIO DE LOGIN
// ============================================

$krd = $_POST['krd'] ?? '';
$drd = $_POST['drd'] ?? '';
$mensaje_error = '';

if (!empty($krd) && !empty($drd)) {
    
    // ============================================
    // INTENTAR AUTENTICAR CON LDAP
    // ============================================
    
    $autenticado = 0;
    
    // Intentar autenticar con LDAP
    if (autenticar($krd, $drd)) {
        $autenticado = 1;
    }
    
    // ============================================
    // SI AUTENTICACIÓN EXITOSA
    // ============================================
    
    if ($autenticado) {

        try {
            // Incluir clases necesarias
            include_once(__DIR__ . '/include/db/ConnectionHandler.php');

            // Inicializar base de datos
            $db = new ConnectionHandler(__DIR__, 'testeo');

            $db->conn->SetFetchMode(ADODB_FETCH_ASSOC);

            // Obtener datos del usuario
            $krd_upper = strtoupper("U" . $krd);
            $query = "SELECT * FROM usuarios 
                      WHERE UPPER(usua_login) = UPPER('$krd_upper') 
                      AND usua_esta = 1 
                      LIMIT 1";

            $rs = $db->conn->Execute($query);
            
            if ($rs && !$rs->EOF) {


                
                // ============================================
                // REGENERAR SESSION ID
                // ============================================
                
                session_regenerate_id(true);
                
                // ============================================
                // OBTENER DATOS DEL USUARIO
                // ============================================
                
                $usua_codi = (int)($rs->fields['USUA_CODI'] ?? 0);
                $depe_codi = (int)($rs->fields['DEPE_CODI'] ?? 0);
                $usua_nomb = $rs->fields['USUA_NOMBRE'] ?? '';
                $usua_email = $rs->fields['USUA_EMAIL'] ?? '';
                $tipo_usuario = (int)($rs->fields['TIPO_USUARIO'] ?? 0);
                $inst_codi = (int)($rs->fields['INST_CODI'] ?? 0);
                $inst_nombre = $rs->fields['INST_NOMBRE'] ?? '';
                
                // ============================================
                // ESTABLECER VARIABLES DE SESIÓN
                // ============================================
                
                $_SESSION["krd"] = $krd_upper;
                $_SESSION["usua_codi"] = $usua_codi;
                $_SESSION["depe_codi"] = $depe_codi;
                $_SESSION["usua_nomb"] = $usua_nomb;
                $_SESSION["usua_email"] = $usua_email;
                $_SESSION["tipo_usuario"] = $tipo_usuario;
                $_SESSION["inst_codi"] = $inst_codi;
                $_SESSION["inst_nombre"] = $inst_nombre;
                $_SESSION["initiated"] = true;
                $_SESSION["hora_session"] = time();
                
                // ============================================
                // INSERTAR SESIÓN EN TABLA usuarios_sesion
                // ============================================
                
                $session_id = session_id();
                $ip_cliente = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $fecha_sesion = date('Y-m-d H:i:s');
                
                // Primero, eliminar sesiones antiguas del usuario
                $delete_query = "DELETE FROM usuarios_sesion 
                                WHERE usua_codi = $usua_codi";
                $db->conn->Execute($delete_query);
                
                // Insertar nueva sesión
                $insert_query = "INSERT INTO usuarios_sesion 
                                (usua_codi, usua_sesion, usua_fech_sesion, usua_intentos, ip_cliente)
                                VALUES 
                                ($usua_codi, '$session_id', '$fecha_sesion', 0, '$ip_cliente')";
                
                $result = $db->conn->Execute($insert_query);
                
                if (!$result) {
                    error_log("ERROR: No se pudo insertar sesión en BD para usuario " . $krd_upper);
                    $mensaje_error = "Error al crear sesión. Por favor intente nuevamente.";
                } else {
                    
                    // ============================================
                    // CARGAR PERMISOS
                    // ============================================
                    
                    $query_permisos = "SELECT p.nombre, COUNT(pc.id_permiso) as permiso
                                       FROM permiso p 
                                       LEFT OUTER JOIN permiso_usuario pc 
                                       ON p.id_permiso = pc.id_permiso 
                                       AND pc.usua_codi = $usua_codi
                                       GROUP BY p.nombre";
                    
                    $rs_permisos = $db->conn->Execute($query_permisos);
                    
                    if ($rs_permisos) {
                        while (!$rs_permisos->EOF) {
                            $nom_perm = $rs_permisos->fields['NOMBRE'] ?? '';
                            $_SESSION[$nom_perm] = (int)($rs_permisos->fields['PERMISO'] ?? 0);
                            $rs_permisos->MoveNext();
                        }
                    }
                    
                    error_log("INFO: Login exitoso para usuario " . $krd_upper . 
                              " desde IP " . $ip_cliente . 
                              " con Session ID: " . $session_id);
                    
                    // ============================================
                    // VERIFICAR SI USUARIO ES NUEVO
                    // ============================================
                    
                    $usua_nuevo = (int)($rs->fields['USUA_NUEVO'] ?? 3);
                    
                    if ($usua_nuevo == 0) {
                        include(__DIR__ . "/contraxx.php");
                        die();
                    }
                    
                    // ============================================
                    // REDIRIGIR A PÁGINA PRINCIPAL
                    // ============================================
                    
                    header('Location: index_frames.php');
                    exit;
                }
                
            } else {
                // Usuario no encontrado
                $mensaje_error = "Usuario o contraseña incorrectos";
                error_log("WARNING: Usuario no encontrado: " . htmlspecialchars($krd));
            }
            
        } catch (Exception $e) {
            $mensaje_error = "Error en el sistema. Por favor intente más tarde.";
            error_log("ERROR: Exception en login: " . $e->getMessage());
        }
        
    } else {
        
        // ============================================
        // AUTENTICACIÓN FALLIDA
        // ============================================
        
        $mensaje_error = "Usuario o contraseña incorrectos";
        error_log("WARNING: Intento de login fallido para usuario " . htmlspecialchars($krd) . 
                  " desde IP " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
}

// ============================================
// INCLUIR FUNCIONES DE INTERFAZ
// ============================================

include_once(__DIR__ . '/funciones_interfaz.php');

?>
<!DOCTYPE html>
<html>
<head>
    <title>.:: Quipux - Sistema de Gestión Documental ::.</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="imagenes/favicon.ico">
    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #302D7E;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .form-actions button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-submit {
            background-color: #667eea;
            color: white;
        }
        
        .btn-submit:hover {
            background-color: #5568d3;
        }
        
        .btn-reset {
            background-color: #f0f0f0;
            color: #333;
        }
        
        .btn-reset:hover {
            background-color: #e0e0e0;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #999;
        }
        
        .login-footer a {
            color: #667eea;
            text-decoration: none;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 600px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 20px;
            }
        }
    </style>
    <script type="text/javascript" src="js/md5.js"></script>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Quipux</h1>
            <p>Sistema de Gestión Documental</p>
        </div>
        
        <?php if (!empty($mensaje_error)): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($mensaje_error); ?>
        </div>
        <?php endif; ?>
        
        <form name="form_login" method="post" onsubmit="return validar_login();">
            <div class="form-group">
                <label for="krd">Usuario:</label>
                <input type="text" id="krd" name="krd" maxlength="50" required autofocus 
                       value="<?php echo htmlspecialchars($_POST['krd'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="drd">Contraseña:</label>
                <input type="password" id="drd" name="drd" required>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn-submit">Ingresar</button>
                <button type="reset" class="btn-reset">Borrar</button>
            </div>
        </form>
        
        <div class="login-footer">
            <p>¿Olvidó su contraseña? <a href="javascript:login_olvido_contraseña();">Recuperar</a></p>
        </div>
    </div>
    
    <script type="text/javascript">
        var intento_login = true;
        var timerID;
        
        function trim(s) {
            return s.replace(/^\s+|\s+$/gi, '');
        }
        
        function login_olvido_contraseña() {
            var windowprops = "top=50,left=50,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=750,height=550";
            var url = '<?php echo __DIR__; ?>/Administracion/usuarios/cambiar_password_olvido.php';
            var ventana = window.open(url, 'cambiar_password_quipux', windowprops);
            ventana.focus();
        }
        
        function validar_login() {
            if (!intento_login) {
                return false;
            }
            
            var usr = trim(document.getElementById('krd').value);
            var pass = trim(document.getElementById('drd').value);
            var flag = true;
            
            if (usr.length === 0) {
                flag = false;
                document.getElementById('krd').focus();
            }
            
            if (pass.length === 0) {
                flag = false;
                document.getElementById('drd').focus();
            }
            
            if (flag) {
                intento_login = false;
                timerID = setTimeout(function() {
                    activar_intento_login();
                }, 2000);
                
                document.form_login.action = 'login.php?acceso=login&txt_administrador=<?php echo $txt_administrador; ?>';
                document.form_login.submit();
            } else {
                alert('Asegúrese de ingresar su usuario y contraseña.');
            }
            
            return flag;
        }
        
        function activar_intento_login() {
            clearTimeout(timerID);
            intento_login = true;
        }
    </script>
</body>
</html>
