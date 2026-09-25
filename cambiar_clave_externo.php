<?php
// Cambio de contraseña del ciudadano en el acceso externo. Es obligatorio mientras
// siga usando la clave inicial (su cédula/documento), que se le asigna al crearlo o
// al aprobar su solicitud de alta; login.php lo marca con ext_forzar_cambio.
session_start();
error_reporting(0);

if (empty($_SESSION['acceso_externo']) || empty($_SESSION['ciu_codigo'])) {
    header('Location: login.php?tipo=externo');
    exit;
}

require_once __DIR__ . '/config/autoload.php';
include_once __DIR__ . '/config.php';
require_once __DIR__ . '/include/db/ConnectionHandler.php';

$ciu_codigo = (int)$_SESSION['ciu_codigo'];
$forzado = !empty($_SESSION['ext_forzar_cambio']);
$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clave  = (string)($_POST['clave'] ?? '');
    $clave2 = (string)($_POST['clave2'] ?? '');
    try {
        $db = new ConnectionHandler(__DIR__, 'testeo');
        $db->conn->SetFetchMode(ADODB_FETCH_ASSOC);
        $documento = (string)$db->conn->GetOne("select coalesce(ciu_documento,'') from ciudadano where ciu_codigo=$ciu_codigo");
        $claves_iniciales = array_filter(array(trim((string)$_SESSION['ciu_cedula']), trim($documento)));

        if (strlen($clave) < 8) {
            $mensaje_error = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($clave !== $clave2) {
            $mensaje_error = 'Las dos contraseñas no coinciden.';
        } elseif (in_array(trim($clave), $claves_iniciales, true)) {
            $mensaje_error = 'La nueva contraseña no puede ser su número de cédula o documento.';
        } else {
            $ok = $db->conn->Execute("update ciudadano set ciu_pasw = ?, ciu_fecha_actualiza = now() where ciu_codigo = ?",
                                     array(md5($clave), $ciu_codigo));
            if ($ok) {
                $_SESSION['ext_forzar_cambio'] = 0;
                header('Location: portal_externo.php');
                exit;
            }
            $mensaje_error = 'No se pudo guardar la contraseña. Por favor intente más tarde.';
        }
    } catch (Exception $e) {
        $mensaje_error = 'Error en el sistema. Por favor intente más tarde.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>.:: Quipux - Cambio de contraseña ::.</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="imagenes/favicon.ico">
    <link rel="stylesheet" type="text/css" href="estilos/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Cambio de contraseña</h1>
            <p><?php echo htmlspecialchars($_SESSION['ciu_nombre'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
        <div class="login-body">
            <?php if ($forzado): ?>
                <p style="margin-bottom:15px">Está ingresando con su contraseña inicial. Por seguridad, cree una nueva contraseña para continuar.</p>
            <?php endif; ?>
            <?php if ($mensaje_error): ?><div class="error-message"><?php echo htmlspecialchars($mensaje_error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <form method="post" action="cambiar_clave_externo.php">
                <div class="form-group"><label for="clave">Nueva contraseña</label><input type="password" name="clave" id="clave" minlength="8" required autofocus></div>
                <div class="form-group"><label for="clave2">Repita la nueva contraseña</label><input type="password" name="clave2" id="clave2" minlength="8" required></div>
                <button type="submit" class="submit-btn">Guardar contraseña</button>
            </form>
            <a class="back-link" href="portal_externo.php?salir=1">Cerrar sesión</a>
        </div>
    </div>
</body>
</html>
