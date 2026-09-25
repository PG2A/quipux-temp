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
if (isset ($replicacion) && $replicacion && $config_db_replica_trd_lista_expediente!="") {
    $db = new ConnectionHandler(dirname(__DIR__), $config_db_replica_trd_lista_expediente);
}
include_once(dirname(__DIR__).'/funciones_interfaz.php');
include_once(dirname(__DIR__).'/funciones.php');

if (!isset($config_numero_meses)) $config_numero_meses = 3;
if ($config_numero_meses < 3) $fecha_inicio = date("Y-m-d", strtotime(date("Y-m-d")." - 1 month"));
  else $fecha_inicio = date("Y-m-d", strtotime(date("Y-m-d")." - 3 month"));

$fecha_fin= date("Y-m-d");
$titulo = "Consulta de Documentos en Carpeta Virtual";
$codexp = limpiar_numero($_POST['trd_codi']);
$nombre_completo = limpiar_sql($_POST['trd_nombre_completo']);

echo "<!DOCTYPE html>".html_head();
  include_once('../js/ajax.js');
  $paginador = new ADODB_Pager_Ajax(dirname(__DIR__), "div_lista_documentos", "lista_documentos_paginador.php", "trd_codi,trd_nombre_completo,txt_fecha_inicio,txt_fecha_fin,txt_reporte","");
 ?>

<link rel="stylesheet" type="text/css" href="../js/spiffyCal/spiffyCal_v2_1.css">
<script type="text/javascript" src="../js/spiffyCal/spiffyCal_v2_1.js"></script>
<script type="text/javascript" src="../js/funciones.js"></script>

<script language="JavaScript" type="text/javascript" >
    var dateAvailable1 = new ctlSpiffyCalendarBox('dateAvailable1', 'formulario', 'txt_fecha_inicio','btnDate1','<?=$fecha_inicio?>',scBTNMODE_CUSTOMBLUE);
    var dateAvailable2 = new ctlSpiffyCalendarBox('dateAvailable2', 'formulario', 'txt_fecha_fin','btnDate2','<?=$fecha_fin?>',scBTNMODE_CUSTOMBLUE);
</script>

<body onload="buscar_documentos(0);">
  <div id="spiffycalendar" class="text"></div>
  <form action=""  method="post" name="formulario">
    <center>
      <input type="hidden" id="trd_codi" value=<?=$codexp?>>
      <input type="hidden" name="trd_nombre_completo" id="trd_nombre_completo" size="20" maxlength="500" value='<?= $nombre_completo; ?>'>
      <input type="hidden" id="txt_reporte" value="0">
      <table class="borde_tab" width="100%">
	    	<tr><td class="titulos2" colspan="8" align='center'><?=$titulo?></td></tr>
                <tr>
                    <td class="titulos2" align='center' width="25%" colspan="2">Fecha Desde:</td>
                    <td width="25%" class="listado2">
                    <script type="text/javascript">
                       dateAvailable1.dateFormat="yyyy-MM-dd";
                       dateAvailable1.writeControl();
                    </script>
                    </td>
                    <td class="titulos2" align='center' width="25%" colspan="2">Fecha Hasta:</td>
                    <td width="25%" class="listado2">
                        <script type="text/javascript">
                           dateAvailable2.dateFormat="yyyy-MM-dd";
                           dateAvailable2.writeControl();
                        </script>
                    </td>
                </tr>
                <tr>
                    <!--td class="listado2" align='center' width="25%" colspan="2"></td-->
                    <td class="listado2" align='right' width="25%" colspan="3">
                        <input type='button' value='Buscar Documentos' name='btn_buscar' class='botones_largo' onClick='buscar_documentos(0)'>
                    </td>
                    <td class="listado2" align='left' width="25%" colspan="3">
                          <?php  if (isset ($version_light) && $version_light==false) //Si hay problemas con la BDD
                                echo '<input type="button" name="btn_buscar" class="botones_largo" value="Generar Reporte" onclick="buscar_documentos(1);" >';
                          ?>
                    </td>
                    <!--td width="25%" class="listado2"></td-->
                </tr>
      </table>
     
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
      
        function buscar_documentos(tipo) {
            if (esRangoFechaValido(document.formulario.txt_fecha_inicio.value, document.formulario.txt_fecha_fin.value)==0) {
                alert ("La Fecha Inicio debe ser menor o igual que la Fecha Fin.");
                return;
            }
            document.getElementById("txt_reporte").value = tipo;
            paginador_reload_div();
        }

        function mostrar_documento(numdoc, txtdoc){
            var_envio='/verradicado.php?verrad='+numdoc+'&textrad='+txtdoc+'&menu_ver_tmp=3&tipo_ventana=popup';
            window.open(var_envio,numdoc,"height=450,width=750,scrollbars=yes");
        }

        function reportes_generar_guardar_como(tipo) {
            nuevoAjax('div_reporte_guardar_como', 'POST', 'lista_generar_archivo.php', 'tipo='+tipo);
        }

    </script>

      <div id="div_lista_documentos"></div>
      <div id='div_reporte_guardar_como' style="width: 99%"></div>
      </center>
  </form>
</body >
</html>