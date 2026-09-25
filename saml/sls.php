<?php
/**
 * sls.php — SAML Single Logout Service endpoint
 *
 * [REQ-2][REQ-3] Maneja dos flujos:
 *
 * 1. IdP-initiated SLO: ADFS envía LogoutRequest/Response vía GET.
 *    Se procesa con processSLO() y se redirige al portal dual.
 *
 * 2. SP-initiated SLO: cerrar_session.php redirige aquí tras destruir
 *    la sesión local. Los datos SAML (nameId, sessionIndex) llegan
 *    como parámetros GET porque $_SESSION ya no existe.
 *    Se construye y envía una LogoutRequest firmada al IdP.
 */

session_start();
require_once __DIR__.'/../vendor/autoload.php';
$settings = require __DIR__.'/settings.php';
$auth     = new OneLogin\Saml2\Auth($settings);

// Destino post-logout: siempre el portal dual [REQ-3]
$returnTo = '/index.php';

// ============================================
// Flujo 1: IdP-initiated SLO
// ============================================
if (isset($_GET['SAMLRequest']) || isset($_GET['SAMLResponse'])) {
    // processSLO(false) destruye la sesión local y valida
    // la LogoutRequest/Response del IdP.
    $auth->processSLO(false);
    $errors = $auth->getErrors();
    if (!empty($errors)) {
        error_log('SAML SLO error (IdP-initiated): ' . implode(', ', $errors));
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    header('Location: ' . $returnTo);
    exit;
}

// ============================================
// Flujo 2: SP-initiated SLO (desde cerrar_session.php)
// ============================================
// La sesión PHP ya fue destruida por cerrar_session.php,
// por lo que leemos nameId y sessionIndex de $_GET.
$nameId       = $_GET['nameId']       ?? ($_SESSION['samlNameId']       ?? null);
$sessionIndex = $_GET['sessionIndex'] ?? ($_SESSION['samlSessionIndex'] ?? null);

// Permitir que cerrar_session.php sobreescriba el returnTo
if (isset($_GET['returnTo'])) {
    $returnTo = $_GET['returnTo'];
}

if ($nameId === null) {
    // Sin NameId no podemos construir LogoutRequest.
    // Fallback defensivo: redirigir al portal dual.
    error_log('SAML SLO: nameId no disponible, saltando LogoutRequest al IdP');
    header('Location: ' . $returnTo);
    exit;
}

// Enviar LogoutRequest firmada al IdP (ADFS).
// $auth->logout() hace redirect automático al IdP.
$auth->logout($returnTo, [], $nameId, $sessionIndex);
exit;

