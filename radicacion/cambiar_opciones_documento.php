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
 * @package    radicacion
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
require_once(dirname(__DIR__).'/rec_session.php');
require_once(dirname(__DIR__).'/funciones.php');
require_once(dirname(__DIR__)."/obtenerdatos.php");
include_once(dirname(__DIR__).'/include/tx/Historico.php');

$Historico = new Historico($db);

$radi_nume = limpiar_numero($_POST["txt_radi_nume"]);
$opcion = limpiar_sql($_POST["txt_opcion"]);
$dato_recibido = trim(limpiar_sql(base64_decode(base64_decode(limpiar_sql($_POST["txt_dato"])))));
$dato = 0+$dato_recibido;

// Las opciones respaldadas por un catalogo se dibujan con GetMenu2(). Si el combo se
// genera sin value (ver ConnectionHandler::_loadADOdb) llega una cadena vacia y el
// UPDATE grabaria 0, dejando el documento con un tipo/categoria equivocado. Hay que
// cortar antes de tocar la base, pero NO se puede usar "> 0" como criterio: el codigo
// 0 es un valor legitimo del catalogo ("Normal" en categoria, "Sin tipificacion" en
// codificacion), y rechazarlo impedia grabar esas dos opciones. Se valida que sea un
// entero y que exista realmente en su catalogo.
$opciones_de_catalogo = array(
    "categoria"    => array("tabla" => "categoria",    "campo" => "cat_codi"),
    "tipificacion" => array("tabla" => "codificacion", "campo" => "cod_codi"),
    "tipo_doc"     => array("tabla" => "tiporad",      "campo" => "trad_codigo"),
);
if (isset($opciones_de_catalogo[$opcion])) {
    $catalogo = $opciones_de_catalogo[$opcion];
    $valido = ctype_digit($dato_recibido);
    if ($valido) {
        $rs_catalogo = $db->conn->Execute("select 1 from ".$catalogo["tabla"]." where ".$catalogo["campo"]." = $dato");
        $valido = ($rs_catalogo && !$rs_catalogo->EOF);
    }
    if (!$valido) {
        error_log("cambiar_opciones_documento: valor invalido para '$opcion' en el radicado $radi_nume (recibido: '$dato_recibido')");
        die("No se recibi&oacute; un valor v&aacute;lido para la opci&oacute;n seleccionada. El documento no fue modificado.");
    }
}

$radicado = ObtenerDatosRadicado($radi_nume, $db);

/**
 * Devuelve un mapa codigo => descripcion para armar la observacion del historico.
 * Se lee todo el recordset: cuando el valor anterior y el nuevo coinciden la consulta
 * trae una sola fila, y el patron anterior (dos lecturas fijas con MoveNext) accedia
 * al recordset ya en EOF.
 */
function nombres_de_catalogo($rs, $campo_codigo, $campo_descripcion) {
    $nomb = array();
    while ($rs && !$rs->EOF) {
        $nomb[0 + $rs->fields[$campo_codigo]] = $rs->fields[$campo_descripcion];
        $rs->MoveNext();
    }
    return $nomb;
}

switch ($opcion) {
    case "categoria":
        if (($radicado["estado"]==1 and $radicado["usua_actu"]==$_SESSION["usua_codi"]) or
            ($radicado["estado"]==9 and $_SESSION["perm_tramitar_docs_ciudadano"]==1 and $_SESSION["inst_codi"]==$radicado["inst_actu"])) {
            $sql = "update radicado set cat_codi=$dato where radi_nume_radi=$radi_nume";
            if($db->conn->Execute($sql)) {
                $rs = $db->conn->Execute("select cat_descr, cat_codi from categoria where cat_codi in ($dato,".(0+$radicado["cat_codi"]).")");
                $nomb = nombres_de_catalogo($rs, "CAT_CODI", "CAT_DESCR");

                $observa = "Cambió de categoría de ".$nomb[(0+$radicado["cat_codi"])]." a ".$nomb[$dato];
                $Historico->insertarHistorico($radi_nume, $_SESSION["usua_codi"], $_SESSION["usua_codi"], $observa, 11);
            }
        }
        break;

    case "tipificacion":
        if (($radicado["estado"]==1 and $radicado["usua_actu"]==$_SESSION["usua_codi"]) or
            ($radicado["estado"]==9 and $_SESSION["perm_tramitar_docs_ciudadano"]==1 and $_SESSION["inst_codi"]==$radicado["inst_actu"])) {
            $sql = "update radicado set cod_codi=$dato where radi_nume_radi=$radi_nume";
            if($db->conn->Execute($sql)) {
                $rs = $db->conn->Execute("select cod_descripcion, cod_codi from codificacion where cod_codi in ($dato,".(0+$radicado["cod_codi"]).")");
                $nomb = nombres_de_catalogo($rs, "COD_CODI", "COD_DESCRIPCION");

                $observa = "Cambió la tipificación del documento de ".$nomb[(0+$radicado["cod_codi"])]." a ".$nomb[$dato];
                $Historico->insertarHistorico($radi_nume, $_SESSION["usua_codi"], $_SESSION["usua_codi"], $observa, 11);
            }
        }
        break;

    case "tipo_doc":
        
        if (($radicado["estado"]==1 and $radicado["usua_actu"]==$_SESSION["usua_codi"]) or
            ($radicado["estado"]==9 and $_SESSION["perm_tramitar_docs_ciudadano"]==1 and $_SESSION["inst_codi"]==$radicado["inst_actu"])) {
            $sql = "update radicado set radi_tipo=$dato where radi_nume_radi=$radi_nume";
            if($db->conn->Execute($sql)) {
                $rs = $db->conn->Execute("select trad_descr, trad_codigo from tiporad where trad_codigo in ($dato,".$radicado["radi_tipo"].")");
                $nomb = nombres_de_catalogo($rs, "TRAD_CODIGO", "TRAD_DESCR");

                $observa = "Cambió el tipo de documento de ".$nomb[$radicado["radi_tipo"]]." a ".$nomb[$dato];
                $Historico->insertarHistorico($radi_nume, $_SESSION["usua_codi"], $_SESSION["usua_codi"], $observa, 11);
                //Eliminar las opciones de impresion
                if ($radi_nume!=''){
                    $sql_exit = "select * from opciones_impresion where radi_nume_radi=$radi_nume";
                    $rs_ex=$db->conn->Execute($sql_exit);
                    if(!$rs_ex->EOF){
                        $sqlDel = "delete from opciones_impresion where radi_nume_radi = $radi_nume";
                        $db->conn->Execute($sqlDel);
                        $observa = "Se eliminó las opciones de impresion por cambio de tipo de Documento de  ".$nomb[$radicado["radi_tipo"]]." a ".$nomb[$dato];
                        $Historico->insertarHistorico($radi_nume, $_SESSION["usua_codi"], $_SESSION["usua_codi"], $observa, 11);
                    }
                }
            }
        }
        break;

    case "nivel_seguridad":
        if ((($radicado["estado"] == 1 or $radicado["estado"] == 2) and $radicado["usua_actu"]==$_SESSION["usua_codi"]) or
            ($radicado["estado"]==9 and $_SESSION["perm_tramitar_docs_ciudadano"]==1 and $_SESSION["inst_codi"]==$radicado["inst_actu"])) {
            $sql = "update radicado set radi_permiso=$dato where radi_nume_radi=$radi_nume";
            if($db->conn->Execute($sql)) {
                $nomb[0] = "Público";
                $nomb[1] = "Confidencial";

                $observa = "Cambió el nivel de seguridad del documento de ".$nomb[$radicado["seguridad"]]." a ".$nomb[$dato];
                $Historico->insertarHistorico($radi_nume, $_SESSION["usua_codi"], $_SESSION["usua_codi"], $observa, 11);
            }
        }
        break;

    case "usua_redirigido":
        if (($radicado["estado"]==1 and $radicado["usua_actu"]==$_SESSION["usua_codi"]) or
            ($radicado["estado"]==9 and $_SESSION["perm_tramitar_docs_ciudadano"]==1 and $_SESSION["inst_codi"]==$radicado["inst_actu"])) {
            $sql = "update radicado set radi_usua_redirigido=$dato where radi_nume_radi=$radi_nume";
            if($db->conn->Execute($sql)) {
                $rs = $db->conn->Execute("select usua_nombre from usuario where usua_codi = $dato");
                $observa = "El documento será enviado a ".$rs->fields["USUA_NOMBRE"];
                $Historico->insertarHistorico($radi_nume, $_SESSION["usua_codi"], $_SESSION["usua_codi"], $observa, 11);
            }
        }
        break;

    case "radi_resumen":
        if ((($radicado["estado"]==2 or $radicado["estado"]==1 or $radicado["estado"]==6) and $radicado["usua_actu"]==$_SESSION["usua_codi"]) or
            ($radicado["estado"]==9 and $_SESSION["perm_tramitar_docs_ciudadano"]==1 and $_SESSION["inst_codi"]==$radicado["inst_actu"])) {
            $dato = $db->conn->qstr(substr(limpiar_sql(base64_decode(base64_decode(limpiar_sql($_POST["txt_dato"])))),0,998));
            $sql = "update radicado set radi_resumen=$dato where radi_nume_radi=$radi_nume";
            if($db->conn->Execute($sql)) {
                $observa = "Se cambió/añadió una nota al documento.";
                $Historico->insertarHistorico($radi_nume, $_SESSION["usua_codi"], $_SESSION["usua_codi"], $observa, 11);
            }
        }
        break;

}
?>