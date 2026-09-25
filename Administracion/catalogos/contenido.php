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

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php'); //para traer funciones p_get y p_post
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');

echo "<! DCOTYPE html>".html_head(); /*Imprime el head definido para el sistema*/
require_once dirname(__DIR__, 2)."/js/ajax.js";

    $ancho1 = "25%";
    $ancho2 = "75%";   
    $txt_texto = "</br>";

    // Initialize variables
    $cmb_tipo_contenido = isset($_REQUEST['cmb_tipo_contenido']) ? $_REQUEST['cmb_tipo_contenido'] : 0;
    $cont_codi = "";
    $descripcion = "";
    $fecha_actualiza = "";
    $descZonaHoraria = isset($descZonaHoraria) ? $descZonaHoraria : ""; // Fix undefined variable warning

    if($cmb_tipo_contenido != "" and $cmb_tipo_contenido != 0){
        $sql = "select * from contenido
        where cont_tipo_codi = $cmb_tipo_contenido
        and fecha_actualiza = (select max(fecha_actualiza) from contenido where cont_tipo_codi = $cmb_tipo_contenido)";        
        $rs = $db->conn->Execute($sql);

        if ($rs && !$rs->EOF) {
            $cont_codi = $rs->fields["CONT_CODI"];
            $descripcion = $rs->fields["DESCRIPCION"];
            $txt_texto = $rs->fields["TEXTO"];
            $fecha_actualiza = $rs->fields["FECHA_ACTUALIZA"];
            $fecha_actualiza = substr($fecha_actualiza,0,19) . " ". $descZonaHoraria;
        }
    }   
?>
<script type="text/javascript" src="../../js/ckeditor/ckeditor.js"></script>
<script type="text/javascript" src='../../js/base64.js'></script>
<script type="text/javascript">
    function crearEditor()
    {
        CKEDITOR.replace('txt_texto');
    }

   function metodoGuardar()
   {
       var oEditor = CKEDITOR.instances.txt_texto;
//    var oEditor = FCKeditorAPI.GetInstance('txt_texto') ;
    if(oEditor.getData()=="" || oEditor.getData()==null || oEditor.getData()=='<br />'){
        alert("Debe ingresar el texto del contenido");
        return false;
    }

    if(document.getElementById("txt_descripcion").value==""){
        alert("Debe ingresar la descripción");
        return false;
    }

    if(document.getElementById("cmb_tipo_contenido").value=="" || document.getElementById("cmb_tipo_contenido").value==0){
        alert("Debe seleccionar un tipo de contenido válido");
        return false;
    }
    var varTexto = Base64.encode(Base64.encode(oEditor.getData()));
    document.getElementById("txt_texto_usuario").value =  varTexto;
    document.formulario.action = "contenido_grabar.php";
    document.formulario.submit();
   }

   function cargar_datos(){

       document.formulario.action = "contenido.php";
       document.formulario.submit();
       
   }
</script>

<body onload="crearEditor();">
 <center>
    <form name="formulario" action="" method="post">
<table width="90%" border="0" align="center" class="t_bordeGris" id="usr_datos">
    <tr><td class="titulos2" colspan="4" align="center">ADMINISTRACIÓN DE CONTENIDOS</td></tr>      
    <input type="hidden" name="txt_texto_usuario" id="txt_texto_usuario" value="">
    <input type="hidden" name="txt_cont_codi" id="txt_cont_codi" value="<?php echo $cont_codi; ?>">
        <tr >
            <td class="titulos2" width="<?php echo $ancho1; ?>">Funcionalidad:</td>
            <td class="listado2" width="<?php echo $ancho2; ?>">
                <?php
                    // Use REQUEST to catch both GET and POST
                    $cmb_tipo_contenido = isset($_REQUEST['cmb_tipo_contenido']) ? $_REQUEST['cmb_tipo_contenido'] : 0;

                    $sql="select funcionalidad || ' - ' || categoria as tipo, cont_tipo_codi from contenido_tipo order by funcionalidad, categoria asc";
                    $rs=$db->conn->Execute($sql);
                    
                    echo "<select name='cmb_tipo_contenido' id='cmb_tipo_contenido' class='select' style='width: 250px;' onChange='cargar_datos()'>";
                    echo "<option value='0'><< Seleccione el tipo de contenido >></option>";
                    
                    if ($rs) {
                        while (!$rs->EOF) {
                            $id = $rs->fields['CONT_TIPO_CODI'];
                            $text = $rs->fields['TIPO'] ?? $rs->fields['tipo']; 
                            $selected = ($cmb_tipo_contenido == $id) ? "selected" : "";
                            echo "<option value='$id' $selected>$text</option>";
                            $rs->MoveNext();
                        }
                    }
                    echo "</select>";
                ?>
            </td>
            <td class="titulos2" width="<?php echo $ancho1; ?>"></td>
            <td class="listado2" width="<?php echo $ancho2; ?>">                
            </td>
        </tr>               
         <tr >
            <td class="titulos2" width="<?php echo $ancho1; ?>">Descripción:</td>
            <td class="listado2" width="<?php echo $ancho2; ?>">
                <input type="text" name="txt_descripcion" id="txt_descripcion" value="<?php echo $descripcion; ?>" size="40">
            </td>
            <td class="titulos2" width="<?php echo $ancho1; ?>">Fecha Actualización:</td>
            <td class="listado2" width="<?php echo $ancho2; ?>"><?php echo $fecha_actualiza; ?></td>
        </tr>
        <tr>
            <td  class="titulos2" width="<?php echo $ancho1; ?>">Texto:</td>
            <td class="listado2" width="<?php echo $ancho2; ?>" colspan ="4"></td>               
        </tr>
        <tr>
            <td class="listado2" colspan ="4" align ="center" width="<?php echo $ancho1; ?>">
               <textarea name="txt_texto" id="txt_texto" rows="100" cols="50" style="width: 100%; height: 500px"><?php echo $txt_texto ?></textarea>
            </td>
        </tr>
        <tr>
        <td class="listado2" colspan ="4" align ="center">
            <input type='button' name='btn_guardar' value='Guardar' class='botones' onClick='metodoGuardar();'>
            <input type='button' name='btn_regresar' value='Regresar' class='botones' onClick="window.location='../formAdministracion.php'">
        </td>
        </tr>
    </table>
  </form>
 </center>
</body>
</html>