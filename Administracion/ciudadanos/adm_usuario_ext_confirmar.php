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
require_once(dirname(__DIR__, 2).'/funciones.php'); //para traer funciones p_get y p_post
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
// modo=solicitud: alta desde la búsqueda de destinatarios (RQT-7); ver adm_usuario_ext.php
$modo_solicitud = (($_GET['modo'] ?? $_POST['modo'] ?? '') === 'solicitud');
$puede_solicitar = ($modo_solicitud && ($_SESSION["tipo_usuario"] ?? 0) != 2
                   && (($_SESSION["usua_prad_tp1"] ?? 0) == 1 || ($_SESSION["usua_perm_ciudadano"] ?? 0) == 1 || ($_SESSION["usua_admin_sistema"] ?? 0) == 1));
if (!$puede_solicitar and ($_SESSION["usua_admin_sistema"] ?? 0) != 1 and ($_SESSION["usua_perm_ciudadano"] ?? 0) != 1) {
    echo html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
    die("");
}
include_once("util_ciudadano.php");
include_once("../usuarios/mnuUsuariosH.php"); // en modo solicitud no exige permiso 16 (ver ese archivo)

$ciud = New Ciudadano($db);

$grabar_ciu=1;

$ciu_cedula = limpiar_sql($_POST['ciu_cedula'] ?? '');
$ciu_documento = limpiar_sql($_POST['ciu_documento'] ?? '');
$ciu_nombre = limpiar_sql($_POST['ciu_nombre'] ?? '');
$ciu_apellido = limpiar_sql($_POST['ciu_apellido'] ?? '');
$ciu_titulo = limpiar_sql($_POST['ciu_titulo'] ?? '');
$ciu_abr_titulo = limpiar_sql($_POST['ciu_abr_titulo'] ?? '');
$ciu_empresa = limpiar_sql($_POST['ciu_empresa'] ?? '');
$ciu_cargo = limpiar_sql($_POST['ciu_cargo'] ?? '');
$ciu_direccion = limpiar_sql($_POST['ciu_direccion'] ?? '');
$ciu_referencia = limpiar_sql($_POST['ciu_referencia'] ?? '');
$ciu_email = limpiar_sql($_POST['ciu_email'] ?? '');
$ciu_telefono = limpiar_sql($_POST['ciu_telefono'] ?? '');
$ciu_password = limpiar_sql($_POST['ciu_password'] ?? 0);
$ciu_sincedula = limpiar_sql($_POST['ciu_sincedula'] ?? 0);
$ciu_nuevo = limpiar_sql($_POST['ciu_nuevo'] ?? 0);
$ciu_ciudad = limpiar_sql($_POST['ciu_ciudad'] ?? 0);

/**
* Verificar si se va a insertar (accion = 1) o actualizar (else) a un ciudadano.
**/

$tmp_cedula = $ciu_cedula;
if ($ciu_password != 1) {
    $ciu_password = 0;
}
// En el caso de que el ciudadano no ingrese su numero de cedula se genera un numero automaticamente igual a 9999999999 menos el codigo del usuario
if ($accion==1) {
    if ($ciu_sincedula==1) {
        $sql = "select last_value from usuarios_usua_codi_seq";
        $rs = $db->conn->Execute($sql);
        $tmp_cedula = 9999999998-$rs->fields["LAST_VALUE"];
    }
    $sql2 = "";

} else {
    if ($ciu_sincedula==1)
	$tmp_cedula = 9999999999-$ciu_codigo;
    $sql2 = " and ciu_codigo<>$ciu_codigo";
}

// Cédula ya registrada (otro ciudadano o un servidor público): se bloquea la
// creación. La lista de "datos similares" de más abajo sigue siendo solo un aviso;
// esto en cambio impide el botón "Crear Ciudadano".
$cuenta_existente = ($ciu_sincedula == 1) ? null
                  : $ciud->cuentaExistentePorCedula($tmp_cedula, ($accion == 1) ? 0 : $ciu_codigo);

$paginador = new ADODB_Pager_Ajax(dirname(__DIR__, 2), "div_datos_confirmacion", "busqueda_pag_usuario_confirmar.php",
                  "ciu_apellido,ciu_nombre,ciu_empresa,ciu_cedula");
?>

<!DOCTYPE html>
<?php
    echo html_head(); /*Imprime el head definido para el sistema*/
    require_once(dirname(__DIR__, 2)."/js/ajax.js");
    $param_ajax = "new_cedula=$ciu_cedula&new_sincedula=$ciu_sincedula&new_documento=$ciu_documento&new_nombre=$ciu_nombre&new_apellido=$ciu_apellido&new_titulo=$ciu_titulo&new_abr_titulo=$ciu_abr_titulo&new_empresa=$ciu_empresa&new_cargo=$ciu_cargo&new_direccion=$ciu_direccion&new_email=$ciu_email&new_telefono=$ciu_telefono";
?>
     <script type="text/javascript" src="jquerysubir/jquery-1.3.2.min.js"></script>
    <script type="text/javascript" src="adm_ciudadanos.js"></script>
    <script type="text/javascript" src="../../js/formchek.js"></script>
    <script type="text/javascript" src="../../js/validar_cedula.js"></script>
    <script type="text/javascript">
        function realizar_busqueda() { 

               paginador_reload_div('');
        }

        function comparar_ciudadanos(codigo) {
            document.getElementById('tabla_usuario').style.display='none';
            nuevoAjax('div_comparar', 'POST', 'adm_usuario_ext_comparar.php', 'old_codigo='+codigo+"&<?=$param_ajax?>");
            return;
        }

        function mover_dato(campo) {
            document.getElementById('btn_aceptar2').style.visibility="visible";
            document.getElementById("old_"+campo).value = document.getElementById("new_"+campo).value;
            if (campo == 'cedula') {
                document.getElementById("old_sincedula").checked = false;
                ver_cedula();
            }
            return;
        }
        
        function ValidarInformacion(grupo)
        {
            flag = true;
            if (grupo=='ciu') {
                if(document.getElementById(grupo+'_sincedula').value==1) flag = false;
            } else {
                if(document.getElementById(grupo+'_sincedula').checked) flag = false;
            }
            if(flag)
                if (!validarCedula(document.getElementById(grupo+'_cedula')))
                    return false;
            if(ltrim(document.getElementById(grupo+'_nombre').value)=='' || ltrim(document.getElementById(grupo+'_apellido').value)=='')
            {	alert("Los campos de Nombres y Apellidos son obligatorios.");
                return false;
            }
            if (!isEmail(document.getElementById(grupo+'_email').value,true))
            {	alert("El campo Email no tiene formato correcto.");
                return false;
            }
            return true;
        }

        function ver_cedula()
        {
            if(document.getElementById('old_sincedula').checked){
            document.getElementById('old_cedula').style.display='none';
            document.getElementById("img_reg2").style.display = 'none';
            document.getElementById('div_datos_registro_civil').style.display = 'none';
            }
            else{
                document.getElementById('old_cedula').style.display='';
                document.getElementById("img_reg2").style.display = '';
                document.getElementById('div_datos_registro_civil').style.display = '';
            }
            /*document.getElementById[grupo+'_cedula'].value='';*/
        }

        function aceptar_cambios() {            
            if (ValidarInformacion('old')!=true) {
                return false;
            }
            
            copiar('codigo');
            copiar('cedula');
            copiar('documento');
            copiar('nombre');
            copiar('apellido');
            copiar('titulo');
            copiar('abr_titulo');
            copiar('empresa');
            copiar('cargo');
            copiar('direccion');
            copiar('email');
            copiar('telefono');
            if(document.getElementById('old_sincedula').checked)
                document.getElementById('ciu_sincedula').value = 1;
            else
                document.getElementById('ciu_sincedula').value = 0;            
            document.frm_confirmar.action='adm_ciudadano_grabar.php?buscar=S&cerrar=<?=$cerrar?>';
            document.frm_confirmar.submit();
        }       
        function validar_cambio_cedula(cedula) { 
           
        if (trim(cedula)!='') 
            nuevoAjax('div_datos_registro_civil', 'POST', '../usuarios/validar_datos_registro_civil.php', 'cedula='+cedula);
        else
             nuevoAjax('div_datos_registro_civil', 'POST', '../usuarios/validar_datos_registro_civil.php', 'cedula=999');
        } 
              
    </script>
    <body>
        <form name='frm_confirmar' action="grabar_usuario_ext.php?cerrar=<?=$cerrar?>&accion=<?=$accion?>" method="post">
<?php 
$cod_impresion = 0;

if (isset($_GET['cod_impresion']))
    $cod_impresion = $_GET['cod_impresion'];
if ($cod_impresion!=1)
  graficarTabsCiud();?>
<?php
            
            $ciud->cajaHidden('ciu_codigo', $ciu_codigo);
            $ciud->cajaHidden('ciu_cedula', $ciu_cedula);
            $ciud->cajaHidden('ciu_sincedula', $ciu_sincedula);
            $ciud->cajaHidden('ciu_documento', $ciu_documento);
            $ciud->cajaHidden('ciu_nombre', $ciu_nombre);
            $ciud->cajaHidden('ciu_apellido', $ciu_apellido);
            $ciud->cajaHidden('ciu_titulo', $ciu_titulo);
            $ciud->cajaHidden('ciu_abr_titulo', $ciu_abr_titulo);
            $ciud->cajaHidden('ciu_empresa', $ciu_empresa);
            $ciud->cajaHidden('ciu_cargo', $ciu_cargo);
            $ciud->cajaHidden('ciu_direccion', $ciu_direccion);
            $ciud->cajaHidden('ciu_referencia', $ciu_referencia);
            $ciud->cajaHidden('ciu_email', $ciu_email);
            $ciud->cajaHidden('ciu_telefono', $ciu_telefono);
            $ciud->cajaHidden('ciu_nuevo', $ciu_nuevo);            
            
            $ciud->cajaHidden('ciu_ciudad', $ciu_ciudad);
            if ($modo_solicitud) {
                $ciud->cajaHidden('modo', 'solicitud');
                $ciud->cajaHidden('nurad', preg_replace('/\D/', '', (string)($_GET['nurad'] ?? $_POST['nurad'] ?? '')));
                $ciud->cajaHidden('ent', (int)($_GET['ent'] ?? $_POST['ent'] ?? 0));
                $ciud->cajaHidden('tipo_destinatario', ((int)($_POST['tipo_destinatario'] ?? 1) == 3) ? 3 : 1);
                $ciud->cajaHidden('observacion_solicita', limpiar_sql($_POST['observacion_solicita'] ?? ''));
            }
            
            //echo $sql;
            if (isset($_POST['ciu_apellido']) and $_POST['ciu_apellido']!='')
                $ciu_apellido = limpiar_sql($_POST['ciu_apellido']);
            else
                $ciu_apellido='';
            if (isset($_POST['ciu_nombre']) and $_POST['ciu_nombre']!='')
                $ciu_nombre = limpiar_sql($_POST['ciu_nombre']);
            else
                $ciu_nombre='';
            if (isset($_POST['ciu_empresa']) and $_POST['ciu_empresa']!='')
                $ciu_empresa = limpiar_sql($_POST['ciu_empresa']);
            else
                $ciu_empresa='';
            
            
            $sql = "select count(*) as contusrciu
                    from usuario u where ";
             if ($tmp_cedula!='')
                       $sql .= "usua_cedula = '$tmp_cedula' or ";           
                    $sql.= " ( ";
                    if ($ciu_nombre!='')
                    $sql.= " ( translate(UPPER(usua_nomb),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN') 
                    LIKE translate(upper('%$ciu_nombre%'),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN') ) ";
                    if ($ciu_apellido!='')
                    $sql.= " or ( translate(UPPER(usua_apellido),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN') 
                    LIKE translate(upper('%$ciu_apellido%'),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN') 
                     ) ";
                    if ($ciu_empresa!='')
                    $sql.= " or translate(UPPER(inst_nombre),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN') 
                    LIKE translate(upper('%$ciu_empresa%'),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN') ";
                    $sql.=" ) ";
           
            $sql.= ' and usua_esta = 1';
            $htmlCiu="<table id='tabla_usuario' width='100%' border='1' class='borde_tab'>
                          <tr><td colspan='4' class='titulos4' align='center'>
                            CIUDADANO A CREAR</td></tr>
                          <tr>
                          <td class='titulos2'>Nombres</td>
                          <td class='listado2'>$ciu_nombre</td>
                          <td class='titulos2'>Apellidos</td>
                          <td class='listado2'>$ciu_apellido</td></tr>
                          <tr><td class='titulos2'>Título</td>
                          <td class='listado2'>$ciu_titulo</td>
                          <td class='titulos2'>Puesto</td>
                          <td class='listado2'>$ciu_cargo</td>
                          </tr></table>";
            
            echo '<center><div id="div_datos_registro_civil" style="width: 100%;"></div></center>';
            
            $rs = $db->conn->query($sql);
            $numeroReg = $rs->fields['CONTUSRCIU'];//sql
            $html="";
            if ($cuenta_existente !== null) { // cédula duplicada: no se puede crear
                echo $htmlCiu;
                echo "<br/><center><span class='titulosError'>" . $ciud->mensajeCuentaExistente($cuenta_existente, $tmp_cedula) . "</span></center>";
                if ($cuenta_existente['tipo_usuario'] == 2 && ($cuenta_existente['estado'] ?? 1) == 1 && ($_SESSION["usua_admin_sistema"]==1 || $_SESSION["usua_perm_ciudadano"]==1)) {
                    echo "<br/><center><input type='button' class='botones_largo' value='Editar ciudadano existente' "
                       . "onclick=\"location='adm_usuario_ext.php?accion=2&cerrar=$cerrar&cod_impresion=$cod_impresion&ciu_codigo=" . $cuenta_existente['usua_codi'] . "'\"/></center>";
                }
            } elseif($numeroReg==0) { //si no existen guarda directamente
                $html.="</form></body</html>";
                echo $html;
                die("<script>document.frm_confirmar.submit()</script>");
            }else{//llamo al paginador
                echo $htmlCiu;
                echo "<center><font size='2'><b>Existen Usuarios con datos similares a los que acaba de ingresar</b></font></center>";
                $html.=$ciud->mostrar_div('div_datos_confirmacion');               
                $html.='<center><div id="div_datos_confirmacion" style="width: 100%;"></div></center>';
                echo $html;
                ?>
            <script>realizar_busqueda();</script>
            <?php } 
            ?>
            
            <div id="div_comparar"></div>            
            <br/>
            <center>
            <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
            <?php           
            if ($cuenta_existente === null && ($_SESSION["usua_codi"]==0 || $_SESSION["usua_admin_sistema"]==1 || $_SESSION["usua_perm_ciudadano"]==1)) {
                  ?>
                    <td>
                    <center>
                        <input name="btn_aceptar" type="submit" class="botones_largo" value="<?=$modo_solicitud ? 'Enviar Solicitud' : 'Crear Ciudadano'?>" onClick="return ValidarInformacion('ciu');"/>
                    </center>
                    </td>
             <?php
               }
             ?>
            	<td>
                    <center>
                        <input  name="btn_accion" type="button" class="botones_largo" value="Cancelar" onclick="<?php echo ($cerrar == 'Si') ? "window.close()" : "location='javascript:history.back();'"?>"/>
                    </center>
                </td>
              </tr>
            </table>
            </center>
        </form>
    </body>
</html>
