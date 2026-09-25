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

    $txt_codigo_titulo = trim(limpiar_numero($_POST["txt_cod_titulo"]));
    $txt_nombre_titulo = trim(limpiar_sql($_POST["txt_nombre_titulo"]));
    $txt_abreviatura_titulo = trim(limpiar_sql($_POST["txt_abreviatura_titulo"]));

    // Initialize variables
    $insertSQL = false;
    $transaccion = isset($db->transaccion) ? $db->transaccion : 0;

    if ($transaccion==0) $db->conn->BeginTrans();

    $sql = "select tit_nombre from titulo where (translate(upper(tit_nombre),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')  like translate(upper('".$txt_nombre_titulo."'),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN'))";
    if($txt_codigo_titulo != "")
        $where = " and tit_codi <> $txt_codigo_titulo";
    else
        $where = "";
    $sql = $sql.$where;
    $rs = $db->conn->Execute($sql);
    $nombre_consultado = ($rs && !$rs->EOF) ? $rs->fields["TIT_NOMBRE"] : "";

    if($nombre_consultado==""){       
        //Datos de Título
        if($txt_codigo_titulo == "") {
             $sql = "select max(tit_codi) as id from titulo";
             $rs = $db->conn->Execute($sql);
             $txt_codigo_titulo = ($rs && !$rs->EOF) ? $rs->fields["ID"]+1 : 1;
        }
        $record["tit_codi"] = $txt_codigo_titulo;
        $record["tit_nombre"] = "'".ucwords(strtolower(trim($txt_nombre_titulo)))."'";
        $record["tit_abreviatura"] = "'".trim($txt_abreviatura_titulo)."'";
        
        //$db->conn->debug = true; // Enable debugging
        $insertSQL=$db->conn->Replace("titulo", $record, "tit_codi", false,false,true,false);
    }

    //Se finaliza transacción
    if(!$insertSQL) {
        if ($transaccion==0){
            $db->conn->RollbackTrans();
            if($nombre_consultado!="")
                $mensaje = "El título académico ". $txt_nombre_titulo . " ya existe. <br> ";
            else
                $mensaje = "Error no se guardó el título académico: " . $db->conn->ErrorMsg();
        }
        else return 0;
    } else {

        if ($transaccion==0){
            $db->conn->CommitTrans();
            $mensaje = "Datos de título académico guardados correctamente. <br> ";
        }
    }

    echo "<!DOCTYPE html>".html_head();
    echo "<center><br>$mensaje</center></br>";
?>
<form name="formulario" action="" method="post">
<center>
<input type='button' name='btn_aceptar' value='Aceptar' class='botones' onClick="window.location='titulo_usuario.php'">
</center>
  </body>
</html>
</form>