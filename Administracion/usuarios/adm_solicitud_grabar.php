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
 * @package    usuarios
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
require_once(dirname(__DIR__, 2)."/funciones_interfaz.php");


$flag_login = true;
$ciu_codigo = limpiar_sql($_POST["ciu_codigo"]);

// Obtener el codigo anterior del ciudadano $old_codigo si la actualización se realiza desde buscar de/para
if($_GET['buscar'] == 'S')
    if(isset($old_codigo))
        $ciu_codigo = $old_codigo;

//campos adicionales para solicitud


//Bandera para determinar si el rgistro de solicitud de firma para ciudadano existe o no
$banExisteSol = 0;
//campos adicionales para solicitud
$recordsolicitud = array();
unset ($recordsolicitud);
$recordsolicitud["CIU_CODIGO"]       = limpiar_sql($_POST["ciu_codigo"]);
$recordsolicitud["CIU_CEDULA"]       = $db->conn->qstr(limpiar_sql(trim($_POST["ciu_cedula"])));
$recordsolicitud["CIU_DOCUMENTO"]    = $db->conn->qstr(limpiar_sql(trim($_POST["ciu_documento"])));
$recordsolicitud["CIU_NOMBRE"]       = "initcap(".$db->conn->qstr(limpiar_sql(trim($_POST["ciu_nombre"]))).")";
$recordsolicitud["CIU_APELLIDO"]     = "initcap(".$db->conn->qstr(limpiar_sql(trim($_POST["ciu_apellido"]))).")";
$recordsolicitud["CIU_TITULO"]       = $db->conn->qstr(limpiar_sql(trim($_POST["ciu_titulo"])));
$recordsolicitud["CIU_ABR_TITULO"]   = $db->conn->qstr(limpiar_sql(trim($_POST["ciu_abr_titulo"])));
$recordsolicitud["CIU_EMPRESA"]      = $db->conn->qstr(limpiar_sql(trim($_POST["ciu_empresa"])));
$recordsolicitud["CIU_CARGO"]        = $db->conn->qstr(limpiar_sql(trim($_POST["ciu_cargo"])));
$recordsolicitud["CIU_DIRECCION"]    = $db->conn->qstr(limpiar_sql(trim($_POST["ciu_direccion"])));
$recordsolicitud["CIU_EMAIL"]        = $db->conn->qstr(limpiar_sql(trim($_POST["ciu_email"])));
$recordsolicitud["CIU_TELEFONO"]     = $db->conn->qstr(limpiar_sql(trim($_POST["ciu_telefono"])));
$recordsolicitud["CIUDAD_CODI"]       = limpiar_sql($_POST["codi_ciudad"]);
//Para editar
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
else //Si el registro no existe inserta
    {
    $whereSol = "";
    $recordsolicitud["SOL_ESTADO"]       = 1;
    }
$recordsolicitud["CIU_CODIGO"]       =  limpiar_sql($ciu_codigo);
$recordsolicitud["SOL_OBSERVACIONES"]       = $db->conn->qstr($_POST["sol_observaciones"]);
$recordsolicitud["SOL_FIRMA"]       = $_POST["sol_firma"];


$ok2 = $db->conn->Replace("solicitud_firma_ciudadano", $recordsolicitud, $whereSol, false,false,false,false);



?>

<!DOCTYPE html>
<?php  echo html_head(); /*Imprime el head definido para el sistema*/?>
<body>
    <div id='wrapper'>
    <?php  if (!$flag_login) echo html_encabezado(); /*Imprime el encabezado del sistema*/ ?>
    <div id='mainbody'><div class='shad-1'><div class='shad-2'><div class='shad-3'><div class='shad-4'><div class='shad-5'>
    <form>
    <br /><br /><br />
    <table align='center' width='100%' cellpadding='0' cellspacing='0' class='mainbody'>
        <tr valign='top' align='center'>
            <td class='left'  align='center' width='100%'>
                <h3>Sus datos fueron guardados exitosamente.</h3>
                    <?php  if (!$flag_login) echo "<br /><br /><input type='button' value='Aceptar' class='botones' name='btn_aceptar' onClick=\"window.location='../../login.php'\">"; ?>
                    <?php  
                            echo "<br /><br /><input type='button' value='Aceptar' class='botones' name='btn_aceptar' onClick=\"window.location='adm_solicitud.php'\">";
                       
                    ?>
            </td>
        </tr>
    </table>
    <br /><br /><br />
    </form>
    </div></div></div></div></div></div>
    <?php  if (!$flag_login) echo html_pie_pagina(); /*Imprime el pie de pagina del sistema*/ ?>
    </div>
</body>
</html>