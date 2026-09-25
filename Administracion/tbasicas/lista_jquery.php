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
 * @package    tbasicas
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');

echo "<!DOCTYPE html>".html_head();
require_once("../../js/ajax.js");
switch ($_GET['tipo_reporte']) {
    case 'instituciones':
        $titulo = "Listado General de Instituciones";
        $sql = "select inst_codi as \"CÓDIGO\", coalesce(inst_ruc,' ') as \"RUC\", coalesce(inst_nombre,' ') as \"NOMBRE\",
                 coalesce(inst_sigla,' ') as \"SIGLA\" from institucion where inst_estado <> 0";
        break;
    default :
        die ("No se encontr&oacute; el reporte solicitado");
}
$rs = $db->conn->query($sql);
if (!$rs or $rs->EOF) die("No se encontraron registros para el reporte solicitado");

$thead = "";
$tbody = "";
foreach ($rs->fields as $id_campo => $valor) {
    $thead .= "<th><center>$id_campo</center></th>";
}
$thead = "<tr>$thead</tr>\n";

$editable = ($_GET['tipo_reporte'] == 'instituciones');

while (!$rs->EOF) {
    $fila_attr = "";
    if ($editable) {
        $codigo_fila = (int)reset($rs->fields);
        $fila_attr = " class=\"fila-editable\" title=\"Editar esta institución\" onclick=\"editar($codigo_fila);\"";
    }
    $tbody .= "<tr$fila_attr>";
    foreach ($rs->fields as $id_campo => $valor) {
        $tbody .= "<td>$valor</td>";
    }
    $tbody .= "</tr>\n";
    $rs->MoveNext();
}


?>
    <style type="text/css" title="currentStyle">
        @import "../../estilos/jquery/style_datatables.css";
        tr.fila-editable{cursor:pointer}
        tr.fila-editable:hover td{background:#e5eeff}
    </style>
    <script type="text/javascript" src="../../js/jquery.js"></script>
    <script type="text/javascript" src="../../js/jquery_tablas.js"></script>
    <script type="text/javascript" charset="utf-8">
        $(document).ready(function() {
            $('#tbl_listado').dataTable({"iDisplayLength": 20});
        });
    function cerrar() {
        if (window.opener && !window.opener.closed) {
            window.close();
        } else {
            window.location.href = 'adm_instituciones.php';
        }
    }

    function editar(codi) {
        var destino = 'adm_instituciones.php?slc_institucion=' + codi;
        if (window.opener && !window.opener.closed) {
            window.opener.location.href = destino;
            window.close();
        } else {
            window.location.href = destino;
        }
    }
    function excel(tipo) {
        if (tipo==1)
            nuevoAjax('div_reporte', 'POST', 'reporte_instituciones.php', 'tipo=xls');
        else
            nuevoAjax('div_reporte', 'POST', 'reporte_instituciones.php', 'tipo=pdf');
    }
    
    </script>
    <body>
        <table cellpadding="0" cellspacing="0" border="0" class="display" id="tbl_listado" width="100%">
            <thead><?=$thead?></thead>
            <tbody><?=$tbody?></tbody>
        </table>
        <table width='100%'><tr><td>
                    <?php
                    echo '<input type="button" name="btn_cerrar" class="botones_largo" value="Cerrar" onclick="cerrar();" title="Cierra esta ventana">';
                    echo '<input type="button" name="btn_buscar" class="botones_largo" value="Exportar a XLS" onclick="excel(1);" title="Exporta todas las instituciones">';?>
                    <?php
                    //echo '<input type="button" name="btn_buscar" class="botones_largo" value="Exportar a PDF" onclick="excel(2);" title="Exporta todas las instituciones">';?>
                    
                </td></tr>
        <tr><td><div id='div_reporte' style="width: 99%"></div>
            </td></tr></table>
        <?php 
        //include "reporte_instituciones.php";
        ?>
    </body>
</html>
