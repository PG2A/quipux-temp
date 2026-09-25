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
require_once(dirname(__DIR__, 2)."/funciones_interfaz.php");

$ciu_codigo = limpiar_sql($_SESSION["usua_codi"]);
$accion_btn_cancelar = "history.back();";
$flag_login = true;

include_once("../ciudadanos/util_ciudadano.php");

$ciud = New Ciudadano($db);
// Obtener el codigo anterior del ciudadano $old_codigo si la actualización se realiza desde buscar de/para
if($_GET['buscar'] == 'S')
    if(isset($old_codigo))
        $ciu_codigo = $old_codigo;

$sql = "select * from ciudadano where ciu_codigo='$ciu_codigo'";

$rs = $db->conn->query($sql);
if ($rs->EOF) {
    echo html_error("No se encont&oacute; el usuario en el sistema.");
    die("");
}

$record = array();
unset ($record);
$record["CIU_CODIGO"]       = 0+limpiar_sql($_POST["ciu_codigo"]);
$record["CIU_CEDULA"]       = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["ciu_cedula"]))));
$record["CIU_DOCUMENTO"]    = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["ciu_documento"]))));
$record["CIU_NOMBRE"]       = "initcap(".$db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["ciu_nombre"])))).")";
$record["CIU_APELLIDO"]     = "initcap(".$db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["ciu_apellido"])))).")";
$record["CIU_TITULO"]       = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["ciu_titulo"]))));
$record["CIU_ABR_TITULO"]   = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["ciu_abr_titulo"]))));
$record["CIU_EMPRESA"]      = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["ciu_empresa"]))));
$record["CIU_CARGO"]        = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["ciu_cargo"]))));
$record["CIU_DIRECCION"]    = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["ciu_direccion"]))));
$record["CIU_EMAIL"]        = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["ciu_email"]))));
$record["CIU_TELEFONO"]     = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["ciu_telefono"]))));

//$record["PAIS_CODI"]     = 0+limpiar_sql(trim($_POST["cod_pais"]));
//$record["PROVINCIA_CODI"]     = 0+limpiar_sql(trim($_POST["cod_prov"]));
//$record["CANTON_CODI"]     = 0+limpiar_sql(trim($_POST["cod_canton"]));
$record["CIU_REFERENCIA"]     = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($_POST["ciu_referencia"]))));
if (isset($_POST["ciu_ciudad"]))
$record["CIUDAD_CODI"]       = 0+limpiar_sql(trim($_POST["ciu_ciudad"]));
else
    $record["CIUDAD_CODI"]=0;
$record["CIU_ESTADO"]       = 1;


$sql="select * from ciudadano_tmp where ciu_codigo = ".$record["CIU_CODIGO"];    
$rs_old=$db->conn->Execute($sql);    
$ok1 = $db->conn->Replace("CIUDADANO_TMP", $record, "CIU_CODIGO", false,false,false,false);   
$rs_new=$db->conn->Execute($sql);
$ciud->grabar_log_tabla('LOG_USR_CIUDADANOS',$rs_old, $rs_new, $_SESSION['usua_codi'],4);



if($ok1)
    $ciud->enviarMail ('ciu_grabar', $amd_email, 'Administrador', '', '');
?>

<!DOCTYPE html>
<?php echo html_head(); /*Imprime el head definido para el sistema*/?>
    <script type="text/javascript">
       function cerrarpag(){
           window.close();
       }
       function redireccionar(){          
           window.location='../usuarios/mnuUsuarios_ext.php';
       }
       function redireccionar_ciu(){
           window.location='../ciudadanos/adm_datos_temporales.php';
       }
    </script>



<!DOCTYPE html>
    <?php echo html_head(); //Imprime el head definido para el sistema?>
<body>
    <form name="frmConfirmaCreacion" action="adm_solicitud_ciu.php" method="post">
        <br>
        &nbsp;
        </br>
        <center><table width="40%" border="2" align="center" class="t_bordeGris">
            <tr>
            <td width="100%" height="30" align="center" class="listado2">
                Datos guardados exitosamente.<br />
                    Estos ser&aacute;n verificados por el administrador del sistema antes de ser modificados definitivamente.
                
            </td>
            </tr>
            <tr><td width="100%" height="30" align="center" class="listado2">
                    <?php
                    if ($_SESSION["tipo_usuario"]==1) {                        
                        if($_GET['buscar'] == 'S' and $_GET['cerrar']=='No')
                            echo "<input type='button' value='Aceptar' class='botones' name='btn_aceptar' onClick=redireccionar();>";
                        else                            
                            echo "<input type='button' value='Aceptar' class='botones' name='btn_aceptar' onClick=cerrarpag();>";
                    }else
                        echo "<input type='button' value='Aceptar' class='botones' name='btn_aceptar' onClick=redireccionar_ciu();>";
                    ?>
            </td>
            </tr>
            
        </table>
    </center>
    </form>
</body>
</html>