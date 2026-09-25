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
 * @package    ciudadanos
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
require_once(dirname(__DIR__, 2)."/funciones_interfaz.php");
include_once("../ciudadanos/util_ciudadano.php");

$ciud = New Ciudadano($db);

$flag_login = true;
$ciu_codigo = limpiar_sql($_SESSION["usua_codi"]);
$respuesta=$ciud->cargar_datos_ciudadano($ciu_codigo,1);
if ($respuesta==0){
    echo html_error("No se encont&oacute; el usuario en el sistema.");
    die("");
}

//campos adicionales para solicitud

//Bandera para determinar si el rgistro de solicitud de firma para ciudadano existe o no
$banExisteSol = 0;
//campos adicionales para solicitud
$recordsolicitud = array();
unset ($recordsolicitud);

//Para editar
$recordsolicitud["CIU_CODIGO"]       = limpiar_sql($ciu_codigo);
$recordsolicitud["CIU_CEDULA"]       = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["cedula_usu"]))));
$recordsolicitud["CIU_DOCUMENTO"]    = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["documento_usu"]))));
$recordsolicitud["CIU_NOMBRE"]       = "initcap(". $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["nombre_usu"])))).")";
$recordsolicitud["CIU_APELLIDO"]     = "initcap(". $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["apellido_usu"])))).")";
$recordsolicitud["CIU_TITULO"]       = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["titulo_usu"]))));
$recordsolicitud["CIU_ABR_TITULO"]   = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["abr_titulo_usu"]))));
$recordsolicitud["CIU_EMPRESA"]      = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["empresa_usu"]))));
$recordsolicitud["CIU_CARGO"]        = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["cargo_usu"]))));
$recordsolicitud["CIU_DIRECCION"]    = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["direccion_usu"]))));
$recordsolicitud["CIU_EMAIL"]        = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["mail_usu"]))));
$recordsolicitud["CIU_TELEFONO"]     = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["telefono_usu"]))));
$recordsolicitud["SOL_OBSERVACIONES"]= $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["observaciones_usu"]))));
$recordsolicitud["SOL_ESTADO"]       = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["sol_estado"]))));
$recordsolicitud["CIU_REFERENCIA"]   = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["referencia_usua"]))));
$recordsolicitud["SOL_FIRMA"]        = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["firma_usu"]))));

$recordsolicitud["CIUDAD_CODI"]       = limpiar_sql($ciudad_usu);



//Consultar si el registro en la tabla solicitud_firma_ciudadano existe
$sqlSol = "select * from solicitud_firma_ciudadano where ciu_codigo = $ciu_codigo";
$rsSol = $db->conn->query($sqlSol);

if(!$rsSol->EOF){
    $recordsolicitud["SOL_CODIGO"] = $rsSol->fields["SOL_CODIGO"];
    $banExisteSol = 1;
}
//Si el registro existe actualiza
if($banExisteSol==1)
    $whereSol = "SOL_CODIGO";
else//Si el registro no existe inserta    
    $whereSol = "";    
    
$recordsolicitud["SOL_ESTADO"]      = 2;
//$recordsolicitud["SOL_FIRMA"]       = 2;
$recordsolicitud["SOL_ACUERDO"]     = 1;

$nombre_usu = limpiar_sql($_POST["nombre_usu"]);
$nombre_usu.= limpiar_sql($_POST["apellido_usu"]);

//LOG
$rs_old = $db->conn->query($sqlSol);
$ok2 = $db->conn->Replace("SOLICITUD_FIRMA_CIUDADANO", $recordsolicitud, $whereSol, false,false,false,false);
$rs_new = $db->conn->query($sqlSol);
$ciud->grabar_log_tabla('LOG_USR_CIUDADANOS',$rs_old, $rs_new, $_SESSION['usua_codi'],3,$db);

if($ok2)
//enviar mail
$ciud->enviarMail('enviar',limpiar_sql(trim($_POST["mail_usu"])),$nombre_usu,limpiar_sql(trim($_POST["cedula_usu"])),$_SESSION['inst_nombre']);

?>

<!DOCTYPE html>
<?php echo html_head(); /*Imprime el head definido para el sistema*/?>
<body>
    <form>
    <br /><br /><br /><center>
    <table  width='40%' border="1" class='t_bordeGris'>
        <tr valign='top' align='center'>
            <td width='100%' class="listado2">
                <font size="1">Sus datos fueron enviados exitosamente.<br />
                Estos ser&aacute;n verificados por el administrador del sistema antes de aprobar la solicitud.
                </font>
            </td>
        </tr>
        <tr><td width="100%" align="center" class="listado2">
        <?php echo "<input type='button' value='Aceptar' class='botones' name='btn_aceptar' onClick=\"window.location='adm_solicitud_ciu.php'\">";?>
            </td></tr>
    </table></center>  
    </form>    
</body>
</html>