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
if($_SESSION["usua_perm_backup"]!=1) {
    echo html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
    die("");
}
include_once(dirname(__DIR__).'/config.php');
include_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__).'/funciones_interfaz.php');

if (isset($nombre_servidor_respaldos) && $nombre_servidor!=$nombre_servidor_respaldos) {
    session_id($_GET["id_sess"]);
}

echo "<!DOCTYPE html>".html_head();
include_once('../js/ajax.js');

echo "<script>\n";
echo "var respaldo = new Array();\n";

/*$sql = "select r.resp_codi, r.fecha_solicita, u.usua_nombre, u.inst_nombre
        from respaldo_usuario r left outer join usuario u on r.usua_codi=u.usua_codi
        where r.fecha_inicio is null and r.fecha_eliminado is null";*/
$sql = "select r.resp_codi, r.fecha_solicita, u.usua_nombre, u.inst_nombre, s.fecha_ejecutar,
case when  s.resp_soli_codi is null then 'A' || r.resp_codi::character varying else s.resp_soli_codi::character varying end as resp_soli_codi
from respaldo_usuario r
left outer join usuario u
on r.usua_codi=u.usua_codi
left outer join respaldo_solicitud s
on r.resp_codi=s.resp_codi
where r.fecha_fin is null and r.fecha_eliminado is null
and r.fecha_inicio is null
and (s.fecha_ejecutar is null or s.fecha_ejecutar ::date <= now()::date)";
$rs = $db->query($sql);
$i = 0;
$tabla = "";
while(!$rs->EOF) {
    echo "respaldo[$i]='" . $rs->fields["RESP_CODI"] . "';";
    $tabla .= "\n<tr><td align='center'>$i</td>".
              "<td>&nbsp;".$rs->fields["RESP_SOLI_CODI"] . "</td>" .
              "<td>&nbsp;".$rs->fields["FECHA_SOLICITA"] . "</td>" .
              "<td>&nbsp;".$rs->fields["USUA_NOMBRE"] . "</td>" .
              "<td>&nbsp;".$rs->fields["INST_NOMBRE"] . "</td>" .
              "<td align='center'>
                  <div align='left' style='color: #FFFFFF; height: 14px; width: 104px; border: thin solid #999999;'>
                      <div name='div_barra_$i' id='div_barra_$i'
                           style='background-color: #a8bac6; color: #FFFFFF; border: thin solid #a8bac6;
                                  height: 10px; width: 0px; position:relative; top:1px; left:1px;'></div>
                  </div>
               </td></tr>";
    $rs->MoveNext();
    $i++;
}
echo "\n</script>";

?>
<script language="JavaScript" type="text/JavaScript">
    num_respaldo = 0;
    timerID = 0;
    function timer_cargar_documentos_respaldo() {
        if (document.getElementById('div_procesar_respaldo').innerHTML!='Procesando...') {
            if (num_respaldo > 0) {
                tamano_barra=100;
                actualizar_barra(num_respaldo-1);
            }
            if (num_respaldo < <?=$i?>) {
                document.getElementById('div_procesar_respaldo').innerHTML='Procesando...';
                nuevoAjax('div_procesar_respaldo', 'POST', 'backup_usuarios_cargar_documentos.php', 'txt_resp_codi='+respaldo[num_respaldo]);
                ++num_respaldo;
                timerID = setTimeout("timer_cargar_documentos_respaldo()", 1000); // Carga un documento cada segundo
            } else {
                clearTimeout(timerID);
                window.location = 'backup_usuarios_respaldar.php';
                return;
            }
        } else {
            if (num_respaldo > 0) actualizar_barra(num_respaldo-1);
            timerID = setTimeout("timer_cargar_documentos_respaldo()", 1000);
        }
    }

    tamano_barra=0;
    function actualizar_barra(id_barra) {// Controla el desplazamiento de las barras
        ++tamano_barra;
        if (tamano_barra>95 && tamano_barra<100) tamano_barra=95;
        if (tamano_barra>=100) tamano_barra=100;
        try {
            document.getElementById('div_barra_'+id_barra.toString()).style.width=tamano_barra;
        } catch (e) {
            return;
        }
    }
</script>

<body>
  <center>
    <br><br>
    <table width="90%" align="center" class=borde_tab border="1">
        <tr>
            <td width="100%" class="titulos5" colspan="6">
              <center>
                <br><b>PASO 1:</b> B&uacute;squeda de documentos a respaldar<br>&nbsp;
              </center>
            </td>
        </tr>
        <tr>
            <th>&nbsp;</th>
            <th>Solicitud</th>
            <th>Fecha</th>
            <th>Nombre</th>
            <th>Instituci&oacute;n</th>
            <th>Estado</th>
        </tr>
        <?php  echo $tabla; ?>
    </table>
    <br>


    <div name='div_procesar_respaldo' id='div_procesar_respaldo' style="display:none">OK</div>
    <br>
    <input type="button" name="btn_cancelar" value="Regresar" class="botones" 
           onClick="window.close();">
  </center>

  <script language="JavaScript" type="text/JavaScript">
    timer_cargar_documentos_respaldo();
  </script>
</body>
</html>
