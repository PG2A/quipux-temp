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

    $txt_cont_codi = trim(limpiar_numero($_POST["txt_cont_codi"]));
    $txt_descripcion = trim(limpiar_sql($_POST["txt_descripcion"]));
    $txt_texto = limpiar_sql(base64_decode(base64_decode($_POST["txt_texto_usuario"])),0);
    $cmb_tipo_contenido = trim(limpiar_numero($_POST["cmb_tipo_contenido"]));
   
    $transaccion = isset($db->transaccion) ? $db->transaccion : 0;
    if ($transaccion==0) $db->conn->BeginTrans();
    //$db->conn->debug = true; // Debug disabled

    //$txt_cont_codi = 1;
    //Datos de Título
    if($txt_cont_codi != "" && $txt_cont_codi != 0)
        $record["cont_codi"] = $txt_cont_codi;
    else {
        // Generate new ID manually if needed (similar to TITULO)
        // Check if sequence or max+1 is used. Assuming max+1 based on Titulo fix.
        $sqlCodi = "SELECT MAX(cont_codi) AS ID FROM contenido";
        $rsCodi = $db->conn->Execute($sqlCodi);
        $txt_cont_codi = ($rsCodi && !$rsCodi->EOF) ? $rsCodi->fields["ID"]+1 : 1;
        $record["cont_codi"] = $txt_cont_codi;
        $record["fecha_crea"] = $db->conn->sysTimeStamp;
    }
    $record["cont_tipo_codi"] = $cmb_tipo_contenido;
    $record["descripcion"] = "'".trim($txt_descripcion)."'";
    $record["texto"] = "'".trim($txt_texto)."'";
    $record["fecha_actualiza"] =$db->conn->sysTimeStamp;
    $insertSQL=$db->conn->Replace("contenido", $record, "cont_codi", false,false,true,false);

    //Se finaliza transacción
    if(!$insertSQL) {
        if ($transaccion==0){
            $db->conn->RollbackTrans();           
            $mensaje = "Error no se guardó el contenido. <br> ERROR: " . $db->conn->ErrorMsg(); // Add detailed error
        }
        else return 0;
    } else {

        if ($transaccion==0){
            $db->conn->CommitTrans();
            $mensaje = "Datos de contenido guardados correctamente. <br> ";
        }
    }

    echo "<!DOCTYPE html>".html_head();
    echo "<center><br>$mensaje</center></br>";
?>
<form name="formulario" action="" method="post">
<center>
<input type='button' name='btn_aceptar' value='Aceptar' class='botones' onClick="window.location='contenido.php'">
</center>
  </body>
</html>
</form>