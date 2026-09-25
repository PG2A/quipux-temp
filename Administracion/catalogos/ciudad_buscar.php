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
 * @package    catalogos
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php"); //para traer funciones p_get y p_post

//Se consulta datos de ciudad
$datos_ciudad = "";
$sql = "select nombre, id from ciudad where id not in(0) order by nombre,id_padre";    
$rs = $db->conn->Execute($sql);
//echo $sql;
if ($rs){
    while (!$rs->EOF) {
        $datos_ciudad .= $rs->fields["NOMBRE"].";".$rs->fields["ID"].":";
        $rs->MoveNext();
    }
}
?>
<table width="100%" border="1">
    <tr>
        <td class="titulos2" width="15%">* Ciudad/País</td>         
        <td class="listado2" colspan="3" width="85%">                
            <textarea style="width: 200px; height: 18px;" class="caja_texto" rows="1" id="ciudad_busca" onKeyUp="buscarCiudad()"></textarea>
            <font size="1">Ingrese los primeros caracteres de la Ciudad o País y seleccione de la lista.</font>
            <div id="divlista" style="display:none; width:300px; height:60px;">
                <select class="listado2" name="cmb_ciudad" id="cmb_ciudad" size="10"  style="font-size:8pt;width:300px; height:60px;color:blue;" onClick="seleccionarCiudad();"></select>
            </div>
        </td>
    </tr>
</table>
<script language="JavaScript" type="text/javascript" >     
    seleccionarCiudadCmb();
    
    function buscarCiudad(){    
        var cadena_ciudad = '<?php echo $datos_ciudad?>';
        var texto_busca = document.getElementById('ciudad_busca').value;
        var ciudades = cadena_ciudad.split(":");
        quitarOpciones("cmb_ciudad");
        for(i=0;i<ciudades.length;i++) { 
            var valor_ciudad = ciudades[i].split(";");
            var resultado = valor_ciudad[0].toLowerCase().search(texto_busca.toLowerCase());
            if(resultado >= 0){                
                document.getElementById("divlista").style.display = "";
                document.getElementById("cmb_ciudad").options[document.getElementById("cmb_ciudad").options.length]=new Option(valor_ciudad[0],valor_ciudad[1]); 
            }
        }
    }
     
    function quitarOpciones(id)
    {
	var selectObj = document.getElementById(id);
	var selectParentNode = selectObj.parentNode;
	var newSelectObj = selectObj.cloneNode(false);
	selectParentNode.replaceChild(newSelectObj, selectObj);
	return newSelectObj;
    }
   
   function seleccionarCiudad()
    {
	var selectObj = document.getElementById("cmb_ciudad");	
	if(selectObj.selectedIndex == -1) {
		return;
	}
	selectedValue = selectObj.options[selectObj.selectedIndex].text;
	selectedValue = selectedValue.replace(/_/g, '-') ;
        selectedId = selectObj.options[selectObj.selectedIndex].value;
        document.getElementById("ciudad_busca").value = selectedValue;
        document.getElementById("ciu_ciudad").value = selectedId;        
    }
    
    function seleccionarCiudadCmb()
    {
        var idCiudad = '<?php echo $ciu_ciudad?>';
        var cadena_ciudad = '<?php echo $datos_ciudad?>';      
        var ciudades = cadena_ciudad.split(":");
        for(i=0;i<ciudades.length;i++) { 
            var valor_ciudad = ciudades[i].split(";");
            if(valor_ciudad[1] == idCiudad){                                
                document.getElementById("ciudad_busca").value = valor_ciudad[0]; 
            }
        }        
    }
</script>