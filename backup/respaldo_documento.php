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

  session_start();
  include_once(dirname(__DIR__).'/rec_session.php');
  require_once(dirname(__DIR__)."/funciones.php");
  include_once(dirname(__DIR__).'/funciones_interfaz.php');
  echo "<!DOCTYPE html>".html_head();
  include_once('../js/ajax.js');
  
  $txt_tipo_lista = $_GET["txt_tipo_lista"];
  $txt_usuario_codi= trim(limpiar_sql($_GET["cod_usuario"]));
  $txt_resp_soli_codi = trim(limpiar_sql($_GET["txt_resp_soli_codi"]));

  $paginador = new ADODB_Pager_Ajax(dirname(__DIR__), "div_buscar_documentos", "respaldo_documento_buscar.php",
                                  "txt_documento","");
?>
  <script>
    function buscar_documentos() {
        txt_documento = document.getElementById('txt_documento').value;
        if (txt_documento.length<=100){
        paginador_reload_div('')
        
            document.getElementById('div_buscar_documentos').style.display = '';
            nuevoAjax('div_buscar_documentos', 'GET', 'respaldo_documento_buscar.php', 
                      'txt_documento=' + txt_documento);  
        }else
            alert("Demasiados caracteres ingresados");
       
    }

    function seleccionar_documento(codigo, numero) {
        
        document.getElementById('txt_radi_codigo').value = codigo;
        document.getElementById('txt_radi_numero').value = numero;
    }

    function aceptar_seleccion() {
        codigo = document.getElementById('txt_radi_codigo').value;
        numero = document.getElementById('txt_radi_numero').value;
        usuario_codigo = document.getElementById("txt_usua_codi_consulta").value;
        tipo_lista = document.getElementById("txt_tipo_lista").value;
        solicitud_codi = document.getElementById("txt_resp_soli_codi").value;
        window.location='respaldo_solicitud.php?txt_tipo_lista='+tipo_lista+'&codigo_documento='+codigo+'&numero_documento='+numero+'&usr_codigo='+usuario_codigo+'&txt_resp_soli_codi='+solicitud_codi;
    }
    
    function ver_documento_asociado(numdoc, txtdoc){                     
        var_envio='/verradicado.php?verrad='+numdoc+'&textrad='+txtdoc+'&menu_ver_tmp=3&tipo_ventana=popup';
        window.open(var_envio,numdoc,"height=450,width=750,scrollbars=yes");
    }
    
    function cerrar() {       
        usuario_codigo = document.getElementById("txt_usua_codi_consulta").value;
        tipo_lista = document.getElementById("txt_tipo_lista").value;
        solicitud_codi = document.getElementById("txt_resp_soli_codi").value;
        window.location='respaldo_solicitud.php?txt_tipo_lista='+tipo_lista+'&usr_codigo='+usuario_codigo+'&txt_resp_soli_codi='+solicitud_codi;
    }
    
  </script>
  <body >
    <center>
      <form name="formulario" id="formulario" action="" method="post">         
        <table width="100%" align="center" class=borde_tab border="0">
            <tr>
                <td width="100%" class="titulos5">
                  <center>
                    <br>Asociaci&oacute;n de Documentos<br>&nbsp;
                  </center>
                </td>
            </tr>
        </table>
        <br>
        <table width="100%" align="center" class=borde_tab border="0">
            <tr>
                <td width="25%" class="titulos5">
                    <br>No. de Documento:<br>&nbsp;
                </td>
                <td width="50%" class="listado5" valign="middle">
                    <input name="txt_documento" id="txt_documento" type="text" size="60" class="tex_area" value="<?=$txt_documento?>"/>
                </td>
                <td width="25%" class="titulos5" valign="middle">
                    <center><input type='button' value='Buscar' name='btn_buscar' class='botones' onClick='buscar_documentos();'></center>
                </td>
            </tr>   
            
            <tr>&nbsp;</tr>
            <tr>
                <td width="25%" class="titulos2">
                    <br>Documento Seleccionado:<br>&nbsp;
                </td>
                <td class="listado5" valign="middle" colspan="2"> 
                    <input type='text' name='txt_radi_numero' id='txt_radi_numero' value="" readonly>
                </td>               
            </tr>
        </table>
        
        <input type='hidden' name='txt_radi_codigo' id='txt_radi_codigo' value="">        
        <input type="hidden" name="txt_tipo_lista" id="txt_tipo_lista" value="<?php echo $txt_tipo_lista; ?>">
        <input type="hidden" name="txt_usua_codi_consulta" id="txt_usua_codi_consulta" value="<?php echo $txt_usuario_codi; ?>">
        <input type="hidden" name="txt_resp_soli_codi" id="txt_resp_soli_codi" value="<?php echo $txt_resp_soli_codi; ?>">
        
        <div id='div_buscar_documentos'></div>
        <br>
        <input type="button" name="btn_aceptar" value="Aceptar" class="botones" onClick="aceptar_seleccion();">        
        <input name="btn_accion" type="button" class="botones" value="Regresar" onClick="cerrar();">
      </form>
    </center>
<?php  
  if ($txt_documento != "")
    echo "<script>buscar_documentos();</script>";
?>
  </body>
</html>