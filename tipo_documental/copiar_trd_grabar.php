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

//session_start();
include_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__).'/funciones_interfaz.php');
require_once(dirname(__DIR__)."/funciones.php"); //para traer funciones p_get y p_post

if (!isset($descTRDpl)) $descTRDpl = "Carpetas Virtuales";

p_register_globals(array());

echo "<!DOCTYPE html>".html_head();

$record = array();

$mensaje = "";
if ($txt_area_destino=="0" or $txt_area_origen=="0") 
    $mensaje = "Por favor seleccione las &aacute;reas origen y destino para copiar las $descTRDpl";

//$rs = $db->conn->query("select depe_codi, count(1) as num from trd_nivel where depe_codi=$txt_area_origen group by 1");
//$num_area_origen =  0 + $rs->fields["NUM"];
//if (!$rs or $num_area_origen==0)
//    $mensaje = "El &aacute;rea origen no tiene creada una estructura de $descTRDpl";
//
//$rs = $db->conn->query("select depe_codi, count(1) as num from trd_nivel where depe_codi=$txt_area_destino group by 1");
//$num_area_destino =  0 + $rs->fields["NUM"];
//if ($num_area_origen>0 and $num_area_destino>0 and $num_area_origen<>$num_area_destino) $mensaje = "No se puede realizar esta acción. Los niveles del área origen son diferentes a los niveles del área destino";
//
//if ($mensaje == "") {
//    if ($num_area_destino == 0) {
//        $sql = "select * from trd_nivel where depe_codi=$txt_area_origen";
//        $rs = $db->conn->query($sql);
//        while (!$rs->EOF) {
//            unset ($record);
//            $record["TRD_CODI"]  = $rs->fields["TRD_CODI"];
//            $record["DEPE_CODI"] = $txt_area_destino;
//            $record["TRD_NOMBRE"] = $db->conn->qstr($rs->fields["TRD_NOMBRE"]);
//            $record["TRD_DESCRIPCION"] = $db->conn->qstr($rs->fields["TRD_DESCRIPCION"]);
//            $ok = $db->conn->Replace("TRD_NIVEL", $record, "", false,false,true,false);
//            $rs->MoveNext();
//        }
//    }
//    CopiarTRD(0,0);
//    $mensaje = "Las $descTRDpl fueron copiadas correctamente";
//}

if ($mensaje == "") {
    CopiarTRD(0,0);
    $mensaje = "Las $descTRDpl fueron copiadas correctamente";
}

function CopiarTRD($trd_padre_origen, $trd_padre_destino) {
    global $db, $txt_area_destino, $txt_area_origen, $record;

    $sql = "select * from trd where trd_padre=$trd_padre_origen";
    if ($trd_padre_origen == 0) $sql .= " and depe_codi=$txt_area_origen";
    $rs = $db->conn->query($sql);
    if (!$rs or $rs->EOF) return;

    while (!$rs->EOF) {
        unset ($record);
        
        $trd_nombre = isset($rs->fields["TRD_NOMBRE"]) ? $rs->fields["TRD_NOMBRE"] : (isset($rs->fields["trd_nombre"]) ? $rs->fields["trd_nombre"] : "");
        $trd_estado = isset($rs->fields["TRD_ESTADO"]) ? $rs->fields["TRD_ESTADO"] : (isset($rs->fields["trd_estado"]) ? $rs->fields["trd_estado"] : "");
        $trd_arch_gestion = isset($rs->fields["TRD_ARCH_GESTION"]) ? $rs->fields["TRD_ARCH_GESTION"] : (isset($rs->fields["trd_arch_gestion"]) ? $rs->fields["trd_arch_gestion"] : "");
        $trd_arch_central = isset($rs->fields["TRD_ARCH_CENTRAL"]) ? $rs->fields["TRD_ARCH_CENTRAL"] : (isset($rs->fields["trd_arch_central"]) ? $rs->fields["trd_arch_central"] : "");
        $trd_fecha_desde = isset($rs->fields["TRD_FECHA_DESDE"]) ? $rs->fields["TRD_FECHA_DESDE"] : (isset($rs->fields["trd_fecha_desde"]) ? $rs->fields["trd_fecha_desde"] : "");
        $trd_nivel = isset($rs->fields["TRD_NIVEL"]) ? $rs->fields["TRD_NIVEL"] : (isset($rs->fields["trd_nivel"]) ? $rs->fields["trd_nivel"] : "");
        $trd_codi = isset($rs->fields["TRD_CODI"]) ? $rs->fields["TRD_CODI"] : (isset($rs->fields["trd_codi"]) ? $rs->fields["trd_codi"] : "");
        
        $record["trd_codi"] = $db->nextId("sec_trd");
        $record["trd_padre"] = $trd_padre_destino;
        $record["trd_nombre"] = $db->conn->qstr($trd_nombre);
        $record["depe_codi"] = $txt_area_destino;
        $record["trd_estado"] = $trd_estado;
        $record["trd_arch_gestion"] = $trd_arch_gestion;
        $record["trd_arch_central"] = $trd_arch_central;
        $record["trd_fecha_desde"] = "'".$trd_fecha_desde."'::timestamp";
        $record["trd_nivel"] = $trd_nivel;
        $ok = $db->conn->Replace("trd", $record, "", false,false,true,false);
        CopiarTRD($trd_codi, $record["trd_codi"]);
        $rs->MoveNext();
    }
    return;
}

?>

<body>
    <center>
        <br><br><br>
        <table width="40%" border="2" align="center" class="t_bordeGris">
            <tr>
                <td width="100%" height="30" class="listado2">
                    <span class=etexto><center><b><br><?=$mensaje?><br>&nbsp;</b></center></span>
                    <br>
                    <center><input type="button" name="btn_aceptar" value="Aceptar" class="botones" onClick="window.location='./menu_trd.php';"></center>
                    <br>
                </td>
            </tr>
        </table>
    </center>
</body>
</html>