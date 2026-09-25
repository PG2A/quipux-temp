<?php
// This file is part of Quipux – Document Management System
//
// Quipux is free software and is currently under a process of technical
// modernization and functional improvement carried out by
// EXDUCERE ONLINE CIA. LTDA., as part of the development of a new version
// of the Quipux platform.
//
// Quipux is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Quipux is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Quipux. If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    quipux
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(__DIR__.'/config.php');
include_once(__DIR__.'/include/db/ConnectionHandler.php');
error_reporting(7);

// ============================================
// [REQ-2] Extraer datos de autenticación ANTES
// de destruir la sesión. Después de session_destroy()
// estas variables ya no estarán disponibles.
// ============================================
$auth_method      = $_SESSION['auth_method']      ?? 'database';
$samlNameId       = $_SESSION['samlNameId']       ?? null;
$samlSessionIndex = $_SESSION['samlSessionIndex'] ?? null;

// ============================================
// [REQ-5] Destruir sesión local usando SessionManager
// para garantizar limpieza completa: $_SESSION, cookie,
// registro de cierre en BD y session_destroy().
// ============================================
$db = new ConnectionHandler(__DIR__);
require_once(__DIR__ . '/SessionManager.php');
$sessionManager = new SessionManager($db);
$sessionManager->destroySession();

// ============================================
// [REQ-2] Redirección condicional post-logout
// ============================================
if ($auth_method === 'saml' && $samlNameId !== null) {
    // SP-initiated Single Logout: delegar al SLS para que
    // envíe una LogoutRequest firmada al IdP (ADFS).
    // Se pasan nameId y sessionIndex como parámetros GET porque
    // la sesión PHP ya fue destruida arriba.
    $sloParams = [
        'returnTo'     => '/index.php',
        'nameId'       => $samlNameId,
        'sessionIndex' => $samlSessionIndex,
    ];
    $sloUrl = 'saml/sls.php?' . http_build_query($sloParams);
    header('Location: ' . $sloUrl);
    exit;
}

// [REQ-3] Usuario local (database): ir al portal dual
header('Location: index.php');
exit;
?>
