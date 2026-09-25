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
include_once("obtener_datos_archivo.php");

p_register_globals(array());

if (!isset($depe_actu)) $depe_actu=0;
if (!isset($descDependencia)) $descDependencia = "Dependencia";

////////////////	VARIABLES BASICAS	////////////////////////
  if (!$depe_actu) $depe_actu=0;
  $sql = "select arch_nombre from archivo_nivel where depe_codi=$depe_actu";
  $rs=$db->conn->query($sql);
  $niveles = 0;
  $titulo = "";
  if ($rs) {
      while (!$rs->EOF) {
        if ($titulo!="") $titulo .= " >> ";
        $titulo .= isset($rs->fields["ARCH_NOMBRE"]) ? $rs->fields["ARCH_NOMBRE"] : $rs->fields["arch_nombre"];
        $niveles++;		//Numero de niveles de almacenamiento
        $rs->MoveNext();
      }
  }

include_once(dirname(__DIR__).'/funciones_interfaz.php');
echo "<!DOCTYPE html>".html_head();

?>

  <body >
  <form method="post" name="formulario"> 
    <center>
    <table class="borde_tab" width="80%" cellspacing="5">
	<tr><td class=titulos2 colspan="2"><center>Consultar Estructura del Archivo F&iacute;sico</center></td></tr>
    	<tr>
	    <td width="25%" align="left" class="titulos2"><b>&nbsp;Seleccione <?=$descDependencia?></b></td>
	    <td width="75%" class="listado2">
<?php
	$sql = "select DEPE_NOMB, DEPE_CODI from dependencia where coalesce(dep_central, depe_codi)=depe_codi 
		and depe_estado=1 and inst_codi=".$_SESSION["inst_codi"]." order by depe_nomb";
	$rs=$db->conn->query($sql);
    
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
    <br>

    <table class="borde_tab" width="80%">
<?	if ($depe_actu==0) {?>
	    <tr><td class="titulos2" colspan="4"><center>Seleccione el <?=$descDependencia?> antes de continuar</center></td></tr>
<?	} else { 
	    if (trim($titulo)=="") { ?>
	    	<tr><td class="titulos2" colspan="4"><center>
		    Esta <?=$descDependencia?> no tiene definida la estructura del Archivo</center></td></tr>
<?	    } else { ?>
	    	<tr><td class="titulos2" colspan="4"><?=$titulo?></td></tr>
	    	<tr><td class="titulos2" width="70%">Nombre Item</td>
		    <td class="titulos2" width="15%">Estado</td>
		    <td class="titulos2" width="15%">Tipo</td>
	    	</tr>
	    	<tr><td  colspan="4">
		    <table width="100%">
			<?php echo ArbolSeleccionarArchivo(0, 0 , $depe_actu, "", $db, __DIR__,"L","T",0,0,"N");?>
		    </table></td>
	    	</tr>
<?	    } 
	}
?>

    </table>
    <br>

<?
////////////////////////	BOTONES 	/////////////////////////
?>
    <table width="80%" cellspacing="5">
	<tr>
    	    <td > <center>
    		<input type="button" name="btn_print" value="Imprimir" class="botones" onClick="window.print();">	  
    	    </center></td>
    	    <td > <center>
    		<input type="button" name="btn_cancelar" value="Regresar" class="botones" onClick="window.location='./menu_archivo.php';">
    	    </center></td>
	</tr>
    </table>

    <script>
	function MostrarFila(fila) {
	    if (document.getElementById(fila).style.display=='none') 
		document.getElementById(fila).style.display='';
	    else
		document.getElementById(fila).style.display='none';
	}

    </script>
  </center>
  </form>
  </body>
</html>



