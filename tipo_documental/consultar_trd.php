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
 * @package    tipo_documental
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
if (isset ($replicacion) && $replicacion) {
    $db = new ConnectionHandler(dirname(__DIR__), "busqueda");
}
include_once("obtener_datos_trd.php");
include_once(dirname(__DIR__).'/funciones_interfaz.php');


////////////////	VARIABLES BASICAS	////////////////////////
$depe_actu = $_SESSION["depe_codi"];

//  $sql = "select trd_nombre from trd_nivel where depe_codi=$depe_actu";
//  $rs=$db->conn->query($sql);
//  $niveles = 0;
//  $titulo = "";
//  while (!$rs->EOF) {
//    if ($titulo!="") $titulo .= " >> ";
//    $titulo .= $rs->fields["TRD_NOMBRE"];
//    $niveles++;		//Numero de niveles de almacenamiento
//    $rs->MoveNext();
//  }

$titulo = "Consulta de Documentos en Carpeta Virtual";

echo "<!DOCTYPE html>".html_head();
include_once('../js/ajax.js');
//$paginador = new ADODB_Pager_Ajax(dirname(__DIR__, 2), "div_lista_documentos", "lista_documentos_paginador.php", "trd_codi,trd_nombre_completo","");
?>

<script type="text/javascript">
        function MostrarFila(fila, ruta_raiz){
            var elemento=document.getElementsByName(fila);
            imgAgregar = "agregar.png";
            imgQuitar = "quitar.png";

            for (var i=0; i<elemento.length; i++){

                if (elemento[i].style.display=='none')
                {
                    if(document.getElementById("spam_"+fila)!=null)
                       document.getElementById("spam_"+fila).innerHTML = '<img src='+ruta_raiz+'/imagenes/'+ imgQuitar +' border="0" height="15px" width="15px">';
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

        function SeleccionarTRD(codigo, nombre_completo) {          
            document.getElementById("trd_codi").value=codigo;
            document.getElementById('trd_nombre_completo').value=nombre_completo;
            document.formulario.action = "lista_documentos_buscar.php";
            document.formulario.submit();
        }
    </script>

  <body >
    <form action=""  method="post" name="formulario">
    <center>
      <input type="hidden" name="trd_codi" id="trd_codi" size="20" maxlength="10" value="0">
      <input type="hidden" name="trd_nombre_completo" id="trd_nombre_completo" size="20" maxlength="500" value="0">
        <table class="borde_tab" width="80%">
	    	<tr><td class="titulos2" colspan="4" align='center'><?=$titulo?></td></tr>               
	    	<tr><td class="titulos2" width="60%">Nombre de Carpeta</td>
		    <td class="titulos2" width="15%" align='center'>Estado</td>
		    <td class="titulos2" width="15%" align='center'>Acci&oacute;n</td>
	    	</tr>
	    	<tr><td  colspan="4">
		    <table width="100%">
			<?php
                            $lista = ConsultarCarpetaVirtual($db, $_SESSION["depe_codi"], 0);
                            ArmarArbolCarpetaVirtual($lista, 0, "..","S", "", "SeleccionarDoc");
                            if(!$lista)
                                echo "<tr><td class='listado2' width='100%' align='center'>No existen carpetas virtuales asociadas a esta área.</td></tr>";
                        ?>
		    </table></td>
	    	</tr>
      </table>
  </center>
  </form>
  </body>
</html>



