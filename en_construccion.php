<?php
$secciones = array(
    'gestion-documental' => array(
        'titulo' => 'Gestión Documental',
        'texto'  => 'Aquí encontrará la información sobre la gestión de documentos electrónicos de la Universidad de Cuenca: tipos de documento, flujo de aprobación y archivo.'
    ),
    'firma-electronica' => array(
        'titulo' => 'Firma Electrónica',
        'texto'  => 'Aquí encontrará la información sobre la firma electrónica en Quipux: requisitos del certificado, instalación de FirmaEC y preguntas frecuentes.'
    )
);
$clave = $_GET['s'] ?? '';
$seccion = $secciones[$clave] ?? array(
    'titulo' => 'Sección en construcción',
    'texto'  => 'Esta sección del portal está en desarrollo y estará disponible próximamente.'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($seccion['titulo']); ?> - Quipux UCUENCA</title>
    <link rel="shortcut icon" href="imagenes/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="estilos/index.css?v=5">
    <link rel="stylesheet" href="estilos/en_construccion.css?v=1">
</head>
<body>
<header class="topbar">
    <div class="topbar-in wrap">
        <a class="brand" href="index.php">Quipux UCUENCA</a>
        <a class="topbar-home" href="index.php" title="Regresar al inicio"><svg viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg><span>Inicio</span></a>
        <button class="iconbtn" title="Ayuda" onclick="window.open('inf_soporte.php?rsw=MQ==','Ayuda')">
            <svg class="icn" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </button>
    </div>
</header>

<main class="wrap">
    <section class="glass obra">
        <div class="obra-ic">
            <svg viewBox="0 0 24 24"><path d="M12 2l9 5v10l-9 5-9-5V7z"/><path d="M12 22V12"/><path d="M21 7l-9 5-9-5"/></svg>
        </div>
        <span class="obra-badge">En construcción</span>
        <h1><?php echo htmlspecialchars($seccion['titulo']); ?></h1>
        <p><?php echo htmlspecialchars($seccion['texto']); ?></p>
        <p class="obra-nota">Mientras tanto, puede consultar los manuales de usuario o comunicarse con soporte técnico.</p>
        <div class="obra-acciones">
            <a class="btn btn-obra-primary" href="index.php">
                <svg class="icn" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Regresar al inicio
            </a>
            <a class="btn btn-obra-ghost" href="inf_soporte.php?rsw=MQ==">
                <svg class="icn" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                Manuales y soporte
            </a>
        </div>
    </section>
</main>

<footer>
    <div class="foot-in wrap">
        <p>© <?php echo date('Y'); ?> Universidad de Cuenca · Todos los derechos reservados.</p>
    </div>
</footer>
</body>
</html>
