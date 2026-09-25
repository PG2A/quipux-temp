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

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php"); //para traer funciones p_get y p_post
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
if($_SESSION["usua_admin_sistema"]!=1) {
    die("");
}

echo "<!DOCTYPE html>".html_head();

$mensajeUsr="";
$html="";
?>
<script>
function regresar(){
    window.location="adm_instituciones.php";
}
</script>
<?php
if (isset($_GET["cod_inst_des"])){
    $id_institucion = 0+limpiar_numero($_GET["cod_inst_des"]);    
    $sql="select inst_nombre,inst_sigla,inst_ruc
    from institucion where inst_codi = $id_institucion";
    $rs_inst = $db->conn->query($sql);
    $nombreInst = $rs_inst->fields["INST_NOMBRE"];
    $siglaInst = $rs_inst->fields["INST_SIGLA"];
    $rucInst = $rs_inst->fields["INST_RUC"];
    if (!$rs_inst->EOF){
    //desactivar
    $sqlup = "update institucion set inst_estado = 0 where inst_codi = $id_institucion";
    if ($_SESSION["usua_codi"]==0)
    $ok=$db->conn->query($sqlup);
    if ($ok==1){
    $html.="<br><br><center><table width='60%' class='borde_tab' align='center'>
        <tr><td class='listado2' align='center'>
        La Institución $nombreInst ha sido desactivada</td></tr>";
    }else{
        $html.="<br><br><center><table width='60%' class='borde_tab' align='center'>
        <tr><td class='listado2' align='center'>
        Hubo problemas al desactivar la institución o su Usuario no tiene permisos para realizar esta
        acción.</td></tr>";
    }    
    $html.="<tr><td class='listado2' align='center'>
            <input  name='btn_accion' type='button' class='botones' value='Regresar' onclick='regresar()'/>
            </td></tr>";
    $html.="</tr></table></center>";
    }
    echo $html;
}else{
    echo "<br><br><center><table width='60%' class='borde_tab' align='center'>
        <tr><td class='listado2' align='center'>Hubo problemas al seleccionar la Institución, intente nuevamente</td></tr>
        <tr><td class='listado2' align='center'>
            <input  name='btn_accion' type='button' class='botones' value='Regresar' onclick='regresar()'/>
            </td></tr>
        </table></center>";
}

 ?>
</html>