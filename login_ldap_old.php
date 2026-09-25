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
 * @package    core
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//if (session_status() !== PHP_SESSION_ACTIVE) {
//    session_start();
//}

function autenticar($user, $password) {

    if (empty($user) || empty($password)) return false;

    // === Configuración del modo de autenticación ===
    // Cambia a 'ldap' si deseas probar con tu LDAP real
    $mode = 'db'; // 'db' = autenticar contra tabla usuario, 'ldap' = usar LDAP

    // Hash que usa Quipux (lo guarda truncado a 26)
    $passwordMd5 = md5($password);
    $_SESSION['drd'] = $passwordMd5;

    // El superusuario legacy
    if (strtoupper($user) === 'ADMINISTRADOR') {
        $_SESSION['user']   = $user;
        $_SESSION['krd']    = $user;
        $_SESSION['access'] = 1;
        return true;
    }

    if ($mode === 'db') {



        // ====== Autenticación por BASE DE DATOS ======
        // Detectar raíz del proyecto de forma robusta
        $root = __DIR__; // p.ej. /root/UCUENCA/quipux
        if (!is_file("$root/include/db/ConnectionHandler.php")) {
            if (is_file("$root/quipux/include/db/ConnectionHandler.php")) {
                $root = "$root/quipux"; // caso: docroot = /root/UCUENCA/quipux/quipux
            } elseif (is_file(dirname(__DIR__) . "/include/db/ConnectionHandler.php")) {
                $root = dirname(__DIR__);
            } else {
                die("No se encontró include/db/ConnectionHandler.php desde $root");
            }
        }

        require_once "$root/include/db/ConnectionHandler.php";
        $db = new ConnectionHandler($root, "");

        $db->conn->SetFetchMode(ADODB_FETCH_ASSOC);

        $db->conn->debug = false;

        $login = strtoupper(trim($user));
        if (strpos($login, 'U') !== 0) {
            $login = 'U' . $login;
        }
        $sql = "
            SELECT u.usua_codi, u.usua_login, u.usua_pasw, u.depe_codi, u.usua_tipo
            FROM usuarios u
            WHERE UPPER(u.usua_login) = ?
              AND u.usua_esta = 1
            LIMIT 1
        ";

        $rs = $db->conn->Execute($sql, array($login));

        $md5_32  = $passwordMd5;
        $md5_26  = substr($passwordMd5, 0, 26);
        $md5_26_bug  = substr($passwordMd5, 1, 26);

        error_log("DEBUG - Usuario buscado: " . $login);
        error_log("DEBUG - SQL ejecutado: " . $sql);
        error_log("DEBUG - Resultado EOF: " . ($rs->EOF ? 'true' : 'false'));
        if (!$rs->EOF) {
            error_log("DEBUG - Hash en BD: " . $rs->fields['USUA_PASW']);
            error_log("DEBUG - Hash calculado (26): " . $md5_26);
            error_log("DEBUG - Hash calculado (26-bug): " . $md5_26_bug);
            error_log("DEBUG - Hash calculado (32): " . $md5_32);
        }

        if (!$rs || $rs->EOF) return false;

        $hashDb  = $rs->fields['USUA_PASW'];

        if (!($hashDb === $md5_26 || $hashDb === $md5_32 || $hashDb === $md5_26_bug)) {
            return false;
        }

        // Variables de sesión típicas que usa Quipux
        $_SESSION['user']         =  $rs->fields['USUA_LOGIN'];
        $_SESSION['krd']          =  $rs->fields['USUA_LOGIN'];
        $_SESSION['drd']          =  $md5_32;
        $_SESSION['depe_codi']    =  $rs->fields['DEPE_CODI'];
        $_SESSION['tipo_usuario'] =  $rs->fields['USUA_TIPO'];
        $_SESSION['access']       = 1;


//        var_dump($_SESSION['krd']);
//        var_dump('pasa a aqui? linea 89');
//        var_dump('session', $_SESSION);
//        var_dump('md5_32', $md5_32);
//        var_dump('md5_26', $md5_26);
//        die();

        return true;
    }

    if ($mode === 'ldap') {
        // ====== Autenticación por LDAP ======
        $servidor_LDAP = "10.0.1.8";
        $baseDN        = "ou=users,dc=ucuenca,dc=edu,dc=ec";
        $adminDN       = "cn=Manager,dc=ucuenca,dc=edu,dc=ec";
        $adminPswd     = "@dm1nNET";
        $username      = $user;
        $userpass      = $password;

        $ldap_conn = ldap_connect($servidor_LDAP, 389);
        ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap_conn, LDAP_OPT_REFERRALS, 0);

        $ldapBindAdmin = @ldap_bind($ldap_conn, $adminDN, $adminPswd);

        if ($ldapBindAdmin) {
            $filter     = '(&(objectClass=ucPerson)(uid=' . $username . '))';
            $attributes = array("cn","ucCedula","mail","ou");
            $result     = ldap_search($ldap_conn, $baseDN, $filter, $attributes);
            $entries    = ldap_get_entries($ldap_conn, $result);

            $first  = ldap_first_entry($ldap_conn, $result);
            $userDN = ldap_get_dn($ldap_conn, $first);

            $ldapBindUser = @ldap_bind($ldap_conn, $userDN, $userpass);

            if ($ldapBindUser) {
                $_SESSION['user']   = $user;
                $_SESSION['access'] = 1;
                $_SESSION['krd']    = $entries[0]["uccedula"][0] ?? $user;
                $_SESSION['drd']    = $passwordMd5;
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    return false; // si ningún modo coincide
}
