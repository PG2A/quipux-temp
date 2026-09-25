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
 * @package    tbasicas
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function autenticar($user, $password)
{
////echo "<br>usuario a consultar : ".$user;	
////echo "<br>password a consultar : ".$password;	

    if (empty($user) || empty($password)) return false;
//desactivamos los erroes por seguridad
    error_reporting(0);
//error_reporting(E_ALL); //activar los errores (en modo depuración)

    $servidor_LDAP = "172.16.0.13";
    $servidor_dominio = "ucuenca.edu.ec";
    $ldap_dn = ",ou=empleados,ou=users,dc=ucuenca,dc=edu,dc=ec"; //verifica la reama de empleado
	//$ldap_dn = ",ou=users,dc=ucuenca,dc=edu,dc=ec";
	$base_dn = "ou=empleados,ou=users,dc=ucuenca,dc=edu,dc=ec";
	//$base_dn = "ou=users,dc=ucuenca,dc=edu,dc=ec";
    $contrasena_LDAP = $password;
    $usuario_consulta = $user;
    $usuario_LDAP = "uid=$usuario_consulta $ldap_dn";
//echo "<br>en longin_ldap hasta aqui antes de conexion ldap";
    $conectado_LDAP = ldap_connect($servidor_LDAP, 389);
    ////echo "<br>en login_ldap paso conexion ldap al servidor";
    if ($conectado_LDAP) {
        ldap_set_option($conectado_LDAP, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($conectado_LDAP, LDAP_OPT_REFERRALS, 0);
//echo "<br>en login_ldap antes de la conexion del usuario";
        $autenticado_LDAP = ldap_bind($conectado_LDAP, $usuario_LDAP, $contrasena_LDAP);
        $access = 0;
        if ($autenticado_LDAP) {
//echo "<br>en login_ldap paso la conexion del usuario";
			$_SESSION['user'] = $user;
//echo "<br>usuario conectado:: ".$user;
            $access = 1;
            $_SESSION['access'] = $access;
			 $filtro="uid=$user*";
			 $datos = array( "ucCedula","uid");
			 $displayAttr = "uccedula"; //atributo del ldap que necesita ser obtenido
			 $result=ldap_search($conectado_LDAP,$base_dn, $filtro,$datos); 
			 $entries=ldap_get_entries($conectado_LDAP, $result);
			 //echo "<br>entradas. ".$entries["count"];
			 if ($entries["count"]>0)
			 {
				 if (isset($entries[0][$displayAttr])) {
							// Recuperar el atributo a incorporar en la respuesta
							$userDisplayName = $entries[0][$displayAttr][0];
							$_SESSION['krd'] = $userDisplayName;
							//echo "<br>krd. ".$userDisplayName;
							$passwordMd5=md5($password);
							$_SESSION['drd'] = $passwordMd5;
					}
					else {
							// Si el atributo no está definido para el usuario
							$userDisplayName = "-";
							$msg = "<br>Atributo no disponible ({$displayAttr})";
							return false;
							//echo $msg;
					}
			 }
			 //$info = ldap_get_entries($conectado_LDAP, $sr);
			 //$_SESSION['numero_entradas'] = $info["count"];
			 
			 
			  //mandar a variable de sesion el usuario de quipux
			 //$passwordMd5=md5( 'P2m3e3_77');
			 //$_SESSION['krd'] = "0103691671";
			 //$_SESSION['nueva'] = "valor de krd";
			 //$_SESSION['drd'] = $passwordMd5;
            return true;
        } else {
            //echo "<br><br>Usuario o password incorrecto, No se ha podido autenticar con el servidor LDAP: " .
            //    $servidor_LDAP .
            ", verifique el usuario y la contraseña introducidos";
            return false;
        }
    } else {
        //echo "<br><br>No se ha podido realizar la conexión con el servidor LDAP: " .
        //    $servidor_LDAP;
        return false;
    }
}


?>
