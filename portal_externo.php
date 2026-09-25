<?php
session_start();
error_reporting(0);

if (ini_get('date.timezone') === '') {
    date_default_timezone_set('America/Guayaquil');
}

if (!empty($_GET['salir'])) {
    $_SESSION = array();
    session_destroy();
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['acceso_externo']) || empty($_SESSION['ciu_codigo'])) {
    header('Location: login.php?tipo=externo');
    exit;
}

// Sigue con la clave inicial (cédula/documento): primero debe cambiarla.
if (!empty($_SESSION['ext_forzar_cambio'])) {
    header('Location: cambiar_clave_externo.php');
    exit;
}

require_once __DIR__ . '/config/autoload.php';
include_once __DIR__ . '/config.php';
require_once __DIR__ . '/include/db/ConnectionHandler.php';

$ciu_codigo = (int)$_SESSION['ciu_codigo'];
$pagina = max(1, (int)($_GET['pag'] ?? 1));
$por_pagina = 15;
$desde = ($pagina - 1) * $por_pagina;

$documentos = array();
$total = 0;
$error_consulta = '';

try {
    $db = new ConnectionHandler(__DIR__, 'testeo');
    $db->conn->SetFetchMode(ADODB_FETCH_ASSOC);

    $sql_base = "FROM radicado r
                 JOIN usuarios_radicado ur ON r.radi_nume_temp = ur.radi_nume_radi
                 WHERE ur.usua_codi = ?";

    $total = (int)$db->conn->GetOne("SELECT count(DISTINCT r.radi_nume_radi) " . $sql_base, array($ciu_codigo));

    $sql = "SELECT DISTINCT r.radi_nume_radi::text AS numero,
                   r.radi_asunto AS asunto,
                   to_char(r.radi_fech_radi,'DD/MM/YYYY') AS fecha,
                   r.radi_fech_radi AS orden,
                   min(ur.radi_usua_tipo) AS rol
            " . $sql_base . "
            GROUP BY r.radi_nume_radi, r.radi_asunto, r.radi_fech_radi
            ORDER BY r.radi_fech_radi DESC
            LIMIT $por_pagina OFFSET $desde";
    $rs = $db->conn->Execute($sql, array($ciu_codigo));
    while ($rs && !$rs->EOF) {
        $documentos[] = array(
            'numero' => (string)($rs->fields['NUMERO'] ?? $rs->fields['numero'] ?? ''),
            'asunto' => (string)($rs->fields['ASUNTO'] ?? $rs->fields['asunto'] ?? ''),
            'fecha'  => (string)($rs->fields['FECHA'] ?? $rs->fields['fecha'] ?? ''),
            'rol'    => (int)($rs->fields['ROL'] ?? $rs->fields['rol'] ?? 0)
        );
        $rs->MoveNext();
    }
} catch (Exception $e) {
    $error_consulta = 'No se pudieron consultar sus documentos en este momento.';
}

$paginas = $total > 0 ? (int)ceil($total / $por_pagina) : 1;

function ext_rol_texto($rol)
{
    if ($rol === 1) {
        return 'Remitente';
    }
    if ($rol === 3) {
        return 'Copia';
    }

    return 'Destinatario';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mis documentos - Quipux UCUENCA</title>
    <link rel="shortcut icon" href="imagenes/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="estilos/index.css?v=5">
    <link rel="stylesheet" href="estilos/portal_externo.css?v=1">
</head>
<body>
<header class="topbar">
    <div class="topbar-in wrap">
        <a class="brand" href="index.php">Quipux UCUENCA</a>
        <a class="topbar-home" href="portal_externo.php?salir=1">
            <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <span>Cerrar sesión</span>
        </a>
    </div>
</header>

<main class="wrap pex-main">
    <section class="glass pex-card pex-perfil">
        <div class="pex-avatar"><?php echo htmlspecialchars(mb_substr(trim($_SESSION['ciu_nombre']), 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="pex-perfil-tx">
            <span>Ciudadano</span>
            <h1><?php echo htmlspecialchars($_SESSION['ciu_nombre'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?php echo htmlspecialchars($_SESSION['ciu_cedula'], ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($_SESSION['ciu_email'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </section>

    <section class="glass pex-card">
        <div class="pex-cab">
            <h2>Mis documentos</h2>
            <span class="pex-total"><?php echo (int)$total; ?> documento<?php echo $total === 1 ? '' : 's'; ?></span>
        </div>

        <?php if ($error_consulta !== ''): ?>
            <p class="pex-vacio"><?php echo htmlspecialchars($error_consulta, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php elseif (!$documentos): ?>
            <p class="pex-vacio">Todavía no hay documentos asociados a su identificación. Cuando la Universidad le remita un documento, aparecerá aquí.</p>
        <?php else: ?>
            <ul class="pex-lista">
                <?php foreach ($documentos as $doc): ?>
                    <li>
                        <div class="pex-doc-cab">
                            <span class="pex-nume"><?php echo htmlspecialchars($doc['numero'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="pex-rol pex-rol-<?php echo $doc['rol']; ?>"><?php echo ext_rol_texto($doc['rol']); ?></span>
                        </div>
                        <p class="pex-asunto"><?php echo htmlspecialchars($doc['asunto'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <span class="pex-fecha"><?php echo htmlspecialchars($doc['fecha'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($paginas > 1): ?>
                <nav class="pex-pag">
                    <?php if ($pagina > 1): ?>
                        <a class="btn pex-btn-ghost" href="portal_externo.php?pag=<?php echo $pagina - 1; ?>">Anterior</a>
                    <?php endif; ?>
                    <span>Página <?php echo $pagina; ?> de <?php echo $paginas; ?></span>
                    <?php if ($pagina < $paginas): ?>
                        <a class="btn pex-btn-ghost" href="portal_externo.php?pag=<?php echo $pagina + 1; ?>">Siguiente</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <section class="glass pex-card pex-ayuda">
        <h2>¿Necesita ayuda?</h2>
        <p>Si no encuentra un documento o tiene dudas sobre un trámite, revise los manuales de usuario o comuníquese con soporte técnico.</p>
        <a class="btn pex-btn-ghost" href="inf_soporte.php?rsw=MQ==">Manuales y soporte</a>
    </section>
</main>

<footer>
    <div class="foot-in wrap">
        <p>© <?php echo date('Y'); ?> Universidad de Cuenca · Todos los derechos reservados.</p>
    </div>
</footer>
</body>
</html>
