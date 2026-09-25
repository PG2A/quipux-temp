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
include_once(dirname(__DIR__).'/rec_session.php');
require_once(dirname(__DIR__).'/funciones.php'); //para traer funciones p_get y p_post
include_once(dirname(__DIR__).'/funciones_interfaz.php');
require_once("respaldo_funciones.php");

$txt_accion = trim(limpiar_numero($_POST["txt_accion"]));
$nombre_accion = "";

$lista_aprobar = $_POST["checkValue"];
$lista_rechazar = $_POST['txt_lista_soli_codigos'];
$fecha_ejecutar = trim(limpiar_sql($_POST['txt_fecha_ejecutar']));
$txt_comentario_rechazo = trim(limpiar_sql($_POST["txt_comentario_rechazo"]));

if($txt_accion==3)
    $titulo="Aprobación de Solicitudes";
else if($txt_accion==4)
    $titulo="Rechazo de Solicitudes";

if($txt_accion == 3 or $txt_accion == 4){
    $ruta_ventana = dirname(__DIR__)."/backup/respaldo_lista.php?txt_tipo_lista=2";
    $tipo_mensaje = "Debe seleccionar las solicitudes que requiere aprobar o rechazar.";
}
else if($txt_accion == 5){
    $ruta_ventana = dirname(__DIR__)."/backup/respaldo_lista.php?txt_tipo_lista=11";
    $tipo_mensaje = "Debe seleccionar las solicitudes que requiere calendarizar.";
}
else
    $ruta_ventana = dirname(__DIR__)."/backup/respaldo_menu.php";

if(!$lista_aprobar and !$lista_rechazar)
    $mensaje = $tipo_mensaje;
else{

    //Se inicia transacción
    if ($db->transaccion==0) $db->conn->BeginTrans();

    //Se consulta usuario que autoriza
    $usua_codi_autoriza = ObtenerCodigoUsuarioAutoriza(33,0,0,$_SESSION["usua_codi"],$db);   

    //Se ejecuta las acciones de: Autorizar o Rechazar
    switch ($txt_accion) {
        case "3": //Autoriza la solicitud
            $nombre_accion = "Aprobar";                     
            foreach ($lista_aprobar as $idLista=>$valor) {
                $datos["RESP_SOLI_CODI"] = limpiar_numero(is_numeric(trim((string)$valor)) ? trim((string)$valor) : $idLista);
                $datos["USUA_CODI_AUTORIZA"] = $usua_codi_autoriza;
                $insertSQL = AutorizarSolicitud($datos, $db);  
            }
            $ruta_ventana = dirname(__DIR__)."/backup/respaldo_lista.php?txt_tipo_lista=2";
           break;
        case "4": //Rechaza la solicitud
            $nombre_accion = "Rechazar";
            foreach ($lista_rechazar as $idLista) {
                $datos["RESP_SOLI_CODI"] =limpiar_numero($idLista);
                $datos["USUA_CODI_AUTORIZA"] = $usua_codi_autoriza;
                $datos["COMENTARIO"] = $txt_comentario_rechazo;
                $insertSQL = RechazarSolicitud($datos, $db);
            }
            $ruta_ventana = dirname(__DIR__)."/backup/respaldo_lista.php?txt_tipo_lista=2";
            break;
        case "5": //Calendarizar fechas de ejecución de solicitud
            $nombre_accion = "Cambiar fecha de ejecución";
            foreach ($lista_rechazar as $idLista) {
                $datos["RESP_SOLI_CODI"] =limpiar_numero($idLista);
                $datos["FECHA_EJECUTAR"] = $fecha_ejecutar;
                $insertSQL = CambiarFechaEjecucion($datos, $db);
            }
            $ruta_ventana = dirname(__DIR__)."/backup/respaldo_lista.php?txt_tipo_lista=11";
            break;      
        default:
            break;
    }

   // echo "tran " . $insertSQL;
    
    //Se finaliza transacción
    if(!$insertSQL) {
        if ($db->transaccion==0){
            $db->conn->RollbackTrans();
            $mensaje = "Error no se realizó la acción de $nombre_accion sobre la(s) solicitud(es). <br> SQL: ".$db->conn->querySql;
        }
        else return 0;
    } else {
        if ($db->transaccion==0){
            $db->conn->CommitTrans();

            //Envío de correo
            switch ($txt_accion) {
                case "3": //Autoriza la solicitud
                    $nombre_accion = "Aprobar";
                    foreach ($lista_aprobar as $idLista=>$valor) {
                        //Se consulta datos de solicitud
                        $codigo = limpiar_numero(is_numeric(trim((string)$valor)) ? trim((string)$valor) : $idLista);
                        $datos = ObtenerSolicitudPorCodigo($codigo,$db);
                        $destinatario = $datos["usua_codi_solicita"];
                        $remitente = $usua_codi_autoriza;
//                        //Se envía correo - Se comenta la notificación por requerimiento
//                        EnviarCorreo($txt_accion, $destinatario, $usua_codi_autoriza, $datos, __DIR__, $db);
                    }
                   break;
                case "4": //Rechaza la solicitud
                    $nombre_accion = "Rechazar";
                    foreach ($lista_rechazar as $idLista) {
                        //Se consulta datos de solicitud
                        $codigo = limpiar_numero($idLista);
                        $datos = ObtenerSolicitudPorCodigo($codigo,$db);
                        $destinatario = $datos["usua_codi_solicita"];
                        $remitente = $usua_codi_autoriza;
                        //Se envía correo
                        EnviarCorreo($txt_accion, $destinatario, $usua_codi_autoriza, $datos, __DIR__, $db);
                    }
                    break;
                default:
                    break;
            }           

            $mensaje = "Datos de solicitud guardados correctamente.";
        }
    }
}

echo "<!DOCTYPE html>".html_head();
echo "<center><br>
<table width='100%' border='1' align='center' class='t_bordeGris' id='usr_datos'>
 <tr><td class='titulos2' colspan='4' align='center'>$titulo</td></tr>
 <tr><td class='listado2' colspan='4' align='center'>$mensaje</td></tr>
</table></center></br>";
?>
<center>
<input type='button' name='btn_aceptar' value='Aceptar' class='botones' onClick='window.location="<?=$ruta_ventana?>"'>
</center>
</body>
</html>