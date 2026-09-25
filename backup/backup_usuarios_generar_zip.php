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

include_once(dirname(__DIR__).'/config.php');
include_once(dirname(__DIR__).'/include/db/ConnectionHandler.php');
include_once(dirname(__DIR__).'/funciones.php');
include_once(dirname(__DIR__).'/funciones_interfaz.php');

$db = new ConnectionHandler(dirname(__DIR__));
$db->conn->SetFetchMode(ADODB_FETCH_ASSOC);


echo "<!DOCTYPE html>".html_head();
include_once('../js/ajax.js');

echo "<script>\n";
echo "var respaldo = new Array();\n";

// Buscamos los respaldos incompletos
$sql = "select distinct resp_codi from respaldo_usuario_radicado where fila is null";
$rs = $db->query($sql);
$respaldos_incompletos = "0";
while (!$rs->EOF) {
    $respaldos_incompletos .= ',' . $rs->fields["RESP_CODI"];
    $rs->MoveNext();
}

// Buscamos los respaldos que terminaron
$sql = "select r.resp_codi, r.fecha_solicita, u.usua_nombre, u.inst_nombre
        from respaldo_usuario r left outer join usuario u on r.usua_codi=u.usua_codi
        where r.resp_codi in (select distinct resp_codi from respaldo_usuario_radicado
                              where fila is not null and resp_codi not in ($respaldos_incompletos))
        and fecha_fin is null";
$rs = $db->query($sql);

$i = 0;
$tabla = "";
while(!$rs->EOF) {
    echo "respaldo[$i]='" . $rs->fields["RESP_CODI"] . "';";
    $tabla .= "\n<tr><td align='center'>$i</td>".
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

$link_siguiente = "backup_usuarios_estado.php";
$sql = "select count(1) as \"num\" from respaldo_usuario_radicado where fila is null and num_error<3";
$rs = $db->query($sql);
if ($rs->fields["NUM"] > 0) $link_siguiente = "backup_usuarios_respaldar.php";


?>
<script language="JavaScript" type="text/JavaScript">
    num_respaldo = 0;
    timerID = 0;
    function timer_empaquetar_respaldo() {
        if (document.getElementById('div_procesar_respaldo').innerHTML!='Procesando...') {
            if (num_respaldo > 0) {
                tamano_barra=100;
                actualizar_barra(num_respaldo-1);
            }
            if (num_respaldo < <?=$i?>) {
                document.getElementById('div_procesar_respaldo').innerHTML='Procesando...';
                nuevoAjax('div_procesar_respaldo', 'POST', 'backup_usuarios_generar_zip_grabar.php', 'txt_resp_codi='+respaldo[num_respaldo]);
                ++num_respaldo;
                timerID = setTimeout("timer_empaquetar_respaldo()", 1000); // Carga un documento cada segundo
            } else {
                clearTimeout(timerID);
                window.location = '<?=$link_siguiente?>';
                return;
            }
        } else {
            if (num_respaldo > 0) actualizar_barra(num_respaldo-1);
            timerID = setTimeout("timer_empaquetar_respaldo()", 1000);
        }
    }

    tamano_barra=0;
    function actualizar_barra(id_barra) {
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
            <td width="100%" class="titulos5" colspan="5">
              <center>
                <br><b>PASO 3:</b> Empaquetar respaldos<br>&nbsp;
              </center>
            </td>
        </tr>
        <tr>
            <th>&nbsp;</th>
            <th>Fecha</th>
            <th>Nombre</th>
            <th>Institucion</th>
            <th>Estado</th>
        </tr>
        <?php  echo $tabla; ?>
    </table>
    <br>


    <div id='div_procesar_respaldo' style="display:none">OK</div>
    <br>
    <input type="button" name="btn_cancelar" value="Regresar" class="botones" 
           onClick="window.close();">
  </center>

  <script language="JavaScript" type="text/JavaScript">
    timer_empaquetar_respaldo();
  </script>
</body>
</html>
