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

$sql = "select radi_nume_radi, radi_fech_asig::date, radi_nume_text, now()::date-radi_fech_asig::date as num_dias from radicado
      where esta_codi=2 and radi_inst_actu=".$_SESSION["inst_codi"]." and radi_usua_actu=".$_SESSION["usua_codi"]." and radi_fech_asig::date<now()::date";

$rs_alerta = $db->query("select count(1) as treasignados from ($sql) as a"); // Cuento el número de documentos vencidos
$total_documentos = $rs_alerta->fields['TREASIGNADOS'];

$rs_alerta = $db->query("$sql order by radi_fech_radi asc limit 15 offset 0");
//
echo '<div id="div_bandeja_alerta" style="display: none;">';
//echo '<br>Usuario: '.$_SESSION["usua_codi"].'<br>'; 
//echo '<br>sql: '.$sql.'<br>';
if ($total_documentos > 0) {
    // Escribimos el mensaje de alerta
    echo "Alerta: De los Documento(s) reasignados,
            <a href='javascript:verReferencia();'><font color='red'>$total_documentos se encuentra(n) Vencido(s)</font></a>
          desde la fecha: ".$rs_alerta->fields['RADI_FECH_ASIG'];
    //Dibujamos la ventana de documentos
    if ($total_documentos > 15) $total_documentos = 15;
    
    echo '<div id="miVentana" style="position: fixed; width: 350px; height: 380px; top: 0; float:right; border: #333333 3px solid; background-color: #FAFAFA; color: #000000; display: none;">
                <table width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <th width="98%"><center>Primeros ('.$total_documentos.') Documentos Vencidos</center></th>
                        <th width="2%"><img src="../imagenes/close_button.gif" width="15" height="15" border="0" alt="X" onClick="cerrarReferencia();"></th>
                    </tr>
                    <tr>
                        <td colspan="2" align="center">
                            <br>
                            <table width="95%" cellpadding="0" cellspacing="1" border="0" class="borde_tab">
                                <tr><th width="50%"><center>No. Documento</center></th><th width="30%"><center>Fecha</center></th><th width="20%"><center>No. D&iacute;as</center></th></tr>';

        $i = 1;
        while ($rs_alerta && !$rs_alerta->EOF) {
        echo '<tr class="listado'.((++$i%2)+1).'">
                  <td width="50%"><a href="javascript:void();" onClick=\'mostrar_documento("'.$rs_alerta->fields['RADI_NUME_RADI'].'","'.$rs_alerta->fields['RADI_NUME_TEXT'].'","2")\' style="color: blue;">'.$rs_alerta->fields['RADI_NUME_TEXT'].'</a></td>
                  <td width="30%">'.$rs_alerta->fields['RADI_FECH_ASIG'].'</td>
                  <td width="20%">'.$rs_alerta->fields['NUM_DIAS'].' d&iacute;as</td>
              </tr>';
        $rs_alerta->MoveNext();
    }

    echo '                  </table>
                        </td>
                    </tr>
               </table>
          </div>';
}
echo '</div>';
//
?>