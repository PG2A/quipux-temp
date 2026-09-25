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

$recordSet = array();

$sqlUsua = "select * from usuario where usua_codi=".(0+$_POST["cargo_usuario"]) .
           " and usua_codi>0 and usua_login not like 'UADM%' and usua_esta=1";
$rs = $db->conn->query($sqlUsua);

if (!$rs or $rs->EOF) {
    $sqlUsua = "select * from usuario where usua_codi=".(0+$_SESSION["usua_codi"]) .
               " and usua_codi>0 and usua_login not like 'UADM%' and usua_esta=1";
    $rs = $db->conn->query($sqlUsua);
    if (!$rs or $rs->EOF) die (include "./paginaError.php");
}
/**
* Verifica si el usuario es tiene el mismo numero de cedula y esta activo caso contrario presenta mensaje de error.
**/
if($_SESSION["usua_doc"] == $rs->fields["USUA_CEDULA"] and trim($rs->fields["USUA_ESTA"])==1){

    //Cierra la session del usuario con el cargo anterior
    // [REQ-4] Formato de fecha corregido: Y-m-d (guiones) en lugar de Y:m:d (dos puntos)
    $sql_sesion = "update usuarios_sesion set usua_sesion='FIN  ".date("Y-m-d H:i:s")."' where usua_codi=".$_SESSION["usua_codi"];
    $db->conn->query($sql_sesion);

    $_SESSION["usua_codi"] = $rs->fields["USUA_CODI"];
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
