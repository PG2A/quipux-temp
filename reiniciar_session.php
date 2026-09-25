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
include_once(__DIR__.'/rec_session.php');
include_once(__DIR__.'/include/subrogacion/Subrogacion.php');

$recordSet = array();

// La persona física detrás de la sesión. usua_codi puede ser el cargo que está
// subrogando en este momento, así que la autorización se evalúa siempre contra
// la persona real, nunca contra la identidad que tenga asumida.
$usua_codi_real = (int)($_SESSION["usua_codi_real"] ?? $_SESSION["usua_codi"]);
$destino        = 0 + ($_POST["cargo_usuario"] ?? 0);

$subrogacion = new Subrogacion($db);
// ¿El destino es un cargo que esta persona subroga con vigencia activa?
$contextoSubrogado = $subrogacion->contextoAutorizado($usua_codi_real, $destino);

$sqlUsua = "select * from usuario where usua_codi=" . $destino .
           " and usua_codi>0 and usua_login not like 'UADM%' and usua_esta=1 and usuario_vigente(usua_codi)";
$rs = $db->conn->query($sqlUsua);

if (!$rs or $rs->EOF) {
    $sqlUsua = "select * from usuario where usua_codi=".(0+$_SESSION["usua_codi"]) .
               " and usua_codi>0 and usua_login not like 'UADM%' and usua_esta=1";
    $rs = $db->conn->query($sqlUsua);
    if (!$rs or $rs->EOF) die (include "./paginaError.php");
}
/**
* Autoriza el cambio de identidad por una de dos vías:
*  - cuenta propia: misma cédula que la persona autenticada (comportamiento histórico)
*  - cargo subrogado: existe una subrogación vigente que la habilita
**/
if(($_SESSION["usua_doc"] == $rs->fields["USUA_CEDULA"] || $contextoSubrogado) and trim($rs->fields["USUA_ESTA"])==1){

    if ($contextoSubrogado) {
        // Se asume la identidad del CARGO, pero la sesión sigue perteneciendo a
        // la persona: no se toca su fila de usuarios_sesion, de modo que el
        // titular del puesto pueda seguir trabajando en paralelo con la suya.
        $_SESSION["usua_codi"]        = $rs->fields["USUA_CODI"];
        $_SESSION["usua_codi_real"]   = $usua_codi_real;
        $_SESSION["subrogacion_codi"] = (int)$contextoSubrogado["USUA_SUBROGACION_CODI"];

        $subrogacion->auditar(
            (int)$contextoSubrogado["USUA_SUBROGACION_CODI"],
            $usua_codi_real,
            (int)$rs->fields["USUA_CODI"],
            'CAMBIO_CONTEXTO');
    } else {
        // Cambio entre cuentas propias: la identidad de sesión se muda con el
        // usuario, igual que antes del rediseño.
        //Cierra la session del usuario con el cargo anterior
        // [REQ-4] Formato de fecha corregido: Y-m-d (guiones) en lugar de Y:m:d (dos puntos)
        $sql_sesion = "update usuarios_sesion set usua_sesion='FIN  ".date("Y-m-d H:i:s")."' where usua_codi=".(int)($_SESSION["usua_codi_sesion"] ?? $_SESSION["usua_codi"]);
        $db->conn->query($sql_sesion);

        $_SESSION["usua_codi"]        = $rs->fields["USUA_CODI"];
        $_SESSION["usua_codi_sesion"] = $rs->fields["USUA_CODI"];
        $_SESSION["usua_codi_real"]   = $rs->fields["USUA_CODI"];
        $_SESSION["subrogacion_codi"] = 0;

        //Crea o actualiza la session con el nuevo cargo
        // [PHP 8.3] Acceso seguro a $_SERVER — las claves HTTP_* no siempre
        // están presentes; el operador ?? evita E_WARNING "Undefined array key".
        $dir_cliente = ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '') . " - " . ($_SERVER['HTTP_CLIENT_IP'] ?? '') . " - " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        unset($recordSet);
        $recordSet["USUA_SESION"] = $db->conn->qstr(session_id());
        $recordSet["USUA_FECH_SESION"] = $db->conn->sysTimeStamp;
        $recordSet["USUA_CODI"] = $_SESSION["usua_codi"];
        $recordSet["USUA_INTENTOS"] = "0";
        $recordSet["IP_CLIENTE"] = $db->conn->qstr($dir_cliente);
        $db->conn->Replace("USUARIOS_SESION", $recordSet, "USUA_CODI", false,false,true,false);
    }
    $ValidacionKrd = "Si";

    $inst_codi = $rs->fields["INST_CODI"];
    $inst_nombre = $rs->fields["INST_NOMBRE"];
    $dependencia=$rs->fields["DEPE_CODI"];
    $depe_nomb =$rs->fields["DEPE_NOMB"];
    $cargo_tipo =$rs->fields["CARGO_TIPO"];
    $usua_nuevo = $rs->fields["USUA_NUEVO"];
    $usua_nomb =$rs->fields["USUA_NOMBRE"];
    $usua_email =$rs->fields["USUA_EMAIL"];
    $tipo_usuario=$rs->fields["TIPO_USUARIO"];

    if (!$dependencia) $dependencia=0;

    $_SESSION["inst_codi"] = $inst_codi;
    $_SESSION["inst_nombre"] = $inst_nombre;
    $_SESSION["dependencia"] = $dependencia;
    $_SESSION["depe_codi"] = $dependencia;
    $_SESSION["depe_nomb"] = $depe_nomb;
    $_SESSION["cargo_tipo"] = $cargo_tipo;
    $_SESSION["usua_nuevo"] = $usua_nuevo;
    $_SESSION["tipo_usuario"] = $tipo_usuario;
    $_SESSION["usua_email"] = $usua_email;
    $_SESSION["depe_codi_padre"] = $depe_codi_padre;
    $_SESSION["usua_nomb"] = $usua_nomb;
    $rsComp = $db->query("select usua_codi_jefe from bandeja_compartida where usua_codi=".$_SESSION["usua_codi"]);
    $_SESSION["usua_codi_jefe"] = (!$rsComp->EOF) ? 0+$rsComp->fields["USUA_CODI_JEFE"] : 0;

    /**
    * Obtener permisos de usuario
    **/

    //Cargamos los permisos del cargo
    $query = "select p.nombre, count(pc.id_permiso) as permiso
    from permiso p left outer join permiso_usuario pc on p.id_permiso=pc.id_permiso
    and pc.usua_codi=".$_SESSION["usua_codi"]." group by p.nombre";
    $rs = $db->conn->query($query);

    //echo "<hr>$query<hr>";
    while(!$rs->EOF) {
        $nom_perm = $rs->fields["NOMBRE"];
        $_SESSION[$nom_perm] = $rs->fields["PERMISO"];
        $rs->MoveNext();
    }
    echo "<script>window.location = 'index_frames.php';</script>";
    die();
}
else
{
    $dir_cliente = ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '') . " - " . ($_SERVER['HTTP_CLIENT_IP'] ?? '') . " - " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    unset($recordSet);
    $recordSet["FECHA"] = $db->conn->sysTimeStamp;
    $recordSet["USUARIO"] = $db->conn->qstr(session_id());
    $recordSet["DESCRIPCION"] = $db->conn->qstr("Intento de conexión a otro usuario de ".$_SESSION["usua_codi"]." a ".$_POST["cargo_usuario"].". IP: ".$dir_cliente);
    $db->conn->Replace("LOG_SESION", $recordSet, "", false,false,false,false);
    die (include "./paginaError.php");
}
?>
