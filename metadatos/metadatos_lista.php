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

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__)."/obtenerdatos.php");
if (isset ($replicacion) && $replicacion && $config_db_replica_trd_consultar_lista_trd!="") {
    $db = new ConnectionHandler(__DIR__, $config_db_replica_trd_consultar_lista_trd);
}
include_once("metadatos_funciones.php");

////////////////	VARIABLES BASICAS	////////////////////////
  $flag_botones = true;
  $tamano_tabla = "80%";
  if (isset($mostrar_botones) && $mostrar_botones == "no") {
      $flag_botones = false;
      $tamano_tabla = "99%";
  }
  if (isset($_REQUEST["depe_actu"])) $depe_actu = $_REQUEST["depe_actu"];
  if (!isset($depe_actu)) $depe_actu=0;
  $descDependencia = isset($descDependencia) ? $descDependencia : "Dependencia";

include_once(dirname(__DIR__).'/funciones_interfaz.php');
echo "<!DOCTYPE html>".html_head();

?>
<body>
  <form method="post" name="formulario" action="">
    <center>
    <br>
<?php
////////////////////////	COMBO DE AREAS  	/////////////////////////
if ($flag_botones) {
?>

    <table class="borde_tab" width="<?=$tamano_tabla?>" cellspacing="5">
	<tr><td class=titulos2 colspan="2"><center>Consulta de Metadatos</center></td></tr>
    	<tr>
	    <td width="25%" align="left" class="titulos2"><b>&nbsp;Seleccione Institución/<?=$descDependencia?>:</b></td>
	    <td width="75%" class="listado2">
<?php
    $depe_codi_admin = 0;
    if ($_SESSION["usua_codi"] != 0)
          $depe_codi_admin= obtenerAreasAdmin($_SESSION["usua_codi"],$_SESSION["inst_codi"],$_SESSION["usua_admin_sistema"],$db);

	$sql = "select DEPE_NOMB, DEPE_CODI from dependencia where depe_estado=1 and inst_codi=".(int)($_SESSION["inst_codi"] ?? 0);
        if (!empty($depe_codi_admin))
            $sql.=" and depe_codi in ($depe_codi_admin)";
        $sql.=" order by 1 asc"; 
	
    $rs=$db->conn->Execute($sql);
    
    echo "<select name='depe_actu' class='select' Onchange='document.formulario.submit()'>";
    echo "<option value='0'><< " . $_SESSION["inst_nombre"] . " >></option>";

    if ($rs) {
        while (!$rs->EOF) {
            $id = $rs->fields['DEPE_CODI']; 
            $text = $rs->fields['DEPE_NOMB']; 
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
<?php  } //Combo areas?>
    
    <table class="borde_tab" width="<?=$tamano_tabla?>">
        <tr><td class="titulos2" width="85%">Nombre de Metadato</td>
            <td class="titulos2" width="15%" align="center">Estado</td>
            
        </tr>
        <tr><td  colspan="4">
            <table width="100%">
                <?php                            
                    $lista = ConsultarMetadatos($db, $depe_actu, 1);
                    ArmarArbolMetadatos($lista, 0, "..","S", "", "Consultar");
                ?>
            </table></td>
        </tr>
    </table>
    <br>

<?php
////////////////////////	BOTONES 	/////////////////////////
if ($flag_botones) {
?>
    <table width="80%" cellspacing="5">
	<tr>
    	    <td > <center>
    		<input type="button" name="btn_print" value="Imprimir" class="botones" onClick="window.print();">	  
    	    </center></td>
    	    <td > <center>
    		<input type="button" name="btn_cancelar" value="Regresar" class="botones" onClick="window.location='./metadatos_menu.php';">
    	    </center></td>
	</tr>
    </table>
<?php  } ?>
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
    </script>
  </center>
  </form>
  </body>
</html>