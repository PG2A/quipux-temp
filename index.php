<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$ruta_raiz = ".";
require_once __DIR__ . '/config/autoload.php';
include_once __DIR__ . '/config.php';
include_once __DIR__ . '/funciones_interfaz.php';
include_once __DIR__ . '/include/db/ConnectionHandler.php';

if (file_exists(__DIR__ . "/config_title.php")) {
    include_once __DIR__ . "/config_title.php";
} else {
    $institucionSigla = "Quipux";
    $institucionNombre = "Nombre de la Institución";
    $footerText = "© " . date("Y") . " Todos los derechos reservados.";
}

$TITULO_PAGINA = $institucionSigla ?? 'Quipux - Sistema de Gestión Documental';

$db = new ConnectionHandler(__DIR__, '');
if (!$db || !isset($db->conn) || !$db->conn) {
    die("Error crítico: No se pudo establecer la conexión con la base de datos.");
}
$db->conn->SetFetchMode(ADODB_FETCH_ASSOC);
$GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;

$sql = "SELECT * FROM contenido c LEFT JOIN contenido_tipo ct ON c.cont_tipo_codi = ct.cont_tipo_codi WHERE ct.funcionalidad = 'Index'";
$rs = $db->conn->query($sql);

$desc_implanta = $titulo_implanta = $desc_procedimiento = $titulo_procedimiento = $desc_soporte = $titulo_soporte = '';
if ($rs) {
    while (!$rs->EOF) {
        $cont_tipo_codo = $rs->fields['CONT_TIPO_CODI'];
        switch ($cont_tipo_codo) {
            case "1":
                $desc_implanta = $rs->fields['TEXTO'];
                $titulo_implanta = $rs->fields['DESCRIPCION'];
                break;
            case "2":
                $desc_procedimiento = $rs->fields['TEXTO'];
                $titulo_procedimiento = $rs->fields['DESCRIPCION'];
                break;
            case "3":
                $desc_soporte = $rs->fields['TEXTO'];
                $titulo_soporte = $rs->fields['DESCRIPCION'];
                break;
        }
        $rs->MoveNext();
    }
}

$sesion_activa = '';
$sesion_nombre = '';
$sesion_destino = '';
if (!empty($_SESSION['krd']) && !empty($_SESSION['access'])) {
    $sesion_activa = 'interno';
    $sesion_nombre = trim((string)($_SESSION['usua_nomb'] ?? '')) !== ''
        ? trim((string)$_SESSION['usua_nomb'])
        : (string)$_SESSION['krd'];
    $sesion_destino = 'index_frames.php';
} elseif (!empty($_SESSION['acceso_externo']) && !empty($_SESSION['ciu_codigo'])) {
    $sesion_activa = 'ciudadano';
    $sesion_nombre = trim((string)($_SESSION['ciu_nombre'] ?? '')) !== ''
        ? trim((string)$_SESSION['ciu_nombre'])
        : (string)$_SESSION['ciu_cedula'];
    $sesion_destino = 'portal_externo.php';
}

require_once __DIR__ . '/include/portal.php';

$instituciones = array();
$filtro_inst = array_filter(array_map('trim', explode(',', (string)($_ENV['PORTAL_INSTITUCIONES'] ?? ''))), 'strlen');
$join_portal = portal_tiene_tabla_visibilidad($db->conn)
    ? " LEFT JOIN institucion_portal ip ON ip.inst_codi = i.inst_codi"
    : "";
$where_portal = portal_tiene_tabla_visibilidad($db->conn)
    ? " AND coalesce(ip.inst_mostrar, 1) = 1"
    : "";
$rs_inst = $db->conn->query("SELECT i.inst_codi, coalesce(i.inst_nombre,'') AS inst_nombre, coalesce(i.inst_sigla,'') AS inst_sigla FROM institucion i$join_portal WHERE i.inst_estado <> 0$where_portal ORDER BY i.inst_nombre");
while ($rs_inst && !$rs_inst->EOF) {
    $codi_inst = (string)(int)$rs_inst->fields['INST_CODI'];
    $nombre_inst = trim((string)$rs_inst->fields['INST_NOMBRE']);
    $visible_inst = !$filtro_inst || in_array($codi_inst, $filtro_inst, true);
    if ($nombre_inst !== '' && $visible_inst) {
        $instituciones[] = array($nombre_inst, trim((string)$rs_inst->fields['INST_SIGLA']));
    }
    $rs_inst->MoveNext();
}

$bloques = array();
if (trim(strip_tags($titulo_implanta . $desc_implanta)) !== '') {
    $bloques[] = array($titulo_implanta !== '' ? $titulo_implanta : 'Gestión Documental', $desc_implanta, 'M10 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-8l-2-2z');
}
if (trim(strip_tags($titulo_procedimiento . $desc_procedimiento)) !== '') {
    $bloques[] = array($titulo_procedimiento !== '' ? $titulo_procedimiento : 'Firma Electrónica', $desc_procedimiento, 'M12 19l7-7 3 3-7 7-3-3zM18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5zM2 2l7.586 7.586M11 13a2 2 0 1 0 0-4 2 2 0 0 0 0 4z');
}
if (trim(strip_tags($titulo_soporte . $desc_soporte)) !== '') {
    $bloques[] = array($titulo_soporte !== '' ? $titulo_soporte : 'Soporte Técnico', $desc_soporte, 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z');
}

$banners = array_values(array_filter(array(($banner1 ?? ''), ($banner2 ?? ''), ($banner3 ?? ''))));
if (empty($banners)) {
    $banners = array('imagenes/index/Banners_Quipux_1.png');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($TITULO_PAGINA); ?></title>
    <link rel="shortcut icon" href="/imagenes/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="estilos/index.css?v=7">
    <script>
        function irLoginSAML(){ window.location.href = './saml/saml_login.php'; }
        function irLoginLocal(){ window.location.href = './login.php'; }
        function irLoginCiudadano(){ window.location.href = './login.php?tipo=externo'; }
        function irRegistroCiudadano(){ window.location.href = './registro_externo.php'; }
        function irLoginInstitucion(){ window.location.href = './login.php'; }
        function filtrarInstituciones(){
            var q = (document.getElementById('buscaInstitucion').value || '').toLowerCase();
            var items = document.querySelectorAll('#listaInstituciones .login-inst');
            var visibles = 0;
            for (var i = 0; i < items.length; i++) {
                var ok = items[i].getAttribute('data-busca').indexOf(q) !== -1;
                items[i].style.display = ok ? '' : 'none';
                if (ok) { visibles++; }
            }
            document.getElementById('sinInstituciones').style.display = visibles ? 'none' : 'block';
        }
        function abrirMenuIngreso(abrir){
            var m = document.getElementById('menuIngreso');
            var b = document.getElementById('btnIngreso');
            if (!m || !b) { return; }
            if (abrir === undefined) { abrir = !m.classList.contains('on'); }
            m.classList.toggle('on', abrir);
            b.setAttribute('aria-expanded', abrir ? 'true' : 'false');
            if (abrir) { var o = m.querySelector('.login-opt'); if (o) { o.focus(); } }
        }
        document.addEventListener('click', function(e){
            var c = document.getElementById('ingreso');
            if (c && !c.contains(e.target)) { abrirMenuIngreso(false); }
        });
        document.addEventListener('keydown', function(e){
            if (e.key === 'Escape') { abrirMenuIngreso(false); }
        });
    </script>
</head>
<body>
<header class="topbar">
    <div class="topbar-in wrap">
        <a class="brand" href="index.php">Quipux UCUENCA</a>
        <button class="iconbtn" title="Ayuda" onclick="window.open('inf_soporte.php?rsw=MQ==','Ayuda')">
            <svg class="icn" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </button>
    </div>
</header>

<main>
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <span class="badge">
                <svg class="icn" viewBox="0 0 24 24" style="width:14px;height:14px"><path d="M9 12l2 2 4-4"/><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Versión 2.0 - Seguridad Reforzada
            </span>
            <h1>Bienvenido al Sistema de Gestión Documental <span>Quipux</span> de la Universidad de Cuenca</h1>
            <p>Plataforma institucional para la gestión administrativa, la firma electrónica y la trazabilidad documental.</p>
            <div class="hero-actions">
<?php if ($sesion_activa !== ''): ?>
                <div class="sesion-activa">
                    <span class="sesion-chip">
                        <svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>
                        Sesión iniciada
                    </span>
                    <p>Ya ingresó como <strong><?php echo htmlspecialchars($sesion_nombre, ENT_QUOTES, 'UTF-8'); ?></strong>. No necesita volver a autenticarse.</p>
                    <div class="sesion-botones">
                        <a class="btn btn-primary" href="<?php echo $sesion_destino; ?>">
                            <svg class="icn" viewBox="0 0 24 24" style="width:16px;height:16px"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            <?php echo $sesion_activa === 'interno' ? 'Volver al sistema' : 'Ir a mis documentos'; ?>
                        </a>
                        <a class="btn btn-ghost" href="cerrar_session.php">
                            <svg class="icn" viewBox="0 0 24 24" style="width:16px;height:16px"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Cerrar sesión
                        </a>
                    </div>
                </div>
<?php else: ?>
                <div class="login-wrap" id="ingreso">
                    <button class="btn btn-primary login-toggle" id="btnIngreso" type="button" aria-haspopup="true" aria-expanded="false" aria-controls="menuIngreso" onclick="abrirMenuIngreso();">
                        <svg class="icn" viewBox="0 0 24 24" style="width:16px;height:16px"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                        Ingresar a Quipux
                        <svg class="icn caret" viewBox="0 0 24 24" style="width:16px;height:16px"><polyline points="6 9 12 15 18 9"/></svg>
                    </button>
                    <div class="login-menu" id="menuIngreso" role="menu" aria-labelledby="btnIngreso">
                        <button class="login-opt" type="button" role="menuitem" onclick="irLoginSAML();">
                            <svg class="icn" viewBox="0 0 24 24"><path d="M4 4h16v16H4z"/><polyline points="4 6 12 13 20 6"/></svg>
                            <span class="login-opt-tx">
                                <strong>Correo Institucional <em class="login-tag">Recomendado</em></strong>
                                <small>Con su cuenta @ucuenca.edu.ec</small>
                            </span>
                        </button>

                        <div class="login-sep">Instituciones</div>
                        <div class="login-busca">
                            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" id="buscaInstitucion" placeholder="Ingrese nombre de institución" autocomplete="off" oninput="filtrarInstituciones();">
                        </div>
                        <div class="login-inst-lista" id="listaInstituciones">
<?php foreach ($instituciones as $inst): ?>
                            <button class="login-opt login-inst" type="button" role="menuitem" data-busca="<?php echo htmlspecialchars(mb_strtolower($inst[0] . ' ' . $inst[1], 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>" onclick="irLoginInstitucion();">
                                <svg class="icn" viewBox="0 0 24 24"><path d="M3 21h18"/><path d="M5 21V8l7-5 7 5v13"/><path d="M10 21v-6h4v6"/></svg>
                                <span class="login-opt-tx">
                                    <strong><?php echo htmlspecialchars($inst[0], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <?php if ($inst[1] !== ''): ?><small><?php echo htmlspecialchars($inst[1], ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
                                </span>
                            </button>
<?php endforeach; ?>
                            <p class="login-sin" id="sinInstituciones">No hay instituciones que coincidan con su búsqueda.</p>
                        </div>

                        <div class="login-sep">Ciudadanía</div>
                        <button class="login-opt" type="button" role="menuitem" onclick="irLoginCiudadano();">
                            <svg class="icn" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <span class="login-opt-tx">
                                <strong>Ingresar como ciudadano</strong>
                                <small>Con su cédula y contraseña</small>
                            </span>
                        </button>
                        <button class="login-opt" type="button" role="menuitem" onclick="irRegistroCiudadano();">
                            <svg class="icn" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                            <span class="login-opt-tx">
                                <strong>Crear cuenta de ciudadano</strong>
                                <small>¿Primera vez? Regístrese en un minuto</small>
                            </span>
                        </button>
                    </div>
                </div>
<?php endif; ?>
            </div>
        </div>
    </section>

    <section class="features">
        <?php if (!empty($bloques)): ?>
            <?php foreach ($bloques as $b): ?>
                <div class="glass feat">
                    <div class="fic">
                        <svg class="icn" viewBox="0 0 24 24" style="width:24px;height:24px"><path d="<?php echo $b[2]; ?>"/></svg>
                    </div>
                    <h3><?php echo htmlspecialchars($b[0]); ?></h3>
                    <div class="body"><?php echo $b[1]; ?></div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="glass feat">
                <div class="fic"><svg class="icn" viewBox="0 0 24 24" style="width:24px;height:24px"><path d="M10 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-8l-2-2z"/></svg></div>
                <h3>Gestión Documental</h3>
                <div class="body"><p>Quipux es la herramienta de gestión documental para administrar documentos electrónicos e información referencial de la Institución.</p></div>
            </div>
            <div class="glass feat">
                <div class="fic"><svg class="icn" viewBox="0 0 24 24" style="width:24px;height:24px"><path d="M12 19l7-7 3 3-7 7-3-3z"/><path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/></svg></div>
                <h3>Firma Electrónica</h3>
                <div class="body"><p>El Sistema Documental Quipux permite el manejo de firma electrónica para identificar al firmante, asegurar la integridad y el no repudio del documento.</p></div>
            </div>
            <div class="glass feat">
                <div class="fic"><svg class="icn" viewBox="0 0 24 24" style="width:24px;height:24px"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
                <h3>Soporte y Manuales</h3>
                <div class="body"><p>Consulte los manuales de usuario y canales de soporte técnico del Sistema de Gestión Documental Quipux.</p></div>
            </div>
        <?php endif; ?>
    </section>

    <section class="carousel glass">
        <?php foreach ($banners as $i => $bn): ?>
            <img class="slide<?php echo $i === 0 ? ' on' : ''; ?>" src="<?php echo htmlspecialchars($bn); ?>" alt="">
        <?php endforeach; ?>
        <?php if (count($banners) > 1): ?>
            <button class="cbtn prev" onclick="qxSlide(-1)" aria-label="Anterior">&#8249;</button>
            <button class="cbtn next" onclick="qxSlide(1)" aria-label="Siguiente">&#8250;</button>
            <div class="cdots">
                <?php foreach ($banners as $i => $bn): ?>
                    <span class="<?php echo $i === 0 ? 'on' : ''; ?>" onclick="qxGo(<?php echo $i; ?>)"></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer>
    <div class="foot-in wrap">
        <div>
            <div class="brand-sm">Quipux UCUENCA</div>
            <p><?php echo htmlspecialchars($footerText); ?></p>
        </div>
        <div class="foot-links">
            <a href="en_construccion.php?s=gestion-documental">Gestión Documental</a>
            <a href="en_construccion.php?s=firma-electronica">Firma Electrónica</a>
            <a href="inf_soporte.php?rsw=MQ==">Manuales de Usuario</a>
            <a href="inf_soporte.php?rsw=MQ==">Soporte Técnico</a>
        </div>
    </div>
</footer>

<script>
(function(){
    var qxIdx=0;
    window.qxShow=function(n){
        var s=document.querySelectorAll('.carousel .slide'), d=document.querySelectorAll('.cdots span');
        if(!s.length){ return; }
        qxIdx=(n+s.length)%s.length;
        for(var i=0;i<s.length;i++){ s[i].classList.toggle('on', i===qxIdx); }
        for(var j=0;j<d.length;j++){ d[j].classList.toggle('on', j===qxIdx); }
    };
    window.qxSlide=function(dir){ window.qxShow(qxIdx+dir); };
    window.qxGo=function(n){ window.qxShow(n); };
    if(document.querySelectorAll('.carousel .slide').length>1){ setInterval(function(){ window.qxSlide(1); }, 5000); }
})();
</script>
</body>
</html>
