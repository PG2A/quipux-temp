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
 * @package    tx
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// Defensive defaults used by some functions in this file (avoid undefined variable notices)
$ruta_raiz = $ruta_raiz ?? '';
$db = $db ?? null; // provided by bootstrap in normal runtime
$carpeta = $carpeta ?? null;
$codTx = $codTx ?? null;

/**
 * @global \ADOConnection $db
 * @global int|null $carpeta
 */
include_once(dirname(__DIR__,2)."/include/tx/Historico.php");
include_once(dirname(__DIR__,2)."/funciones_interfaz.php");
class Tx extends Historico
{
    var  $db;
    var  $ruta_raiz;
    var  $flag_firmar;

/**
* Constructor de la clase Tx
* @param $db variable en la cual se recibe la conexión con la BDD
*/
function __construct($db)
{
    $this->db=$db;
    $this->ruta_raiz = $this->getRutaRaiz();
    $this->flag_firmar = false;
}


function getRutaRaiz() {
    if (is_file("./config.php")) return ".";
    if (is_file("../config.php")) return "..";
    if (is_file("../../config.php")) return "../..";
    return "";
}

function comentarDocumento($radicados, $usua_codi, $observa)
{
    // Devolvia "" tanto si comentaba como si no, asi que realizarTx no podia
    // distinguir exito de fracaso y la pantalla decia "ACCION REQUERIDA COMPLETADA"
    // aunque el continue de abajo hubiera saltado todos los documentos.
    include_once(dirname(__DIR__,2).'/obtenerdatos.php');
    $usr_actu = ObtenerDatosUsuario($usua_codi,$this->db);
    $comentados = 0;
    foreach($radicados as $radi_nume)
    {
        $rs = $this->validarEstado($radi_nume);
        if (!$rs || $rs->EOF) { error_log("QUIPUX comentarDocumento: no se encontro el radicado $radi_nume"); continue; }
        $this->insertarHistorico($radi_nume, $usua_codi, $rs->fields["RADI_USUA_ACTU"], $observa, 21);
        if ($_SESSION["usua_codi"]!=$rs->fields["RADI_USUA_ACTU"]){
            $mail_param["comentario"] = $observa;
            $this->enviarMail($_SESSION["usua_codi"], $rs->fields["RADI_USUA_ACTU"], $radi_nume, "Documento Comentado", "21", $mail_param);
        }
        ++$comentados;
    }
    if ($comentados == 0) return "";
    return $usr_actu["nombre"] ?? '';
}

function devolverDocumento($radicados, $usua_codi, $observa)
{
    foreach($radicados as $noRadicado)
    {
        $rs = $this->validarEstado($noRadicado);
        $this->insertarHistorico($noRadicado, $usua_codi, $usua_codi, $observa, 23);
        $this->insertarHistorico($rs->fields["RADI_NUME_TEMP"], $usua_codi, $usua_codi, $observa, 23);
    }
    return "";
}


function eliminarDocumento($radicados, $usua_codi, $observa)
{
    include_once "$this->ruta_raiz/funciones.php";
    include_once "$this->ruta_raiz/obtenerdatos.php";

    $usr_actu = ObtenerDatosUsuario($usua_codi,$this->db);

    $eliminados = 0;
    foreach($radicados as $radi_nume)
    {
    	$flag = false;
        $rs = $this->validarEstado($radi_nume);
        if (!$rs || $rs->EOF) { error_log("QUIPUX eliminarDocumento: no se encontro el radicado $radi_nume"); continue; }
        if($rs->fields["ESTA_CODI"]==1 && $rs->fields["RADI_USUA_ACTU"]==$usua_codi) {
            // Si elimino un documento en estado de elaboracion
            $estado = 7;
            $flag = true;
            // Cancelamos todas las tareas pendientes
            $this->cancelarTodasTareasEnviadas($radi_nume, "Se eliminó el documento");
        }
        if($rs->fields["ESTA_CODI"]==7 && $rs->fields["RADI_USUA_ACTU"]==$usua_codi && substr($radi_nume,-1)!="1") {
            // Si elimino definitivamente un documento, solo se puede eliminar documentos en estado de elaboracion
            $estado = 8;
            $flag = true;
        }
        if($rs->fields["ESTA_CODI"]==5) {
            // Eliminar documentos pendientes para envio manual
            $estado = 7;
            $flag = true;
            $radi = ObtenerDatosRadicado ($radi_nume, $this->db);
            $usr_dest = ObtenerDatosUsuario(str_replace("-","",$radi["usua_dest"]),$this->db);
            $tmp_obs = "Se eliminó documento destinado a " . $usr_dest["nombre"] . ". $observa";

            $this->insertarHistorico($rs->fields["RADI_NUME_TEMP"], $usua_codi, $usua_codi, $tmp_obs, 16, $radi_nume);

            $usr_dest = ObtenerDatosUsuario($rs->fields["RADI_USUA_ACTU"],$this->db);
            $mail = "<!DOCTYPE html><title>Informaci&oacute;n Quipux</title>";
            $mail .= "<body><center><h1>QUIPUX</h1><br><h2>Sistema de Gesti&oacute;n Documental</h2></center>";
            $mail .= "<br><br>Estimado(a):<br><br>".$usr_dest["abr_titulo"] . " " . $usr_dest["nombre"] . "<br>" . $usr_dest["cargo"];
            $mail .= "<br><br>El funcionario ".$usr_actu["abr_titulo"] . " " . $usr_actu["nombre"] .
                     ", ha eliminado el documento No. " . $radi["radi_nume_text"] .
                     " que se encontraba en espera de ser firmado y enviado manualmente en la bandeja Por Imprimir.";
            $mail .= "<br><br>Por favor revise su bandeja de Documentos Eliminados en el sistema &quot;**SISTEMA**&quot;";
            $mail .= "**DESPEDIDA**</body></html>";
            enviarMail($mail, "Informaci&oacute;n documento eliminado", $usr_dest["email"], $usr_dest["nombre"], $this->ruta_raiz);
        }
        if ($flag) { // Si cumple los requerimientos para eliminar el documento
            $this->db->conn->Execute("update radicado set esta_codi=$estado where radi_nume_radi=$radi_nume");
            // Quitar la asociacion de documentos
            $this->db->conn->Execute("update radicado set radi_nume_asoc=null where radi_nume_asoc=$radi_nume");
            $this->insertarHistorico($radi_nume, $usua_codi, $usua_codi, $observa, 16);
            ++$eliminados;
        }
    }
    if ($eliminados == 0) return "";
    return $usr_actu["nombre"];
}


function noEliminarDocumento($radicados, $usua_codi, $observa, &$mensaje)
{
    include_once "$this->ruta_raiz/obtenerdatos.php";
    $usr_actu = ObtenerDatosUsuario($usua_codi,$this->db);
    $flag1 = false;
    $flag2 = false;

    foreach($radicados as $radi_nume)
    {
        $flag = false;
		$rs = $this->validarEstado($radi_nume);
		if (!$rs || $rs->EOF) { error_log("QUIPUX noEliminarDocumento: no se encontro el radicado $radi_nume"); continue; }

		if($rs->fields["ESTA_CODI"]==7 && $rs->fields["RADI_USUA_ACTU"]==$usua_codi)
		{
            $estado = 1;
            if (substr($radi_nume,-1) == "1") {
                $estado = 5;

                $radi = ObtenerDatosRadicado ($radi_nume, $this->db);
                $usr_dest = ObtenerDatosUsuario(str_replace("-","",$radi["usua_dest"]),$this->db);
                $tmp_obs = "Se restauró documento destinado a " . $usr_dest["nombre"] . ". $observa";
                $this->insertarHistorico($rs->fields["RADI_NUME_TEMP"], $usua_codi, $usua_codi, $tmp_obs, 17, $radi_nume);
                $flag2 = true;
            } else
                $flag1 = true;
            $flag = true;
		}
    	if ($flag) {
            $this->db->conn->Execute("update radicado set esta_codi=$estado where radi_nume_radi=$radi_nume");
            $this->insertarHistorico($radi_nume, $usua_codi, $usua_codi, $observa, 17);
        }
    }
    if ($flag1 && $flag2) {
        $mensaje = "El/Los documento(s) est&aacute;n en las bandejas &quot;En Elaboración&quot; y &quot;Por Imprimir&quot;.";
    } else {
        if ($flag1)
            $mensaje = "El/Los documento(s) est&aacute;n en la bandeja &quot;En Elaboración&quot;.";
        if ($flag2) 
            $mensaje = "El/Los documento(s) est&aacute;n en la bandeja &quot;Por Imprimir&quot;.";
    }
    if (!$flag1 && !$flag2) return "";
    return $usr_actu["nombre"];
}


function informar($radicados, $usua_codi, $usua_dest, $observa)
{
    include_once(dirname(__DIR__,2).'/obtenerdatos.php');		//Consulta de datos de los usuarios y radicados
    $usua_dest = (int)$usua_dest;
    if ($usua_dest <= 0) return "";
    $usr_dest = ObtenerDatosUsuario($usua_dest,$this->db);

    //$observa = "A: " . $usr_dest["login"] . " - $observa";
    $mail_param["num_docs"] = 0;
    foreach($radicados as $radi_nume)
    {
        # Asignar el valor de los campos en el registro
        $record["RADI_NUME_RADI"] = $radi_nume;
        $record["INFO_DESC"] = $this->db->conn->qstr($observa);
        $record["INFO_FECH"] = $this->db->conn->sysTimeStamp;
        $record["USUA_CODI"] = $usua_dest;
        $record["USUA_INFO"] = $usua_codi;
        //Insertamos los datos
        $informaSql = $this->db->conn->Replace("INFORMADOS",$record,array('RADI_NUME_RADI','USUA_INFO','USUA_CODI'),false,false,true,false);
        if (!$informaSql) { error_log("QUIPUX informar: no se registro el informado del documento $radi_nume para el usuario $usua_dest. ".$this->db->conn->ErrorMsg()); continue; }
        $this->insertarHistorico($radi_nume, $usua_codi, $usua_dest, $observa, 8);
        ++$mail_param["num_docs"];
    }
    if ($mail_param["num_docs"] == 0) return "";
    $mail_param["enviado_por"] = "Informado por:";
    $mail_param["bandeja"] = "Informados";
    if ($mail_param["num_docs"] == 1)
        $this->enviarMail($usua_codi, $usua_dest, $radi_nume, "Documento Informado", "0", $mail_param);
    elseif ($mail_param["num_docs"] > 1)
        $this->enviarMail($usua_codi, $usua_dest, $radi_nume, "Documento Informado", "9", $mail_param);
    return $usr_dest["nombre"] ?? '';
}


function borrarInformado($radicados, $usua_codi, $observa)
{
	foreach($radicados as $noRadicado)
	{
		$sql = "select usua_info from informados WHERE RADI_NUME_RADI=$noRadicado and USUA_CODI=$usua_codi";
		$rs = $this->db->query($sql);
		$informadores = array();
		while ($rs && !$rs->EOF) {
			$informadores[] = $rs->fields['USUA_INFO'];
			$rs->MoveNext();
		}
		if (!$informadores) continue; // no lo tiene en su lista de informados: nada que borrar ni que historiar
		$deleteSQL = $this->db->conn->Execute("DELETE FROM INFORMADOS WHERE RADI_NUME_RADI=$noRadicado and USUA_CODI=$usua_codi");
		if (!$deleteSQL) {
			error_log("QUIPUX borrarInformado: no se borro el informado del documento $noRadicado para el usuario $usua_codi. ".$this->db->conn->ErrorMsg());
			continue;
		}
		foreach ($informadores as $usua_dest)
			$this->insertarHistorico($noRadicado, $usua_codi, $usua_dest, $observa, 7);
	}
	return;
}



/**
 * Devuelve la subrogación vigente del destinatario de una entrega, o null.
 *
 * Se consulta en el momento de entregar el documento —no al activar la
 * subrogación— porque los documentos que llegan durante el período deben
 * desviarse uno a uno, no en un traspaso masivo inicial.
 */
function contextoSubrogacionDestino($usua_dest)
{
    if ((int)$usua_dest <= 0) return null;
    include_once(dirname(__DIR__) . '/subrogacion/Subrogacion.php');
    $subrogacion = new Subrogacion($this->db);
    return $subrogacion->vigenteParaSubrogado($usua_dest);
}

/**
 * Marca un documento como recibido durante una subrogación.
 *
 * El documento NO se desvía: queda en la bandeja del puesto, a nombre del
 * titular. El subrogante lo tramita cambiando al contexto del cargo en el menú
 * "Usuario:", con lo que el trámite consta a nombre del puesto —que es lo
 * correcto documentalmente— y la persona real queda registrada en
 * subrogacion_auditoria a través del hook de Historico::insertarHistorico().
 *
 * El sello sólo sirve para identificar después qué documentos entraron al
 * puesto mientras estaba subrogado.
 *
 * @param $ctx fila devuelta por contextoSubrogacionDestino()
 */
function aplicarCopiaSubrogacion($radi_nume_radi, $ctx)
{
    if (!$ctx) return;

    include_once(dirname(__DIR__) . '/subrogacion/Subrogacion.php');
    $subrogacion = new Subrogacion($this->db);
    $subrogacion->sellarDocumento($radi_nume_radi,
        (int)$ctx['USUA_SUBROGACION_CODI'], Subrogacion::DOC_TRAMITABLE);
}

function reasignar( $radicados, $usua_codi, $usua_dest, $observa, $fecha_tramite="", $flag_administrador=false, $carpeta=0)
{
    // La guarda comparaba contra la cadena "0", asi que un usCodSelect vacio ("") la
    // atravesaba y llegaba al UPDATE de mas abajo como RADI_USUA_ACTU= (sin valor).
    if ((int)$usua_dest <= 0) return "";
    $ruta_raiz = $this->db->rutaRaiz;
    include_once(dirname(__DIR__,2).'/obtenerdatos.php');		//Consulta de datos de los usuarios y radicados

    $mail_param["enviado_por"] = "Reasignado por:";
    // Quien recibe el documento debe enterarse por el correo de qué se le pide y
    // con qué comentario, sin tener que entrar al sistema a buscarlo.
    include_once(dirname(__DIR__).'/sumillas/Sumillas.php');
    $mail_param["sumillas"]   = sumillas_texto_seleccion($this->db);
    $mail_param["comentario"] = $observa;
    $flag_bandeja_compartida = false; //en caso de que la reasignación sea por bandeja compartida
    $codTx = 9;

    if (!$flag_administrador) {
        foreach($radicados as $radi_nume) {
            $rs = $this->validarEstado($radi_nume);

            if(($rs->fields["ESTA_CODI"]!=2 && $rs->fields["ESTA_CODI"]!=1) || 
               ($rs->fields["RADI_USUA_ACTU"]!=$_SESSION["usua_codi"] && $rs->fields["RADI_USUA_ACTU"]!=$_SESSION["usua_codi_jefe"]))
                die ("No se puede realizar esta acci&oacute;n con este documento.");
        }
    } else {
        if ($_SESSION["usua_admin_sistema"] != 1) die ("Usted no tiene los permisos suficientes para realizar esta aci&oacute;n.");
        $codTx = 10;
    }

    if (trim($fecha_tramite)=="") $fecha_tramite = date("Y-m-d");
    $usr_dest = ObtenerDatosUsuario($usua_dest,$this->db);
    $radicadosIn = join(",",$radicados);
    $isql = "update radicado set
              RADI_USUA_ANTE=$usua_codi
             ,RADI_USUA_ACTU=$usua_dest
             ,RADI_LEIDO=0
             , radi_fech_asig=to_timestamp('$fecha_tramite', 'YYYY-MM-DD')
             where RADI_NUME_RADI in($radicadosIn)";
    // Sin comprobar el retorno, un UPDATE fallido pasaba inadvertido: el documento se
    // quedaba en la bandeja de origen y aun asi se insertaba el historico, dejando
    // Reasignados con movimientos que nunca ocurrieron.
    if (!$this->db->conn->Execute($isql)) {
        error_log("Tx::reasignar UPDATE fallido: ".$this->db->conn->ErrorMsg()." | sql=".$isql);
        die(html_error("No se pudo reasignar el/los documento(s). No se registr&oacute; ning&uacute;n cambio; "
          . "por favor intente nuevamente o comunique el caso al administrador."));
    }
    foreach($radicados as $radi_nume) {        
        // En caso de Bandeja compartida se reasigna primero el documento del jefe al asistente
        if($rs->fields["RADI_USUA_ACTU"]==$_SESSION["usua_codi_jefe"] and (0+$_SESSION["usua_codi_jefe"])!=0 and !$flag_administrador){
            $usr_rem = ObtenerDatosUsuario($_SESSION["usua_codi"],$this->db);
            $usr_jefe = ObtenerDatosUsuario($_SESSION['usua_codi_jefe'],$this->db);
            $observaJefe = 'Documento tomado por '.$usr_rem["nombre"].' de la Bandeja de Documentos Recibidos de '.$usr_jefe['nombre'].'.';
            if ($_SESSION["usua_codi"]==$usua_dest) // Si se está auto asignando el asistente el documento
                $observaJefe .= "\n".$observa;

            $this->insertarHistorico($radi_nume, $_SESSION["usua_codi_jefe"], $_SESSION["usua_codi"], $observaJefe, 9, $fecha_tramite);

            // Envio de correo de notificacion de que el documento ha sido tomado al jefe.
            $flag_bandeja_compartida = true;
            if (count($radicados) == 1) //Para validar que se envie un solo mail por todos los documentos
                $this->enviarMail($_SESSION["usua_codi"], $_SESSION['usua_codi_jefe'], $radi_nume,'Documento Reasignado','1');
        }
        // Fin
        if (($flag_bandeja_compartida and $_SESSION["usua_codi"]!=$usua_dest) or !$flag_bandeja_compartida) {
            $this->insertarHistorico($radi_nume, $_SESSION["usua_codi"], $usua_dest, $observa, $codTx, $fecha_tramite);
            if ($_SESSION["usua_codi"]!=$usua_dest) {
                if (count($radicados) == 1)
                    $this->enviarMail($_SESSION["usua_codi"], $usua_dest, $radi_nume, "Documento Reasignado", "0", $mail_param);
            }
        }
        $this->cambiarPropietarioTareas($radi_nume, $usua_dest, $usua_codi);
    }
    if (count($radicados) > 1) {
        $mail_param["num_docs"] = count($radicados);
        if ($_SESSION["usua_codi"]!=$usua_dest)
            $this->enviarMail($_SESSION["usua_codi"], $usua_dest, $radi_nume, "Documento Reasignado", "9", $mail_param);
        if ($flag_bandeja_compartida and $_SESSION["usua_codi_jefe"]!=$usua_dest)
            $this->enviarMail($_SESSION["usua_codi"], $_SESSION['usua_codi_jefe'], $radi_nume,'Documento Reasignado','1A', $mail_param);
    }
    return $usr_dest["nombre"] ?? '';

}

function enviarfisico( $radicados, $usua_codi, $usua_dest, $observa, $usua_respo, $flag_administrador=false)
{
    if ($usua_dest == "0" or $usua_respo=="") return "";
    $ruta_raiz = $this->db->rutaRaiz;
    include_once(dirname(__DIR__,2).'/obtenerdatos.php');//Consulta de datos de los usuarios y radicados

    $usr_dest = ObtenerDatosUsuario($usua_dest,$this->db);

    if ($_POST['opcDoc']!='') {
        $estado=$_POST['opcDoc'];
    } elseif ( !isset($_POST['opcDoc']) or ($_POST['opcDoc']=='')) {
        $estado="M";
    }

    foreach($radicados as $radi_nume) {
        if (trim($radi_nume)!='') {
            $this->insertarHistorico($radi_nume, $usua_codi, $usua_dest, $observa, 69,$usua_respo);
            $sql = "select max(hist_codi) as hist_codi from hist_eventos
                    WHERE RADI_NUME_RADI=$radi_nume and USUA_CODI_ORI=$usua_codi
                    and USUA_CODI_DEST=$usua_dest and SGD_TTR_CODIGO=69";
            $rs = $this->db->query($sql);
            $fecha = date("Y-m-d H:i:s");
            $secuencial = $rs->fields['HIST_CODI'];
            $this->insertarHistoricoFisico($radi_nume, $secuencial, $fecha, $usua_codi, $usua_dest, $observa, $estado, $usua_respo, 1);
        }//if
    }//for
    return $usr_dest["nombre"];
}


//Esta funcion afecta a todos los documentos (Consultar con Mauricio Haro antes de algun cambio)

function GenerarDocumentosEnvio($radicados, $usua_codi, $observa, $ruta_raiz="..")
{
    include_once "Radicacion.php";				//Registro de radicados
    include_once(dirname(__DIR__,2).'/obtenerdatos.php');			//Consulta de datos de los usuarios y radicados
    foreach($radicados as $radi_nume) {
        $rs = $this->validarEstado($radi_nume);
        if($rs->fields["ESTA_CODI"]!=1 || $rs->fields["RADI_USUA_ACTU"]!=$_SESSION["usua_codi"])
            die ("No se puede realizar esta acci&oacute;n con este documento.");
    }

    $rad = new Radicacion($this->db);
//var_dump($rad);	
    $rad->transaccion=1; //1;  //Indica que el commit o rollback de la transacción se manejará localmente
//echo "<br><br>Transaccion=".$rad->transaccion;	
    $usua_nomb = "";
    $flag = false;  //Indica si por lo menos se generó un radicado
    $usr_actual = ObtenerDatosUsuario($usua_codi,$this->db);
	$prueba="inicio";
	//saco el numero de elementos

    foreach ($radicados as $radi_nume) {
//var_dump($radi_nume);		
        $this->db->conn->BeginTrans();	//Inicia la transaccion
        // Cancelamos todas las tareas pendientes
        $this->cancelarTodasTareasEnviadas($radi_nume, "Se realizo la acción de \"Firmar y Enviar\" el documento");

        $this->insertarHistorico($radi_nume, $usua_codi, $usua_codi, $observa, 65);	//Firmar y enviar
        $tiporad = substr($radi_nume,-1);

//este comando estaba comentado CORREGIDO POR CARMITA SE QUITA EL COMENTARIO Y SE SOLUCIONA EL ENVIO MASIVO
ObtenerDatosRadicado($radi_nume,$this->db);
        $radicado = $_SESSION["array_radicado"]; 
		 
		//$radicado = ObtenerDatosRadicado("20180000440000000450",$this->db);
//echo "<br>Carmita<br>";
//var_dump($radicado);

        $rad->radiNumeTemp = $radi_nume;
        $rad->radiTextTemp = $radicado["radi_text_temp"] ?? '';
        $rad->radiNumeDeri = $radicado["radi_padre"];
        $rad->radiNumeAsoc = $radicado["radi_nume_asoc"];
        $rad->radiPath = $radicado["radi_path"];
        $rad->radiUsuaRadi = $usua_codi;
        $rad->radiDescAnex = $radicado["radi_desc_anexos"];
        $rad->radiAsunto = $radicado["radi_asunto"];
        $rad->radiResumen = $radicado["radi_resumen"];
        $rad->radiTexto = $radicado["radi_codi_texto"];
        $rad->usar_plantilla = $radicado["usar_plantilla"];
        $rad->ajust_texto = $radicado["ajust_texto"];
        $rad->radi_tipo_impresion = $radicado["radi_tipo_impresion"];
        $rad->cod_codi = $radicado["cod_codi"];
        $rad->cat_codi = $radicado["cat_codi"];
        $rad->radi_lista_dest = $radicado["radi_lista_dest"];
        $rad->flagRadiTexto = "1";
        $rad->radiFlagImprimir = "1";
        $rad->radiSeguridad = $radicado["seguridad"];
        $rad->radiUsuaRem = $radicado["usua_rem"];
        $rad->radiTipo = $radicado["radi_tipo"];
        $rad->radiCuentai = $radicado["radi_referencia"];
        $rad->radiNumeText = "";
        $rad->radiUsuaAnte = $usua_codi;
        $rad->radiUsuaActu = $usua_codi;
        $rad->radiInstActu = $usr_actual["inst_codi"];
        $rad->radiEstado = "4";	//No enviado, para envío electrónico
        $rad->radiFechOfic = "";
        $rad->usua_redirigido = "0";
        $rad->radi_imagen = $radicado["radi_imagen"];
        if ($tiporad == 2) {
            $rad->radiNumeText = $radicado["radi_nume_text"];
            $rad->radiFechOfic = $radicado["radi_fecha"];
            $rad->usua_redirigido = $radicado["usua_redirigido"];
        }

        // Guardamos datos de los destinatarios y remitentes en la tabla usuarios_radicado
        $this->db->conn->Execute("delete from usuarios_radicado where radi_nume_radi=$radi_nume");
        $this->GuardarUsuariosRadicado($radi_nume, $radicado["usua_rem"], 1,$radicado);
        $this->GuardarUsuariosRadicado($radi_nume, $radicado["usua_dest"], 2,$radicado);
        $this->GuardarUsuariosRadicado($radi_nume, $radicado["cca"], 3,$radicado);

        // Initialize before loop in case all recipients are skipped
        $noRad = 0;
        $flag = false;
        $usua_nomb = '';
        // Generamos un documento para cada uno de los destinatarios
        foreach (explode('-',$radicado["usua_dest"].$radicado["cca"]) as $usua_dest) {
            if (trim($usua_dest) != "") {
                $usr = ObtenerDatosUsuario($usua_dest,$this->db);
                // For tiporad=0 (outgoing), proceed even if recipient not in usuario table.
                // For tiporad=2 (external), we need inst_codi to match — skip if not found.
                if (empty($usr) && $tiporad != 0) continue;
                $rad->radiUsuaDest = "-".$usua_dest."-";

                $inst_codi_doc = $usr["inst_codi"] ?? 0;
                $inst_codi_sess= $_SESSION["inst_codi"];

                if ($tiporad==0 or ($tiporad==2 and ($usr["inst_codi"]??0)!=0 and ($usr["inst_codi"]??0)==$_SESSION["inst_codi"])) {
                    // Se crean documentos solo si es un documento de salida o en el caso de registro de docs externos si el destinatario es usuario de la institucion
                    $flag = true;
                    $usua_nomb .= ($usr["nombre"] ?? '').", ".($usr["institucion"] ?? '')."<br>";
//echo "anyes de en historico".$radi_nume;																				
                    $noRad = $rad->newRadicado(1, $usr_actual["depe_codi"], $textrad);
//echo "luegod e instrar en historico".$textrad;																				
                    $this->insertarHistorico($noRad, $usua_codi, $usua_dest, $observa, 2);	//registro
                    $observa2 = "Se generó documento para ".$usr["nombre"].".";
//echo "bandera en true";															
                    $this->insertarHistorico($radi_nume, $usua_codi, $usua_dest, $observa2, 2, $noRad);	//registro
//echo "luegod e instrar en historico".$radi_nume;															
                }
            }
        }
//echo "luego de forecah para cad destitanaio";					
        if ($flag) { // Si se generaron documentos cambia el estado del documento padre
            $tmp = "";
            if ($tiporad == 0) $tmp = ", radi_fech_ofic = '" . $rad->radiFechOfic . "'::timestamp";
            $isql = "update radicado set radi_fech_agend=null, esta_codi=3 $tmp, radi_nume_text='".$rad->radiNumeText."' where RADI_NUME_RADI = $radi_nume";
            $this->db->conn->Execute($isql); //Cambio de estado del documento padre
        } else {
            echo "<br/><span><font color='Navy'><b>No existen destinatarios que pertenezcan a la instituci&oacute;n.<br/>
                  El documento ".$radicado["radi_nume_text"]." no ser&aacute; enviado.</b></font></span><br/>";
				  //var_dump($prueba);
				  //var_dump($rad);
				  //var_dump(count($rad));
        }
        if ($noRad!=0 and $flag) {
            $this->db->conn->CommitTrans();
        } else {
            $this->db->conn->RollbackTrans();
            echo "<br/><span><font color='Red'><b>Existieron errores al firmar el documento No. " . $radicado["radi_nume_text"].".</b></font></span><br/>";
        }
    }
    return substr($usua_nomb,0,-4);
} 

function cambioEstadoDocumentoGenerado($radicados)
{
    include_once $this->ruta_raiz."/plantillas/generar_documento.php";	//Genera el archivo PDF
    $pdf = New GenerarDocumento($this->db);
    $flag_firma_digital = 0;
    foreach($radicados as $radi_nume) {
        $tiporad = substr(trim($radi_nume),-1);
        $sql = "select * from radicado where radi_nume_radi=$radi_nume
                union all
                select * from radicado where radi_nume_temp=$radi_nume and radi_nume_radi<>$radi_nume";
        // ordeno por fecha para que el padre sea el primer registro
        $rs = $this->db->conn->query($sql);
        if($rs->fields["ESTA_CODI"]==3) { //El documento padre debe estar en estado 3 (pendiente)
            if ($tiporad == "2") { //Documentos Externos
                $lista_destinatarios = $rs->fields["RADI_USUA_DEST"];
                $redirigido = 0+$rs->fields["RADI_USUA_REDIRIGIDO"];
                while (!$rs->EOF) {
                    $ctxSubrogacion = null;
                    if (substr($rs->fields["RADI_NUME_RADI"],-1) == "1") {
                        $destino = str_replace("-", "", $rs->fields["RADI_USUA_DEST"]);
                        $estado = 2;
                        // Redirigidos, valido que no se redirija al mismo destinatario, y que se redirija solo el documento del destinatario
                        if ($redirigido!=0 and $destino!=$redirigido and strpos($lista_destinatarios, $rs->fields["RADI_USUA_DEST"])!==false) { //redirigido
                            $this->insertarHistorico($radi_nume, $_SESSION["usua_codi"], $redirigido, "", 28); //Redirigir Documento
                            $this->insertarHistorico($rs->fields["RADI_NUME_RADI"], $_SESSION["usua_codi"], $redirigido, "", 28);
                            $this->informar(array($rs->fields["RADI_NUME_RADI"]), $_SESSION["usua_codi"], $destino, "Documento externo dirigido a otro usuario.");
                            $destino = $redirigido; // Para actualizar el radi_usua_actu
                        }
                        $this->insertarHistorico($rs->fields["RADI_NUME_RADI"], $_SESSION["usua_codi"], $destino, "", 18); //Envío Electrónico
                        //$this->enviarMail($_SESSION["usua_codi"], $destino, $rs->fields["RADI_NUME_RADI"]);
                        $remitente = str_replace("-", "", $rs->fields["RADI_USUA_REM"]);
                        $this->enviarMail($remitente, $destino, $rs->fields["RADI_NUME_RADI"], "Documento Recibido");

                        // El documento se queda en el puesto; sólo se marca para
                        // saber que entró durante una subrogación.
                        $ctxSubrogacion = $this->contextoSubrogacionDestino($destino);
                    } else { // Estado del documento padre
                        $destino = $rs->fields["RADI_USUA_ACTU"];
                        $estado = 6;
                    }
                    // Cambiamos el estado y el usuario actual
                    $sql = "update radicado set esta_codi=$estado, radi_usua_actu=$destino where radi_nume_radi=".$rs->fields["RADI_NUME_RADI"];
                    $this->db->conn->Execute($sql);

                    $this->aplicarCopiaSubrogacion($rs->fields["RADI_NUME_RADI"], $ctxSubrogacion);

                    $rs->MoveNext();
                }
            } // fin documentos externos


            if ($tiporad == "0") { //Documentos de salida
                if (!$this->flag_firmar or $_SESSION["firma_digital"]!=1) { // Si no firma electronicamente
                    // Pongo como documento por imprimir todas las copias
                    $sql = "update radicado set esta_codi=5 where radi_nume_radi<>$radi_nume and radi_nume_temp=$radi_nume";
                    $this->db->conn->Execute($sql);
                    // Pongo como enviado el documento original
                    $sql = "update radicado set esta_codi=6 where radi_nume_radi=$radi_nume";
                    $this->db->conn->Execute($sql);
                    // Genero el PDF
                    $pdf->GenerarPDF($radi_nume,"no");
                } else { // Si firma electronicamente
                    $radi_fisico = "";
                    $radi_electronico = "";
                    while (!$rs->EOF) {
                        if (substr($rs->fields["RADI_NUME_RADI"],-1) == "1") {
                            $destino = str_replace("-", "", $rs->fields["RADI_USUA_DEST"]);
                            $rs_dest = $this->db->conn->query("select count(1) as num from usuarios where usua_codi=$destino");
                            if ($rs_dest->fields["NUM"]==0) { // Si el destinatario es ciudadano
                                $radi_fisico = $rs->fields["RADI_NUME_RADI"];
                                $sql = "update radicado set esta_codi=5 where radi_nume_radi=$radi_fisico";
                                $this->db->conn->Execute($sql);
                            } else { // Si el destinatario es funcionario publico
                                $radi_electronico = $rs->fields["RADI_NUME_RADI"];
                                $flag_firma_digital = 1;
                            }
                        }
                        $rs->MoveNext();
                    }
                    if ($radi_fisico!="") { // Si se envia a algun ciudadano
                        if ($radi_electronico == "") { // Si todos eran ciudadanos
                            $sql = "update radicado set esta_codi=6 where radi_nume_radi=$radi_nume";
                            $this->db->conn->Execute($sql);
                            //$radi_fisico = $radi_nume;
                        }
                        $pdf->GenerarPDF($radi_fisico,"si");
                    }
                }
            }
        }
    }
    return $flag_firma_digital;
}


function forzarEnvioManualDocumentos($radicados, $observa="")
{
    foreach($radicados as $radi_nume) {
        $this->insertarHistorico($radi_nume, $_SESSION["usua_codi"], $_SESSION["usua_codi"], $observa, 20);
    }
    $this->flag_firmar = false;
    $this->cambioEstadoDocumentoGenerado($radicados);
    return "bandeja &quot;Por Imprimir&quot; de la secretaria";
}


function GuardarUsuariosRadicado($radicado, $usuario, $usua_tipo, $rad) {
    global $nombre_servidor;
    $tipoDoc = $rad["radi_tipo"];
    include_once(dirname(__DIR__,2).'/obtenerdatos.php');		//Consulta de datos de los usuarios y radicados
    foreach (explode('-', $usuario ?? '') as $usua_codi) {
        if (trim($usua_codi ?? '') != "") {
            unset($recordSet);
            $usr = ObtenerDatosUsuario($usua_codi,$this->db);
            if (empty($usr)) continue; // skip unknown/system users (e.g. usua_codi=0)
            //$rad = ObtenerDatosRadicado($radicado,$this->db);
            $recordSet["RADI_NUME_RADI"] = $radicado;
            $recordSet["RADI_USUA_TIPO"] = $usua_tipo;
            $recordSet["USUA_CEDULA"] = $this->db->conn->qstr($usr["cedula"]);
            $recordSet["USUA_NOMBRE"] = $this->db->conn->qstr($usr["usua_nombre"]);
            $recordSet["USUA_APELLIDO"] = $this->db->conn->qstr($usr["usua_apellido"]);
            $recordSet["USUA_TITULO"] = $this->db->conn->qstr($usr["titulo"]);
            $recordSet["USUA_ABR_TITULO"] = $this->db->conn->qstr($usr["abr_titulo"]);
            $recordSet["USUA_INSTITUCION"] = $this->db->conn->qstr($usr["institucion"]);
            $recordSet["USUA_EMAIL"] = $this->db->conn->qstr($usr["email"]);
            $recordSet["USUA_AREA_CODI"] = (int)($usr["depe_codi"] ?? 0);
            $recordSet["USUA_CODI"] = (int)$usua_codi;
            $recordSet["INST_CODI"] = (int)($usr["inst_codi"] ?? 0);
            $recordSet["USUA_CIUDAD"] = $this->db->conn->qstr($usr["ciudad"]);
            $recordSet["USUA_AREA"] = $this->db->conn->qstr($usr["dependencia"]); //Area a la que pertenece el usuario
            $recordSet["USUA_CARGO"] = $this->db->conn->qstr($usr["cargo"]);
            if($usua_tipo==2 and $tipoDoc==1 and $usr["tipo_usuario"]==1)//Para, Oficio y Funcionario
                $recordSet["USUA_CARGO"] = $this->db->conn->qstr($usr["cargo_cabecera"]);
            
            //Obtener los nombres de las listas en caso de que los destinatarios se seleccionaron de una lista.
            if(trim($rad['radi_lista_dest'])!='' and trim($rad['radi_lista_dest'])!='0') {
                $radi_lista_dest = $rad['radi_lista_dest'];
                $codList = explode("-",$radi_lista_dest);
                
                if(sizeof($codList)>2) {
                    for($j=1;$j<sizeof($codList)-2;$j+=2) {
                        $datosLista = ObtenerDatosLista(trim($codList[$j]),$this->db);
                        $radi_lista_nombre .= $datosLista['nombre'] . '<br>';
                    }
                    $datosLista = ObtenerDatosLista(trim($codList[$j]),$this->db);
                    $radi_lista_nombre .= $datosLista['nombre'];
                } else {
                    $datosLista = ObtenerDatosLista(trim($codList[$j]),$this->db);
                    $radi_lista_nombre .= $datosLista['nombre'];
                }
                $recordSet["LISTA_NOMBRE"] = $this->db->conn->qstr($radi_lista_nombre); //Nombre de listas para el caso de que el tipo de impresion sea con nombre de lista.
            }
            if ($usua_tipo==1 and trim($usr["usua_firma_path"] ?? '') != "")
                $recordSet["USUA_FIRMA_PATH"] = $this->db->conn->qstr($nombre_servidor."/".$usr["usua_firma_path"]);
            $this->db->conn->Replace("USUARIOS_RADICADO", $recordSet, "", false,false,false);
        }
    }
    return;
}


function enviarDocumentosFirmaElectronica($radicados, $certBase64 = '', $certPassword = '')
{
    include $this->ruta_raiz."/config.php";			//Consulta de datos de los usuarios y radicados
    include_once $this->ruta_raiz."/obtenerdatos.php";			//Consulta de datos de los usuarios y radicados
    include_once $this->ruta_raiz."/plantillas/generar_documento.php";	//Genera el archivo PDF
//    include_once $this->ruta_raiz."/interconexion/ws_cliente_firma_digital.php";	//Web service para realizar la firma digital de los documentos
    $pdf = New GenerarDocumento($this->db);

    $firma = array();
    $usr = ObtenerDatosUsuario($_SESSION["usua_codi"],$this->db);

    $clave_archivo = date('Y-m-d-H-i-s'); // En el caso que se envien varios documentos a la vez
    $flag_firmar = false;
    foreach ($radicados as $radi_nume) {
        $sql = "select * from radicado where radi_nume_radi=$radi_nume
                union all
                select * from radicado where radi_nume_temp=$radi_nume and radi_nume_radi<>$radi_nume";
        // ordeno por fecha para que el padre sea el primer registro
        $rs = $this->db->conn->query($sql);
        if($rs->fields["ESTA_CODI"]==3 || $rs->fields["ESTA_CODI"]==1) { //El documento padre debe estar en estado 3 (pendiente)
        //  Generamos el archivo pdf
            $path_pdf = $pdf->GenerarPDF($radi_nume,"si");
        //  Firmamos digitalmente el archivo
            if ($path_pdf != "") {
                 //envio_documentos_para_firma($usr["cedula"], $radi_nume, $path_arch.$nomb_arch,$nombre_servidor,$clave_archivo,$servidor_wsfirma);
                $path_pdf = $this->ruta_raiz."/bodega".$path_pdf;
	        $archivo = file_get_contents($path_pdf);
		
		if(file_exists($path_pdf))
		   $flag_firmar = true;
		else
		   echo "<br/><span><font color='Navy'><b>Existieron problemas en la creación de documentos.</b></font></span><br/>";


         	//$flag_envio_documento = envio_documentos_para_firma(substr($usr["cedula"],0,10), $radi_nume, $path_pdf, $nombre_servidor, $clave_archivo, $servidor_firma);
                //if ($flag_envio_documento!="0") $flag_firmar = true;
            
	    }
        }
    }
    if ($flag_firmar) {
        // --- SMART-SIGN: Usar API de firma directa si está habilitado ---
        if (($usar_smart_sign ?? false) && $certBase64 !== '' && $certPassword !== '') {
            $this->firmarConSmartSign($radicados, $certBase64, $certPassword);
        } else {
            // --- FIRMAEC TRANSVERSAL (flujo original) ---
            $this->mostrar_applet_firma_digital($radicados,'');
        }
    } else {
        echo "<br/><span><font color='Navy'><b>Existieron errores al firmar los documentos. Por favor vuelva a intentarlo.</b></font></span><br/>";
    }
    return "";
}


/**
 * Firma documentos usando la API Smart-Sign (reemplazo síncrono de FirmaEC Transversal).
 * Replica la lógica de grabar_archivos_firmados() de interconexion/ws_firma_digital.php:
 *   1. Envía PDF + certificado .p12 al API
 *   2. Recibe el PDF firmado
 *   3. Graba el PDF firmado en la bodega
 *   4. Actualiza radicado con fecha de firma, datos del firmante, arch_codi
 *   5. Inserta historico (acción 40 = Firma Digital)
 *   6. Llama envioElectronicoDocumento() para cambiar estados y enviar notificaciones
 *
 * @param array  $radicados     IDs de documentos a firmar
 * @param string $certBase64    Certificado .p12 codificado en base64
 * @param string $certPassword  Contraseña del certificado
 */
function firmarConSmartSign($radicados, $certBase64, $certPassword) {
    include $this->ruta_raiz."/config.php";
    include_once $this->ruta_raiz."/obtenerdatos.php";

    $usua_codi = $_SESSION['usua_codi'];
    $usr = ObtenerDatosUsuario($usua_codi, $this->db);

    $docsOk = 0;
    $docsError = 0;

    foreach ($radicados as $radi_nume) {
        $sql = "select radi_nume_text, radi_nume_radi from radicado where radi_nume_radi=$radi_nume and esta_codi=3
                union all
                select radi_nume_text, radi_nume_radi from radicado where radi_nume_temp=$radi_nume and radi_nume_radi<>$radi_nume and esta_codi=4";
        $rs = $this->db->conn->Execute($sql);
        if ($rs->EOF) continue;

        $radi_nume_real = $rs->fields["RADI_NUME_RADI"];
        $path_pdf = $this->ruta_raiz . "/bodega/tmp/$radi_nume_real.pdf";

        if (!file_exists($path_pdf)) {
            echo "<br/><font color='red'>Error: No se encontró el PDF para el documento $radi_nume_real.</font><br/>";
            $docsError++;
            continue;
        }

        $pdfBase64 = base64_encode(file_get_contents($path_pdf));

        // --- Construir payload para Smart-Sign API ---
        $payload = json_encode([
            'pdfBase64'          => $pdfBase64,
            'certificateBase64'  => $certBase64,
            'certificatePassword'=> $certPassword,
            'keyword'            => 'Atentamente',
            'reason'             => 'Firma Digital - Quipux',
            'location'           => 'Ecuador'
        ]);

        // --- Llamar al API ---
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $servidor_smart_sign);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: */*']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120); // 2 minutos max por documento

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($httpCode !== 200 || $response === false) {
            echo "<br/><font color='red'>Error al firmar documento $radi_nume_real: HTTP $httpCode";
            if ($curlError) echo " - $curlError";
            echo "</font><br/>";
            // Mostrar el cuerpo completo de la respuesta del API para diagnóstico
            if ($response !== false && $response !== '') {
                $jsonError = json_decode($response, true);
                if ($jsonError !== null) {
                    // Respuesta JSON estructurada: mostrar cada campo
                    $detalleError = '<ul>';
                    foreach ($jsonError as $key => $val) {
                        $valStr = is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : htmlspecialchars((string)$val);
                        $detalleError .= "<li><b>" . htmlspecialchars($key) . ":</b> $valStr</li>";
                    }
                    $detalleError .= '</ul>';
                    echo "<br/><font color='red'><b>Detalle del error (API):</b> $detalleError</font>";
                } else {
                    // Respuesta no-JSON: mostrar como texto plano
                    echo "<br/><font color='red'><b>Respuesta del servicio:</b> " . htmlspecialchars(substr($response, 0, 2000)) . "</font>";
                }
            }
            $docsError++;
            continue;
        }

        $json = json_decode($response, true);
        if (!$json || !($json['success'] ?? false) || empty($json['signedPdfBase64'])) {
            $errorMsg = $json['message'] ?? 'Respuesta inválida del servicio de firma';
            echo "<br/><font color='red'>Error al firmar documento $radi_nume_real: $errorMsg</font><br/>";
            // Mostrar campos adicionales del error si existen
            $extraFields = ['error', 'details', 'errors', 'cause', 'status'];
            $extras = '';
            foreach ($extraFields as $field) {
                if (isset($json[$field])) {
                    $val = is_array($json[$field]) ? json_encode($json[$field], JSON_UNESCAPED_UNICODE) : htmlspecialchars((string)$json[$field]);
                    $extras .= "<li><b>" . htmlspecialchars($field) . ":</b> $val</li>";
                }
            }
            if ($extras !== '') {
                echo "<br/><font color='red'><b>Detalle adicional:</b> <ul>$extras</ul></font>";
            }
            $docsError++;
            continue;
        }

        // --- Guardar PDF firmado en el sistema de archivos (UCUENCA usa filesystem, no DB) ---
        $signedPdfBytes = base64_decode($json['signedPdfBase64']);
        $bytesWritten = file_put_contents($path_pdf, $signedPdfBytes);

        if ($bytesWritten === false) {
            echo "<br/><font color='red'>Error al guardar el PDF firmado del documento $radi_nume_real en disco.</font><br/>";
            $docsError++;
            continue;
        }

        // --- Construir datos del firmante ---
        $certInfo = $json['certificateInfo'] ?? [];
        $nombre_firmante = $certInfo['subjectName'] ?? ($usr['nombre'] ?? '');
        $cedula_firmante = $usr['cedula'] ?? '';
        $institucion_firmante = $certInfo['issuerName'] ?? '';
        $cargo_firmante = $usr['cargo'] ?? '';
        $fecha_firma = $json['signatureDate'] ?? date('Y-m-d H:i:s');

        // Formatear fecha si viene en ISO 8601
        if (strpos($fecha_firma, 'T') !== false) {
            $fecha_firma = date('Y-m-d H:i:s', strtotime($fecha_firma));
        }

        $datos_firmante_html = "<table><tr><th>Cédula</th><th>Nombre</th><th>Institución</th><th>Cargo</th><th>Fecha</th></tr>";
        $datos_firmante_html .= "<tr><td>$cedula_firmante</td><td>$nombre_firmante</td><td>$institucion_firmante</td><td>$cargo_firmante</td><td>$fecha_firma</td></tr></table>";
        $datos_firmante_escaped = addslashes($datos_firmante_html);

        // --- Actualizar radicado (replica ws_firma_digital.php) ---
        // radi_path = '/tmp/{radi_nume_real}.pdf' → el descargador concatena bodega + radi_path
        $sql = "UPDATE radicado SET 
                    radi_fech_firma = '$fecha_firma',
                    radi_tipo_archivo = 1, 
                    radi_nomb_usua_firma = '$datos_firmante_escaped',
                    radi_path = '/tmp/$radi_nume_real.pdf'
                WHERE radi_nume_temp = $radi_nume 
                  AND (esta_codi = 4 OR esta_codi = 3 OR radi_nume_radi = $radi_nume)";
        $this->db->conn->Execute($sql);

        // --- Registrar histórico y enviar documento (replica ws_firma_digital.php líneas ~75-76) ---
        $this->insertarHistorico($radi_nume, $usr["usua_codi"], $usr["usua_codi"], "Documento Firmado Electrónicamente (Smart-Sign)", 40);
        $this->envioElectronicoDocumento($radi_nume, $usr["usua_codi"]);

        $docsOk++;
    }

    // --- Mostrar resultado al usuario ---
    if ($docsOk > 0) {
        echo "<br/><font color='blue' size='2'><b>Se ha firmado $docsOk documento(s) satisfactoriamente.</b></font><br/>";
    }
    if ($docsError > 0) {
        echo "<br/><font color='red'><b>$docsError documento(s) no pudieron ser firmados.</b></font><br/>";
    }
}


/**
* Llama a la aplicacion Firma 
* @radicados, documentos a firmar
* @token, genera el servicio web
*/


function mostrar_applet_firma_digital($radicados,$token='',$ejecucion=0,$numdocs=0) {
    include $this->ruta_raiz."/config.php";
    require_once($this->ruta_raiz."/funciones.php");
    $path_raiz = $this->ruta_raiz."/include/tx/applet.php";
    $cedula = $_SESSION["usua_doc"] ?? '';
    $usua_codi = $_SESSION['usua_codi'];

	$rs = $this->db->conn->Execute("select usua_tipo_certificado from usuarios where usua_codi=".$_SESSION["usua_codi"]);

    	$tipo_certificado = $rs->fields["USUA_TIPO_CERTIFICADO"];
	$documentos="";
	$nombre='"nombre"';
	$documento='"documento"';
	$documentosjson="";
	$validaToken=0;
	$radicadosToken="";
	$doctxt="";
	$numdocs=count($radicados);

 foreach ($radicados as $radi_nume) {
        $sql = "select radi_nume_text,radi_nume_radi from radicado where radi_nume_radi=$radi_nume and esta_codi=3
                union all
                select radi_nume_text,radi_nume_radi from radicado where radi_nume_temp=$radi_nume and radi_nume_radi<>$radi_nume and esta_codi=4";
        $rs = $this->db->conn->Execute($sql);
        if(!$rs->EOF){
         $validaToken=1;
         $ejecucion++;
	$radi_nume_text = $rs->fields["RADI_NUME_RADI"];
         //path del documento
         $path_pdf = $this->ruta_raiz."/bodega/tmp/$radi_nume_text.pdf";
         //get en variable
         $im = file_get_contents("$path_pdf");
         //transformo en base64
         $base64 = base64_encode($im);
         $this->db->conn->Execute($sql);
         $lo='"'.$base64.'"';
         //$nombre_text ='"'.$radi_nume_text.'"';
         $nombre_text ='"'.$radi_nume.'"';
         //forma json interno
         if ($radi_nume_text!='')
           $documentosjson.="{".$nombre.":".$nombre_text.",".$documento.":"."$lo"."},";
       }
         $radicadosToken.=",".$radi_nume;
 }
        $doctxt = $radi_nume_text.",";
        $documentosjson = substr($documentosjson,0,-1);
        $documentosjson="[$documentosjson]";
        $jsoncedula = '"'."cedula".'":';
        $jsonsistema = '"'."sistema".'":';
        $cedula = '"'.$cedula.'"';
        $sistema = "quipux";
        $sistema = '"'.$sistema.'"';
        $jsondocumentos = ',"'."documentos".'":';
        //forma json final
        $body = '{'.$jsoncedula.$cedula.','.$jsonsistema.$sistema.$jsondocumentos.$documentosjson.'}';
        //$body = "'$body;
        $docstxt = ($docstxt ?? '') . $radi_nume_text.",";
        //CONSUMO DE SERVICIO WEB RES
        // $urlws = "https://firmadigital.ucuenca.edu.ec/servicio/documentos";
        $urlws = $servidor_ws_firma . "/servicio/documentos";
	//$urlws = "http://172.16.1.76:8090/servicio/documentos";
        // ------------------------------------------------------------
 
        $headers = array("Content-Type: application/json", "X-API-KEY: $api_key_token");
	
//	echo "---0-";
//	echo "$urlws";
//	echo "---0.0-";
//      echo "$api_key_token";

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $urlws);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

	//ToDo: validar los certificados, no deben quedar las siguientes dos líneas que no comprueban los SSL
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        $token = curl_exec($curl);
        $curl_error = curl_error($curl);
	
//	echo "---2-";
//	echo "$token";
//	echo "$curl";
//	echo "-->";

        curl_close($curl);
// ------------------------------------------------------------------
    echo "<div id='div_firmar_doc' ></div>";
    echo "<div id='div_applet'></div>";
    $pos = strpos($token, 'Error');
     
//	echo "--3-";
//	echo "$pos";
//	echo "-->";

    //echo '<script type="text/javascript">alert("'.$token.'");</script>';

 
  if ($token=='' || $pos !== false){
    $detalle = "";
    if (trim($token) == "" && trim($curl_error) != "")
        $detalle = "<br><span style='font-size:11px'>No se pudo contactar el servicio de firma ($urlws): $curl_error</span>";
    die("<font color='blue'>Existieron errores con la aplicación de firma electrónica.$detalle</font>");
   }
    $pagina_actual= $_SERVER['REQUEST_URI'];
    $tamanio="width='15' height='15'";
    //api_key_token, se encuentra configurado en el config.php, dependiendo de la base de datos de firma electrónica 
      echo "<script>token(\"$token\",\"$tipo_certificado\",\"$radicadosToken\",\"quipux\");</script>";
      $html = "<a href='javascript:;' onclick='token(\"$token\",\"$tipo_certificado\",\"$radicadosToken\");' class='aqui'>";
      $html.="<font color='blue' size='2'>aquí</font>";
      $html.="</a>";



      echo " <input type='text' id='message' name='message' style='display:none' />";
      echo linkFirmaEc('left');
      echo "<br/><div id='div_link_token'><span><font size='2' color='blue'>Para firmar el documento, favor haga clic <b>$html</b></font></span></div>
         <br/>";
     $radtext = explode(",", $docstxt ?? '');
}
/**
 * Verificacion para la version 2 de firma.
 * @archivobase64, archivo
 * @tipoVerificacion, D retorna la fecha
 * @tipoArchivo, para los diferetes tipos de archivo 1 PDF 
 */
//tipoVerificacion=D, verificara el tiempo
function verificacionFirmaNueva($archivobase64,$tipoVerificacion="F",$tipoArchivo=''){
    include $this->ruta_raiz."/config.php";
    //$this->db->conn->query($sql);
    $im = $archivobase64;
    if ($tipoArchivo==1){
	    $im = base64_decode($archivobase64);
	    //verificar instalación de los servicios de firma
	    //http://www.firmadigital.gob.ec/informacion-para-desarrolladores/
            // $urlws = "https://firmadigital.ucuenca.edu.ec/servicio/validacioncms";//otros tipos de archivos ESTE VALE
            $urlws = $servidor_ws_firma . "/servicio/validacioncms";//otros tipos de archivos ESTE VALE
	    //$urlws = "http://172.16.1.76:8090/servicio/validacioncms";
    }else
            // $urlws = "https://firmadigital.ucuenca.edu.ec/servicio/validacionpdf";//para pdf // ESTE VALE        
            $urlws = $servidor_ws_firma . "/servicio/validacionpdf";//para pdf // ESTE VALE
	    //$urlws = "http://172.16.1.76:8090/servicio/validacionpdf";
    $headers = array("Content-Type: text/plain");
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $urlws);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $im);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    $resultado = curl_exec($curl);
    $json = json_decode($resultado);
    // object method
    $htmlFirmante="";
    $existefirma=0;
    if ($tipoVerificacion=='F'){
     $htmlFirmante.= "<table>";
     $htmlFirmante.= "<tr><th>Cédula</th><th>Nombre</th><th>Institución</th><th>Cargo</th><th>Fecha</th></tr>";

     foreach($json->firmantes as $firmante) {

      $nombre = $firmante->nombre;
      $cargo = $firmante->cargo;
      $cedula = $firmante->cedula;
      $existefirma=1;
      $institucion = $firmante->institucion;
      $fecha = $firmante->fecha;
       $fechadia = substr($fecha,0,2);
                $fechames = substr($fecha,3,2);
                $fechaanio = substr($fecha,6,4);
                $hora = substr($fecha,11,8);
                $fecha = "$fechaanio-$fechames-$fechadia $hora";
      $htmlFirma['fecha']=$fecha;
      $htmlFirmante.= "<tr><td>$cedula</td><td>$nombre</td><td>$institucion</td><td>$cargo</td><td>$fecha</td></tr>";
     }
     $htmlFirmante.= "</table>";
     $htmlFirma["tabla"]=$htmlFirmante;
    }elseif($tipoVerificacion=='HTML'){

     $htmlFirmante = "<table border='1' cellspacing='0'>";
     $htmlFirmante.= "<tr><td colspan='4'><font color='red' size='3'><center>La verificación de la firma digital del documento fue exitosa.</center></font></td></tr>";
     $htmltdi= "<td bgcolor='#6a819d'><font color='white'>";
     $htmltdf= "</font></td>";
     $htmlFirmante.= "<tr>$htmltdi Cédula $hmltdf $htmltdi Nombre $htmldf $htmltdi Cargo $htmltdf $htmltdi Fecha $htmltdf </tr>";

     foreach($json->firmantes as $firmante) {

      $nombre = $firmante->nombre;
      $cargo = $firmante->cargo;
      $cedula = $firmante->cedula;
              $existefirma=1;

      $institucion = $firmante->institucion;
      $fecha = $firmante->fecha;
      $htmlFirmante.= "<tr><td>$cedula</td><td>$nombre</td><td>$cargo</td><td>$fecha</td></tr>";
     }
     $htmlFirmante.= "</table>";
      $htmlFirma['html']=$htmlFirmante;
    }else{
            foreach($json->firmantes as $firmante) {
              $existefirma=1;

                $htmlFirmante = $firmante->fecha;
          }
                $fechadia = substr($htmlFirmante,0,2);
                $fechames = substr($htmlFirmante,3,2);
                $fechaanio = substr($htmlFirmante,6,4);
                $hora = substr($htmlFirmante,11,8);
                $htmlFirmante = "$fechaanio-$fechames-$fechadia $hora";
                $htmlFirma['fecha']=$htmlFirmante;
    }
    if ($existefirma==1){
            return $htmlFirma;
    }
    else{
            return "";
    }
}



/*
function mostrar_applet_firma_digital() {
    include $this->ruta_raiz."/config.php";
    $rs = $this->db->conn->Execute("select usua_tipo_certificado from usuarios where usua_codi=".$_SESSION["usua_codi"]);
    echo "<script>
    function firma_electronica() {
        windowprops = 'top=100,left=100,location=no,status=no, menubar=no,scrollbars=yes, resizable=yes,width=600,height=400';
        URL = '$servidor_firma/applet.php?sistema=$nombre_servidor&tipo_certificado=".$rs->fields["USUA_TIPO_CERTIFICADO"]."&accion=firma';
        window.open(URL , 'Firma Electronica', windowprops);
    }
    firma_electronica();
    </script>";

    echo "<br/><span><font color='blue'><h4>Si la pantalla que le permite realizar la firma electrónica <br/>
          no aparece en unos segundos, por favor de click
          <a href=\"javascript:firma_electronica();\" class='aqui' ><b>&quot;AQU&Iacute;&quot;</b></a></h4></font></span><br/>";

}*/




function envioElectronicoDocumento($radi_nume, $usua_codi) {

    include_once "Radicacion.php";          //Registro de radicados
    $rad = new Radicacion($this->db);
    $radi_nume_text = array();              //Se la utiliza para que no se generen 2 codigos de documentos si se envia a funcionarios de la misma institución
    unset($radi_nume_text);
    $respInstitucion = "";

    $rs_usr = $this->db->conn->query("select depe_codi, inst_codi, cargo_tipo from usuarios where usua_codi=$usua_codi");

    $sql = "select r.radi_nume_radi, r.radi_nume_text, u.usua_codi, u.depe_codi, u.inst_codi, r.radi_nume_deri, r.radi_usua_rem, radi_usua_dest
            from radicado r
                left outer join usuarios u on replace(r.radi_usua_dest,'-','')::integer=u.usua_codi
            where r.esta_codi=4 and radi_nume_temp=$radi_nume";
    $rs = $this->db->conn->query($sql);

    $radi_nume_text[1] = $rs->fields["RADI_NUME_TEXT"]; //Para que no cambie el numero de documento si el destinatario es un ciudadano

    $estado = 2;
    if ($rs_usr->fields["INST_CODI"]==1) $estado = 9; // Si es un ciudadano el que firma para que se vaya a una bandeja de entrada

    while ($rs && !$rs->EOF) {
        $usr_destino = $rs->fields["USUA_CODI"];

        // El documento se queda en el puesto; sólo se marca para saber que entró
        // durante una subrogación.
        $ctxSubrogacion = $this->contextoSubrogacionDestino($usr_destino);

        $sql = "update radicado set esta_codi=$estado, radi_usua_actu=$usr_destino";
        if ($rs_usr->fields["INST_CODI"] != $rs->fields["INST_CODI"]) {
            // Validamos para cuando se envien documentos a 2 funcionarios de otra institucion no se generen 2 codigos
            if (!isset($radi_nume_text[$rs->fields["INST_CODI"]])) {
                $tmp = date("Y").str_pad($rs->fields["DEPE_CODI"],6,"0", STR_PAD_LEFT)."0000000002";
                $radi_nume_text[$rs->fields["INST_CODI"]] = $rad->GenerarTextRadicado($tmp, 2, "N");
            }
            $sql .= ", radi_inst_actu=".$rs->fields["INST_CODI"];
            $sql .= ", radi_nume_text='".$radi_nume_text[$rs->fields["INST_CODI"]]."', radi_tipo=2 ";
            if ($rs->fields["INST_CODI"] != 1) $sql .= ", radi_cuentai='".$rs->fields["RADI_NUME_TEXT"]."'";
        }
        $sql .= " where radi_nume_radi=".$rs->fields["RADI_NUME_RADI"];
        $this->db->conn->Execute($sql);

        $this->aplicarCopiaSubrogacion($rs->fields["RADI_NUME_RADI"], $ctxSubrogacion);

        // Registramos el histórico
        $this->insertarHistorico($rs->fields["RADI_NUME_RADI"], $usua_codi, $usua_codi, "Documento Firmado Electrónicamente", 40);	//Firma Digital
        $this->insertarHistorico($rs->fields["RADI_NUME_RADI"], $usua_codi, $usua_codi, "", 18); //Envío Electrónico
        if (trim($rs->fields["RADI_NUME_DERI"] ?? '')!="") {
            $sql = "select radi_nume_radi, radi_nume_temp from radicado where radi_nume_radi=".$rs->fields["RADI_NUME_DERI"];
            $rs_padre = $this->db->conn->Execute($sql);
            if ($rs_padre) { // Registramos el histórico en el padre
                $this->insertarHistorico($rs_padre->fields["RADI_NUME_RADI"], str_replace('-','',$rs->fields["RADI_USUA_REM"]), str_replace('-','',$rs->fields["RADI_USUA_DEST"]), "Se envió electrónicamente el documento de respuesta No: ".$rs->fields["RADI_NUME_TEXT"], 37, $rs->fields["RADI_NUME_RADI"]);
                $this->insertarHistorico($rs_padre->fields["RADI_NUME_TEMP"], str_replace('-','',$rs->fields["RADI_USUA_REM"]), str_replace('-','',$rs->fields["RADI_USUA_DEST"]), "Se envió electrónicamente el documento de respuesta No: ".$rs->fields["RADI_NUME_TEXT"], 37, $rs->fields["RADI_NUME_RADI"]);
            }
        }
        if ($rs_usr->fields["INST_CODI"]!=1)
            $this->enviarMail($usua_codi, $usr_destino, $rs->fields["RADI_NUME_RADI"], "Documento Recibido");
        $radiNumeRadi = $rs->fields["RADI_NUME_RADI"];

        // Envio de correo electronico a la asistente si el usuario es jefe
        if($rs_usr->fields["CARGO_TIPO"]==1) {
            // Obtener datos de la asistente de area
            $datosAsistente = ObtenerJefeArea($rs_usr->fields["INST_CODI"], $rs_usr->fields["DEPE_CODI"], '2', $this->db);
            // Envio de correo de notificacion a la asistente que el Jefe de area a firmado un documento digitalmente. accion 2
            $mail_param["usuario"] = $usr_destino;
            $this->enviarMail($usua_codi, $datosAsistente["usua_codi"], $radiNumeRadi,'Documento Recibido','2', $mail_param);
        }
        $rs->MoveNext();
    }
    $sql = "select count(radi_nume_radi) as num from radicado where esta_codi=4 and radi_nume_temp=$radi_nume";
    $rs = $this->db->conn->query($sql);
    if ($rs->fields["NUM"]==0) {
        $sql = "update radicado set esta_codi=6 where radi_nume_radi=$radi_nume";
        $this->db->conn->Execute($sql);
    }
    return;
}


function envioManualDocumento($radicados, $observa)
{
	$respEnvio = "";
	if (trim($observa)!="") $observa .= "<br/>";
//	$rs = $this->db->conn->query("select inst_codi from usuarios where usua_codi=$usua_codi");
//	$inst_codi = $rs->fields["INST_CODI"];
	foreach($radicados as $radi_nume)
	{
	    $sql = "select r.radi_nume_radi, r.radi_nume_temp, r.radi_nume_text, r.radi_usua_rem, r.radi_usua_dest, u.usua_codi, u.usua_nombre
                        , u.inst_codi, u.inst_nombre, u.usua_esta, r.radi_nume_deri
		    from radicado r left outer join usuario u on replace(r.radi_usua_dest,'-','')::integer=u.usua_codi
		    where radi_nume_radi=$radi_nume";
/*            $sql = "select r.radi_nume_radi, r.radi_nume_temp, r.radi_nume_text,r.radi_usua_rem, r.radi_usua_dest
            ,(f_datos_usuarios(replace(radi_usua_dest,'-','')::integer)).usua_codi
            ,(f_datos_usuarios(replace(radi_usua_dest,'-','')::integer)).usua_nombre as usua_nombre,
            (f_datos_usuarios(replace(radi_usua_dest,'-','')::integer)).inst_codi as inst_codi,
            (f_datos_usuarios(replace(radi_usua_dest,'-','')::integer)).inst_nombre,
            (f_datos_usuarios(replace(radi_usua_dest,'-','')::integer)).usua_esta,
            r.radi_nume_deri from radicado r where
            radi_nume_radi=$radi_nume";*/
            //echo $sql;
	    $rs = $this->db->conn->query($sql);
	    // Sin el guardia de $rs, una consulta fallida caia en !$rs->EOF sobre un bool y
	    // el documento cambiaba de estado igual, quedando fuera de No Enviados y sin
	    // radi_usua_actu: ni en Enviados del remitente ni en Recibidos de nadie.
	    if ($rs && !$rs->EOF) {
                // Si el destinatario no resuelve contra la tabla usuario (no sincronizada,
                // inactivo o de otra institucion) se envia como externo (esta_codi=6). Para
                // un destinatario interno eso es un documento huerfano: dejar traza.
                if (trim((string)($rs->fields["USUA_CODI"] ?? '')) === '')
                    error_log("QUIPUX envioManualDocumento: destinatario no resuelto para el documento $radi_nume (radi_usua_dest=".($rs->fields["RADI_USUA_DEST"] ?? '').")");

                $ctxSubrogacion = null;
                if ($rs->fields["INST_CODI"]==$_SESSION["inst_codi"] and $rs->fields["USUA_ESTA"]==1) {
                    // El documento se queda en el puesto; sólo se marca para
                    // saber que entró durante una subrogación.
                    $usr_destino = $rs->fields["USUA_CODI"];
                    $ctxSubrogacion = $this->contextoSubrogacionDestino($usr_destino);
                    $cadena = "esta_codi=2, radi_usua_actu=".$usr_destino;
                }
                else
                    $cadena = "esta_codi=6";
                $sql = "update radicado set $cadena, radi_nomb_usua_firma=null, radi_fech_firma=null, radi_leido=0 where radi_nume_radi=$radi_nume";
                if (!$this->db->conn->Execute($sql)) {
                    error_log("QUIPUX envioManualDocumento: no se pudo enviar el documento $radi_nume. ".$this->db->conn->ErrorMsg());
                    continue;
                }

                $this->aplicarCopiaSubrogacion($radi_nume, $ctxSubrogacion);

                $this->insertarHistorico($radi_nume, $_SESSION["usua_codi"], $_SESSION["usua_codi"], $observa, 19);
                $cadena = $observa . "Envío manual del documento al usuario ".$rs->fields["USUA_NOMBRE"];
                $this->insertarHistorico($rs->fields["RADI_NUME_TEMP"], $_SESSION["usua_codi"], $_SESSION["usua_codi"], $cadena, 19);
                $respEnvio .= $rs->fields["USUA_NOMBRE"].", ".$rs->fields["INST_NOMBRE"]."<br/>";
                if ($rs->fields["INST_CODI"]==$_SESSION["inst_codi"] || $rs->fields["INST_CODI"]==0) {
                    $this->enviarMail(str_replace("-","",$rs->fields["RADI_USUA_REM"]), $rs->fields["USUA_CODI"], $radi_nume, "Documento Recibido");
                }

                if (trim($rs->fields["RADI_NUME_DERI"])!="") {
                    $sql = "select radi_nume_radi, radi_nume_temp from radicado where radi_nume_radi=".$rs->fields["RADI_NUME_DERI"];
                    $rs_padre = $this->db->conn->Execute($sql);
                    if ($rs_padre) { // Registramos el histórico en el padre
                        $this->insertarHistorico($rs_padre->fields["RADI_NUME_RADI"], str_replace('-','',$rs->fields["RADI_USUA_REM"]), str_replace('-','',$rs->fields["RADI_USUA_DEST"]), "Se envió manualmente el documento de respuesta No: ".$rs->fields["RADI_NUME_TEXT"], 38, $rs->fields["RADI_NUME_RADI"]);
                        $this->insertarHistorico($rs_padre->fields["RADI_NUME_TEMP"], str_replace('-','',$rs->fields["RADI_USUA_REM"]), str_replace('-','',$rs->fields["RADI_USUA_DEST"]), "Se envió manualmente el documento de respuesta No: ".$rs->fields["RADI_NUME_TEXT"], 38, $rs->fields["RADI_NUME_RADI"]);
                    }
                }

                // Envio de correo electronico a la asistente si el usuario es jefe
                if($_SESSION['cargo_tipo']==1)
                {
                    // Obtener datos de la asistente de area
                    $datosAsistente = ObtenerJefeArea($_SESSION['inst_codi'], $_SESSION['depe_codi'], '2', $this->db);
                    // Envio de correo de notificacion a la asistente que el Jefe de area a firmado un documento digitalmente. accion 2
                    $mail_param["usuario"] = $rs->fields["USUA_CODI"];
                    $this->enviarMail(str_replace("-","",$rs->fields["RADI_USUA_REM"]), $datosAsistente["usua_codi"], $radi_nume,'Documento Recibido','2', $mail_param);
                }
	    }
	}
	return $respEnvio;
}


function reintentarEnvioElectronicoDocumento($radicados, $usua_codi, $observa)
{
	$respEnvio = "";
	$respEnvio = $this->enviarDocumentosFirmaElectronica($radicados);
	foreach($radicados as $noRadicado)
	{
	    $this->insertarHistorico($noRadicado, $usua_codi, $usua_codi, $observa, 18);
	}
	return $respEnvio;
}

function enviarDocumentoElectronicoCiudadano ($radicados, $observa) {
    foreach($radicados as $radi_nume) {
        $sql = "select * from radicado where radi_nume_radi=$radi_nume";
        $rs = $this->db->conn->query($sql);
        if($rs->fields["ESTA_CODI"]==9) { //El documento debe estar en estado 9 (pendiente envio ciudadanos)
            $redirigido = 0+$rs->fields["RADI_USUA_REDIRIGIDO"];
            $destino = 0+str_replace("-", "", $rs->fields["RADI_USUA_DEST"]);
            // Redirigidos, valido que no se redirija al mismo destinatario
            if ($redirigido!=0 and $destino!=$redirigido) { //redirigido
                $this->insertarHistorico($radi_nume, $_SESSION["usua_codi"], $redirigido, "", 28); //Redirigir Documento
                $this->informar(array($radi_nume), $_SESSION["usua_codi"], $destino, "Documento externo dirigido a otro usuario.");
                $destino = $redirigido; // Para actualizar el radi_usua_actu
            }
            $this->insertarHistorico($rs->fields["RADI_NUME_RADI"], $_SESSION["usua_codi"], $destino, "", 18); //Envío Electrónico
            $this->enviarMail(str_replace("-", "", $rs->fields["RADI_USUA_REM"]), $destino, $radi_nume, "Documento Recibido");
            // Cambiamos el estado y el usuario actual
            $sql = "update radicado set esta_codi=2, radi_usua_actu=$destino where radi_nume_radi=$radi_nume";
            $this->db->conn->Execute($sql);
        }
    }
}

  function archivar($radicados, $usua_codi, $observa)
  {
    // Devolvia "Archivo" literal aunque el UPDATE no tocara nada, y realizarTx ni
    // siquiera miraba el retorno: la pantalla decia "ACCION REQUERIDA COMPLETADA"
    // en todos los casos. Mismo criterio que eliminarDocumento(): contar lo que
    // realmente se archivo y devolver "" si no fue ninguno.
    include_once(dirname(__DIR__,2).'/obtenerdatos.php');
    $usr_actu = ObtenerDatosUsuario($usua_codi,$this->db);
    $archivados = 0;
    foreach ($radicados as $radi_nume) {
        $sql = "update radicado set
                radi_fech_agend=null
                ,esta_codi=0
                where RADI_NUME_RADI = $radi_nume";
        if (!$this->db->conn->Execute($sql)) { # Ejecuta la modificacion
            error_log("QUIPUX archivar: no se archivo el documento $radi_nume. ".$this->db->conn->ErrorMsg());
            continue;
        }
        $this->insertarHistorico($radi_nume, $usua_codi, $usua_codi, $observa, 13);
        // Cancelamos todas las tareas pendientes
        $this->cancelarTodasTareasEnviadas($radi_nume, "Se archivó el documento");
        ++$archivados;
    }
    if ($archivados == 0) return "";
    return $usr_actu["nombre"] ?? '';
  }

  function noArchivar($radicados, $usua_codi, $observa)
  {
    include_once(dirname(__DIR__,2).'/obtenerdatos.php');
    $usr_actu = ObtenerDatosUsuario($usua_codi,$this->db);
    $restaurados = 0;
    foreach ($radicados as $radi_nume) {
        $estado = 2;
        if (substr($radi_nume,-1) !=1 ) $estado = 6;
        $isql = "update radicado set
                RADI_LEIDO=0
                ,radi_fech_agend=null
                ,esta_codi=$estado
                where RADI_NUME_RADI = $radi_nume";
        if (!$this->db->conn->Execute($isql)) { # Ejecuta la modificacion
            error_log("QUIPUX noArchivar: no se restauro el documento $radi_nume. ".$this->db->conn->ErrorMsg());
            continue;
        }
        $this->insertarHistorico($radi_nume, $usua_codi, $usua_codi, $observa, 25);
        ++$restaurados;
    }
    if ($restaurados == 0) return "";
    return $usr_actu["nombre"] ?? '';
  }

  function asignarTareas($radicados, $usua_codi_dest, $fecha_max_tram, $comentario)
  {
    if ($usua_codi_dest == $_SESSION["usua_codi"]) return "<font color='#c90a0a'>Error: No se puede asignar una tarea al mismo usuario.</font>";
    $mensaje = "La tarea fue asignada y deber&aacute; ser ejecutada antes del $fecha_max_tram.";
    foreach($radicados as $radi_nume) {
//TODO: Validar que no tenga tareas asignadas y el documento no le pertenezca al jefe
        $sql = "select radi_usua_actu, (select count(tarea_codi) from tarea where radi_nume_radi=$radi_nume and estado=1) as num
                from radicado where radi_nume_radi=$radi_nume";
        $rs = $this->db->conn->Execute($sql);
        if ($rs->fields["RADI_USUA_ACTU"]==$_SESSION["usua_codi_jefe"] && $_SESSION["usua_codi_jefe"]!=$_SESSION["usua_codi"] && $rs->fields["NUM"]==0) {
            $this->reasignar(array($radi_nume), $_SESSION["usua_codi_jefe"], $_SESSION["usua_codi"], "Asignación de tareas desde bandeja compartida");
        }
//        $mensaje .= "Documento No. ". $rs->fields["RADI_NUME_TEXT"]."<br>";

        $sql = "select tarea_codi from tarea where radi_nume_radi=$radi_nume and estado=1 and usua_codi_dest=".(int)$usua_codi_dest;
        $rs = $this->db->conn->Execute($sql);
        if (!$rs) {
            $mensaje = "<font color='#c90a0a'>Error DB: " . htmlspecialchars($this->db->conn->ErrorMsg()) . " q=" . htmlspecialchars($sql) . "</font><br>";
        } elseif (!$rs->EOF) {
            $mensaje = "<font color='#c90a0a'>Error: El usuario seleccionado ya tiene una tarea asignada, por favor, verifique.</font><br>";
        } else {
            $tarea_codi = $this->db->nextId("sec_tarea");
            $record["tarea_codi"] = $tarea_codi;
            $record["radi_nume_radi"] = $radi_nume;
            $record["fecha_inicio"] = $this->db->conn->sysTimeStamp;
            $record["fecha_maxima"] = "'$fecha_max_tram'::timestamp";
            $record["usua_codi_ori"] = $_SESSION["usua_codi"];
            $record["usua_codi_dest"] = $usua_codi_dest;
            $record["estado"] = "1";
            $record["leido"] = "0";
            $record["avance"] = "0";
            $sql = "select tarea_codi from tarea where radi_nume_radi=$radi_nume and estado=1 and usua_codi_dest=".$_SESSION["usua_codi"];
            $rs = $this->db->conn->Execute($sql);
            if (!$rs->EOF) $record["tarea_codi_padre"] = $rs->fields["TAREA_CODI"];
            $ok = $this->db->conn->Replace("tarea" ,$record, "", false, false, true, false);
            $this->insertarHistorico($radi_nume, $_SESSION["usua_codi"], $usua_codi_dest, $comentario, 50, $tarea_codi);
            $hist_codi = $this->insertarHistoricoTarea($tarea_codi, $radi_nume, $comentario, 50, $fecha_max_tram);
            $sql = "update tarea set comentario_inicio=$hist_codi where tarea_codi=$tarea_codi";
            $this->db->conn->Execute($sql);
//            $mensaje .= "&nbsp;&nbsp;&nbsp;&nbsp;Tarea Asignada<br>";
            $mail_param["fecha_maxima"] = $fecha_max_tram;
            $mail_param["comentario"] = $comentario;
            $this->enviarMail($_SESSION["usua_codi"], $usua_codi_dest, $radi_nume, "Tarea Asignada", "50", $mail_param);
        }
    }
    return $mensaje;
  }


  function finalizarTareas($tarea_codi, $comentario, $reasignar_respuesta=0)
  {
    $mensaje = "";
    $record = array();
    $sql = "select radi_nume_radi, usua_codi_ori, fecha_maxima from tarea where estado = 1 and tarea_codi=$tarea_codi and usua_codi_dest=".$_SESSION["usua_codi"];
    $rs = $this->db->conn->Execute($sql);
    if (!$rs or $rs->EOF) {
        $mensaje = "<font color='#c90a0a'>Error: No se encontro la tarea.</font><br>";
    } else {
        // Cancelamos las tareas hijas
        $sql = "select tarea_codi from tarea where estado=1 and tarea_codi_padre=$tarea_codi";
        $rsh = $this->db->conn->Execute($sql);
        while ($rsh and !$rsh->EOF) {
            $mensaje = $this->cancelarTareas($rsh->fields["TAREA_CODI"], $comentario, 1);
            $rsh->MoveNext();
        }

        $mensaje = "La tarea fue finalizada.".$mensaje;
        $record["tarea_codi"] = $tarea_codi;
        $record["estado"] = "2";
        $record["avance"] = "100";
        $record["fecha_fin"] = $this->db->conn->sysTimeStamp;
        $ok = $this->db->conn->Replace("tarea" ,$record, "tarea_codi", false, false, true, false);

        $hist_codi = $this->insertarHistoricoTarea($tarea_codi, $rs->fields["RADI_NUME_RADI"], $comentario, 51);
        $this->insertarHistorico($rs->fields["RADI_NUME_RADI"], $_SESSION["usua_codi"], $_SESSION["usua_codi"], $comentario, 51, $tarea_codi);
        $mail_param["tarea_codi"] = $tarea_codi;
        $mail_param["comentario"] = $comentario;
        $mail_param["fecha_maxima"] = substr($rs->fields["FECHA_MAXIMA"],0,16);
        $this->enviarMail($_SESSION["usua_codi"], $rs->fields["USUA_CODI_ORI"], $rs->fields["RADI_NUME_RADI"], "Tarea Finalizada", "51", $mail_param);

        // Reasignar respuestas
        if ($reasignar_respuesta == 1) {
            $sql = "select r.radi_nume_radi
                    from (select radi_nume_resp from tarea_radi_respuesta where tarea_codi=$tarea_codi) as tr
                    left outer join radicado r on tr.radi_nume_resp=r.radi_nume_radi where r.esta_codi=1 and r.radi_usua_actu=".$_SESSION["usua_codi"];
            $rsr = $this->db->conn->Execute($sql);
            while ($rsr and !$rsr->EOF) {
                $this->reasignar( array($rsr->fields["RADI_NUME_RADI"]), $_SESSION["usua_codi"], $rs->fields["USUA_CODI_ORI"], $comentario);
                $rsr->MoveNext();
            }
        }
    }
    return $mensaje;
  }

  function cancelarTareas($tarea_codi, $comentario, $forzar=0)
  {
    // Cancelamos las tareas hijas
    $mensaje = "";
    $sql = "select tarea_codi from tarea where estado=1 and tarea_codi_padre=$tarea_codi";
    $rs = $this->db->conn->Execute($sql);
    while ($rs and !$rs->EOF) {
        $mensaje = $this->cancelarTareas($rs->fields["TAREA_CODI"], $comentario, ($forzar+1));
        $rs->MoveNext();
    }

    $mensaje = "La tarea fue cancelada.".$mensaje;
    if ($forzar!=0) $mensaje = "<br>Fueron canceladas otras tareas dependientes.";
    $record = array();
    $sql = "select radi_nume_radi, usua_codi_dest, fecha_maxima from tarea where estado = 1 and tarea_codi=$tarea_codi";
    if ($forzar==0) $sql .=" and usua_codi_ori=".$_SESSION["usua_codi"];
    $rs = $this->db->conn->Execute($sql);
    if (!$rs or $rs->EOF) {
        $mensaje = "<font color='#c90a0a'>Error: No se encontro la tarea.</font>";
    } else {
        $record["tarea_codi"] = $tarea_codi;
        $record["estado"] = "3";
        $record["fecha_fin"] = $this->db->conn->sysTimeStamp;
//        $record["avance"] = "100";
        $ok = $this->db->conn->Replace("tarea" ,$record, "tarea_codi", false, false, true, false);

        $hist_codi = $this->insertarHistoricoTarea($tarea_codi, $rs->fields["RADI_NUME_RADI"], $comentario, 52);
        $this->insertarHistorico($rs->fields["RADI_NUME_RADI"], $_SESSION["usua_codi"], $_SESSION["usua_codi"], $comentario, 52, $tarea_codi);
        $mail_param["tarea_codi"] = $tarea_codi;
        $mail_param["comentario"] = $comentario;
        $mail_param["fecha_maxima"] = substr($rs->fields["FECHA_MAXIMA"],0,16);
        $this->enviarMail($_SESSION["usua_codi"], $rs->fields["USUA_CODI_DEST"], $rs->fields["RADI_NUME_RADI"], "Tarea Cancelada", "52", $mail_param);
    }
    return $mensaje;
  }


  function comentarTareas($tarea_codi, $comentario)
  {
    $mensaje = "Se a&ntilde;adi&oacute; un comentario a la tarea.";
    $sql = "select radi_nume_radi, usua_codi_ori, usua_codi_dest from tarea where tarea_codi=$tarea_codi";
    $rs = $this->db->conn->Execute($sql);
    if (!$rs or $rs->EOF) {
        $mensaje = "<font color='#c90a0a'>Error: No se encontro la tarea.</font>";
    } else {
        $hist_codi = $this->insertarHistoricoTarea($tarea_codi, $rs->fields["RADI_NUME_RADI"], $comentario, 53);

        $mail_param["tarea_codi"] = $tarea_codi;
        $mail_param["comentario"] = $comentario;
        $usua_dest = $rs->fields["USUA_CODI_DEST"];
        if ($rs->fields["USUA_CODI_DEST"] == $_SESSION["usua_codi"]) $usua_dest = $rs->fields["USUA_CODI_ORI"];
        $this->enviarMail($_SESSION["usua_codi"], $usua_dest, $rs->fields["RADI_NUME_RADI"], "Tarea Comentada", "53", $mail_param);
    }
    return $mensaje;
  }

function reabrirTareas($tarea_codi, $fecha_max_tram, $comentario)
  {
    $mensaje = "Se reabri&oacute; la tarea para que sea ejecutada hasta $fecha_max_tram.";
    $record = array();
    $sql = "select radi_nume_radi, usua_codi_dest from tarea where tarea_codi=$tarea_codi and estado in (2,3) and usua_codi_ori=".$_SESSION["usua_codi"];
    $rs = $this->db->conn->Execute($sql);
    if (!$rs or $rs->EOF) {
        $mensaje = "<font color='#c90a0a'>Error: No se encontro la tarea.</font>";
    } else {
        $record["tarea_codi"] = $tarea_codi;
        $record["estado"] = "1";
        $record["avance"] = "0";
        $record["fecha_maxima"] = "'$fecha_max_tram'::timestamp";
        $record["fecha_fin"] = "null";
        $ok = $this->db->conn->Replace("tarea" ,$record, "tarea_codi", false, false, true, false);

        $hist_codi = $this->insertarHistoricoTarea($tarea_codi, $rs->fields["RADI_NUME_RADI"], $comentario, 54, $fecha_max_tram);

        $mail_param["tarea_codi"] = $tarea_codi;
        $mail_param["comentario"] = $comentario;
        $mail_param["fecha_maxima"] = $fecha_max_tram;
        $this->enviarMail($_SESSION["usua_codi"], $rs->fields["USUA_CODI_DEST"], $rs->fields["RADI_NUME_RADI"], "Tarea Reabierta", "54", $mail_param);
    }
    return $mensaje;
  }

function editarTareas($tarea_codi, $fecha_max_tram, $comentario)
  {
    $fechaDebe=$this->buscarFechaTareaHija($tarea_codi,$fecha_max_tram,0);
    if ($this->buscarFechaTareaHija($tarea_codi,$fecha_max_tram,1)==1)
    $mensaje = "Se modific&oacute; la fecha m&aacute;xima para que se ejecute la tarea hasta $fecha_max_tram.";
    else
        $mensaje ="<font color='red'>No se actualizó la fecha, existen tareas hijas mayores a la fecha
            seleccionada (Fecha sugerida mayor a $fechaDebe)</font>";
    $record = array();
    $sql = "select radi_nume_radi, usua_codi_dest, comentario_inicio from tarea where tarea_codi=$tarea_codi and estado=1 and usua_codi_ori=".$_SESSION["usua_codi"];
    $rs = $this->db->conn->Execute($sql);
    if (!$rs or $rs->EOF) {
        $mensaje = "<font color='#c90a0a'>Error: No se encontro la tarea.</font><br>";
    } else {
        if ($this->buscarFechaTareaHija($tarea_codi,$fecha_max_tram,1)==1) 
        $hist_codi = $this->insertarHistoricoTarea($tarea_codi, $rs->fields["RADI_NUME_RADI"], $comentario, 55, $fecha_max_tram);
        else{
            
            $hist_codi = $this->insertarHistoricoTarea($tarea_codi, $rs->fields["RADI_NUME_RADI"], "La fecha debe ser mayor a $fechaDebe", 55, $fecha_max_tram);
        }
        $record["tarea_codi"] = $tarea_codi;
        $record["fecha_maxima"] = "'$fecha_max_tram'::timestamp";        
        $record["comentario_inicio"] = $hist_codi;    
        if ($this->buscarFechaTareaHija($tarea_codi,$fecha_max_tram,1)==1)                
        $ok = $this->db->conn->Replace("tarea" ,$record, "tarea_codi", false, false, true, false);
        $mail_param["comentario_inicio"] = $rs->fields["COMENTARIO_INICIO"];
        $mail_param["comentario"] = $comentario;
        $mail_param["fecha_maxima"] = $fecha_max_tram;
        $this->enviarMail($_SESSION["usua_codi"], $rs->fields["USUA_CODI_DEST"], $rs->fields["RADI_NUME_RADI"], "Tarea Modificada", "55", $mail_param);
    }
    return $mensaje;
  }
  //
  //busca la fecha de las tareas hijas para modificar la fecha de la tarea padre
function buscarFechaTareaHija($tarea_codi,$fecha_maxima_tram,$tipo){
    $sql = "select * from tarea where tarea_codi_padre = $tarea_codi";
    //echo $sql;
    $rs=$this->db->conn->query($sql);
    while(!$rs->EOF){
        $fechaMaxima=substr($rs->fields['FECHA_MAXIMA'],0,16);
        if($fechaMaxima<$fechaMaximaNext)
            $fechaFinal=$fechaMaximaNext;
        else 
             $fechaFinal=$fechaMaxima;       
            
            $fechaMaximaNext=substr($rs->fields['FECHA_MAXIMA'],0,16);
            $rs->MoveNext();
    }
    if ($tipo==1){        
        if ($fechaFinal<$fecha_maxima_tram)
            return 1;
        else
            return 0;
    }
    else
        return $fechaFinal;
     
     
}
  //


  function registrarAvanceTareas($tarea_codi, $tarea_avance, $comentario, $reasignar_respuesta=0)
  {
    $tarea_avance = 0+$tarea_avance;
    $mensaje = "Se registr&oacute; un avance en la tarea del $tarea_avance%.";
    $record = array();
    $sql = "select radi_nume_radi from tarea where tarea_codi=$tarea_codi and estado=1 and usua_codi_dest=".$_SESSION["usua_codi"];
    $rs = $this->db->conn->Execute($sql);
    if (!$rs or $rs->EOF) {
        $mensaje = "<font color='#c90a0a'>Error: No se encontro la tarea.</font><br>";
    } else {
        $record["tarea_codi"] = $tarea_codi;
        $record["avance"] = $tarea_avance;
        $ok = $this->db->conn->Replace("tarea" ,$record, "tarea_codi", false, false, true, false);
        $comentario .= " Avance: $tarea_avance%";
        $hist_codi = $this->insertarHistoricoTarea($tarea_codi, $rs->fields["RADI_NUME_RADI"], $comentario, 55, $tarea_avance);
        if ($tarea_avance==100) {
            $mensaje .= "<br>".$this->finalizarTareas($tarea_codi, "", $reasignar_respuesta);
        }
//        $this->enviarMail($usua_codi, $usua_dest, $radi_nume, "Informados");
    }
    return $mensaje;
  }

  function cambiarPropietarioTareas($radi_nume, $usua_dest, $usua_ori)
  {
    $rs = $this->db->conn->Execute("select usua_nombre from usuario where usua_codi=$usua_dest");
    $usua_nombre = ($rs && !$rs->EOF) ? $rs->fields["USUA_NOMBRE"] : "";

    $mensaje = "Se cambi&oacute; propietario de la tarea.";
    $sql = "select tarea_codi from tarea where radi_nume_radi=$radi_nume and usua_codi_ori=".$usua_ori;
    $rs = $this->db->conn->Execute($sql);
    if (!$rs or $rs->EOF) {
        $mensaje = "<font color='#c90a0a'>Error: No se encontro la tarea.</font><br>";
    } else {
        while (!$rs->EOF) {
            $record["tarea_codi"] = $rs->fields["TAREA_CODI"];
            $record["usua_codi_ori"] = $usua_dest;
            $ok = $this->db->conn->Replace("tarea" ,$record, "tarea_codi", false, false, true, false);
            $comentario = "El documento fue reasignado a $usua_nombre";
            $hist_codi = $this->insertarHistoricoTarea($rs->fields["TAREA_CODI"], $radi_nume, $comentario, 57, $tarea_avance);
            $rs->MoveNext();
        }
    }
    return $mensaje;
  }
   /**
   * Funcion que permite asignar las tareas de los documentos de un usuario a otro
   * @autor David Gamboa, snap, 2014-02-06
   * @param array $radi_nume
   * @param integer $usua_dest->nuevo dueño de la tarea->subrogante
   * @param integer $usua_ori->usuario subrogado
   * @return string
   */
  
  function cambiarPropietarioTareasSubrogacion($radicados, $usua_dest, $usua_ori,$tipo=1)
  {
    $rs = $this->db->conn->Execute("select usua_nombre from usuario where usua_codi=$usua_dest");
    $usua_nombre = $rs->fields["USUA_NOMBRE"];

    $mensaje = "Se cambi&oacute; propietario de la tarea.";
    foreach($radicados as $radi_nume) {
        $sql = "select tarea_codi from tarea where radi_nume_radi=$radi_nume 
            and usua_codi_dest=".$usua_ori;
        //echo $sql;
        $rs = $this->db->conn->Execute($sql);
        if (!$rs or $rs->EOF) {
            $mensaje = "<font color='#c90a0a'>Error: No se encontro la tarea.</font><br>";
        } else {        
            while (!$rs->EOF) {
                $record["tarea_codi"] = $rs->fields["TAREA_CODI"];
                $record["usua_codi_dest"] = $usua_dest;                
                $ok = $this->db->conn->Replace("tarea" ,$record, "tarea_codi", false, false, true, false);
                if ($tipo==1)
                    $subrogacion="Por Subrogación";
                else
                $subrogacion="Por desactivación de Subrogación.";
                $comentario = "El documento fue reasignado a $usua_nombre, $subrogacion";
                $hist_codi = $this->insertarHistoricoTarea($rs->fields["TAREA_CODI"], $radi_nume, $comentario, 57, $tarea_avance);
                $rs->MoveNext();
            }
        }
    }
    return $mensaje;
  }
  /* Función que cambia el usuario de las tareas enviadas   
   * $usuario_actual: Código del usuario actual
   * $usuario_nuevo: Código del usuario nuevo
   */
  function cambiarUsuarioTareasEnviadas($usuario_actual, $usuario_nuevo)
  {
    $rs = $this->db->conn->Execute("select usua_nombre from usuario where usua_codi=$usuario_nuevo");
    $usua_nombre = $rs->fields["USUA_NOMBRE"];

    //Cambio de usuario de tareas enviadas
    $mensaje = "Se cambi&oacute; propietario de la tarea enviada por inactivación de usuario.";
    $sql = "select * from tarea where usua_codi_ori=$usuario_actual";   
    $rs = $this->db->conn->Execute($sql);
    while (!$rs->EOF) {
        $radi_nume = $rs->fields["RADI_NUME_RADI"];
        $tarea_codi = $rs->fields["TAREA_CODI"];        
        $record["tarea_codi"] = $rs->fields["TAREA_CODI"];
        $record["usua_codi_ori"] = $usuario_nuevo;
        $ok = $this->db->conn->Replace("tarea" ,$record, "tarea_codi", false, false, true, false);
        $comentario = "La tarea enviada fue asignada a $usua_nombre";
        $hist_codi = $this->insertarHistoricoTarea($tarea_codi, $radi_nume, $comentario, 59, $tarea_avance);
        $rs->MoveNext();
    }
    
    return $mensaje;
  }

  /* Función que cambia el usuario de las tareas recibidas  
   * $usuario_actual: Código del usuario actual
   * $usuario_nuevo: Código del usuario nuevo
   */
  function cambiarUsuarioTareasRecibidas($usuario_actual, $usuario_nuevo)
  {
    $rs = $this->db->conn->Execute("select usua_nombre from usuario where usua_codi=$usuario_nuevo");
    $usua_nombre = $rs->fields["USUA_NOMBRE"];

    //Cambio de usuario de tareas recibidas
    $mensaje = "Se cambi&oacute; propietario de la tarea recibida por inactivación de usuario.";
    $sql = "select * from tarea where usua_codi_dest=$usuario_actual";
    $rs = $this->db->conn->Execute($sql);
    while (!$rs->EOF) {
        $radi_nume = $rs->fields["RADI_NUME_RADI"];
        $tarea_codi = $rs->fields["TAREA_CODI"];       
        $record["tarea_codi"] = $rs->fields["TAREA_CODI"];
        $record["usua_codi_dest"] = $usuario_nuevo;
        $ok = $this->db->conn->Replace("tarea" ,$record, "tarea_codi", false, false, true, false);
        $comentario = "La tarea recibida fue asignada a $usua_nombre";
        $hist_codi = $this->insertarHistoricoTarea($tarea_codi, $radi_nume, $comentario, 59, $tarea_avance);
        $rs->MoveNext();
    }

    return $mensaje;
  }

  // === DEBUG TEMPORAL - BORRAR DESPUÉS ===
  function _txCheck($step) {
      // Run a harmless SELECT 1 to detect if the transaction is aborted
      $probe = @$this->db->conn->Execute("SELECT 1 AS ok");
      if (!$probe) {
          $err = $this->db->conn->ErrorMsg();
          echo "<pre style='background:#f8d7da;border:1px solid #f5c6cb;padding:4px;font-size:11px'>"
             . "[TX-ABORT] After step: <b>" . htmlspecialchars($step) . "</b>\n"
             . "Error: " . htmlspecialchars($err) . "\n"
             . "</pre>";
      } else {
          echo "<pre style='background:#d4edda;border:1px solid #c3e6cb;padding:2px 4px;font-size:11px'>"
             . "[TX-OK] " . htmlspecialchars($step) . "</pre>";
      }
  }
  // === FIN DEBUG ===

  /* Función que cambia el usuario de las tareas recibidas
   * $usuario_actual: Código del usuario actual
   * $usuario_nuevo: Código del usuario nuevo
   */
  function cancelarTodasTareasEnviadas($radi_nume, $comentario)
  {
      // Cancelamos todas las tareas pendientes
      $sql = "select tarea_codi from tarea where radi_nume_radi=$radi_nume and estado=1 order by tarea_codi desc";
      $rst = $this->db->conn->Execute($sql);
      while (!$rst->EOF) {
          $this->cancelarTareas($rst->fields["TAREA_CODI"], $comentario, 1);
          $rst->MoveNext();
      }
      return;
  }


  function registrarDocumentoRespuestaTareas($radi_nume, $radi_respuesta, $comentario)
  {
    $sql = "select tarea_codi from tarea where radi_nume_radi=$radi_nume and estado=1 and usua_codi_dest=".$_SESSION["usua_codi"];
    $rs = $this->db->conn->Execute($sql);
    if (!$rs or $rs->EOF) {
        return 0;
    } else {
        $record = array();
        $record["tarea_codi"] = $rs->fields["TAREA_CODI"];
        $record["radi_nume_radi"] = $radi_nume;
        $record["radi_nume_resp"] = $radi_respuesta;
        $ok = $this->db->conn->Replace("tarea_radi_respuesta" ,$record, "", false, false, false, false);
//        $comentario = "Se registr&oacute; el documento de respuesta No. " . $comentario;
        $hist_codi = $this->insertarHistoricoTarea($rs->fields["TAREA_CODI"], $radi_nume, $comentario, 58, $radi_respuesta);
        return 1;
    }
    return 0;
  }

    /**
     * Recuperar documentos reasignados
     */
  //$radicados al que se ejecuta la accion
  //$usua_codi_ori usuario que ejecuta la accion
  function recuperarReasignado($radicados, $usua_codi_ori,$observa)
  {
      $fecha_tramite = date("Y-m-d");
    foreach ($radicados as $radi_nume) {        
        $sqlr = "select radi_usua_actu,radi_usua_ante, radi_usua_rem, esta_codi 
        from radicado where radi_nume_radi=$radi_nume";
        //echo $sqlr;
        $rsr=$this->db->conn->Execute($sqlr);
        if (!$rsr->EOF){
            $usua_actu = $rsr->fields['RADI_USUA_ACTU'];//usuario actual del documento
            //$usua_ante = $rsr->fields['RADI_USUA_ANTE'];           
            $estadoDoc = $rsr->fields['ESTA_CODI'];//estado del documento
        }
        //busco el último evento de reasignacion
        
        $sql = "select max(hist_codi) as hist_codi 
        from hist_eventos where radi_nume_radi = $radi_nume and sgd_ttr_codigo = 9 and usua_codi_ori = ".$_SESSION['usua_codi'];
        //echo $sql;
        $rs=$this->db->conn->Execute($sql);
        if (!$rs->EOF){
            $hist_codi = $rs->fields['HIST_CODI'];
            if ($hist_codi!=''){//busco el usuario destino del evento de la reasignación
                $sqlh = "select usua_codi_dest,usua_codi_ori from hist_eventos where hist_codi = $hist_codi";
                $rsh=$this->db->conn->Execute($sqlh);
                if (!$rsh->EOF){
                    $re_usua_codi_dest = $rsh->fields['USUA_CODI_DEST'];
                    $re_usua_codi_ori = $rsh->fields['USUA_CODI_ORI'];
                }
            }
        }
        /**
         * verificar si el usuario actual del documento es igual al
         * ultimo del evento de reasignacion.
        */        
        //recuperar si son iguales, usuario destino y usuario actual, ademas que el documento
        //este en estado en Tramite
        $estadoUsrIn = 0;
        $datosUsrIn = ObtenerDatosUsuario($re_usua_codi_dest, $this->db);
        $estadoUsrIn = $datosUsrIn['usua_estado'];
        //Remitente
        $sql = "select usua_esta from usuarios where usua_codi = ".str_replace("-","",$usua_actu);        
        //echo $sql;
        $rsrem = $this->db->conn->Execute($sql);  
        
        if ($estadoDoc == 2 || $estadoDoc == 1){
            $sqlUp ="update radicado set radi_usua_actu = $re_usua_codi_ori";
            if ($rsrem->fields['USUA_ESTA']!='')//evitar errores de documentos externos
            $sqlUp.=" , radi_usua_ante = ".str_replace("-","",$usua_actu);
            $sqlUp.=" , radi_fech_asig=to_timestamp('$fecha_tramite', 'YYYY-MM-DD')";
            $sqlUp.=" where radi_nume_radi = $radi_nume";
            //volver el documento al usuario
            
            if ($re_usua_codi_dest==$usua_actu){                
                $this->db->conn->Execute($sqlUp);                
                $mensaje=$observa."<br> Se recuperó el documento desde Reasignación";                
                $this->insertarHistorico($radi_nume, $usua_codi_ori, $usua_codi_ori, $mensaje, 83);
                //recuperar tareas
                $this->recuperarTareas($radi_nume,$_SESSION["usua_codi"]);
                
            }else{                
                if($estadoUsrIn==0){
                    $mensaje=$observa."<br> Se recuperó el documento desde Reasignación (de un usuario Subrogante o Inactivo)";                   
                    $this->db->conn->Execute($sqlUp);                   
                    $this->insertarHistorico($radi_nume, $usua_codi_ori, $usua_codi_ori, $mensaje, 83);
                    //recuperar tareas
                    $this->recuperarTareas($radi_nume,$_SESSION["usua_codi"]);
                }
                elseif($_SESSION['usua_codi']==$usua_actu)
                    $mensaje="<font color='blue'>El documento ya fue recuperado, favor revise la bandeja de Recibidos</font>";
                    else                                
                    $mensaje="<font color='red'>No se puede ejecutar esta acción, ya que el documento se está procesando</font>";
            }            
            
        }else{
            $mensaje="<font color='red'>No se puede ejecutar esta acción, ya que el documento se está procesando</font>";
        }
    }
    return $mensaje;
  }
  /*
   * Buscar las tareas involucradas del documento con la persona de session
   */
  function recuperarTareas($radi_nume_radi,$usua_codi_ori){
      //busco la transaccion de tareas del usuario a recuperar (esta en sesion)
      $sql = "select * from hist_eventos where usua_codi_ori = $usua_codi_ori 
      and radi_nume_radi = $radi_nume_radi and sgd_ttr_codigo = 50";
      //echo $sql;
      $rs = $this->db->conn->Execute($sql);
      
      while (!$rs->EOF) {
          $ttr_codigo = $rs->fields["HIST_REFERENCIA"];  
          $this->actualizarTareaRecuperar($ttr_codigo,$usua_codi_ori,$radi_nume_radi);
          $rs->MoveNext();
      }
  }
  /*
   * Al recuperar el documento desde tareas
   */
  function actualizarTareaRecuperar($ttr_codigo,$usua_codi_ori,$radi_nume){
      $fecha_tramite = date("Y-m-d");
      $recTarea = array();
      unset($recTarea);
      $recTarea["tarea_codi"] = $ttr_codigo;      
      $recTarea["usua_codi_ori"] = $usua_codi_ori;      
      //print_r($recTarea);
      $this->db->conn->Replace("tarea" ,$recTarea, "tarea_codi", false, false, true, false);
      $comentario = "Se cambió propietario de la tarea.";
      //insertar historico tareas
      $this->insertarHistoricoTarea($ttr_codigo, $radi_nume, $comentario, 59, $tarea_avance);
  }
  /*
   * Funcion asociar a carpetas virtuales
   * radicadoSerl: listado de radicados
   * usua_codi: usuario quien realiza la accion
   * depe_codi: dependencia de usuario en sesion
   * trd_codigo: carpeta virtual a actualizar
   * observa: observaciones
   */
  function AsoCarpetasVirtuales($radicadosSel,$usua_codi,$depe_codi,$trd_codigo,$observa){
              $fecha_tramite = date("Y-m-d");
              $mensaje = "";
       //consultar la carpeta

    foreach ($radicadosSel as $radi_nume) {
         $record["FECHA"] = "to_timestamp('$fecha_tramite', 'YYYY-MM-DD')";
         $record["USUA_CODI"] = $usua_codi;
         $record["DEPE_CODI"] = $depe_codi;
         $record["TRD_CODI"] = $trd_codigo;
         $record["RADI_NUME_RADI"] = $radi_nume;
         
         $sql2="select fecha, trd_codi from trd_radicado 
         where radi_nume_radi = $radi_nume";
         $rs2 = $this->db->conn->Execute($sql2);
         //si el documento tiene registro        
                if (!$rs2->EOF){                    
                    $carpetaAnterior = $rs2->fields['TRD_CODI'];
                    if ($carpetaAnterior!=$trd_codigo){
                        $where = array("RADI_NUME_RADI","DEPE_CODI");
                        $mensaje = "Se actualiza la carpeta de ".$this->nombreCarpeta($carpetaAnterior)." a la carpeta ".$this->nombreCarpeta($trd_codigo);
                        $ok = $this->db->conn->Replace("TRD_RADICADO", $record, $where, false,false,true,false);
                        $this->insertarHistorico($radi_nume, $usua_codi, $usua_codi, $mensaje, 88);
                    }
                }else{//si no tiene registro
                    $mensaje = "Incluir documento en carpeta: ".$this->nombreCarpeta($trd_codigo);
                    $where = "";
                    $ok = $this->db->conn->Replace("TRD_RADICADO", $record, $where, false,false,true,false);
                    $this->insertarHistorico($radi_nume, $usua_codi, $usua_codi, $mensaje, 88);
                }
              
         }
         if (trim($mensaje)=='')
             $mensaje="EL Documento ya está incluido en la carpeta virtual ".$this->nombreCarpeta($trd_codigo);
    return $mensaje;
  }
  /*
   * Busqueda de nombre de carpeta
   */
  function nombreCarpeta($trd_codigo){
       $sql= "select * from trd where trd_codi = $trd_codigo";       
       $rs = $this->db->conn->Execute($sql);
       if (!$rs->EOF){
           $nombreCarpeta = $rs->fields['TRD_NOMBRE'];
       }  else {
           $nombreCarpeta="";
       }
       return $nombreCarpeta;
  }
    /**
    * Metodo que sirve para envio de mail.
    *
    * @param int $remitente quien envia el mail.
    * @param int $destinatario a quien se envia el mail.
    * @param string $asunto descripcion pequeña del mail.
    * @param string $desc texto contenido del mail.
    * @return confirmación.
    */
    /**
     * Filas de "Sumilla" y "Comentario" del correo de reasignación.
     *
     * Se emiten sólo si vienen con contenido, para no dejar filas vacías en las
     * transacciones que no llevan sumilla ni comentario.
     *
     * @param  array $parametros claves "sumillas" y "comentario"
     * @return string filas <tr> listas para insertar en la tabla del correo
     */
    function filasSumillaComentario($parametros)
    {
        $filas    = "";
        $sumillas = trim($parametros["sumillas"] ?? "");
        $comenta  = trim($parametros["comentario"] ?? "");

        if ($sumillas != "")
            $filas .= "<tr><td valign='top'><b>Sumilla:</b></td><td>".htmlspecialchars($sumillas)."</td></tr>";

        if ($comenta != "")
            $filas .= "<tr><td valign='top'><b>Comentario:</b></td><td>"
                    . nl2br(htmlspecialchars($comenta))."</td></tr>";

        return $filas;
    }

    function enviarMail($remitente, $destinatario, $radi_nume, $nombre_accion="", $accion="0", $parametros = array())
    {
        if ($remitente == $destinatario) return;
        $ruta_raiz = $this->db->rutaRaiz;
        include(dirname(__DIR__,2).'/config.php');
        include_once(dirname(__DIR__,2).'/obtenerdatos.php');		//Consulta de datos de los usuarios y radicados
        // Puente del entorno local de desarrollo. /local/ está en .gitignore y no se
        // despliega, así que en producción esto no existe y el envío sigue por mail().
        if (is_file(dirname(__DIR__,2).'/local/mail/Mailer.php')) include_once(dirname(__DIR__,2).'/local/mail/Mailer.php');

        if (ObtenerPermisoUsuario($destinatario, 21, $this->db) == 0) return;
        $dest = ObtenerDatosUsuario ($destinatario, $this->db);

        if($dest["email"]!="" and strpos($dest["email"],"@") and strpos($dest["email"],".",strpos($dest["email"],"@")))
        {
            /**
            * Estructura de la descripcion de email.
            */
            $rem = ObtenerDatosUsuario ($remitente, $this->db);
            $radicado = ObtenerDatosRadicado ($radi_nume, $this->db);

            $asunto = " - ".$radicado["radi_asunto"];
            $mail_body = "<!DOCTYPE html><title>Informaci&oacute;n Quipux</title>";
            $mail_body .= "<body><center><h2>Sistema de Gesti&oacute;n Documental Quipux</h2><br><br></center>";
            $mail_body .= "Estimado(a):<br><br>".$dest["abr_titulo"] . " " . $dest["nombre"] . "<br>" . $dest["cargo"]. "<br><br>";


            switch ($accion)
            {
                case '0':
                    if (!isset ($parametros["bandeja"])) $parametros["bandeja"] = ($radicado["estado"]==1) ? "en Elaboraci&oacute;n" : "Recibidos";
                    if (!isset ($parametros["enviado_por"])) $parametros["enviado_por"] = "Remitente:";

                    $mail_body .= "Ha recibido un documento en el sistema, por favor revise su bandeja de Documentos ".$parametros["bandeja"].
                              " ingresando a &quot;<a href='$nombre_servidor' target='_blank'>$nombre_servidor</a>&quot;";

                    if ($dest["tipo_usuario"]==2)
                        $mail_body .= " con el usuario: &quot;".$dest["cedula"]."&quot;";
                    $mail_body .= "<br><br>Informaci&oacute;n del Documento:<br><br>";
                    $mail_body .= "<table border='0' width='100%'><tr><td width='30%'><b>Fecha:</b></td><td width='70%'>".date("Y-m-d H:i:s")."</td></tr>";
                    $mail_body .= "<tr><td><b>No. de Documento:</b></td><td>".$radicado["radi_nume_text"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Asunto:</b></td><td>".$radicado["radi_asunto"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>".$parametros["enviado_por"]."</b></td><td>".$rem["abr_titulo"] . " " . $rem["nombre"] .
                                  "<br>" . $rem["cargo"] . "<br>" . $rem["institucion"]."</td></tr>";
                    $mail_body .= $this->filasSumillaComentario($parametros);
                    $mail_body .= "</table>";
                         // "<br><a href='mailto:" . $rem["email"]. "'>" . $rem["email"]. "</a></td></tr></table>";
                    break;
                case '9':
                    if (!isset ($parametros["bandeja"])) $parametros["bandeja"] = ($radicado["estado"]==1) ? "en Elaboraci&oacute;n" : "Recibidos";
                    if (!isset ($parametros["enviado_por"])) $parametros["enviado_por"] = "Remitente:";
                    $asunto = "";

                    $mail_body .= "Ha recibido varios documentos en el sistema, por favor revise su bandeja de Documentos ".$parametros["bandeja"].
                                  " ingresando a &quot;<a href='$nombre_servidor' target='_blank'>$nombre_servidor</a>&quot;";
                    $mail_body .= "<br><br><table border='0' width='100%'><tr><td width='30%'><b>Fecha:</b></td><td width='70%'>".date("Y-m-d H:i:s")."</td></tr>";
                    $mail_body .= "<tr><td><b>No. de Documentos:</b></td><td>".$parametros["num_docs"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>".$parametros["enviado_por"]."</b></td><td>".$rem["abr_titulo"] . " " . $rem["nombre"] .
                                  "<br>" . $rem["cargo"] . "<br>" . $rem["institucion"]."</td></tr>";
                    $mail_body .= $this->filasSumillaComentario($parametros);
                    $mail_body .= "</table>";
                         // "<br><a href='mailto:" . $rem["email"]. "'>" . $rem["email"]. "</a></td></tr></table>";
                    break;
                case '1': // Envio de mail para el Jefe de área cuando un documento de su bandeja de recibidos ha sido tomado.
                    $nombre_accion = "Bandeja Compartida";
                    $mail_body .= "Un Documento ha sido tomado de su bandeja de Documentos Recibidos del sistema &quot;<a href='$nombre_servidor' target='_blank'>$nombre_servidor</a>&quot;";
                    $mail_body .= "<br><br>Informaci&oacute;n del Documento:<br><br>";
                    $mail_body .= "<table border='0' width='100%'><tr><td width='30%'><b>Fecha:</b></td><td width='70%'>".date("Y-m-d H:i:s")."</td></tr>";
                    $mail_body .= "<tr><td><b>No. de Documento:</b></td><td>".$radicado["radi_nume_text"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Asunto:</b></td><td>".$radicado["radi_asunto"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Tomado por:</b></td><td>".$rem["abr_titulo"] . " " . $rem["nombre"] .
                                  "<br>" . $rem["cargo"] . "<br>" . $rem["institucion"].
                                  "<br><a href='mailto:" . $rem["email"]. "'>" . $rem["email"]. "</a></td></tr></table>";
                    $mail_body .= "<br><br>Si desea ver el contenido del documento, por favor, revise su Bandeja de Documentos Reasignados.";
                    break;
                case '1A': // Envio de mail para el Jefe de área cuando un documento de su bandeja de recibidos ha sido tomado.
                    $nombre_accion = "Bandeja Compartida";
                    $asunto = "";
                    $mail_body .= "Varios Documentos han sido tomados de su bandeja de Documentos Recibidos del sistema &quot;<a href='$nombre_servidor' target='_blank'>$nombre_servidor</a>&quot;";
                    $mail_body .= "<br><br><table border='0' width='100%'><tr><td width='30%'><b>Fecha:</b></td><td width='70%'>".date("Y-m-d H:i:s")."</td></tr>";
                    $mail_body .= "<tr><td><b>No. de Documentos:</b></td><td>".$parametros["num_docs"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Tomado por:</b></td><td>".$rem["abr_titulo"] . " " . $rem["nombre"] .
                                  "<br>" . $rem["cargo"] . "<br>" . $rem["institucion"].
                                  "<br><a href='mailto:" . $rem["email"]. "'>" . $rem["email"]. "</a></td></tr></table>";
                    $mail_body .= "<br><br>Si desea ver el contenido de los documentos, por favor, revise su Bandeja de Documentos Reasignados.";
                    break;
                case '2': // Envio de mail a la asistente cuando el Jefe de área ha firmado un documento digitalmente
                    //Obtener los datos del destinatario del documento
                    $nombre_accion = "Documento Firmado";
                    $destRadi = ObtenerDatosUsuario ($parametros["usuario"], $this->db);

                    $mail_body .= "Documento enviado al jefe de &Aacute;rea, por favor revise su bandeja compartida ingresando a &quot;<a href='$nombre_servidor' target='_blank'>$nombre_servidor</a>&quot;";
                    $mail_body .= "<br><br>Informaci&oacute;n del Documento:<br><br>";
                    $mail_body .= "<table border='0' width='100%'><tr><td width='30%'><b>Fecha:</b></td><td width='70%'>".date("Y-m-d H:i:s")."</td></tr>";
                    $mail_body .= "<tr><td><b>No. de Documento:</b></td><td>".$radicado["radi_nume_text"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Asunto:</b></td><td>".$radicado["radi_asunto"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Remitente:</b></td><td>".$rem["abr_titulo"] . " " . $rem["nombre"] .
                                  "<br>" . $rem["cargo"] . "<br>" . $rem["institucion"].
                                  "<br><a href='mailto:" . $rem["email"]. "'>" . $rem["email"]. "</a></td></tr>".
                                  "</table>";
                    $mail_body .= "<br><br> Si desea ver m&aacute;s informaci&oacute;n, por favor, buscar el documento en la opci&oacute;n \"B&uacute;squeda Avanzada\".";
                    break;
                case '21': // comentar Documento
                    $mail_body .= "Han realizado un comentario en uno de sus documentos, por favor revise el documento ingresando a &quot;<a href='$nombre_servidor' target='_blank'>$nombre_servidor</a>&quot;";
                    $mail_body .= "<br><br>Informaci&oacute;n del documento:<br><br>";
                    $mail_body .= "<table border='0' width='100%'><tr><td width='30%'><b>Fecha:</b></td><td width='70%'>".date("Y-m-d H:i:s")."</td></tr>";
                    $mail_body .= "<tr><td><b>Comentario:</b></td><td>".$parametros["comentario"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Comentario realizado por:</b></td><td>".$rem["abr_titulo"] . " " . $rem["nombre"] .
                                  "<br>" . $rem["cargo"] . "<br><a href='mailto:" . $rem["email"]. "'>" . $rem["email"]. "</a></td></tr>";
                    $mail_body .= "<tr><td><b>No. de documento:</b></td><td>".$radicado["radi_nume_text"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Asunto del documento:</b></td><td>".$radicado["radi_asunto"]."</td></tr></table>";
                    //$mail_body .= "<br><br>Por favor revise el documento en el sistema &quot;<a href='$nombre_servidor' target='_blank'>$nombre_servidor</a>&quot;";
                    break;
                case '50': // Asignar nueva Tarea
                    $mail_body .= "Le han asignado una nueva tarea.";
                    $mail_body .= "<br><br>Informaci&oacute;n de la tarea:<br><br>";
                    $mail_body .= "<table border='0' width='100%'><tr><td width='30%'><b>Fecha:</b></td><td width='70%'>".date("Y-m-d H:i:s")."</td></tr>";
                    $mail_body .= "<tr><td><b>Tarea asignada:</b></td><td>".$parametros["comentario"]."</td></tr>";
                    $mail_body .= "<tr><td><b>Fecha m&aacute;xima de tr&aacute;mite:</b></td><td>".$parametros["fecha_maxima"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Asignado por:</b></td><td>".$rem["abr_titulo"] . " " . $rem["nombre"] .
                                  "<br>" . $rem["cargo"] . "<br><a href='mailto:" . $rem["email"]. "'>" . $rem["email"]. "</a></td></tr>";
                    $mail_body .= "<tr><td><b>No. de documento:</b></td><td>".$radicado["radi_nume_text"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Asunto del documento:</b></td><td>".$radicado["radi_asunto"]."</td></tr></table>";
                    $mail_body .= "<br><br>Por favor revise su bandeja de Tareas Recibidas en el sistema &quot;<a href='$nombre_servidor' target='_blank'>$nombre_servidor</a>&quot;";
                    break;
                case '51': // finalizar Tarea
                    $rs = $this->db->conn->Execute("select comentario from tarea_hist_eventos where tarea_hist_codi in (select comentario_inicio from tarea where tarea_codi=".$parametros["tarea_codi"].")");
                    $mail_body .= "Se ha finalizado una tarea asignada por usted.";
                    $mail_body .= "<br><br>Informaci&oacute;n de la tarea:<br><br>";
                    $mail_body .= "<table border='0' width='100%'><tr><td width='30%'><b>Fecha:</b></td><td width='70%'>".date("Y-m-d H:i:s")."</td></tr>";
                    $mail_body .= "<tr><td><b>Tarea asignada:</b></td><td>".$rs->fields["COMENTARIO"]."</td></tr>";
                    $mail_body .= "<tr><td><b>Fecha m&aacute;xima de tr&aacute;mite:</b></td><td>".$parametros["fecha_maxima"]."</td></tr>";
                    $mail_body .= "<tr><td><b>Comentario final:</b></td><td>".$parametros["comentario"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Finalizado por:</b></td><td>".$rem["abr_titulo"] . " " . $rem["nombre"] .
                                  "<br>" . $rem["cargo"] . "<br><a href='mailto:" . $rem["email"]. "'>" . $rem["email"]. "</a></td></tr>";
                    $mail_body .= "<tr><td><b>No. de documento:</b></td><td>".$radicado["radi_nume_text"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Asunto del documento:</b></td><td>".$radicado["radi_asunto"]."</td></tr></table>";
                    $mail_body .= "<br><br>Por favor revise el documento en el sistema &quot;<a href='$nombre_servidor' target='_blank'>$nombre_servidor</a>&quot;";
                    break;
                case '52': // Cancelar Tarea
                    $rs = $this->db->conn->Execute("select comentario from tarea_hist_eventos where tarea_hist_codi in (select comentario_inicio from tarea where tarea_codi=".$parametros["tarea_codi"].")");
                    $mail_body .= "Se ha cancelado una tarea asignada a usted.";
                    $mail_body .= "<br><br>Informaci&oacute;n de la tarea:<br><br>";
                    $mail_body .= "<table border='0' width='100%'><tr><td width='30%'><b>Fecha:</b></td><td width='70%'>".date("Y-m-d H:i:s")."</td></tr>";
                    $mail_body .= "<tr><td><b>Tarea asignada:</b></td><td>".$rs->fields["COMENTARIO"]."</td></tr>";
                    $mail_body .= "<tr><td><b>Fecha m&aacute;xima de tr&aacute;mite:</b></td><td>".$parametros["fecha_maxima"]."</td></tr>";
                    $mail_body .= "<tr><td><b>Comentario final:</b></td><td>".$parametros["comentario"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Cancelada por:</b></td><td>".$rem["abr_titulo"] . " " . $rem["nombre"] .
                                  "<br>" . $rem["cargo"] . "<br><a href='mailto:" . $rem["email"]. "'>" . $rem["email"]. "</a></td></tr>";
                    $mail_body .= "<tr><td><b>No. de documento:</b></td><td>".$radicado["radi_nume_text"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Asunto del documento:</b></td><td>".$radicado["radi_asunto"]."</td></tr></table>";
                    $mail_body .= "<br><br>Por favor revise el documento en el sistema &quot;<a href='$nombre_servidor' target='_blank'>$nombre_servidor</a>&quot;";
                    break;
                case '53': // Comentar Tarea
                    $rs = $this->db->conn->Execute("select comentario from tarea_hist_eventos where tarea_hist_codi in (select comentario_inicio from tarea where tarea_codi=".$parametros["tarea_codi"].")");
                    $mail_body .= "Se ha comentado una tarea en la que usted interviene.";
                    $mail_body .= "<br><br>Informaci&oacute;n de la tarea:<br><br>";
                    $mail_body .= "<table border='0' width='100%'><tr><td width='30%'><b>Fecha:</b></td><td width='70%'>".date("Y-m-d H:i:s")."</td></tr>";
                    $mail_body .= "<tr><td><b>Tarea:</b></td><td>".$rs->fields["COMENTARIO"]."</td></tr>";
                    $mail_body .= "<tr><td><b>Comentario:</b></td><td>".$parametros["comentario"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Comentario realizado por:</b></td><td>".$rem["abr_titulo"] . " " . $rem["nombre"] .
                                  "<br>" . $rem["cargo"] . "<br><a href='mailto:" . $rem["email"]. "'>" . $rem["email"]. "</a></td></tr>";
                    $mail_body .= "<tr><td><b>No. de documento:</b></td><td>".$radicado["radi_nume_text"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Asunto del documento:</b></td><td>".$radicado["radi_asunto"]."</td></tr></table>";
                    $mail_body .= "<br><br>Por favor revise el documento en el sistema &quot;<a href='$nombre_servidor' target='_blank'>$nombre_servidor</a>&quot;";
                    break;
                case '54': // Reabrir Tarea
                    $rs = $this->db->conn->Execute("select comentario from tarea_hist_eventos where tarea_hist_codi in (select comentario_inicio from tarea where tarea_codi=".$parametros["tarea_codi"].")");
                    $mail_body .= "Se reabri&oacute; una tarea asignada a usted.";
                    $mail_body .= "<br><br>Informaci&oacute;n de la tarea:<br><br>";
                    $mail_body .= "<table border='0' width='100%'><tr><td width='30%'><b>Fecha:</b></td><td width='70%'>".date("Y-m-d H:i:s")."</td></tr>";
                    $mail_body .= "<tr><td><b>Tarea asignada:</b></td><td>".$rs->fields["COMENTARIO"]."</td></tr>";
                    $mail_body .= "<tr><td><b>Fecha m&aacute;xima de tr&aacute;mite:</b></td><td>".$parametros["fecha_maxima"]."</td></tr>";
                    $mail_body .= "<tr><td><b>Comentario:</b></td><td>".$parametros["comentario"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Reabierta por:</b></td><td>".$rem["abr_titulo"] . " " . $rem["nombre"] .
                                  "<br>" . $rem["cargo"] . "<br><a href='mailto:" . $rem["email"]. "'>" . $rem["email"]. "</a></td></tr>";
                    $mail_body .= "<tr><td><b>No. de documento:</b></td><td>".$radicado["radi_nume_text"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Asunto del documento:</b></td><td>".$radicado["radi_asunto"]."</td></tr></table>";
                    $mail_body .= "<br><br>Por favor revise el documento en el sistema &quot;<a href='$nombre_servidor' target='_blank'>$nombre_servidor</a>&quot;";
                    break;
                case '55': // Editar Tarea
                    $rs = $this->db->conn->Execute("select comentario from tarea_hist_eventos where tarea_hist_codi in (".$parametros["comentario_inicio"].")");
                    $mail_body .= "Se modific&oacute; una tarea asignada a usted.";
                    $mail_body .= "<br><br>Informaci&oacute;n de la tarea:<br><br>";
                    $mail_body .= "<table border='0' width='100%'><tr><td width='30%'><b>Fecha:</b></td><td width='70%'>".date("Y-m-d H:i:s")."</td></tr>";
                    $mail_body .= "<tr><td><b>Tarea asignada:</b></td><td>".$rs->fields["COMENTARIO"]."</td></tr>";
                    $mail_body .= "<tr><td><b>Fecha m&aacute;xima de tr&aacute;mite:</b></td><td>".$parametros["fecha_maxima"]."</td></tr>";
                    $mail_body .= "<tr><td><b>Comentario:</b></td><td>".$parametros["comentario"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Modificada por:</b></td><td>".$rem["abr_titulo"] . " " . $rem["nombre"] .
                                  "<br>" . $rem["cargo"] . "<br><a href='mailto:" . $rem["email"]. "'>" . $rem["email"]. "</a></td></tr>";
                    $mail_body .= "<tr><td><b>No. de documento:</b></td><td>".$radicado["radi_nume_text"]."</td></tr>";
                    $mail_body .= "<tr><td valign='top'><b>Asunto del documento:</b></td><td>".$radicado["radi_asunto"]."</td></tr></table>";
                    $mail_body .= "<br><br>Por favor revise el documento en el sistema &quot;<a href='$nombre_servidor' target='_blank'>$nombre_servidor</a>&quot;";
                    break;
                default:
                    return;
                    break;
            }
            $mail_body .= "<br><br>Saludos cordiales,<br><br>Soporte Quipux.";
            $mail_body .= "<br><br><b>Nota: </b>Este mensaje fue enviado autom&aacute;ticamente por el sistema, por favor no lo responda.";
            $mail_body .= "<br>Si tiene alguna inquietud respecto a este mensaje, comun&iacute;quese con <a href='mailto:$cuenta_mail_soporte'>$cuenta_mail_soporte</a>";
            $mail_body .= "</body></html>";

            $tmp = explode(",", $dest["email"]);
            foreach ($tmp as $destinatario) {
                $email = trim($destinatario); //recipient
                $subject = "Quipux: $nombre_accion $asunto"; //asunto
//echo "$subject<br>$mail_body<hr>";
                if (function_exists('quipux_enviar_correo')) {
                    quipux_enviar_correo($email, $subject, $mail_body, $dest["nombre"], $cuenta_mail_envio);
                } else {
                    $header  = 'MIME-Version: 1.0' . "\r\n";
                    $header .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
                    //$header .= "To: ".$dest["titulo"] . " " . $dest["nombre"] . " <" . $destinatario . ">" . "\r\n";
                    $header .= "From: Quipux <$cuenta_mail_envio>" . "\r\n";

                    ini_set('sendmail_from', "$cuenta_mail_envio");
                    mail($email, $subject, $mail_body, $header);
                }
            }
//            echo "<br/><span><font color='Navy'><b>El destinatario ha sido notificado a su cuenta de correo electr&oacute;nico.</b></font></span><br/>";
        }
//        else
//            echo "<br/><span><font color='Navy'><b>El sr(a). ".$dest["nombre"]." no fue notificado, no posee una cuenta de correo electr&oacute;nico. v&aacute;lida</b></font></span><br/>";
    }

    /**
    * Metodo que sirve para validar el estado del radicado o documento.
    *
    * @param int $numero de radicado (primary key).
    * @return arreglo.
    */
    function validarEstado($nume_rad){
	$isql = "select esta_codi, radi_usua_actu, radi_nume_temp from radicado where radi_nume_radi=".$nume_rad;
	$rs = $this->db->conn->query($isql);
	return $rs;
    }
}
?>
