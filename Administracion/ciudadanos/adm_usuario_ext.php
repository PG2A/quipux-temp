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
require_once(dirname(__DIR__, 2).'/rec_session.php');
if($_SESSION["usua_admin_sistema"]!=1)
    if($_SESSION["usua_perm_ciudadano"]!=1)
    {
        include(dirname(__DIR__, 2)."/funciones_interfaz.php");
        die(html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina."));
    }
require_once(dirname(__DIR__, 2).'/funciones.php');
include(dirname(__DIR__, 2)."/obtenerdatos.php");
include(dirname(__DIR__, 2)."/funciones_interfaz.php");
include_once("util_ciudadano.php");
include_once("../usuarios/mnuUsuariosH.php");

$ciud = New Ciudadano($db);

$ciu_codigo = $ciu_codigo ?? 0;
$usr_codigo = $usr_codigo ?? 0;
// Ensure ciu_cedula is a string to prevent substr(null) deprecation
$ciu_cedula = $ciu_cedula ?? "";
$codigo1 = $codigo1 ?? "";
$cerrar = $cerrar ?? ($_GET['cerrar'] ?? ($_POST['cerrar'] ?? ""));
$accion = $accion ?? ($_GET['accion'] ?? ($_POST['accion'] ?? 0));
$read_cedula = $read_cedula ?? "";
$deshabilitar_campos = $deshabilitar_campos ?? "";
$descEmpresa = $descEmpresa ?? "Empresa";
$descCargo = $descCargo ?? "Cargo";
$cod_impresion = $cod_impresion ?? "";

// Initialize form variables
$ciu_nombre = $ciu_nombre ?? "";
$ciu_apellido = $ciu_apellido ?? "";
$ciu_titulo = $ciu_titulo ?? "";
$ciu_abr_titulo = $ciu_abr_titulo ?? "";
$ciu_empresa = $ciu_empresa ?? "";
$ciu_cargo = $ciu_cargo ?? "";
$ciu_direccion = $ciu_direccion ?? "";
$ciu_referencia = $ciu_referencia ?? "";
$ciu_email = $ciu_email ?? "";
$ciu_telefono = $ciu_telefono ?? "";
$ciu_documento = $ciu_documento ?? "";
$ciu_desactiva = $ciu_desactiva ?? "";

$accion_btn_aceptar = "setTime(ValidarInformacion());";

//$cod_impresion=0+limpiar_sql($_GET['cod_impresion']);//viene del NEW.php
$cod_impresion = filter_input(INPUT_GET, 'cod_impresion', FILTER_VALIDATE_INT) ?: 0;

if ($cod_impresion==1)
    $cerrar='Si';
$ciu_ciudad =0;
    $read = "";
    if ($accion == 1) {	//Nuevo
        $accionForm = "adm_usuario_ext_confirmar.php?cerrar=$cerrar&accion=$accion&cod_impresion=$cod_impresion";
        $tituloForm = "Registrar Datos de Ciudadano";
        $ciu_estado = "checked";
    } else {
        if (!isset($recargar)) {
            $sql = "select * from ciudadano where ciu_codigo=$ciu_codigo";
            $rs = $db->conn->query($sql);
            if ($rs && !$rs->EOF){
            $ciu_cedula             = $rs->fields["CIU_CEDULA"];
            $ciu_documento          = $rs->fields["CIU_DOCUMENTO"];
            $ciu_nombre             = $rs->fields["CIU_NOMBRE"];
            $ciu_apellido           = $rs->fields["CIU_APELLIDO"];
            $ciu_titulo             = $rs->fields["CIU_TITULO"];
            $ciu_abr_titulo         = $rs->fields["CIU_ABR_TITULO"];
            $ciu_empresa            = $rs->fields["CIU_EMPRESA"];
            $ciu_cargo              = $rs->fields["CIU_CARGO"];
            $ciu_direccion          = $rs->fields["CIU_DIRECCION"];
            $ciu_referencia          = $rs->fields["CIU_REFERENCIA"];
            $ciu_email              = $rs->fields["CIU_EMAIL"];
            $ciu_telefono           = $rs->fields["CIU_TELEFONO"];
            $ciu_nuevo              = $rs->fields["CIU_NUEVO"];
            $ciu_estado             = $rs->fields["CIU_ESTADO"];
            $ciu_desactiva          = ($rs->fields["CIU_ESTADO"] == 0) ? "" : "checked";
            $usua_codi_actualiza    = $rs->fields["USUA_CODI_ACTUALIZA"];
            $ciu_fecha_actualiza    = $rs->fields["CIU_FECHA_ACTUALIZA"];
            $ciu_obs_actualiza      = $rs->fields["CIU_OBS_ACTUALIZA"];
            $ciu_ciudad             = $rs->fields["CIUDAD_CODI"];
            if ($ciu_ciudad=='')
                $ciu_ciudad =0;

    //            if (trim($rs->fields["PAIS_CODI"])=='' || trim($rs->fields["PAIS_CODI"])=='null')
//                    $ciu_pais = 25;
//            else
//            $ciu_pais               = $rs->fields["PAIS_CODI"];
//            $ciu_provincia          = $rs->fields["PROVINCIA_CODI"];
//            $ciu_canton             = $rs->fields["CANTON_CODI"];
            //Si el usuario esta inactivo por defecto se mostraran las cajas de texto deshabilitadas
                if($ciu_estado==0){
                    $read_cedula = "readonly";
                    $deshabilitar_campos = "disabled";
                }else{
                    $read_cedula = "";
                    $deshabilitar_campos = "";
                }
            }
        }
    }
    
    if ($accion == 2) { //Modificar
        $accionForm = "grabar_usuario_ext.php?cerrar=$cerrar&accion=$accion&codigo1=$codigo1&cod_impresion=$cod_impresion";
        
        $tituloForm = "Modificar Datos de Ciudadano";
    }
    if ($accion == 3) {	//Consultar
        $accionForm = "";
    	$read = "readonly";
        $tituloForm = "Consultar Datos Ciudadano";
    }


require_once(dirname(__DIR__, 2)."/js/ajax.js");
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');

echo "<!DOCTYPE html>".html_head();
?>
<script type="text/javascript" src="jquerysubir/jquery-1.3.2.min.js"></script>
<script type="text/javascript" language="JavaScript" src="adm_ciudadanos.js"></script>
<script type="text/javascript" language="JavaScript" src="../../js/formchek.js"></script>
<script type="text/javascript" language="JavaScript" src="../../js/validar_cedula.js"></script>
<script type="text/javascript" language="javascript">

function desactivarCiudadano(){
   
    if(document.getElementById('ciu_desactiva').checked) {
        //Para no desactivar el usario o volver a activar
        document.getElementById('desactivar').value = '1';
        document.getElementById('ciu_cedula').value = document.getElementById('ciu_cedula').value.substr(0, 10);
        estado= false;
    }
    else{
        document.getElementById('desactivar').value = '0';        
        estado = true;
    }
        document.getElementById('ciu_nombre').disabled = estado;
        document.getElementById('ciu_apellido').disabled = estado;
        document.getElementById('ciu_cedula').readOnly = estado;
        document.getElementById('ciu_documento').disabled = estado;
        document.getElementById('ciu_titulo').disabled = estado;
        document.getElementById('ciu_abr_titulo').disabled = estado;
        document.getElementById('ciu_empresa').disabled = estado;
        document.getElementById('ciu_cargo').disabled = estado;
        document.getElementById('ciu_direccion').disabled = estado;
        document.getElementById('ciu_telefono').disabled = estado;
        document.getElementById('ciu_email').disabled = estado;
        document.getElementById('ciu_ciudad').disabled = estado;
        document.getElementById('ciu_sincedula').disabled = estado;
        document.getElementById('ciu_password').disabled = estado;
}

function EnableButton(estado,accion){
    if (estado==0 && accion==2)
        document.getElementById('ciu_sincedula').disabled=true;
}


function ValidarInformacion()
{
	
        if(!document.forms[0].ciu_sincedula.checked)
        if (!validarCedula(document.forms[0].ciu_cedula)) {
            alert ('El número de cédula ingresado no es correcto. Por favor intente de nuevo.');
            document.forms[0].ciu_cedula.focus();
	    	return false;
        }
	if(ltrim(document.forms[0].ciu_nombre.value)=='' || ltrim(document.forms[0].ciu_apellido.value)=='') {
            alert("Los campos de Nombres y Apellidos son obligatorios.");
            return false;
        }

        if (document.forms[0].ciu_ciudad.value==0) {
            alert("Por favor seleccione una ciudad de la lista.");
            return false;
        }
        //si hace checked en cambiar contraseña
        if(document.forms[0].ciu_password.checked==true){
            if (document.forms[0].ciu_email.value==''){
                alert("Para cambiar la contraseña, Ingrese el Email");
                return false;
            }else{
                if (!isEmail(document.forms[0].ciu_email.value)){
                alert("Ingrese un Email válido");
                return false;
                }
               }
        }
        
            
        
	return true;
}


function ver_cedula()
{
    document.getElementById('div_datos_registro_civilimg_mas').style.display = 'none';
        document.getElementById('div_datos_registro_civilimg_menos').style.display = '';
    if(document.forms[0].ciu_sincedula.checked){ 
	//document.forms[0].ciu_cedula.style.display='none';        
        document.getElementById('ciu_cedula').disabled = true;
        document.getElementById('div_datos_registro_civil').style.display = 'none';        
    }
    else{
	//document.forms[0].ciu_cedula.style.display='';        
        //document.forms[0].ciu_cedula.readOnly = false;
        document.getElementById('ciu_cedula').disabled = false;
        document.getElementById('div_datos_registro_civil').style.display = '';
    }
}

function cambioMayusculasDoc()
{
	document.frmCrear.ciu_documento.value = document.frmCrear.ciu_documento.value.toUpperCase();
}

function mostrar_div_observacion(accion) {
    if (accion == 1)
        document.getElementById('div_observacion').style.display = '';
    else
        document.getElementById('div_observacion').style.display = 'none';
}



function copiar_datos_registro_civil(campo_rc, campo_usr) {
        try {
            document.getElementById(campo_usr).value = document.getElementById('lbl_datos_rc_'+campo_rc).innerHTML;
        } catch (e) {}
    }
    


</script>


<body onload="ver_cedula();EnableButton('<?=$ciu_estado?>','<?=$accion?>');">
  <form name='frmCrear'  id='frmCrear' action="<?=$accionForm?>" method="post" >
 <input type="hidden" name="usr_codigo" id="usr_codigo" size="40" value='<?=$ciu_codigo?>' />
 <input type="hidden" name="ciu_ciudad" id="ciu_ciudad" size="40" value='<?=$ciu_ciudad?>' />
 <?php 
  if ($cod_impresion==0)
    graficarTabsCiud();?>
    
   <?php echo $ciud->divsInformacionUsrCiud($ciu_cedula);
   
   ?>

    <input type='hidden' name='ciu_codigo' value='<?=$ciu_codigo?>'>
    <table width="100%"><tr><td>
    <div id="div_informacion_ext" name="div_informacion_ext">
        <?php 
        echo "<table width='100%'  class='borde_tab'><tr><td class='listado2'>";
        echo graficarTabsMenuCiud($usr_codigo,1);
        echo "</td></tr></table>";
        ?>
    <table width="100%" border="1"  align="center"  name="usr_datos" id="usr_datos">
        
<?php if ($accion != 1) { ?>
	<tr>
	    <td class="titulos2"> Usuario: </td>
	    <td class="listado2"><?=$ciu_cedula?></td>
	    <?php if ($_SESSION["admin_institucion"]==1) { ?>
                <td class="titulos2">Ciudadano: 
                <td class="listado2">&nbsp;                
                    <input class="tex_area" type="checkbox" name="ciu_desactiva" id="ciu_desactiva" value="1" <?=$ciu_desactiva?> onclick="desactivarCiudadano();">Ciudadano Activo
                    <input type="hidden" value="1" id="desactivar" name="desactivar">
           </td> 
           <?php }else{?>
                <td class="titulos2">&nbsp;</td>
                <td class="listado2">&nbsp;</td>
           <?php }?>
	</tr>
<?php } ?>
    <tr>
            
	    <td class="titulos2" width="15%">* C&eacute;dula/RUC: </td>
	    <td class="listado2" width="35%">
        <?php if($codigo1=="ciu_s"){?>
             
             <input class="caja_texto" type="text" name="ciu_cedula" id="ciu_cedula" value='<?=$ciu_cedula?>' size="20" maxlength="13" readonly <?=$read?> <?=$read_cedula?>>
            
            <input type="checkbox"  disabled  name="ciu_sincedula" id="ciu_sincedula" value="1" onchange="ver_cedula()" <?=$deshabilitar_campos?>
            <?php  if (substr($ciu_cedula,0,2)=="99") echo "checked "; ?>> No tiene No. de c&eacute;dula
        <?php }else{//sin cedula?>
            
            <input class="caja_texto" type="text" name="ciu_cedula" id="ciu_cedula" value='<?=$ciu_cedula?>' size="20" maxlength="13" onChange='openDivciudadano(); validar_cambio_cedulajs();' <?=$read?> <?=$read_cedula?>>
            
            <input type="checkbox"  name="ciu_sincedula" id="ciu_sincedula" value="1" onchange="ver_cedula()" <?=$deshabilitar_campos?>
            <?php  if (substr($ciu_cedula,0,2)=="99") echo "checked "; ?>> No tiene No. de c&eacute;dula
        <?php }
        ?>
        </td>
        <td class="titulos2" width="15%">Otro Documento: </td>
	    <td class="listado2" width="35%">
		<input class="caja_texto" type="text" name="ciu_documento" id="ciu_documento" value='<?=$ciu_documento?>' size="50" maxlength="50" <?=$read?> <?=$deshabilitar_campos?>/>
	    </td>
	</tr>
    <tr>
        <td class="titulos2">* Nombre: &nbsp;&nbsp;&nbsp;
            
               <img src="/iconos/copy.gif" alt="copiar" title="Copiar datos del Registro Civil" onclick="copiar_datos_registro_civil('nombre', 'ciu_nombre')">
            
        </td>
        <td class="listado2">
            <input class="caja_texto" type="text" name="ciu_nombre" id="ciu_nombre" onblur="javascript:changeCase_Articulos(this)" value='<?=$ciu_nombre?>' size="50" maxlength="150" <?=$read?> <?=$deshabilitar_campos?>>
        </td>
        <td class="titulos2">* Apellido: &nbsp;&nbsp;&nbsp;
           
            <img src="/iconos/copy.gif" alt="copiar" title="Copiar datos del Registro Civil" onclick="copiar_datos_registro_civil('nombre', 'ciu_apellido')">
            
        </td>
        <td class="listado2">
            <input class="caja_texto" type="text" name="ciu_apellido" id="ciu_apellido" onblur="javascript:changeCase_Articulos(this)" value='<?=$ciu_apellido?>' size="50" maxlength="150" <?=$read?> <?=$deshabilitar_campos?>>
        </td>
    </tr>
    <tr>
	    <td class="titulos2"> T&iacute;tulo: </td>
	    <td class="listado2">
		<input class="caja_texto" type="text" name="ciu_titulo" id="ciu_titulo" onblur="javascript:changeCase_Articulos(this,'<?=$accion?>')"  value='<?=$ciu_titulo?>' size="50" maxlength="100" <?=$read?> <?=$deshabilitar_campos?>>
	    </td>
	    <td class="titulos2"> Abr. T&iacute;tulo: </td>
	    <td class="listado2">
		<input class="caja_texto" type="text" name="ciu_abr_titulo" id="ciu_abr_titulo" onblur="javascript:changeCase_Articulos(this,'<?=$accion?>')" value='<?=$ciu_abr_titulo?>' size="50" maxlength="30" <?=$read?> <?=$deshabilitar_campos?>>
	    </td>
	</tr>
    	<tr>
	    <td class="titulos2"><?=$descEmpresa?>: </td>
	    <td class="listado2">
                <input class="caja_texto" type="text" name="ciu_empresa" id="ciu_empresa" onblur="this.value=this.value.toUpperCase();"  value='<?=strtoupper($ciu_empresa)?>' size="50" maxlength="150" <?=$read?> <?=$deshabilitar_campos?>>
	    </td>
	    <td class="titulos2"> <?=$descCargo?>: </td>
	    <td class="listado2">
		<input class="caja_texto" type="text" name="ciu_cargo" id="ciu_cargo" onblur="javascript:changeCase_Articulos(this)" value='<?=$ciu_cargo?>' size="50" maxlength="150" <?=$read?> <?=$deshabilitar_campos?>>
	    </td>
	</tr>
    	<tr>
	    <td class="titulos2"> Tel&eacute;fono: </td>
	    <td class="listado2">
		<input class="caja_texto" type="text" name="ciu_telefono" id="ciu_telefono" value='<?=$ciu_telefono?>' size="50" maxlength="50" <?=$read?> <?=$deshabilitar_campos?>>
	    </td>
	    <td class="titulos2"> Email: </td>
	    <td class="listado2">
		<input class="caja_texto" type="text" name="ciu_email" id="ciu_email" value='<?=$ciu_email?>' size="50" maxlength="50" <?=$read?> <?=$deshabilitar_campos?>>
	    </td>
	</tr>
        <tr>
	    <td class="titulos2"> Contrase&ntilde;a: </td>
            <td class="listado2" colspan="3">
                <input type="checkbox" name="ciu_password" id="ciu_password" value="1" <?php echo $read;?> <?=$deshabilitar_campos?>/> Cambiar contrase&ntilde;a
	    </td>
            
        </tr>
        <tr>
        <td class="titulos2">
		Dirección Principal (Barrio/Número)</td>
        <td class="listado2"><input class="caja_texto" type="text" name="ciu_direccion" id="ciu_direccion" onblur="javascript:changeCase_Articulos(this)" value='<?=$ciu_direccion?>' size="50" maxlength="150">
                   </td>
          <td class="titulos2">Referencia (Calles/Transversales)</td><td class="listado2"><input class="caja_texto" type="text" name="ciu_referencia" id="ciu_referencia" onblur="javascript:changeCase_Articulos(this)" value='<?=$ciu_referencia?>' size="50" maxlength="150">
	    </td></tr>
        <tr><td colspan="4">
          <?php include_once(dirname(__DIR__).'/catalogos/ciudad_buscar.php')?></td>
      </tr>
    </table>
    </div>
            </td></tr>
    <tr><td>
    <div id="div_historico_ext" name="div_historico_ext" style="display:none">
        <table width="100%">
            <tr><td>
                  <?php   
                     echo "<table width='100%' class='borde_tab'><tr><td class='listado2'>";
                    echo graficarTabsMenuCiud(0,2);
                    echo "</td></tr></table>";
                    echo $ciud->verHistorico($ciu_codigo,1,'1,4');
        ?>  
                </td></tr>
        </table>
      </div>
    <br></br>
    <center>
        </td></tr></table>
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr>
    	<?php  if ($accion == 1 ) {?>          
           <td><center><input name="btn_aceptar" id="btn_aceptar" type="submit" class="botones"  title="Almacena los cambios realizados." value="Aceptar" onClick="return ValidarInformacion();"/></center></td>           
            <?php }elseif($accion==2) {
              ?>
                  <td><center><input name="btn_aceptar" id="btn_aceptar" type="submit" class="botones"  title="Almacena los cambios realizados" value="Aceptar" onClick="return ValidarInformacion();"/></center></td>
             
        <?php }elseif($accion==3 and $_SESSION["usua_perm_ciudadano"]==1){?>
            <td><center><input name="btn_aceptar" id="btn_aceptar" style="visibility: hidden" type="submit" class="botones"  title="Almacena los cambios realizados" value="Aceptar" onClick="return ValidarInformacion();"/></center></td>
        <?php }?>
    	<td>

        <?php if($codigo1=="ciu_s"){?>
                <input  name="btn_accion" type="hidden" class="botones" title="Regresa a la página anterior sin guardar los cambios" value="Regresar"/>
        <?php }else if($accion==2 or $accion==3){?>
         <center><input  name="btn_accion" type="button" class="botones" title="Regresa a la página anterior sin guardar los cambios" value='<?php echo ($cerrar == 'Si') ? "Cerrar" : "Regresar"?>' onclick="<?php echo ($cerrar == 'Si') ? "window.close()" : "location='cuerpoUsuario_ext.php?cerrar=$cerrar&accion=$accion'"?>"/></center>
        <?php }else{?>
         <center><input  name="btn_accion" type="button" class="botones" title="Regresa a la página anterior sin guardar los cambios" value="Regresar" onclick="<?php echo ($cerrar == 'Si') ? "window.close()" : "location='cuerpoUsuario_ext.php?cerrar=$cerrar'"?>"/></center>
        <?php }?>
	</td>
      </tr>
      
    </table>
        </center>
    
    
    
  </form>
    <?php if ($ciu_codigo!=''){ ?>
    <script>
    mostrar_div_historico("div_historico",<?=$ciu_codigo?>,'1','1,4');
    </script>
    <?php } ?>
    <script language="javascript" type="">
        validar_cambio_cedulajs();
    </script>

</body>
</html>
