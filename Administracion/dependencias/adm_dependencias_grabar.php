<?php
/**  Programa para el manejo de gestion documental, oficios, memorandus, circulares, acuerdos
*    Desarrollado y en otros Modificado por la SubSecretaría de Informática del Ecuador
*    Quipux    www.gestiondocumental.gov.ec
*------------------------------------------------------------------------------
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU Affero General Public License as
*    published by the Free Software Foundation, either version 3 of the
*    License, or (at your option) any later version.
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU Affero General Public License for more details.
*
*    You should have received a copy of the GNU Affero General Public License
*    along with this program.  If not, see http://www.gnu.org/licenses. 
*------------------------------------------------------------------------------
**/
/**
*	Autor			Iniciales		Fecha (dd/mm/aaaa)
*
*
*	Modificado por		Iniciales		Fecha (dd/mm/aaaa)
*	Santiago Cordovilla	SC			19-12-2008
*
*	Comentado por		Iniciales		Fecha (dd/mm/aaaa)
*	Santiago Cordovilla	SC			19-12-2008
**/
$ruta_raiz = "../..";
session_start();
if($_SESSION["usua_admin_sistema"]!=1) die("");
include_once(dirname(__DIR__, 2).'/rec_session.php');
include_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once(dirname(__DIR__).'/usuarios_dependencias/area_ajax_grabar.php');
$error = "";
//Permite grabar el area en la base de datos con todos sus atributos
$txt_depe_codi = (isset($_GET['txt_depe_codi'])) ? 0+limpiar_numero($_GET['txt_depe_codi']) : (isset($_POST['txt_depe_codi']) ? 0+limpiar_numero($_POST['txt_depe_codi']) : 0);
$accion = isset($_GET['accion']) ? $_GET['accion'] : (isset($_POST['accion']) ? $_POST['accion'] : 0);
$txt_ok = isset($_POST['txt_ok']) ? $_POST['txt_ok'] : 0;
$txt_nombre = isset($_POST['txt_nombre']) ? limpiar_sql($_POST['txt_nombre']) : '';

if ($txt_ok==1) {
    $record = array();
    $db->conn->BeginTrans();
    if ($txt_depe_codi==0) 
	$txtIdDep = $db->conn->nextId('sec_dependencia');
    else
	$txtIdDep = $txt_depe_codi;
    $record['inst_codi'] = $_SESSION["inst_codi"];
    $record['depe_codi'] = limpiar_numero($txtIdDep);
    $record['depe_nomb'] = $db->conn->qstr(limpiar_sql($_POST['txt_nombre']));
    $record['dep_sigla'] = $db->conn->qstr(limpiar_sql(strtoupper($_POST['txt_sigla'])));
    if ($_POST['txt_estado']!='')
        $record['depe_estado'] = $db->conn->qstr(limpiar_numero ($_POST['txt_estado']));
    else
    $record['depe_estado'] = "1";	//$_POST['Slc_destado'];
    
    $record['depe_pie1'] = $db->conn->qstr(limpiar_numero($_POST['txt_ciudad'])); //se graba la ciudad del area
    $record['depe_codi_padre'] = ($_POST['slc_padre']>0) ? limpiar_numero($_POST['slc_padre']) : $txtIdDep;
    $record['dep_central'] = ($_POST['slc_archivo']>0) ? limpiar_numero($_POST['slc_archivo']) : $txtIdDep;
    $record['depe_plantilla'] = ($_POST['slc_plantilla']>0) ? limpiar_numero($_POST['slc_plantilla']) : $txtIdDep;

    // Instituciones adscritas
    $record['inst_adscrita'] = $_SESSION["inst_codi"];
    $rs_adsc = $db->query("select inst_adscrita from dependencia where depe_codi=".$record['depe_codi_padre']);
    if ($rs_adsc && !$rs_adsc->EOF) $record['inst_adscrita'] = $rs_adsc->fields['INST_ADSCRITA'];

    $gd=obtenerCodigos($_SESSION['usua_codi'],$record['depe_codi'],$db,1);//grabar dependencia
    if ($txt_depe_codi==0)//es nueva    
        $ok1 = $db->conn->Replace("dependencia", $record, "depe_codi", false);
    else//modificar area       
        $ok1 = $db->conn->Replace("dependencia", $record, "depe_codi", false);
    if(!$ok1) $error = "No se pudo crear el área: " . $db->conn->ErrorMsg();

    $ok3 = true;
    $arch_plantilla = trim($_FILES["arch_plantilla"]['tmp_name']);
    if ($arch_plantilla != "") {
        $cab_plantilla = @file_get_contents($arch_plantilla, false, null, 0, 1024);
        if (strpos(ltrim((string)$cab_plantilla), '%PDF-') !== 0) {
            unlink($arch_plantilla);
            $error = "No se pudo cargar la plantilla; el archivo no es un PDF v&aacute;lido.";
            $ok3 = false;
        } else {
            $path_arch = dirname(__DIR__, 2) . "/bodega/plantillas/$txtIdDep.pdf";
            $ok3 = move_uploaded_file($arch_plantilla, $path_arch);
            if(!$ok3) $error = "No se pudo cargar la plantilla para los documentos del &aacute;rea";
        }
    }

    if ($ok1) {
	$db->conn->CommitTrans();
    } else
	$db->conn->RollbackTrans();
}

$mensaje = "";
if ($accion==1) $mensaje = "El área ".$_SESSION["descDependencia"]." $txt_nombre <br/> fue creada correctamente";
if ($accion==2) $mensaje = "Los cambios en el ".$_SESSION["descDependencia"]." $txt_nombre <br/> se realizaron correctamente";
if ($error!="") $mensaje = "Error al crear o modificar el ".$_SESSION["descDependencia"]." $txt_nombre <br> $error";

//guardar dependencia en administracion
$depe_codi_admin = obtenerAreasAdmin($_SESSION["usua_codi"],$_SESSION["inst_codi"],$_SESSION["usua_admin_sistema"],$db);
if (!empty($depe_codi_admin))
    grabar_instancia($txtIdDep,$_SESSION['usua_codi'],$_SESSION['usua_codi'],$record['depe_codi_padre'],$_SESSION['inst_codi'],$db,1);

include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
echo "<!DOCTYPE html>".html_head();
?>

<body>
    <form name="frmConfirmaCreacion" action="adm_dependencias_nuevo.php?accion=2&des_activar=3" method="post">
    <br><br>
    <center>
	<table width="40%" border="2" align="center" class="t_bordeGris">
	    <tr> 
		<td width="100%" height="30" class="listado2">
		    <span class="etexto"><center><b>
                        <?php echo $mensaje; ?>
                    </b></center></span>
		</td> 
	    </tr>
	    <tr>	
		<td height="30" class="listado2">
			<center><input class="botones" type="submit" name="Submit" value="Aceptar"></center>
		</td> 
	    </tr>
	</table>
    </center>
    </form>
</body>
</html>