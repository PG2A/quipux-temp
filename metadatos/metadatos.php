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
 * @package    metadatos
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//session_start();
include_once(dirname(__DIR__).'/rec_session.php');
if (isset ($replicacion) && $replicacion && $config_db_replica_trd_nuevo_trd!="") {
    $db = new ConnectionHandler(dirname(__DIR__), $config_db_replica_trd_nuevo_trd);
}
include_once(dirname(__DIR__)."/obtenerdatos.php");
include_once("metadatos_funciones.php");

////////////////	VARIABLES BASICAS	////////////////////////
$mensaje = isset($mensaje) ? $mensaje : "";
$depe_actu = isset($_REQUEST["depe_actu"]) ? $_REQUEST["depe_actu"] : (isset($depe_actu) ? $depe_actu : 0);
$descDependencia = isset($descDependencia) ? $descDependencia : "Dependencia";

include_once(dirname(__DIR__).'/funciones_interfaz.php');
echo "<!DOCTYPE html>".html_head();

?>
<script type="text/javascript" src="../js/formchek.js"></script>

<body >
  <form method="post" name="formulario" action="./metadatos_grabar.php">
    <center>
    <table class="borde_tab" width="80%" cellspacing="5">
	<tr><td class=titulos2 colspan="2"><center>Administraci&oacute;n de Metadatos</center></td></tr>
    	<tr>
	    <td width="25%" align="left" class="titulos2"><b>&nbsp;Seleccione Institución/<?=$descDependencia?>:</b></td>
	    <td width="75%" class="listado2">
<?php
        $depe_codi_admin = 0;
        if ($_SESSION["usua_codi"] != 0)
            $depe_codi_admin = obtenerAreasAdmin($_SESSION["usua_codi"],$_SESSION["inst_codi"],$_SESSION["usua_admin_sistema"],$db);
        $sql="select depe_nomb, depe_codi from dependencia where depe_estado=1 and inst_codi=".(int)($_SESSION["inst_codi"] ?? 0);
        if (!empty($depe_codi_admin))
            $sql.=" and depe_codi in ($depe_codi_admin)";
        $sql.=" order by 1 asc";           
        
        $rs=$db->conn->Execute($sql);
        
        echo "<select name='depe_actu' class='select' Onchange='document.formulario.submit()'>";
        $defaultLabel = "0:<< " . $_SESSION["inst_nombre"] . " >>";
        // Handle the weird ADODB "0:Label" syntax by parsing or just creating a clean option
        echo "<option value='0'><< " . $_SESSION["inst_nombre"] . " >></option>";

        if ($rs) {
            while (!$rs->EOF) {
                // ADODB usually UPPERCASES keys by default
                $id = $rs->fields['DEPE_CODI']; 
                $text = $rs->fields['DEPE_NOMB']; 
                // Fallback for case sensitivity just in case
                if (!$id) $id = $rs->fields['depe_codi'];
                if (!$text) $text = $rs->fields['depe_nomb'];
                
                $selected = ($depe_actu == $id) ? "selected" : "";
                echo "<option value='$id' $selected>$text</option>";
                $rs->MoveNext();
            }
        }
        echo "</select>";
        if ($rs) $rs->Move(0);
?>
	</td>
    </tr>
    </table>
    <br>
    <center><font color="red" face='Arial' size='3'><?=$mensaje?></font></center>
    <table class="borde_tab" width="80%" cellspacing="5" name="tblCrear" id="tblCrear" style="display:none">
	<tr><td class="titulos2" colspan="4"><center><span name='spn_accion' id='spn_accion'></span></center></td></tr>
    	<tr>	    
	    <td width="25%" align="left" class="titulos2">Nombre:</td>
	    <td colspan="3" align="left" class="listado2">
	    	<input type="hidden" name="txtOk" id="txtOk" value="">
	    	<input type="hidden" name="txtCodigo" id="txtCodigo" value="">
	    	<input type="hidden" name="txtPadre" id="txtPadre" value="">
	    	<input type="hidden" name="txtEstado" id="txtEstado" value="">
                <input type="hidden" name="txtNivel" id="txtNivel" value="">
                <input type="hidden" name="txtInstCodi" id="txtInstCodi" value="">
	    	<input type="text" name="txtNombre" id="txtNombre"  size="50" maxlength="40" value=""></td>	   
    	</tr>
    	<tr>
	    <td width="25%" align="left" class="titulos2">Acciones</td>
	    <td width="25%" align="center" class="listado2"><center>
   		<input type="button" name="btn_guardar" value="Guardar" class="botones" onClick="ValidarForm();">
	    </center></td>
	    <td width="25%" align="center" class="listado2"><center>
   		<input type="button" name="btn_activar" id="btn_activar" value="Activar" class="botones" onClick="BotonesItem(3,'Activar');" style="display:none">
   		<input type="button" name="btn_desactivar" id="btn_desactivar" value="Desactivar" class="botones" onClick="BotonesItem(4,'Desactivar');" style="display:none">
	    </center></td>
	    <td width="25%" align="center" class="listado2"><center>
   		<input type="button" name="btn_borrar" id="btn_borrar" value="Borrar" class="botones" onClick="BotonesItem(2,'Borrar');" style="display:none">
	    </center></td>
    	</tr>
    </table>
    <br>

    <table class="borde_tab" width="80%">
        <tr><td class="titulos2" width="60%"><center>Nombre de Metadato</center></td>
            <td class="titulos2" width="15%"><center>Estado</center></td>
            <td class="titulos2" width="25%"><center>Acci&oacute;n</center></td>
        </tr>
        <tr><td  colspan="4">
            <table width="100%">
                <tr>
                    <td class="listado5" colspan="4" width="85%">&nbsp;</td>
                    <td class="listado5" colspan="2" align="center" width="15%"><a href="#" class="vinculos" onClick="CrearItem(0,-1,'')">Crear</a></td>
                </tr>
                <?                        
                $lista = ConsultarMetadatos($db, $depe_actu, 0);
                ArmarArbolMetadatos($lista, 0, "..","S", "", "Editar");
                ?>
            </table></td>
        </tr>
    </table>
    <br>

<?
////////////////////////	BOTONES 	/////////////////////////
?>
    <table  width="80%" cellspacing="5">
	<tr>
    	    <td > <center>
    		<input type="button" name="btn_cancelar" value="Regresar" class="botones" onClick="window.location='./metadatos_menu.php';">
    	    </center></td>
	</tr>
    </table>

<script language="JavaScript" type="text/JavaScript">
	function ltrim(s) {
	   return s.replace(/^\s+/, "");
	}

	function ValidarForm() {	    
	    if (ltrim(document.getElementById('txtNombre').value)!='') {
		document.getElementById('txtOk').value = '1';
		document.formulario.submit();
	    } else
		alert('El nombre del item es obligatorio');
	}

	function BotonesItem(dato, accion) {
	    if (confirm('Desea ' + accion + ' el Item Seleccionado?')) {
		document.getElementById('txtOk').value = dato;
		document.formulario.submit();
	    }
	}

	function MostrarFila(fila, ruta_raiz){
	    var elemento=document.getElementsByName(fila);
            imgAgregar = "agregar.png";
            imgQuitar = "quitar.png";

            for (var i=0; i<elemento.length; i++){

                if (elemento[i].style.display=='none')
                {
                    if(document.getElementById("spam_"+fila)!=null)
                       document.getElementById("spam_"+fila).innerHTML = '<img src='+ruta_raiz+'/imagenes/'+ imgQuitar  +' border="0" height="15px" width="15px">';
                    elemento[i].style.display='';
                }
                else{
                   if(document.getElementById("spam_"+fila)!=null)
                        document.getElementById("spam_"+fila).innerHTML = '<img src='+ruta_raiz+'/imagenes/'+ imgAgregar +' border="0" height="15px" width="15px">';
                   elemento[i].style.display='none';
                }
               MostrarFila(elemento[i].id, ruta_raiz);
            }
	}
	
	function EditarItem(codigo, codigo_padre, nombre, estado, nivel, borrar, nombre_completo) {
	    document.getElementById('txtCodigo').value=codigo;
	    document.getElementById('txtPadre').value=codigo_padre;
	    document.getElementById('txtNombre').value=nombre;	   
            document.getElementById('txtEstado').value=estado;
            document.getElementById('txtNivel').value=nivel;            
	    document.getElementById('tblCrear').style.display='';           
	    if (borrar)
		document.getElementById('btn_borrar').style.display='';
	    else
		document.getElementById('btn_borrar').style.display='none';
	    document.getElementById('btn_activar').style.display='none';
	    document.getElementById('btn_desactivar').style.display='none';
	    if (estado == 0)
	    	document.getElementById('btn_activar').style.display='';
	    if (estado == 1)
	    	document.getElementById('btn_desactivar').style.display='';                      
	    document.getElementById('spn_accion').innerHTML = 'MODIFICAR ' + nombre_completo;
	}

	function CrearItem(codigo, nivel, nombre_completo) {
	    document.getElementById('txtCodigo').value='';
	    document.getElementById('txtPadre').value=codigo;
	    document.getElementById('txtNombre').value='';	    
            document.getElementById('txtNivel').value=nivel+1;           
	    document.getElementById('txtEstado').value='1';            
	    document.getElementById('tblCrear').style.display='';
	    document.getElementById('btn_borrar').style.display='none';
	    document.getElementById('btn_activar').style.display='none';
	    document.getElementById('btn_desactivar').style.display='none';
	    document.getElementById('spn_accion').innerHTML = 'Crear ' + nombre_completo;
	}      
    </script>
  </center>
  </form>
  </body>
</html>