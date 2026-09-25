<?php
/**
 * login.php - Versión Final v8
 * Lógica 100% original y funcional, con los estilos modernos que te gustaron.
 */

session_start();
error_reporting(0);

// [PHP 8.3] Forzar timezone consistente con el servidor de BD (PostgreSQL).
// Sin esto, PHP puede usar UTC mientras el cluster PG usa 'America/Guayaquil',
// causando discrepancias en usua_fech_sesion entre login.php (date()) y
// saml/accs.php (sysTimeStamp). date_default_timezone_get() ya retorna
// un valor si php.ini lo define; solo sobreescribimos si no está configurado.
if (ini_get('date.timezone') === '') {
    date_default_timezone_set('America/Guayaquil');
}

include_once "config.php";
include_once "login_ldap.php";
require_once __DIR__ . '/SessionManager.php';

$txt_administrador = (int)($_GET["txt_administrador"] ?? 0);
if ($activar_bloqueo_sistema && $txt_administrador != 1) {
    if (is_file("./bodega/mensaje_bloqueo_sistema.html")) {
        include_once(__DIR__ . '/funciones_interfaz.php');
        $mensaje = file_get_contents("./bodega/mensaje_bloqueo_sistema.html");
        die(html_error($mensaje));
    }
}

require_once __DIR__ . '/include/externos.php';

$krd = $_POST['krd'] ?? '';
$drd = $_POST['drd'] ?? '';
$mensaje_error = '';
$tipo_acceso = (($_POST['tipo'] ?? $_GET['tipo'] ?? '') === 'externo') ? 'externo' : 'interno';

if ($tipo_acceso === 'externo' && !empty($krd) && !empty($drd)) {
    try {
        include_once(__DIR__ . '/include/db/ConnectionHandler.php');
        $db_ext = new ConnectionHandler(__DIR__, 'testeo');
        $db_ext->conn->SetFetchMode(ADODB_FETCH_ASSOC);
        $externo = ext_autenticar($db_ext->conn, $krd, $drd);
    } catch (Exception $e) {
        $externo = null;
        $mensaje_error = 'Error en el sistema. Por favor intente más tarde.';
    }

    if ($externo) {
        session_regenerate_id(true);
        $_SESSION['acceso_externo'] = 1;
        $_SESSION['ciu_codigo']     = $externo['ciu_codigo'];
        $_SESSION['ciu_cedula']     = $externo['ciu_cedula'];
        $_SESSION['ciu_nombre']     = trim($externo['ciu_nombre'] . ' ' . $externo['ciu_apellido']);
        $_SESSION['ciu_email']      = $externo['ciu_email'];
        $_SESSION['tipo_usuario']   = 'c';
        header('Location: portal_externo.php');
        exit;
    }

    if ($mensaje_error === '') {
        $mensaje_error = 'Identificación o contraseña incorrectas';
    }
} elseif (!empty($krd) && !empty($drd)) {
    $autenticado = 0;
    if (autenticar($krd, $drd)) {
        $autenticado = 1;
    }

    if ($autenticado) {
        try {
            include_once(__DIR__ . '/include/db/ConnectionHandler.php');
            $db = new ConnectionHandler(__DIR__, 'testeo');
            $db->conn->SetFetchMode(ADODB_FETCH_ASSOC);

            $krd_upper = strtoupper("U" . $krd);
            $query = "SELECT u.*, d.depe_nomb FROM usuarios u
                      LEFT JOIN dependencia d ON d.depe_codi = u.depe_codi
                      WHERE UPPER(u.usua_login) = UPPER('$krd_upper') AND u.usua_esta = 1 LIMIT 1";
            $rs = $db->conn->Execute($query);

            if ($rs && !$rs->EOF) {
                session_regenerate_id(true);

                $usua_codi = (int)($rs->fields['USUA_CODI'] ?? 0);
                $depe_codi = (int)($rs->fields['DEPE_CODI'] ?? 0);
                $usua_nomb = trim(($rs->fields['USUA_NOMB'] ?? '') . ' ' . ($rs->fields['USUA_APELLIDO'] ?? ''));
                $usua_email = $rs->fields['USUA_EMAIL'] ?? '';
                $tipo_usuario = (int)($rs->fields['USUA_TIPO'] ?? 0);
                $inst_codi = (int)($rs->fields['INST_CODI'] ?? 0);
                if ($inst_codi <= 0 && $depe_codi > 0)
                    $inst_codi = (int)$db->conn->GetOne("select coalesce(inst_codi,0) from dependencia where depe_codi=$depe_codi");
                $inst_nombre = trim($rs->fields['INST_NOMBRE'] ?? '');
                if ($inst_nombre == '' && $inst_codi > 0)
                    $inst_nombre = (string)$db->conn->GetOne("select inst_nombre from institucion where inst_codi=$inst_codi");

                $_SESSION["krd"] = $krd_upper;
                $_SESSION["user"] = $rs->fields['USUA_LOGIN'] ?? $krd_upper;
                $_SESSION["usua_codi"] = $usua_codi;
                $_SESSION["depe_codi"] = $depe_codi;
                $_SESSION["dependencia"] = $depe_codi;
                $_SESSION["depe_nomb"] = $rs->fields['DEPE_NOMB'] ?? '';
                $_SESSION["usua_nomb"] = $usua_nomb;
                $_SESSION["usua_doc"] = trim($rs->fields['USUA_CEDULA'] ?? '');
                $_SESSION["cargo_tipo"] = (int)($rs->fields['CARGO_TIPO'] ?? 0);
                $_SESSION["usua_email"] = $usua_email;
                $_SESSION["tipo_usuario"] = $tipo_usuario;
                $_SESSION["inst_codi"] = $inst_codi;
                $_SESSION["inst_nombre"] = $inst_nombre;
                $_SESSION["nivelus"] = "1";
                $_SESSION["access"] = 1;
                $_SESSION["initiated"] = true;
                $_SESSION["hora_session"] = time();

                $rsComp = $db->conn->Execute("select usua_codi_jefe from bandeja_compartida where usua_codi = $usua_codi");
                $_SESSION["usua_codi_jefe"] = ($rsComp && !$rsComp->EOF) ? (int)$rsComp->fields['USUA_CODI_JEFE'] : 0;

                // [REQ-1] Marcar método de autenticación para que cerrar_session.php
                // pueda decidir si invoca SLO (Single Logout) contra el IdP.
                $_SESSION["auth_method"] = 'database';

                $session_id = session_id();
                $ip_cliente = SessionManager::getClientIP();

                // [REQ-5] Usar Replace (upsert atómico) con sysTimeStamp en lugar
                // de DELETE+INSERT con date() de PHP. Esto elimina:
                // (a) la race condition del DELETE+INSERT no transaccional,
                // (b) la discrepancia de timestamp entre PHP y PostgreSQL.
                $recordSet = array();
                $recordSet["usua_codi"]       = $usua_codi;
                $recordSet["usua_sesion"]     = $db->conn->qstr($session_id);
                $recordSet["usua_fech_sesion"]= $db->conn->sysTimeStamp;
                $recordSet["usua_intentos"]   = "0";
                $recordSet["ip_cliente"]      = $db->conn->qstr($ip_cliente);
                $result = $db->conn->Replace("usuarios_sesion", $recordSet, "usua_codi", false, false, true, false);

                if (!$result) {
                    $mensaje_error = "Error al crear sesión. Por favor intente nuevamente.";
                } else {
                    $query_permisos = "SELECT p.nombre, COUNT(pc.id_permiso) as permiso FROM permiso p LEFT OUTER JOIN permiso_usuario pc ON p.id_permiso = pc.id_permiso AND pc.usua_codi = $usua_codi GROUP BY p.nombre";
                    $rs_permisos = $db->conn->Execute($query_permisos);
                    if ($rs_permisos) {
                        while (!$rs_permisos->EOF) {
                            $nom_perm = $rs_permisos->fields['NOMBRE'] ?? '';
                            $_SESSION[$nom_perm] = (int)($rs_permisos->fields['PERMISO'] ?? 0);
                            $rs_permisos->MoveNext();
                        }
                    }

                    $usua_nuevo = (int)($rs->fields['USUA_NUEVO'] ?? 0);
                    if ($usua_nuevo == 0) {
                        include(__DIR__ . "/contraxx.php");
                        die();
                    }
                    
                    // header('Location: index_frames.php');
                    // exit;
                    ?>
                    <script type="text/javascript">
                        window.moveTo(0, 0);
                        window.resizeTo(screen.availWidth, screen.availHeight);
                        
                        window.location.href = 'index_frames.php';
                    </script>
                    <?php
                    exit;
                }
            } else {
                $mensaje_error = "Usuario o contraseña incorrectos";
            }
        } catch (Exception $e) {
            $mensaje_error = "Error en el sistema. Por favor intente más tarde.";
        }
    } else {
        $mensaje_error = "Usuario o contraseña incorrectos";
    }
}

include_once(__DIR__ . '/funciones_interfaz.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title>.:: Quipux - Sistema de Gestión Documental ::.</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="imagenes/favicon.ico">
    <link rel="stylesheet" type="text/css" href="estilos/login.css">
</head>
<body>
    <?php dibujar_loader_pantalla('Ingresando...'); ?>
    <div class="login-container">
        <div class="login-header">
            <h1><?php echo $tipo_acceso === 'externo' ? 'Ingreso de Ciudadanos' : 'Acceso de Usuarios'; ?></h1>
            <p>Sistema de Gestión Documental Quipux</p>
        </div>
        <div class="login-body">
            <?php if ($mensaje_error): ?><div class="error-message"><?php echo htmlspecialchars($mensaje_error); ?></div><?php endif; ?>
            <form name="form_login" method="post" action="login.php" onsubmit="bloquearPantalla('Ingresando...');">
                <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($tipo_acceso); ?>">
                <div class="form-group"><label for="krd"><?php echo $tipo_acceso === 'externo' ? 'Número de cédula o pasaporte' : 'Usuario'; ?></label><input type="text" name="krd" id="krd" required autofocus value="<?php echo htmlspecialchars($krd ?? 
''); ?>"></div>
                <div class="form-group"><label for="drd">Contraseña</label><input type="password" name="drd" id="drd" required></div>
                <button type="submit" class="submit-btn">Ingresar</button>
<?php if ($tipo_acceso === 'externo'): ?>
                <p class="login-registro">¿No tiene cuenta? <a href="registro_externo.php">Regístrese aquí</a></p>
<?php else: ?>
                <p class="login-registro">¿Es ciudadano? <a href="login.php?tipo=externo">Ingrese o regístrese aquí</a></p>
<?php endif; ?>
                <a class="back-link" href="index.php">
                    <svg viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Regresar al inicio
                </a>
            </form>
        </div>
    </div>
</body>
</html>
