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
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
if($_SESSION["usua_admin_sistema"]!=1 and $_SESSION["usua_perm_ciudadano"]!=1) {
    echo html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
    die("");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php"); //para traer funciones p_get y p_post
require_once(dirname(__DIR__, 2).'/obtenerdatos.php'); //formar la observacion de edicion
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once("../ciudadanos/util_ciudadano.php");

$ciud = New Ciudadano($db);
$record = array();
$recargar=true;
$ruta_raiz = dirname(__DIR__, 2);
require_once(dirname(__DIR__, 2).'/config.php');

$tmp_cedula = $ciu_cedula;
if (!isset($ciu_nuevo)) $ciu_nuevo = 1;
$ciu_password = $ciu_password ?? 0;
$mensaje = $mensaje ?? "";
$codigo1 = $codigo1 ?? "";
$ciu_sincedula = $ciu_sincedula ?? ($_POST['ciu_sincedula'] ?? 0);
$nombre_servidor = $nombre_servidor ?? "";
$cuenta_mail_soporte = $cuenta_mail_soporte ?? "";

if (!isset($ciu_password)) {
    $ciu_nuevo = 0;
}
//variable validar en servidor el grabar
$grabar_ciu = 1;
// Verificar si se va a insertar (accion = 1) o actualizar (else) a un ciudadano.
// En el caso de que el ciudadano no ingrese su numero de cedula se genera un numero automaticamente igual a 9999999999 menos el codigo del usuario
$flag_copiar_contrasena = false;

if ($accion==1) {
    $record["inst_codi"] = $_SESSION["inst_codi"];

    $ciu_codigo = $db->nextId("usuarios_usua_codi_seq");
    $flag_copiar_contrasena = true;

    $ciu_nuevo = 0;
    $mensajeCorreo = "Se ha creado un usuario en el sistema QUIPUX como ciudadano con la siguiente información:";
} else {

    // Valido el cambio de contraseña segun la cedula actual del usuario
    $sql = "select usua_cedula from usuario where usua_codi=$ciu_codigo";
    $rs= $db->conn->query($sql);
    if ($rs && !$rs->EOF && $rs->fields['USUA_CEDULA']!=$tmp_cedula) {
        $flag_copiar_contrasena = true;
    }
    if ($ciu_password == 1) $flag_copiar_contrasena = true;
    $mensajeCorreo = "Se han realizado los siguientes cambios en la información personal de su usuario:";
}


if ($ciu_sincedula==1)
    $tmp_cedula = 9999999999-$ciu_codigo;
if (substr($tmp_cedula,0,2)=="99" or trim($tmp_cedula)=="")
    $tmp_cedula = 9999999999-$ciu_codigo;

if ($flag_copiar_contrasena) {
    $sql = "select usua_pasw from usuario where usua_nuevo=1 and usua_esta=1 and usua_cedula='$tmp_cedula' and usua_codi<>$ciu_codigo";
    $rs= $db->conn->query($sql);
    if ($rs && !$rs->EOF) {
        $record["ciu_pasw"] = $db->conn->qstr($rs->fields['USUA_PASW']);
        $ciu_nuevo=1;
        $mensaje = "<b>La contrase&ntilde;a registrada es la que se encuentra definida para las otras cuentas del usuario.</b><br>";
    }
}


// Verifico si existen usuarios o ciudadanos creados con el mismo numero de cedula
$sql = "select * from usuario where usua_cedula='$tmp_cedula'";


if(isset ($_POST["desactivar"]))
    $desactivar = $_POST["desactivar"];
else
    $desactivar = "1";

$record["ciu_codigo"]       = $ciu_codigo;

if($desactivar==0 && (!isset($_POST['ciu_desactiva']) || $_POST['ciu_desactiva']==null)){
    //Si se va ha desactivar el ciudadano se modifica el numero de cedula y el login
    $tmp_cedula = substr($tmp_cedula,0,10)."-$ciu_codigo";
    $ciu_estado = "0";

    $record["ciu_estado"]       = $ciu_estado;
    $record["ciu_cedula"]       = $db->conn->qstr(limpiar_sql(trim($tmp_cedula)));

}
else
{
    //$tmp_cedula = substr($tmp_cedula,0,10);
    $ciu_estado = "1";

    $record["ciu_estado"]       = $ciu_estado;
    $record["ciu_cedula"]       = $db->conn->qstr(limpiar_sql(trim($tmp_cedula)));
    $record["ciu_documento"]    = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($ciu_documento))));
    
    if (trim($ciu_nombre)=='') 
        $grabar_ciu=0;
    $record["ciu_nombre"]       = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($ciu_nombre))));
    
    if (trim($ciu_apellido)=='') 
        $grabar_ciu=0;
    
    $record["ciu_apellido"]    = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($ciu_apellido))));
    $record["ciu_titulo"]      = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($ciu_titulo))));           
    
    $record["ciu_abr_titulo"]   = $db->conn->qstr(substr(limpiar_sql(trim($ciud->caracterEspecial($ciu_abr_titulo))),0,30));
    $record["ciu_empresa"]      = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($ciu_empresa))));    
    
    $record["ciu_cargo"]        = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($ciu_cargo))));
    
    $record["ciu_direccion"]    = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($ciu_direccion))));
    
    $record["ciu_email"]        = $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($ciu_email))));

    $record["ciu_telefono"]     = $db->conn->qstr(substr(limpiar_sql(trim($ciud->caracterEspecial($ciu_telefono))),0,50));
    
    $record["ciu_referencia"]   =  $db->conn->qstr(limpiar_sql(trim($ciud->caracterEspecial($ciu_referencia))));
      if ($ciu_ciudad!='')
        $record["ciudad_codi"] = limpiar_sql(trim($ciu_ciudad));
      else
           $record["ciudad_codi"] = limpiar_sql(trim($old_ciudad));
    

    
    if (trim($ciu_nuevo)!="")  $record["ciu_nuevo"] = "$ciu_nuevo";
   
}


//Datos del usuario que modifico al ciudadano la ultima ves.
$record["usua_codi_actualiza"] = $_SESSION['usua_codi'];
$record["ciu_fecha_actualiza"] = "CURRENT_TIMESTAMP";

//Armar observacion de campos modificados
if($accion == 1)
    $record["ciu_obs_actualiza"] =  "'Registro Nuevo'";
else
    $record["ciu_obs_actualiza"] = "'".ObtenerObservacionCiudadano($ciu_codigo, $record, $db)."'";
    if ($grabar_ciu==1){
        $sql="select * from ciudadano where ciu_codigo = $ciu_codigo";    
        $rs_old=$db->conn->Execute($sql);    
        $ok1 = $db->conn->Replace("ciudadano", $record, "ciu_codigo", false, false);    
        if (!$ok1) {
            $grabar_ciu = 0;
            $mensaje .= "Error al guardar en base de datos: " . $db->conn->ErrorMsg();
        }
        $rs_new=$db->conn->Execute($sql);
        //echo $sql;
        if (isset($rs_old->fields["ciu_pasw"])) unset ($rs_old->fields["ciu_pasw"]);
        if (isset($rs_new->fields["ciu_pasw"])) unset ($rs_new->fields["ciu_pasw"]);
        if ($grabar_ciu==1) $ciud->grabar_log_tabla('LOG_USR_CIUDADANOS',$rs_old, $rs_new, $_SESSION['usua_codi'],1);    
    }
    //Si son ciudadanos con nombre homónimos no modificar el ciudadano existente crear nuevo y eleminar de la tabla tmp.
    
    $upSql="update ciudadano_tmp set ciu_estado = 0 where ciu_codigo=$ciu_codigo";
    
    $db->conn->query($upSql);
    
    // Cambiamos la contraseña del usuario y le mandamos un mail
    if ($ciu_nuevo==0 and trim($ciu_email)!="") {
        $usr_tipo = 2;
        $usr_codigo = $ciu_codigo;
        $usr_nombre = $ciu_nombre . " " . $ciu_apellido;
        $usr_login = "U".$tmp_cedula;
        $usr_cedula = $tmp_cedula;
        $usr_email = $ciu_email;
        include(dirname(__DIR__).'/usuarios/cambiar_password_mail.php');
    
    }
    
    if (trim($ciu_email)!="") {
        
        $mail = "<!DOCTYPE html><title>Informaci&oacute;n Quipux</title>";
        $mail .= "<body><center><h1>QUIPUX</h1><br /><h2>Sistema de Gesti&oacute;n Documental</h2><br /><br /></center>";
        $mail .= "Estimado(a) $ciu_nombre $ciu_apellido.<br /><br />";
        $mail .= $mensajeCorreo;
        $mail .= "<br /><br />";
        $mail .= "<table border='0'>
                  <tr><td><b>C&eacute;dula:</b></td><td>$tmp_cedula</td></tr>
                  <tr><td><b>Nombre:</b></td><td>$ciu_nombre</td></tr>
                  <tr><td><b>Apellido:</b></td><td>$ciu_apellido</td></tr>
                  <tr><td><b>Abr. T&iacute;tulo:</b></td><td>$ciu_abr_titulo</td></tr>
                  <tr><td><b>T&iacute;tulo:</b></td><td>$ciu_titulo</td></tr>
                  <tr><td><b>Instituci&oacute;n:</b></td><td>$ciu_empresa</td></tr>
                  <tr><td><b>Puesto:</b></td><td>$ciu_cargo</td></tr>
                  <tr><td><b>Direcci&oacute;n:</b></td><td>$ciu_direccion</td></tr>
                  <tr><td><b>E-mail:</b></td><td>$ciu_email</td></tr>    
                  <tr><td><b>Referencia:</b></td><td>$ciu_referencia</td></tr>
                  
                  </table>";
        $mail .= "<br /><br />Le recordamos que para acceder al sistema deber&aacute; hacerlo con el usuario &quot;$tmp_cedula&quot;
                  ingresando a <a href='$nombre_servidor' target='_blank'>$nombre_servidor</a>";
        $mail .= "<br /><br />Saludos cordiales,<br /><br />Soporte Quipux.";
        $mail .= "<br /><br /><b>Nota: </b>Este mensaje fue enviado autom&aacute;ticamente por el sistema, por favor no lo responda.";
        $mail .= "<br />Si tiene alguna inquietud respecto a este mensaje, comun&iacute;quese con <a href='mailto:$cuenta_mail_soporte'>$cuenta_mail_soporte</a>";
        $mail .= "</body></html>";
        if ($ciu_nuevo==1)
            enviarMail($mail, "Quipux: Actualización de datos.", $ciu_email, "$ciu_nombre $ciu_apellido", $ruta_raiz);
        else
            enviarMail($mail, "Quipux: Creación de Ciudadano.", $ciu_email, "$ciu_nombre $ciu_apellido", $ruta_raiz);
    }
    
    if (isset($_POST["ciu_codigo_eliminar"])) {
        if ($_POST["ciu_codigo_eliminar"]!=$ciu_codigo) {
            $ciu_codigo_eliminar = 0+limpiar_sql($_POST["ciu_codigo_eliminar"]);
            
            
            //movemos los documentos en los que el ciudadano es el remitente
            //se cambia el select para buscar en el indice con array
            $sql = "select radi_nume_radi, radi_usua_rem from radicado where 
            string_to_array(trim(both '-' from radi_usua_rem), '--') @> array['$ciu_codigo_eliminar']";
            
            $rs = $db->conn->query($sql);
            while (!$rs->EOF) {
                unset ($record);
                $record["radi_nume_radi"] = $rs->fields["RADI_NUME_RADI"];
                $record["radi_usua_rem"] = $db->conn->qstr(str_replace("-$ciu_codigo_eliminar-","-$ciu_codigo-",$rs->fields["RADI_USUA_REM"]));
                $ok1 = $db->conn->Replace("radicado", $record, "radi_nume_radi", false, false);
                $rs->MoveNext();
            }
    
            //movemos los documentos en los que el ciudadano es el destinatario        
            //movemos los documentos en los que el ciudadano es el remitente
            //se cambia el select para buscar en el indice con array
            $sql = "select radi_nume_radi, radi_usua_dest from radicado where 
            string_to_array(trim(both '-' from radi_usua_dest), '--') @> array['$ciu_codigo_eliminar']";
            $rs = $db->conn->query($sql);
            while (!$rs->EOF) {
                unset ($record);
                $record["radi_nume_radi"] = $rs->fields["RADI_NUME_RADI"];
                $record["radi_usua_dest"] = $db->conn->qstr(str_replace("-$ciu_codigo_eliminar-","-$ciu_codigo-",$rs->fields["RADI_USUA_DEST"]));
                $ok1 = $db->conn->Replace("radicado", $record, "radi_nume_radi", false, false);
                $rs->MoveNext();
            }
    
            //movemos los documentos en los que el ciudadano tiene copias (cca)        
            //se cambia el select para buscar en el indice con array
            $sql = "select radi_nume_radi, radi_cca from radicado 
            where string_to_array(trim(both '-' from radi_cca), '--') 
            @> array['$ciu_codigo_eliminar']";
            $rs = $db->conn->query($sql);
            while (!$rs->EOF) {
                unset ($record);
                $record["radi_nume_radi"] = $rs->fields["RADI_NUME_RADI"];
                $record["radi_cca"] = $db->conn->qstr(str_replace("-$ciu_codigo_eliminar-","-$ciu_codigo-",$rs->fields["RADI_CCA"]));
                $ok1 = $db->conn->Replace("radicado", $record, "radi_nume_radi", false, false);
                $rs->MoveNext();
            }
            // desactivamos el usuario
    
            $sql = "select ciu_cedula, ciu_email from ciudadano where ciu_codigo=$ciu_codigo_eliminar";
            $rs = $db->conn->query($sql);
            $old_cedula = $rs->fields["CIU_CEDULA"];
    
            unset ($record);
            $record["ciu_codigo"] = "$ciu_codigo_eliminar";
            $record["ciu_estado"] = "0";
            $record["ciu_cedula"] = $db->conn->qstr("$old_cedula-$ciu_codigo_eliminar");
    
            //Datos del usuario que modifico al ciudadano la ultima ves.
            $record["usua_codi_actualiza"] = $_SESSION['usua_codi'];
            $record["ciu_fecha_actualiza"] = "CURRENT_TIMESTAMP";
            
            
            $sql="select * from ciudadano where ciu_codigo = $ciu_codigo_eliminar";    
            $rs_old=$db->conn->Execute($sql);    
            $ok1 = $db->conn->Replace("ciudadano", $record, "ciu_codigo", false, false);    
        $rs_new=$db->conn->Execute($sql);
        unset ($rs_old->fields["CIU_PASW"]);
        unset ($rs_new->fields["CIU_PASW"]);
        $ciud->grabar_log_tabla('LOG_USR_CIUDADANOS',$rs_old, $rs_new, $_SESSION['usua_codi'],2);
        
        
        

        $mail = "<!DOCTYPE html><title>Informaci&oacute;n Quipux</title>";
        $mail .= "<body><center><h1>QUIPUX</h1><br /><h2>Sistema de Gesti&oacute;n Documental</h2><br /><br /></center>";
        $mail .= "Estimado(a) $ciu_nombre $ciu_apellido.<br /><br />";
        $mail .= "Se ha unificado la información de los usuarios &quot;$old_cedula&quot; y &quot;$tmp_cedula&quot; en uno solo.<br /><br />";
        $mail .= "Todos los documentos pertenecientes al usuario &quot;$old_cedula&quot; fueron movidos a las bandejas del usuario &quot;$tmp_cedula&quot; y el usuario &quot;$old_cedula&quot; ha sido desactivado.<br /><br />";
        $mail .= "Le recordamos que para acceder al sistema deber&aacute; hacerlo con el usuario &quot;$tmp_cedula&quot;
                  ingresando a <a href='$nombre_servidor' target='_blank'>$nombre_servidor</a>";
        $mail .= "<br /><br />Saludos cordiales,<br /><br />Soporte Quipux.";
        $mail .= "<br /><br /><b>Nota: </b>Este mensaje fue enviado autom&aacute;ticamente por el sistema, por favor no lo responda.";
        $mail .= "<br />Si tiene alguna inquietud respecto a este mensaje, comun&iacute;quese con <a href='mailto:$cuenta_mail_soporte'>$cuenta_mail_soporte</a>";
        $mail .= "</body></html>";
        if (trim($ciu_email)!="")
            if ($grabar_ciu==1)
            enviarMail($mail, "Quipux: Actualización de datos.", $ciu_email, "$ciu_nombre $ciu_apellido", $ruta_raiz);
        if (trim($rs->fields["CIU_EMAIL"])!="" && $rs->fields["CIU_EMAIL"]!=$ciu_email)
                 if ($grabar_ciu==1)
            enviarMail($mail, "Quipux: Actualización de datos.", $rs->fields["CIU_EMAIL"], "$ciu_nombre $ciu_apellido", $ruta_raiz);
 

    }
}

echo "<!DOCTYPE html>".html_head();
?>
<body>
    <br><br>
    <?php
     if ($grabar_ciu==0){
         ?>
    <center>
        <h3><?php echo $mensaje; ?></h3>
        <table width="40%" border="2" align="center" class="t_bordeGris">
	    <tr> 
                <td width="100%" height="30" class="listado2">
                    Existió un problema al guardar el ciudadano, comuníquese con el Administrador
                    del Sistema.
                    <center><input class="botones" type="button" name="Atras" value="Aceptar" onclick="window.location='../usuarios/mnuUsuarios_ext.php';"/></center>
                </td>
            </tr>
    </table></center>
     <?php      
     }else{
    ?>
    <center>        
        <?=$mensaje?><br>
	<table width="40%" border="2" align="center" class="t_bordeGris">
	    <tr> 
		<td width="100%" height="30" class="listado2">
		<?php
		    if ($accion==1) {?>
		    <span class=etexto><center><B>El ciudadano <?="$ciu_nombre $ciu_apellido"?><br/>fue creado correctamente con el usuario &quot;<?=$tmp_cedula?>&quot;</B></center></span>
		<?php } else {
                 
                 ?>
                <span class=etexto><center><B>Los cambios en el ciudadano <?="$ciu_nombre $ciu_apellido"?>
                            <br/> se realizaron correctamente con el usuario &quot;<?=$tmp_cedula?>&quot;</B></center></span>
		<?php } ?>
		</td> 
	    </tr>
	    <tr>	
		<td height="30" class="listado2">
            <?php           
            if($codigo1=="ciu_s"){                
                ?>
                <center><input  name="btn_accion" type="button" class="botones" title="Cerrar" value="Cerrar" onclick="window.close();"></center>
            <?php }elseif($accion==2){
                $cod_impresion = "'".$_GET['cod_impresion']."'";
                ?>
                <center><input class="botones" type="submit" name="Submit" value="Aceptar" onclick="<?php echo ($cerrar == 'Si') ? "window.opener.refrescar_pagina('OI',".$cod_impresion."); window.close();" : "location='cuerpoUsuario_ext.php?cerrar=$cerrar&accion=2'"?>"/></center>
            <?php }else{
                 
                ?>                                                                                                                                                                
                <center><input class="botones" type="submit" name="Submit" value="Aceptar" onclick="<?php echo ($cerrar == 'Si') ? "window.close()" : "location='cuerpoUsuario_ext.php?cerrar=$cerrar&accion=2'"?>"/></center>
            <?php } ?>
		</td> 
	    </tr>
	</table>
       
    </center>
<?php } ?>
</body>
</html>
