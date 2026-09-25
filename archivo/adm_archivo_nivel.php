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
 * @package    anexos
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//session_start();
include_once(dirname(__DIR__).'/rec_session.php');
require_once(dirname(__DIR__)."/funciones.php"); //para traer funciones p_get y p_post

if (!isset($depe_actu)) $depe_actu = 0;
if (!isset($descDependencia)) $descDependencia = "Dependencia";

p_register_globals(array());

$flag_nivel = false;
if ($depe_actu) {
    // Fix deprecation by casting to int
    $sql_count = "select count(*) as num from archivo where depe_codi=". (int)$depe_actu;
    $rs=$db->conn->query($sql_count);
    $num = isset($rs->fields["NUM"]) ? $rs->fields["NUM"] : (isset($rs->fields["num"]) ? $rs->fields["num"] : 0);
    
    if ($num==0) 
	$max_nivel = 10;
    else {
	$flag_nivel = true;
	$sql_count = "select count(*) as num from archivo_nivel where depe_codi=". (int)$depe_actu;
    	$rs=$db->conn->query($sql_count);
	$max_nivel = isset($rs->fields["NUM"]) ? $rs->fields["NUM"] : (isset($rs->fields["num"]) ? $rs->fields["num"] : 10);
    }
} else
    $max_nivel = 10;

if (isset($_POST['txt_ok']) && $_POST['txt_ok']=="1")
{
	$j=0;
	$db->conn->Execute("delete from archivo_nivel where depe_codi=$depe_actu");
	for ($i=0;$i<$max_nivel;$i++)
	{
	    if (isset($_POST["nom_$i"])) {
            $nom=trim(strtoupper($_POST["nom_$i"]));
            if (!isset($_POST["desc_$i"]) || $_POST["desc_$i"]=="") $desc="null"; 
            else $desc=$db->conn->qstr($_POST["desc_$i"]);
            
            if ($nom!="" or $flag_nivel) {
            $db->conn->Execute("insert into archivo_nivel (arch_codi,depe_codi,arch_nombre,arch_descripcion) values ($j, $depe_actu, '$nom', $desc)");
            $j++;
            }
	    }
	}
}
include_once(dirname(__DIR__).'/funciones_interfaz.php');
echo "<!DOCTYPE html>".html_head();

?>

<script language="Javascript">
    function validar_form()
    {	
	<?php if ($flag_nivel) {?> 
	    for (i=0;i < <?=$max_nivel?>;i++) {
		if (document.getElementById('nom_'+i).value.replace(/ /g, '')=='') {
		    alert ('El nombre en los <?=$max_nivel?> items es obligatorio');
		    return;
		}
	    }
	<?php  } ?>
	document.getElementById('txt_ok').value='1';
	document.formulario.submit();
    }

    function ver_datos(num)
    {	
	if (num < <?=$max_nivel?>) 
	    document.getElementById('tr_'+num).style.display='';
	
    }

    function ocultar_datos()
    {	
	for (i=0;i < <?=$max_nivel-1?>;i++) {
	    j=i+1;
	    if (document.getElementById('nom_'+i).value=='')
		document.getElementById('tr_'+j).style.display='none';
	}
    }
</script>
<body>
  <center>
<form name="formulario" id="formulario" method="post">
<input type="hidden" name="txt_ok" id="txt_ok" value="">
<table width="80%" align="center" class="borde_tab">
    <tr>
	<td colspan="6" height="40" align="center" class="titulos4"><b>Organizaci&oacute;n F&iacute;sica del Archivo</b></td>
    </tr>
    <tr>
	<td width="25%" align="left" class="titulos2"><b>&nbsp;Seleccione <?=$descDependencia?></b></td>
	<td width="75%" colspan="5" class="listado2">
<?php
	$sql = "select distinct a.DEPE_NOMB, a.DEPE_CODI from dependencia a, dependencia b where a.depe_codi=coalesce(b.dep_central,b.depe_codi) 
		and a.depe_estado=1 and a.inst_codi=".$_SESSION["inst_codi"]." order by a.depe_nomb";
	$rs=$db->conn->query($sql);
    
    // Manual Select Loop replacing GetMenu2
    echo "<select name='depe_actu' class='select' Onchange='document.formulario.submit()'>";
    echo "<option value='0'> << seleccione >> </option>";
    while (!$rs->EOF) {
        $cod = $rs->fields['DEPE_CODI'];
        $nom = $rs->fields['DEPE_NOMB'];
        $selected = ($cod == $depe_actu) ? "selected" : "";
        echo "<option value='$cod' $selected>$nom</option>";
        $rs->MoveNext();
    }
    echo "</select>";
?>
	</td>
    </tr>
</table>

<?php if ($depe_actu) {?>
    <br>
    <table width="80%" class="borde_tab">
    	<tr>
	    <td width="10%" align="center" class="titulos2"><b>No.</b></td>
	    <td width="30%" align="center" class="titulos2"><b>Nombre Item</b></td>
	    <td width="40%" align="center" class="titulos2"><b>Descripci&oacute;n Item</b></td>
    	</tr>
<?php
	$sql = "select * 
		from archivo_nivel
		where depe_codi=$depe_actu order by arch_codi ASC";
//echo $sql;
	$rs2=$db->conn->query($sql);

	for ($i=0;$i<$max_nivel;$i++) {
	    if (!$rs2->EOF) {
		${"nom_$i"}= isset($rs2->fields["ARCH_NOMBRE"]) ? $rs2->fields["ARCH_NOMBRE"] : $rs2->fields["arch_nombre"];
		${"desc_$i"}= isset($rs2->fields["ARCH_DESCRIPCION"]) ? $rs2->fields["ARCH_DESCRIPCION"] : $rs2->fields["arch_descripcion"];
		$rs2->MoveNext();
	    } else {
		${"nom_$i"}="";
		${"desc_$i"}="";
	    }
?>
	    <tr name="tr_<?=$i?>" id="tr_<?=$i?>">
		<td class='listado2'><center><?=$i+1?></center></td>
		<td class='listado2'><center>
		    <input type="text" name="nom_<?=$i?>" id="nom_<?=$i?>" class='ecajasfecha' size=30 maxlength=50 value="<?=${'nom_'.$i}?>" onfocus="ver_datos(<?=$i+1?>)">
		</center></td>
		<td class='listado2'><center>
		    <input type="text" name="desc_<?=$i?>" id="desc_<?=$i?>" class='ecajasfecha' size=60 maxlength=100 value="<?=${'desc_'.$i}?>">
		</center></td>
	    </tr>
<?php	}	?>

    </table>
<?php  if(!$flag_nivel) echo "<script>ocultar_datos();</script>";
 } ?>

<br>
<table width="80%" cellpadding="0" cellspacing="0">
    <tr>
	<?php if ($depe_actu) {?>
	    <td align="center">
	    	<input name="btn_accion" type="button" class="botones" id="btn_accion" value="Aceptar" onClick="validar_form()">
	    </td>
	<?php  } ?>
	<td align="center">
	    <input name="btn_accion" type="button" class="botones" id="btn_accion" value="Regresar" onClick="window.location='./menu_archivo.php';">
	</td>
    </tr>
</table>

</form>
</center>
</body>
</html>
