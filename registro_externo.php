<?php
session_start();
error_reporting(0);

if (ini_get('date.timezone') === '') {
    date_default_timezone_set('America/Guayaquil');
}

require_once __DIR__ . '/config/autoload.php';
include_once __DIR__ . '/config.php';
require_once __DIR__ . '/include/db/ConnectionHandler.php';
require_once __DIR__ . '/include/externos.php';

if (empty($_SESSION['token_registro_ext'])) {
    $_SESSION['token_registro_ext'] = bin2hex(random_bytes(16));
}

$campos = array(
    'tipo_doc'       => 'C',
    'identificacion' => '',
    'nombres'        => '',
    'apellidos'      => '',
    'email'          => '',
    'telefono'       => '',
    'institucion'    => '',
    'direccion'      => ''
);
$errores = array();
$registrado = false;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    foreach ($campos as $campo => $valor) {
        $campos[$campo] = trim((string)($_POST[$campo] ?? $valor));
    }
    $clave  = (string)($_POST['clave'] ?? '');
    $clave2 = (string)($_POST['clave2'] ?? '');
    $acepta = !empty($_POST['acepta']);
    $token  = (string)($_POST['token'] ?? '');

    if (!hash_equals($_SESSION['token_registro_ext'], $token)) {
        $errores['general'] = 'La sesión del formulario expiró. Vuelva a enviar los datos.';
    }

    $campos['identificacion'] = strtoupper(preg_replace('/\s+/', '', $campos['identificacion']));
    $campos['email'] = strtolower($campos['email']);

    if ($campos['identificacion'] === '') {
        $errores['identificacion'] = 'Escriba su número de identificación.';
    } elseif ($campos['tipo_doc'] === 'C' && !ext_cedula_valida($campos['identificacion'])) {
        $errores['identificacion'] = 'La cédula no es válida. Verifique los 10 dígitos.';
    } elseif ($campos['tipo_doc'] === 'P' && !ext_pasaporte_valido($campos['identificacion'])) {
        $errores['identificacion'] = 'El pasaporte debe tener entre 6 y 20 caracteres.';
    }

    if ($campos['nombres'] === '') {
        $errores['nombres'] = 'Escriba sus nombres.';
    }
    if ($campos['apellidos'] === '') {
        $errores['apellidos'] = 'Escriba sus apellidos.';
    }
    if ($campos['email'] === '' || !filter_var($campos['email'], FILTER_VALIDATE_EMAIL)) {
        $errores['email'] = 'Escriba un correo electrónico válido.';
    }
    if ($campos['telefono'] !== '' && !preg_match('/^[0-9 +()-]{7,20}$/', $campos['telefono'])) {
        $errores['telefono'] = 'El teléfono solo admite números, espacios y los signos + ( ) -';
    }
    if (strlen($clave) < 8) {
        $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif ($clave !== $clave2) {
        $errores['clave2'] = 'Las dos contraseñas no coinciden.';
    }
    if (!$acepta) {
        $errores['acepta'] = 'Debe aceptar el tratamiento de sus datos para continuar.';
    }

    if (!$errores) {
        try {
            $db = new ConnectionHandler(__DIR__, 'testeo');
            $db->conn->SetFetchMode(ADODB_FETCH_ASSOC);

            $duplicado = ext_identificacion_registrada($db->conn, $campos['identificacion']);
            if ($duplicado !== '') {
                $errores['identificacion'] = $duplicado === 'usuario'
                    ? 'Esa identificación pertenece a un usuario de la Universidad. Ingrese con su usuario institucional.'
                    : 'Esa identificación ya está registrada en Quipux. Use la opción Ingresar.';
            }

            if (!$errores) {
                $duplicado = ext_correo_registrado($db->conn, $campos['email']);
                if ($duplicado !== '') {
                    $errores['email'] = 'Ese correo electrónico ya está registrado en Quipux. Use la opción Ingresar.';
                }
            }

            if (!$errores) {
                $sql = "INSERT INTO ciudadano
                            (ciu_cedula, ciu_documento, ciu_nombre, ciu_apellido, ciu_email, ciu_telefono,
                             ciu_empresa, ciu_direccion, ciu_pasw, ciu_estado, ciu_nuevo, ciu_referencia,
                             ciu_fecha_actualiza)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?, now())";
                $ok = $db->conn->Execute($sql, array(
                    $campos['identificacion'],
                    $campos['tipo_doc'] === 'P' ? $campos['identificacion'] : '',
                    $campos['nombres'],
                    $campos['apellidos'],
                    $campos['email'],
                    $campos['telefono'],
                    $campos['institucion'],
                    $campos['direccion'],
                    ext_hash_clave($clave),
                    'REGISTRO EN LINEA'
                ));

                if ($ok) {
                    $registrado = true;
                    $_SESSION['token_registro_ext'] = bin2hex(random_bytes(16));
                } else {
                    $errores['general'] = 'No se pudo guardar el registro. Intente nuevamente en unos minutos.';
                }
            }
        } catch (Exception $e) {
            $errores['general'] = 'No se pudo guardar el registro. Intente nuevamente en unos minutos.';
        }
    }
}

function ext_val($campos, $campo)
{
    return htmlspecialchars($campos[$campo] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de ciudadano - Quipux UCUENCA</title>
    <link rel="shortcut icon" href="imagenes/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="estilos/index.css?v=5">
    <link rel="stylesheet" href="estilos/registro_externo.css?v=3">
</head>
<body>
<header class="topbar">
    <div class="topbar-in wrap">
        <a class="brand" href="index.php">Quipux UCUENCA</a>
        <a class="topbar-home" href="index.php" title="Regresar al inicio">
            <svg viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            <span>Inicio</span>
        </a>
    </div>
</header>

<main class="wrap reg-main">
<?php if ($registrado): ?>
    <section class="glass reg-card reg-ok">
        <div class="reg-ok-ic">
            <svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
        </div>
        <h1>¡Registro completado!</h1>
        <p>Su cuenta de ciudadano ya está activa. Ingrese con su número de cédula y la contraseña que acaba de crear.</p>
        <div class="reg-ok-datos">
            <span>Identificación</span>
            <strong><?php echo ext_val($campos, 'identificacion'); ?></strong>
            <span>Correo registrado</span>
            <strong><?php echo ext_val($campos, 'email'); ?></strong>
        </div>
        <div class="reg-acciones">
            <a class="btn reg-btn-primary" href="login.php?tipo=externo">Ingresar ahora</a>
            <a class="btn reg-btn-ghost" href="index.php">Volver al inicio</a>
        </div>
    </section>
<?php else: ?>
    <section class="glass reg-card">
        <h1>Registro de ciudadano</h1>
        <p class="reg-intro">Si usted es ciudadano y no tiene cuenta en Quipux, regístrese aquí. Solo toma un minuto.</p>

        <?php if (!empty($errores)): ?>
            <div class="reg-alerta" role="alert">
                <strong>Revise los datos marcados:</strong>
                <ul>
                    <?php foreach ($errores as $mensaje): ?>
                        <li><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="registro_externo.php" autocomplete="off" novalidate>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['token_registro_ext'], ENT_QUOTES, 'UTF-8'); ?>">

            <h2 class="reg-paso"><span>1</span> Identifíquese</h2>
            <div class="reg-grid">
                <div class="reg-campo">
                    <label for="tipo_doc">Tipo de identificación</label>
                    <select id="tipo_doc" name="tipo_doc">
                        <option value="C"<?php echo $campos['tipo_doc'] === 'C' ? ' selected' : ''; ?>>Cédula ecuatoriana</option>
                        <option value="P"<?php echo $campos['tipo_doc'] === 'P' ? ' selected' : ''; ?>>Pasaporte</option>
                    </select>
                </div>
                <div class="reg-campo<?php echo isset($errores['identificacion']) ? ' con-error' : ''; ?>">
                    <label for="identificacion">Número de identificación</label>
                    <input type="text" id="identificacion" name="identificacion" maxlength="20" required value="<?php echo ext_val($campos, 'identificacion'); ?>">
                    <?php if (isset($errores['identificacion'])): ?><small class="reg-error"><?php echo htmlspecialchars($errores['identificacion'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
                </div>
            </div>

            <h2 class="reg-paso"><span>2</span> Sus datos</h2>
            <div class="reg-grid">
                <div class="reg-campo<?php echo isset($errores['nombres']) ? ' con-error' : ''; ?>">
                    <label for="nombres">Nombres</label>
                    <input type="text" id="nombres" name="nombres" maxlength="60" required value="<?php echo ext_val($campos, 'nombres'); ?>">
                    <?php if (isset($errores['nombres'])): ?><small class="reg-error"><?php echo htmlspecialchars($errores['nombres'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
                </div>
                <div class="reg-campo<?php echo isset($errores['apellidos']) ? ' con-error' : ''; ?>">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" id="apellidos" name="apellidos" maxlength="60" required value="<?php echo ext_val($campos, 'apellidos'); ?>">
                    <?php if (isset($errores['apellidos'])): ?><small class="reg-error"><?php echo htmlspecialchars($errores['apellidos'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
                </div>
                <div class="reg-campo<?php echo isset($errores['email']) ? ' con-error' : ''; ?>">
                    <label for="email">Correo electrónico</label>
                    <input type="email" id="email" name="email" maxlength="80" required value="<?php echo ext_val($campos, 'email'); ?>">
                    <small class="reg-ayuda">A este correo llegarán las notificaciones de sus documentos.</small>
                    <?php if (isset($errores['email'])): ?><small class="reg-error"><?php echo htmlspecialchars($errores['email'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
                </div>
                <div class="reg-campo<?php echo isset($errores['telefono']) ? ' con-error' : ''; ?>">
                    <label for="telefono">Teléfono <em>(opcional)</em></label>
                    <input type="text" id="telefono" name="telefono" maxlength="20" value="<?php echo ext_val($campos, 'telefono'); ?>">
                    <?php if (isset($errores['telefono'])): ?><small class="reg-error"><?php echo htmlspecialchars($errores['telefono'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
                </div>
                <div class="reg-campo">
                    <label for="institucion">Institución o empresa <em>(opcional)</em></label>
                    <input type="text" id="institucion" name="institucion" maxlength="80" value="<?php echo ext_val($campos, 'institucion'); ?>">
                </div>
                <div class="reg-campo">
                    <label for="direccion">Dirección <em>(opcional)</em></label>
                    <input type="text" id="direccion" name="direccion" maxlength="120" value="<?php echo ext_val($campos, 'direccion'); ?>">
                </div>
            </div>

            <h2 class="reg-paso"><span>3</span> Cree su contraseña</h2>
            <div class="reg-grid">
                <div class="reg-campo<?php echo isset($errores['clave']) ? ' con-error' : ''; ?>">
                    <label for="clave">Contraseña</label>
                    <input type="password" id="clave" name="clave" minlength="8" required>
                    <small class="reg-ayuda">Mínimo 8 caracteres.</small>
                    <?php if (isset($errores['clave'])): ?><small class="reg-error"><?php echo htmlspecialchars($errores['clave'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
                </div>
                <div class="reg-campo<?php echo isset($errores['clave2']) ? ' con-error' : ''; ?>">
                    <label for="clave2">Repita la contraseña</label>
                    <input type="password" id="clave2" name="clave2" minlength="8" required>
                    <?php if (isset($errores['clave2'])): ?><small class="reg-error"><?php echo htmlspecialchars($errores['clave2'], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
                </div>
            </div>

            <label class="reg-check<?php echo isset($errores['acepta']) ? ' con-error' : ''; ?>">
                <input type="checkbox" name="acepta" value="1">
                <span>Autorizo a la Universidad de Cuenca el tratamiento de mis datos personales para la gestión de mis trámites documentales.</span>
            </label>

            <div class="reg-acciones">
                <button type="submit" class="btn reg-btn-primary">Crear mi cuenta</button>
                <a class="btn reg-btn-ghost" href="login.php?tipo=externo">Ya tengo cuenta, ingresar</a>
            </div>
        </form>
    </section>
<?php endif; ?>
</main>

<footer>
    <div class="foot-in wrap">
        <p>© <?php echo date('Y'); ?> Universidad de Cuenca · Todos los derechos reservados.</p>
    </div>
</footer>
</body>
</html>
