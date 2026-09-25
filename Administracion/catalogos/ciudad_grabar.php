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

    $txt_codigo_ciudad = (int)trim(limpiar_numero($_POST["txt_cod_ciudad"]));    
    $txt_nombre_ciudad = trim(limpiar_sql($_POST["txt_nombre_ciudad"]));
    
    $txt_codigo_ciudad_dep = (int)trim(limpiar_numero($_POST["txt_cod_ciudad_dep"]));//nuevo    
    $txt_nombre_ciudad_dep = trim(limpiar_sql($_POST["txt_nombre_ciudad_dep"]));
    $txt_id_padre = (int)trim(limpiar_numero($_POST["txt_id_padre"]));    
    //echo $txt_codigo_ciudad."-".$txt_codigo_ciudad_dep;
    
    // Initialize variables
    $insertSQL = false;
    $transaccion = isset($db->transaccion) ? $db->transaccion : 0;

    if ($transaccion==0) $db->conn->BeginTrans();
        
            $sql = "select nombre from ciudad where (translate(upper(nombre),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')  like translate(upper('".$txt_nombre_ciudad_dep."'),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN'))";
            if($txt_codigo_ciudad ==0 || $txt_codigo_ciudad=='')
                $where = " and id_padre in ($txt_id_padre)";
            else
                $where = " and id_padre in ($txt_codigo_ciudad)";
            $sql = $sql.$where;
        
    //echo $sql;
    $rs = $db->conn->Execute($sql);
    $nombre_consultado = ($rs && !$rs->EOF) ? $rs->fields["NOMBRE"] : ""; // Check result

    if ($nombre_consultado!=''){
        $nombreExiste= "El nombre ".$nombre_consultado;
    }else{
        if($txt_codigo_ciudad == ""){
            $sql = "select max(id) as id from ciudad";
            $rs = $db->conn->Execute($sql);
            $txt_codigo_ciudad = ($rs && !$rs->EOF) ? $rs->fields["ID"]+1 : 1;
        }
        //Datos de Ciudad
        //nuevo en casos de paises o ciudades que se modifiquen
        if ($txt_nombre_ciudad==''){
            $record["ID"] = $txt_codigo_ciudad;
            $record["NOMBRE"] = "'".ucwords(strtolower(trim($txt_nombre_ciudad_dep)))."'";
            if ($txt_id_padre>0)
            $record["ID_PADRE"] = $txt_id_padre;
            
            if (isset($record["ID_PADRE"]) && $record["ID"]!=$record["ID_PADRE"] && trim($txt_nombre_ciudad_dep)!='')                
            $insertSQL=$db->conn->Replace("CIUDAD", $record, "ID", false,false,true,false);
            
        }else{
            
            $record["ID"] = $txt_codigo_ciudad;
            $record["NOMBRE"] = "'".ucwords(strtolower(trim($txt_nombre_ciudad)))."'";
            $record["ID_PADRE"] = $txt_id_padre;
            if (isset($record["ID_PADRE"]) && $record["ID"]!=$record["ID_PADRE"])
            $insertSQL=$db->conn->Replace("CIUDAD", $record, "ID", false,false,true,false);

            //dependencia
            if (trim($txt_nombre_ciudad_dep)!=''){                
                $record["ID"] = $txt_codigo_ciudad_dep;
                $record["NOMBRE"] = "'".ucwords(strtolower(trim($txt_nombre_ciudad_dep)))."'";
                $record["ID_PADRE"] = $txt_codigo_ciudad;
                if (isset($record["ID_PADRE"]) && $record["ID"]!=$record["ID_PADRE"])
                $insertSQL=$db->conn->Replace("CIUDAD", $record, "ID", false,false,true,false);
            }
        }
    }

    //Se finaliza transacción
    if(!$insertSQL) {
        if ($transaccion==0){
            $db->conn->RollbackTrans();
            if(isset($nombre_consultado) && $nombre_consultado!=""){
                
                $mensaje = "$nombreExiste ya existe<br> ";
                
             }    
            else
                $mensaje = "Se ha modificado correctamente. <br> "; //SQL: ".$db->conn->querySql;
        }
        else return 0;
    } else {

        if ($transaccion==0){
            $db->conn->CommitTrans();
            $mensaje = "Datos de ciudad guardados correctamente. <br> ";
        }
    }

    echo "<!DOCTYPE html>".html_head();
    
    //echo "<center><br>$nombreExiste</center></br>";
    echo "<center><br>$mensaje</center></br>";
?>
<form name="formulario" action="" method="post">
<center>
<input type='button' name='btn_aceptar' value='Aceptar' class='botones' onClick="window.location='ciudad.php'">
</center>
  </body>
</html>
</form>