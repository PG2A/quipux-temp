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
 * @package    usuarios
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
include_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2)."/funciones_interfaz.php");

p_register_globals(array());


if(isset($_REQUEST['codigo'])!="") {
    $codigo = $_REQUEST['codigo'];
} else {
    $codigo = "";
}

  if (!$dep_sel) $dep_sel = $_SESSION['dependencia'];

    if($orden_cambio==1)
    {
 	if ($orderTipo=="asc")
	   $orderTipo="desc";
	else
	   $orderTipo="asc";
    }
    if (!$orderTipo) $orderTipo="asc";
    if (!$orderNo) $orderNo=0;



    if(trim($_POST["ciu_ver"])!='')
     $opc=$_POST["ciu_ver"];
    else
     $opc=$_GET['ver'];
  

  $encabezado = "accion=$accion&buscar_nom=$buscar_nom&adodb_next_page=1&orderTipo=$orderTipo&orderNo=&ver=$opc";

  $linkPagina = "$PHP_SELF?$encabezado";

  $pagina_siguiente = "./adm_solicitud_ext.php?codigo=$codigo&accion=$accion&ciu_codigo=";

?>
<!DOCTYPE html>
<?php echo html_head(); /*Imprime el head definido para el sistema*/?>
<script>
    function seleccionar_ciudadano(codigo) {
        //alert(codigo);
        window.location='<?=$pagina_siguiente?>' + codigo;
        return true;
    }
</script>
<body>
  <form name="formEnviar" action="<?=$linkPagina?>" method="post">
          <table width="100%" border="1" align="center" class="t_bordeGris">
  	<tr>
    	    <td class="titulos4">
		<center>
		<p><B><span class=etexto>Existen Solicitudes por Autorizar</span></B> </p>
		</center>
	    </td>
	</tr>
    </table>
    <table border=0 width="100%" class="borde_tab" cellpadding="0" cellspacing="5">
	<tr>
	    <td width="30%" class="titulos5"><font class="tituloListado">Buscar ciudadano: </font></td>
	    <td class="listado5" valign="middle">
	    	<table>
 	  	    <tr>
			<td><span class="listado5">Nombre / C.I. </span> </td>
			<td><input type=text name="buscar_nom" value="<?=$buscar_nom?>" class="tex_area"></td>
            </tr>
	    	</table>
	    </td>
        
       
        <td class="titulos5">



                <input type="radio"  name="ciu_ver" id="ciu_ver" value="a" onclick="Obtener_val(this)"
                <?php  if ($opc=="a") echo "checked "; ?>>Rechazado                
                <input type="radio"  name="ciu_ver" id="ciu_ver" value="c" onclick="Obtener_val(this)"
                <?php  if ($opc=="c") echo "checked "; ?>>Enviado
                <input type="radio"  name="ciu_ver" id="ciu_ver" value="d"  onclick="Obtener_val(this)"
                <?php  if ($opc=="d") echo "checked "; ?>> Autorizado
                <input type="radio"  name="ciu_ver" id="ciu_ver" value="e" onclick="Obtener_val(this)"
                <?php  if ($opc!="a" && $opc!="c" && $opc!="d") echo "checked "; ?>>Ver Todos

        </td>
     

        <td width="20%" align="center" class="titulos5" >
        <?php  if ($_SESSION["usua_admin_sistema"]==1){?>
                <input type="button" name="btn_buscar" value="Buscar" class="botones" onClick="submit('<?=$accion?>','<?=$opc?>');">
         
         <?php }?>
	    </td>
    </tr>
    </table>
    <br />
 <?


	include __DIR__."/include/query/administracion/queryCuerpoSolicitud_ext.php";
   //echo $sql;
//        $rs=$db->conn->Execute($sql);
//
//	if ($rs->EOF)  
//	    echo "<br/><center><font color='red' face='Arial' size='3'>No se encontró nada con el criterio de búsqueda</font></center><br/>";
//	else  {

		$pager = new ADODB_Pager($db->conn,$sql,'adodb', true,$orderNo,$orderTipo);
		$pager->checkAll = false;
		$pager->checkTitulo = true;
		$pager->toRefLinks = $linkPagina;
		$pager->toRefVars = $encabezado;
		$pager->Render($rows_per_page=20,$linkPagina,$checkbox=chkEnviar);
	//}
 ?>
    <br/>
    <center><input  name="btn_accion" type="button" class="botones" value="Regresar" onClick="location='./mnuUsuarios_ext.php?cerrar=<?=$cerrar?>'"/></center>

</form>
</body>
</html>

<script>

var marcado ="";
function Obtener_val(formulario){
    var f=document.forms[0]['ciu_ver'];
    for(i=0;i<f.length;i++){
        if(f[i].checked){
            marcado=i;            
        }
    }
}

</script>