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
if($_SESSION["usua_admin_sistema"]!=1) {
    die("");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php"); //para traer funciones p_get y p_post
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once("refrescarArbol.php");

echo "<!DOCTYPE html>".html_head();
?>
	<script type="text/javascript" src="tree/_lib/jquery.js"></script>
	<script type="text/javascript" src="tree/_lib/jquery.cookie.js"></script>
	<script type="text/javascript" src="tree/_lib/jquery.hotkeys.js"></script>
	<script type="text/javascript" src="tree/jquery.jstree.js"></script>

	<link type="text/css" rel="stylesheet" href="tree/_docs/syntax/!style.css"/>
	<link type="text/css" rel="stylesheet" href="tree/_docs/!style.css"/>
	<script type="text/javascript" src="tree/_docs/syntax/!script.js"></script>
        <script type="text/javascript" src="ajaxArbol.js"></script>
        <?php include_once('../../js/ajax.js');?>
        <?php
        //instanciar arbol
        if(isset($_GET['depe_codi_instancia']))
            $instancia=0+limpiar_sql($_GET['depe_codi_instancia']);            
        else
            $instancia = 0;
        
        //comprobar si existe la instancia en la tabla usuario_dependencia
        if(isset($_GET['usr_codigo']))
            $usrCodigo=0+limpiar_sql($_GET['usr_codigo']);            
        else
            $usrCodigo = 0;
        
        
        //buscar dependencia padre
       
    if ($_SESSION['usua_codi']==$usrCodigo)
     $modificar=obtenerCodigos($_SESSION['usua_codi'],$instancia,$db);
    else
        $modificar=1;
  
    $sql = "select * from dependencia where depe_codi = $instancia";
    $rs=$db->conn->query($sql);
    if (!$rs->EOF){
    $nombre = $rs->fields['DEPE_NOMB'];
    }
   
                    $checked="";
                    $existe=0;
                    $existe=obtenerCodigos($usrCodigo,$instancia,$db);
                    
                    if ($existe==1)
                        $checked="checked";
                    ?>
       
      
<script type="text/javascript">

    $(function () { 
    $("#search").click(function () {    
                valor = document.getElementById("area_buscar").value;
                if (valor.length<=100){   
                    if(valor=='')
                        return 0;
                    else{
                    $("#demo1").jstree("search",valor);//enviar valor para buscar               
                    }
                }else{
                    document.getElementById('area_buscar').value='';
                    alert("El texto no tienen coincidencias en la búsqueda");
                }
                     
            });
           $("#demo1")
		.jstree({
                    "themes" : {
                            "theme" : "classic",
                            "dots" : true,
                            "icons" : true
                    },
			"plugins" : [ "themes", "html_data", "search" ]
		})
                    //resultado de la busqueda
                    .bind("search.jstree", function (e, data) {
                            leyenda="<font color='blue' size='1'>Se encontraron " + data.rslt.nodes.length + " (s) similitudes con la palabra </font><b>" + data.rslt.str + "</b>.";
                            document.getElementById('div_buscar').innerHTML=leyenda;
                    });
                    //abrir nodo principal

                    if (document.getElementById('modificar').value==1)
                    setTimeout(function () { $.jstree._reference("#phtml_<?=$instancia?>").open_node("#phtml_<?=$instancia?>"); }, 1000);
                   
    });


</script>
<?php
//usuario desde el listado
//include_once __DIR__."/Administracion/tbasicas/listaAreas.php";

?>
<body onload="refrescarDatos()">
<form name="arbolAjax" id="arbolAjax">
    <input type="hidden" name="modificar" id="modificar" value="<?=$modificar?>"/>
<div id="container">
    <table width="100%">
         
        <tr>
            <td colspan="4" align="center" class="titulos4">
                <b>Usuario</b>
            </td>
        </tr>
            <?php            
            if($usr_codigo!=''){
                
                $datosUsr = array();
                $datosUsr = ObtenerDatosUsuario($usrCodigo,$db);
                ?>
                <tr>
                    <td align="center" class="titulos2"><font size="1">Nombre</font></td>
                    <td align="center" class="listado2"><font size="1"><?=$datosUsr['usua_nombre'];?></font></td>
                    <td align="center" class="titulos2"><font size="1">Apellidos</font></td>
                    <td align="center" class="listado2"><font size="1"><?=$datosUsr['usua_apellido'];?></font></td>
                    <input type="hidden" name="usr_codigo" id="usr_codigo" value="<?=$usrCodigo?>"/>
                    <input type="hidden" name="instancia" id="instancia" value="<?=$instancia?>"/>                    
                </tr>
                <tr>
                    <td align="center" class="titulos2"><font size="1">Institución</font></td>
                    <td align="center" class="listado2"><font size="1"><?=$datosUsr['institucion'];?></font></td>
                    <td align="center" class="titulos2"><font size="1">Dependencia</font></td>
                    <td align="center" class="listado2"><font size="1"><?=$datosUsr['dependencia'];?></font></td>
                </tr>
                <tr>
                    <td colspan="4">
                        <hr></hr>
                    </td>
                </tr>
            <?php            
            }            
            ?>
    </table>
    <?php 
    

   
    if ($modificar==1){?>
    <center><div id="imagen_div" name="imagen_div">
    <table width="100%">        
        <tr>
            <td colspan="2" align="center" class="titulos4">
                <b>Administraci&oacute;n por Áreas</b>
            </td>
        </tr>
        <tr class="listado2">
            <td align="right"><font size="1">Buscar dependencia</font>
                <input type="text" name="area_buscar" id="area_buscar" size="40" value="" onKeyPress='if (event.keyCode==13) return false;'/>
            </td>
                <td>
                    <input name="search" id="search" type="button" class="botones" value="Buscar" onKeyPress='if (event.keyCode==13) return false;'/>
<!--                <input type="button" class="button" value="Buscar" id="search" style="" />-->
            </td>
        </tr>
        <tr class="listado2">
            <td colspan="2" align="center" class="titulo4">
                <div id="div_buscar"></div>
            </td>
        </tr>
        <tr class="listado2">
            <td align="center" width="50%">
                <font size="1">Seleccione el/las área(s) para la Administración</font>
            </td>
            <td align="center" width="50%">
                <font size="1">Áreas Actuales que Administra <?=$datosUsr['usua_nombre'];?>&nbsp;<?=$datosUsr['usua_apellido'];?></font>
                
            </td>
        </tr>
        <tr class="listado2">
            
            <td align="center" width="50%">
                
                <a href="javascript:seleccionar_todo(1)"><font color="blue">Agregar todas las Áreas de la Institución</font></a>
                &nbsp;&nbsp;
                <a href="javascript:seleccionar_todo(0)"><font color="blue">Eliminar todas las Áreas de la Institución</font></a>        
            </td>
            <td align="center" width="50%">
                <input name="atras" id="atras" onclick="window.location='cuerpoAreas.php?usr_codigo=<?=$usr_codigo?>';" type="button" class="botones" value="Regresar"/>
                &nbsp;&nbsp;
                <input name="cerrar" id="cerrar" onclick="window.close();" type="button" class="botones" value="Cerrar"/>
                
            </td>
        </tr>
        <tr class="listado2"><td align="center" width="50%">
                
                <a href="javascript:ArbolAll(<?=$instancia?>,<?=$instancia?>,1)"><font color="blue">Agregar Áreas Actuales</font></a>
                &nbsp;&nbsp;
                <a href="javascript:ArbolAll(<?=$instancia?>,<?=$instancia?>,0)"><font color="blue">Eliminar Áreas Actuales</font></a>        
            </td>
        </tr>
          
        <tr>
           
            <td width="50%">
                <div id="demo1" class="demo">
                <ul>
		<li id="phtml_<?=$instancia?>">
                    
                    <a href="#"><?php
                    $usua_admin = $_SESSION['usua_codi'];
                    $admin_areas=pertenece($_SESSION['inst_codi'],$usua_admin,$db,1);//si no administra nada 
                    $existe=0;$existeAdmin = 0;
                    $existe=obtenerCodigos($usrCodigo,$instancia,$db);//usuario a dar permisos
                    $existeAdmin=obtenerCodigos($usua_admin,$instancia,$db);//usuario de session
                    //Grafica el check segun permisos
                    echo graficarCheckDesabled($instancia,$instancia,$admin_areas,$existeAdmin,0,$existe);                    
                    echo $nombre;?></a>
                    <ul>
                        <?php 
                        echo obtenerDependencia($instancia, $db,$usrCodigo)."<br>";                       
                        ?>
                    </ul>
                    
                </li>
                       </ul>
                    
                </div>
            </td>
            
            <td width="50%">               
                <div id='div_area_selecciona'></div>
                <div id='div_datos'>                
                </div>
            </td>
        </tr>       
    </table>
    <?php }else{
        ?>
    <table width="100%">        
        <tr>
            <td colspan="2" align="center" class="listado2">
                <font size="1" color="red">No tiene permisos para Administrar Áreas, Por favor comuníquese con el Administrador del
                    Sistema.</font>
                <input name="search" id="search" onclick="javscript:cerrar();"type="button" class="botones" value="Cerrar"/>
                <input type="hidden" name="area_buscar" id="area_buscar" value="" />
            </td>
        </tr>
    </table>
    <?php }?>
    <table width="100%">
    <tr><td><center>
                
        </center></td>
    </tr>
    </table>
 </div></center>
</div>
</form>
</body>
</html>
