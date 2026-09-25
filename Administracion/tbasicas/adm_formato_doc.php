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
if($_SESSION["usua_admin_sistema"]!=1) {
    die("");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
include_once(dirname(__DIR__, 2).'/funciones.php'); //para traer funciones p_get y p_post
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');

p_register_globals(array());
$ADODB_COUNTRECS = false;
$depe_actu = isset($_POST['depe_actu']) ? $_POST['depe_actu'] : 0;

//include __DIR__."/radicacion/crea_combos_universales.php";
if (isset($_POST['btn_accion']) && $_POST['btn_accion']=="Aceptar") {
    $registro = array();
    //$sql="delete from formato_numeracion where depe_codi=$depe_actu";
    //$db->conn->Execute($sql);
    if (isset($_POST['numreg'])) $numreg = $_POST['numreg'];
    if (!isset($numreg)) $numreg = 0;
    for ($num=0;$num<$numreg;$num++) {
        unset($registro);
        if (!isset($_POST["depe_doc_$num"])) continue; 
        $depe = $_POST["depe_doc_$num"];
        $registro["depe_codi"]=$depe_actu;
        $registro["depe_numeracion"]=$depe;
        $registro["fn_tiporad"] = isset($_POST["tipo_$num"]) ? $_POST["tipo_$num"] : 0;
        //--$db->conn->Execute("update dependencia set depe_rad_tp$num=$depe where depe_codi=$depe_actu");
        if ($depe_actu==$depe) {
            if (isset($_POST["txt_$num"]) && $_POST["txt_$num"]!="") 	$registro["fn_formato"] = $db->conn->qstr($_POST["txt_$num"]);
            if (isset($_POST["txtip_$num"]) && $_POST["txtip_$num"]!="") 	$registro["fn_abr_texto"]   = $db->conn->qstr($_POST["txtip_$num"]);
            if (isset($_POST["sep_$num"]) && $_POST["sep_$num"]!="") 	$registro["fn_caracter"]= $db->conn->qstr($_POST["sep_$num"]);
            $registro["fn_num_consec"] = isset($_POST["dsec_$num"]) ? $_POST["dsec_$num"] : 4;
            $registro["fn_num_anio"]   = isset($_POST["danio_$num"]) ? $_POST["danio_$num"] : 4;
            $sql = "select fn_contador from formato_numeracion where depe_codi=$depe_actu and fn_tiporad=". $registro["fn_tiporad"] ." for update";
            $rs = $db->conn->Execute($sql);
            
            $sec_num = isset($_POST["sec_$num"]) ? $_POST["sec_$num"] : 0;
            $fn_contador = ($rs && !$rs->EOF) ? $rs->fields["FN_CONTADOR"] : 0;
            
            if ($sec_num > $fn_contador) $registro["fn_contador"] = $sec_num;
                else $registro["fn_contador"] = $fn_contador;
        }
        $insertSQL = $db->conn->Replace("FORMATO_NUMERACION", $registro, array("depe_codi","fn_tiporad"), false,false,true,false);
    }
}



echo "<!DOCTYPE html>".html_head();
?>
<script language="JavaScript" src="../../js/crea_combos_2.js"></script>
<script language="JavaScript" src="../../js/formchek.js"></script>

<script language="Javascript">
function ver_datos(depe,num)
{	
      //alert(depe + <?=$depe_actu?>);
       <?php   if( isset( $depe_actu ) && !empty( $depe_actu )  ):  ?>
	   <?php     echo "var depe_actu=$depe_actu;" ?>
       <?php   else:     ?> 
 	   <?php     echo "var depe_actu;" ?>
       <?php   endif;         ?>       	
	accion='none';
	if (depe==depe_actu)
		accion='';
	document.getElementById('txtv_'+num).style.display=accion;
	document.getElementById('btn_A_'+num).style.display=accion;
	document.getElementById('btn_B_'+num).style.display=accion;
	document.getElementById('slc_form_'+num).style.display=accion;
	document.getElementById('txtip_'+num).style.display=accion;
	document.getElementById('sep_'+num).style.display=accion;
	document.getElementById('danio_'+num).style.display=accion;
	document.getElementById('dsec_'+num).style.display=accion;
	document.getElementById('sec_'+num).style.display=accion;
}

function ValidarInformacion(dato)
{
	for (num=0;num<dato;num++)
	{
		if (!(isPositiveInteger(document.getElementById('danio_'+num).value,false)))
		{
			alert('El número de dígitos del año debe ser un número');
			document.getElementById('danio_'+num).focus();
			return false;
		}
		if (!(isPositiveInteger(document.getElementById('dsec_'+num).value,false)))
		{
			alert('El número de dígitos del secuencial debe ser un número');
			document.getElementById('dsec_'+num).focus();
			return false;
		}
		if (!(isNonnegativeInteger(document.getElementById('sec_'+num).value,false)))
		{
			alert('El número secuencial debe ser un número');
			document.getElementById('sec_'+num).focus();
			return false;
		}
		if (document.getElementById('danio_'+num).value > 4)
		{
			alert('El número de dígitos del año no debe ser mayor que cuatro');
			document.getElementById('danio_'+num).focus();
			return false;
		}
	}
	return true;
}

function validar_numero(e) {
	tecla = (document.all)?e.keyCode:e.which;
	if (tecla==8) return true;
	patron = /\d/;
	te = String.fromCharCode(tecla);
	return patron.test(te); 
}

function AnadirFormato(num, accion)
{
//alert ("hola"+num+accion);
    if (accion=='B') {
	var str = Array();
	var str2 = Array();
	str1  = document.getElementById('txt_'+num).value.split('-');
	str2  = document.getElementById('txtv_'+num).value.split('-');
	tmp = ''; cad1=''; cad2='';
	for (i=0 ; i<str1.length-1 ; i++) {
	    cad1 += tmp + str1[i];
	    cad2 += tmp + str2[i];
	    tmp = '-'; 
	}
	document.getElementById('txt_'+num).value = cad1;
	document.getElementById('txtv_'+num).value = cad2;
    }
    if (accion=='A') {
	if (document.getElementById('txt_'+num).value == '')
	    separa='';
	else
	    separa='-';

	str = document.getElementById('slc_form_'+num).value;
	if (document.getElementById('txt_'+num).value.indexOf(str) < 0) {
	    document.getElementById('txt_'+num).value += separa + document.getElementById('slc_form_'+num).value;

	    if (str == "inst") txtv = "Institucion";
	    if (str == "dep") txtv = "Area";
	    if (str == "anio") txtv = "Año";
	    if (str == "secuencial") txtv = "Secuencial";
	    if (str == "tipodoc") txtv = "Ab. Documento";

	    document.getElementById('txtv_'+num).value += separa + txtv;
	}

    }
}
</script>

<body>
<center>
<form action=""  name="formulario" id="formulario" method="post">
<table width="100%"  class="borde_tab" >
<tr>
	<td colspan="6"   width="100%"  height="40" align="center" class="titulos4"><b>Formato de la Numeraci&oacute;n de  <?=(isset($_SESSION["descRadicado"]) ? $_SESSION["descRadicado"] : "Radicado")?></b></td>
</tr>
<tr >
	<td width="25%" align="left" class="titulos2"><b>&nbsp;Seleccione <?=$_SESSION["descDependencia"]?></b></td>
	<td width="75%" colspan="5" class="listado2">
<?php
	//$sql = "select DEPE_NOMB, DEPE_CODI from dependencia where depe_estado=1 and inst_codi=".$_SESSION["inst_codi"]." order by depe_nomb";
	//$rs=$db->conn->query($sql);
        $depe_codi_admin = obtenerAreasAdmin($_SESSION["usua_codi"],$_SESSION["inst_codi"],$_SESSION["usua_admin_sistema"],$db);
                $sql = "select depe_nomb, depe_codi from dependencia where depe_estado=1 and inst_codi=" . (isset($_SESSION["inst_codi"]) ? $_SESSION["inst_codi"] : 0);
                if (!empty($depe_codi_admin))
                $sql.=" and depe_codi in ($depe_codi_admin)"; 
                $sql.= " order by 1 asc";
                $rs=$db->conn->query($sql);
    if ($rs && !$rs->EOF) {
        echo "<select name='depe_actu' class='select' Onchange='document.formulario.submit()'>";
        echo "<option value='0'>:<< seleccione >></option>";
        while (!$rs->EOF) {
            $selected = ($depe_actu == $rs->fields['DEPE_CODI']) ? "selected" : "";
            echo "<option value='".$rs->fields['DEPE_CODI']."' $selected>".$rs->fields['DEPE_NOMB']."</option>";
            $rs->MoveNext();
        }
        echo "</select>";
    } else {
        $errorMsg = $db->conn->ErrorMsg();
        echo "<select class='select' name='depe_actu'><option value='0'>Error: $errorMsg</option></select>";
        // echo "<!-- SQL: $sql -->"; 
    }
?>
	</td>
</tr>
</table>
<?php if (!$depe_actu) die();?>
<br>
<table width="100%"  class="borde_tab">
    <tr>
	<td width="10%" align="center" class="titulos2"><b>Clase de Documento</b></td>
	<td width="15%" align="center" class="titulos2"><b>Copiar Formato del  &Aacute;rea</b></td>
	<td width="35%" align="center" class="titulos2"><b>Formato</b></td>
	<td width="8%" align="center" class="titulos2"><b>Abreviaci&oacute;n del Documento</b></td>
	<td width="8%" align="center" class="titulos2"><b>Separador</b></td>
	<td width="8%" align="center" class="titulos2"><b>Nº. Digitos Año</b></td>
	<td width="8%" align="center" class="titulos2"><b>Nº. Digitos Secuencial</b></td>
	<td width="8%" align="center" class="titulos2"><b>Secuencia Actual</b></td>
    </tr>
<?php
    $acceso_ciudadano_inst = isset($acceso_ciudadano_inst) ? $acceso_ciudadano_inst : 0;
    $where_tipo_ciudadano = "";
    if(isset($_SESSION['inst_codi']) && $_SESSION['inst_codi']==$acceso_ciudadano_inst)
        $where_tipo_ciudadano = " and trad_codigo in ($tipo_doc_ciudadano)";

    $sql = "select t.trad_codigo, f.depe_codi, t.trad_descr, f.fn_abr_texto, f.fn_formato,
            f.fn_caracter, f.fn_num_consec, f.fn_num_anio, f.fn_contador, f.depe_numeracion
            from (select * from tiporad where trad_estado=1 and trad_inst_codi in (0,".$_SESSION["inst_codi"].") $where_tipo_ciudadano) as t
            left outer join (select * from formato_numeracion where depe_codi=$depe_actu) as f on t.trad_codigo=f.fn_tiporad
            order by trad_codigo";
    $rs2=$db->conn->query($sql);
//echo $sql;
	$numreg = 0;
      	$num = 0;
	while(!$rs2->EOF) {
//	    $tipo_doc=$rs2->fields["TRAD_CODIGO"];
	    //--$sql = "select depe_rad_tp$num as dr from dependencia where depe_codi=$depe_actu";
            $depe_doc=$rs2->fields["DEPE_NUMERACION"];
	    if (!$depe_doc) $depe_doc=$depe_actu;
	    echo "<tr><td align='Left' class='listado2'><b>".$rs2->fields["TRAD_DESCR"]."</b></td>";
	    echo "<td align='center' class='listado2'><b>"
		.$rs->GetMenu2("depe_doc_$num", $depe_doc, "", false,"","class='select' Onchange='ver_datos( this.value  ,$num)'")
		."</b></td>";
	    $rs->Move(0);
	    $tmp=explode("-",(string)$rs2->fields["FN_FORMATO"]);
	    $tmp_sep="";
	    $txtv="";
	    for ($i=0;$i<count($tmp);++$i) {
		if ($txtv!="") $tmp_sep="-";
		if ($tmp[$i]=="inst") $txtv .= $tmp_sep."Institucion";
		if ($tmp[$i]=="dep") $txtv .= $tmp_sep."Area";
		if ($tmp[$i]=="anio") $txtv .= $tmp_sep."Año";
		if ($tmp[$i]=="secuencial") $txtv .= $tmp_sep."Secuencial";
		if ($tmp[$i]=="tipodoc") $txtv .= $tmp_sep."Ab. Documento";
		
	    }
	    echo "<td align='center' class='listado2'><center>
		<input type='hidden' name='tipo_$num' id='tipo_$num' value='".$rs2->fields["TRAD_CODIGO"]."'>
		<input type='text' name='txtv_$num' id='txtv_$num' value='$txtv' class='ecajasfecha' size=50 readonly>
		<input type='hidden' name='txt_$num' id='txt_$num' value='".$rs2->fields["FN_FORMATO"]."'>
		<br><SELECT NAME='slc_form_$num' ID='slc_form_$num' CLASS='select'>
		<option value='inst'>Institucion</option>
		<option value='dep'>Area</option>
		<option value='anio'>Año</option>
		<option value='secuencial'>Secuencial</option>
		<option value='tipodoc'>Ab. Documento</option>
		</SELECT>
		<input name=btn_Formato id=btn_A_$num type=button class=botones_pequeno value=Añadir onClick=AnadirFormato('$num','A');>
		<input name=btn_Formato id=btn_B_$num type=button class=botones_pequeno value=Borrar onClick=AnadirFormato('$num','B');></center>
	        </td>";
	    echo "<td align='center' class='listado2'><center>
		<input type='text' name='txtip_$num' id='txtip_$num' class='ecajasfecha' size=6 maxlength=10 value='".$rs2->fields["FN_ABR_TEXTO"]."'>
		</center></td>";
	    if (trim((string)$rs2->fields["FN_CARACTER"])=="") $val_temp="-"; else $val_temp=$rs2->fields["FN_CARACTER"];
	    echo "<td align='center' class='listado2'><center>
		<input type='text' name='sep_$num' id='sep_$num' class='ecajasfecha' size=6 maxlength=1 value='".$val_temp."'>
		</center></td>";
	    if (trim((string)$rs2->fields["FN_NUM_ANIO"])=="") $val_temp="4"; else $val_temp=$rs2->fields["FN_NUM_ANIO"];
	    echo "<td align='center' class='listado2'><center>
		<input type='text' name='danio_$num' id='danio_$num' class='ecajasfecha' size=6 maxlength=1 value='$val_temp'
			onkeypress='return validar_numero(event)'>
		</center></td>";
	    if (trim((string)$rs2->fields["FN_NUM_CONSEC"])=="") $val_temp="4"; else $val_temp=$rs2->fields["FN_NUM_CONSEC"];
	    echo "<td align='center' class='listado2'><center>
		<input type='text' name='dsec_$num' id='dsec_$num' class='ecajasfecha' size=6 maxlength=1 value='$val_temp'
			onkeypress='return validar_numero(event)'>
		</center></td>";
	    if (trim((string)$rs2->fields["FN_CONTADOR"])=="") $val_temp="0"; else $val_temp=$rs2->fields["FN_CONTADOR"];
	    echo "<td align='center' class='listado2'><center>
		<input type='text' name='sec_$num' id='sec_$num' class='ecajasfecha' size=6 maxlength=9 value='$val_temp'
			 onkeypress='return validar_numero(event)'>
		<input type=hidden name='hsec_$num' id='hsec_$num' value='".$rs2->fields["FN_CONTADOR"]."'>
		</center></td>";
	    echo "</tr>";

    	    $rs2->MoveNext();
	    echo "<script>ver_datos($depe_doc,$num)</script>";
	    $num++;
	    $numreg = $num;
            //if( !isset( $depe_doc ) && empty( $depe_doc ) ) {   $depe_doc =0; echo "Entro".$depe_doc;  }

	}
?>
</table>
<br>
<input type="hidden" name=numreg value='<?=$numreg?>'>
<table width="100%"cellpadding="0" cellspacing="0" >
<tr>
	<td width="10%">&nbsp;</td>
	<td width="40%" align="center"><input name="btn_accion" type="submit" class="botones" id="btn_accion" value="Aceptar" title="Almacena los cambios realizados" onClick="return ValidarInformacion('<?=$numreg?>');" accesskey="A"></td>
	<td width="40%" align="center"><input name="btn_accion" type="button" class="botones" id="btn_accion" value="Regresar" title="Regresa a la página anterior, sin guardar los cambios" onClick="window.location='../formAdministracion.php';" accesskey="C"></td>
	<td width="10%">&nbsp;</td>
</tr>
</table>


</form>
</center>
</body>
</html>
