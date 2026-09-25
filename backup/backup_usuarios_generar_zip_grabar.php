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
include_once(dirname(__DIR__).'/obtenerdatos.php');
include_once(dirname(__DIR__)."/backup/backup_usuarios_generar_zip_html.php");
include_once("respaldo_funciones.php");

$db = new ConnectionHandler(dirname(__DIR__));
$db->conn->SetFetchMode(ADODB_FETCH_ASSOC);

try {
    $resp_codi=limpiar_sql($_POST["txt_resp_codi"]);

    $sql = "select count(resp_codi) as \"num\" from respaldo_usuario_radicado where fila is null and resp_codi=$resp_codi";
    $rs = $db->query($sql);
    if ($rs->fields["NUM"] > 0) die ("OK");

    $path = dirname(__DIR__)."/bodega/respaldos/respaldo_$resp_codi";

    // Copiamos las imágenes y creamos los archivos index, menú, bandejas, etc.
    copy (dirname(__DIR__)."/img/content/down_icon.png" , "$path/archivos/descargar.png");
    copy (dirname(__DIR__)."/img/content/regresar.png" , "$path/archivos/regresar.png");
    copy (dirname(__DIR__)."/quipux-logo.png" , "$path/archivos/logo.png");
    copy (dirname(__DIR__)."/quipux-logo.png" , "$path/archivos/logo.png");
    copy_r (dirname(__DIR__)."/estilos/jquery" , "$path/documentos/jquery");
    copy (dirname(__DIR__)."/js/jquery.js" , "$path/documentos/jquery/jquery.js");
    copy (dirname(__DIR__)."/js/jquery_tablas.js" , "$path/documentos/jquery/jquery_tablas.js");
    $html = cargar_estilos();
    file_put_contents ("$path/documentos/estilos.css", $html);
    $html = cargar_index();
    file_put_contents ("$path/index.html", $html);
    $html = cargar_top ($resp_codi);
    file_put_contents ("$path/documentos/top.html", $html);
    $html = cargar_menu ();
    file_put_contents ("$path/documentos/menu.html", $html);
    $html = cargar_informacion($resp_codi);
    file_put_contents ("$path/documentos/informacion.html", $html);
    $html = cargar_bandejas ($resp_codi, 2);
    file_put_contents ("$path/documentos/recibidos.html", $html);
    $html = cargar_bandejas ($resp_codi, 1);
    file_put_contents ("$path/documentos/enviados.html", $html);

    $path_actual = exec("pwd");

    chdir($path);
    shell_exec("zip -s 1g -r ../respaldo_$resp_codi.zip *");
    if (!is_file("../respaldo_$resp_codi.zip")) {
        shell_exec("zip -r ../respaldo_$resp_codi.zip *");
        if (!is_file("../respaldo_$resp_codi.zip")) die ("ERROR - No se pudo generar el archivo ZIP");
    }
    chdir($path_actual);

    $sql = "update respaldo_usuario set fecha_fin=".$db->conn->sysTimeStamp." where resp_codi=$resp_codi";
    $db->query($sql);

    //Se consulta fecha de inicio y fin de la solicitud
    $sql_sol = "select * from respaldo_solicitud where resp_codi = $resp_codi";
    $rs_sol = $db->query($sql_sol);

    if($rs_sol && !$rs_sol->EOF){
        $resp_soli_codi = $rs_sol->fields["RESP_SOLI_CODI"];
        $estado_solicitud = 6;
        $estado_respaldo = 12;
        
        //Se actualiza solicitud de respaldo
        $sql = "update respaldo_solicitud set fecha_fin_ejec=".$db->conn->sysTimeStamp.",
            estado_solicitud = $estado_solicitud,
            estado_respaldo  = $estado_respaldo
            where resp_codi=$resp_codi";
        $db->query($sql);

        //Se inserta el histórico
        $usua_codi = 0; //$_SESSION["usua_codi"];
        $fecha_accion = $db->conn->sysTimeStamp;
        $accion = 77;
        $sql = "INSERT INTO respaldo_hist_eventos(resp_soli_codi, usua_codi, fecha, accion, estado_solicitud, estado_respaldo)
        VALUES ($resp_soli_codi, $usua_codi, $fecha_accion, $accion, $estado_solicitud, $estado_respaldo)";
        $db->query($sql);

        //Se envía correo
        //Se consulta datos de solicitud
        $txt_accion = 7;
        $codigo = $rs_sol->fields["RESP_SOLI_CODI"];
        $datos = ObtenerSolicitudPorCodigo($codigo,$db);
        if($datos["estado_solicitud"] == 6){
            $destinatario = $datos["usua_codi_solicita"];
            $remitente = $datos["usua_codi_autoriza"];
            //Se envía correo
            EnviarCorreo($txt_accion, $destinatario, $remitente, $datos, __DIR__, $db);
        }

    }
    $rs->MoveNext();
} catch (Exception $e) {
    die ("OK");
}

die ("OK");


function copy_r( $path, $dest ) {
    if (is_dir($path)) {
        @mkdir( $dest );
        $objects = scandir($path);
        if( sizeof($objects) > 0 ) {
            foreach( $objects as $file ) {
                if( $file == "." || $file == ".." ) continue;
                if( is_dir( "$path/$file" ) )
                    copy_r( "$path/$file", "$dest/$file" );
                else
                    copy( "$path/$file", "$dest/$file" );
            }
        }
        return true;
    } elseif( is_file($path) ) {
        return copy($path, $dest);
    } else {
        return false;
    }
}
?>