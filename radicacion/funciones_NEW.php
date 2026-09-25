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
require_once(dirname(__DIR__)."/tx/tx_actualiza_opcion_imp.php");
include_once(dirname(__DIR__).'/obtenerdatos.php');
include_once(dirname(__DIR__)."/include/tx/Radicacion.php");
include_once(dirname(__DIR__)."/include/tx/Tx.php");
include_once("funciones_copiar_documentos.php");


include_once(dirname(__DIR__)."/funciones.php"); 

// Se incluyo por register globals
$clean = array();

$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
$nurad = $_POST['nurad'] ?? $_GET['nurad'] ?? '';
$documento_us1 = $_POST['documento_us1'] ?? $_GET['documento_us1'] ?? '';
$documento_us2 = $_POST['documento_us2'] ?? $_GET['documento_us2'] ?? '';
$concopiaa = $_POST['concopiaa'] ?? $_GET['concopiaa'] ?? '';
$radi_lista_dest = $_POST['radi_lista_dest'] ?? $_GET['radi_lista_dest'] ?? '';
$fecha_doc = $_POST['fecha_doc'] ?? $_GET['fecha_doc'] ?? '';
$radi_padre = $_POST['radi_padre'] ?? $_GET['radi_padre'] ?? '';
$raditipo = trim((string)($_POST['raditipo'] ?? $_GET['raditipo'] ?? ''));
// El formulario de NEW.php siempre envia raditipo (hidden para ciudadano y para
// documentos de entrada, combo para el resto). Que llegue vacio o en 0 significa que
// el <select> se genero sin value (ver ConnectionHandler::_loadADOdb): sustituirlo
// por '1' hacia que TODO documento se grabara como tipo 1 sin que nadie lo notara.
// Es preferible abortar que registrar un tipo que el usuario no eligio.
if ($raditipo === '' || 0+$raditipo <= 0) {
    error_log("funciones_NEW: raditipo invalido (recibido: '$raditipo') - accion=$accion nurad=$nurad");
    include_once(dirname(__DIR__).'/funciones_interfaz.php');
    die(html_error("No se recibi&oacute; el tipo de documento.<br>Por favor recargue el formulario e intente nuevamente."));
}
$ent = $_POST['ent'] ?? $_GET['ent'] ?? '';
$textrad = $_POST['textrad'] ?? $_GET['textrad'] ?? '';
$radi_padre_copia = $_POST['radi_padre_copia'] ?? $_GET['radi_padre_copia'] ?? '';
$radi_lista_nombre = $_POST['radi_lista_nombre'] ?? $_GET['radi_lista_nombre'] ?? '';
$krd = $_SESSION['krd'] ?? $_GET['krd'] ?? '';
$opc_grab = $_POST['opc_grab'] ?? $_GET['opc_grab'] ?? '';
$carpeta = $_POST['carpeta'] ?? $_GET['carpeta'] ?? '';


    function validar_html($html)
    {
        $html = preg_replace(':<input (.*?)type=["\']?(hidden|submit|button|image|reset|file)["\']?.*?>:i', '', $html);
        $html = preg_replace(':<style.*?>.*?</style>:is', '', $html);
        $html = preg_replace(':<img.*?/>:is', '', $html);
        $html = preg_replace(':<!--.*?-->:is', '', $html);
        $html = preg_replace(':<p .*?>:is', '', $html); //aumentado por SC para evitar que se dañen las viñetas al pegar desde OOo
        $html = preg_replace(':<col .*?>:is', '', $html); //aumentado por SC para evitar que se dañen las tablas al pegar desde OOo
        $html = str_replace("</p>", "<br />", $html);
        $origen = array("á","é","í","ó","ú","ñ","Á","É","Í","Ó","Ú","Ñ");
        $destino = array("&aacute;","&eacute;","&iacute;","&oacute;","&uacute;","&ntilde;","&Aacute;","&Eacute;","&Iacute;","&Oacute;","&Uacute;","&Ntilde;");
        $html = str_replace($origen, $destino, $html);
        return $html;
    }

    // Esta linea se incluyo por seguridades, CSRF
    if (isset($_SESSION['token']) && $_POST['token'] == $_SESSION['token']) {
        $hist = new Historico($db);
        $tx = new Tx($db);

        $usr = ObtenerDatosUsuario($_SESSION["usua_codi"],$db);

        $institucion=$usr['institucion'];

        if ($accion == "Editar") {
            $radicado = ObtenerDatosRadicado($nurad,$db);
            if ($radicado["estado"] != 1 or $_SESSION["usua_codi"] != $radicado["usua_actu"]) {
                include_once(__DIR__.'/funciones_interfaz.php');
                die (html_error("Usted no tiene los permisos suficientes para modificar este documento."));
            }
        }
        //if ($radi_lista_dest==0)
            $radi_lista_dest = $_POST['radi_lista_dest'];
        //echo $radi_lista_dest;
        $rad = new Radicacion($db);
//        if (trim($_POST['referencia'])=='')//si borra
//            $rad->radiCuentai = trim(limpiar_sql($_POST['referencia_hidd']));
//        else
        if (ltrim($documento_us1.$documento_us2.$concopiaa," -1234567890") != "")
                die("Error: Usted est&aacute; ingresando c&oacute;digo no v&aacute;lido.");

        $rad->radiCuentai = trim(limpiar_sql($_POST['referencia'] ?? ''));
        
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $fecha_doc)) {
            $fecha_gen_doc = substr($fecha_doc, 0, 10); // Already YYYY-MM-DD
        } elseif ($fecha_doc != '') {
            $fecha_gen_doc = substr($fecha_doc, 6, 4)."-".substr($fecha_doc, 3, 2)."-".substr($fecha_doc, 0, 2);
        } else {
            $fecha_gen_doc = date('Y-m-d');
        }
        
        $rad->radiFechOfic =  $fecha_gen_doc;      
        $radi_padre = (!$radi_padre) ? 'null' : $radi_padre;        
        $rad->radiNumeDeri = trim($radi_padre);
        $rad->radiUsuaRadi = $usr["usua_codi"];
        $rad->radiUsuaAnte = "null";
        $rad->radiDescAnex = trim(limpiar_sql($_POST['desc_anex']));  //limpiar_sql($desc_anex)
        $rad->radiAsunto = trim(limpiar_sql($_POST['asunto']));
        $rad->radiAsunto = str_ireplace(array("&quot;","&amp;"), array('"',"&"), $rad->radiAsunto);
        $rad->radiResumen = trim(limpiar_sql($_POST['notas'])); //Para guardar las notas heredadas de una respuesta
        $rad->textTexto = validar_html(trim(str_replace('\"', '"', limpiar_sql($_POST['raditexto'], 0))));
        $rad->radiUsuaDest=$documento_us1;
        $rad->radiUsuaRem=$documento_us2;
        $rad->radiCCA=$concopiaa;
        $rad->radiTipo = (int)$raditipo;
        $rad->radiUsuaActu = $usr["usua_codi"];
        $rad->radiInstActu = $usr["inst_codi"];
        $rad->radiEstado="1";
        $rad->usar_plantilla = (isset($_POST["chk_plantilla"])) ? "1" : "0";
        $rad->ajust_texto = (isset($_POST["cmb_texto"])) ? trim(limpiar_sql($_POST['cmb_texto'])) : "100"; //cambiar tamaño del texto
        $rad->radi_tipo_impresion = trim($_POST["radi_tipo_impresion"] ?? '');     // Tipo de impresion
        $rad->cod_codi = (int)trim($_POST["cod_codi"] ?? '');     // Tipo de codificacion
        $rad->cat_codi = (int)trim($_POST["cat_codi"] ?? '');     // Categoría
        if (trim((string)($_POST["radi_tipo_impresion"] ?? ''))=="999")//limpiamos la lista si selecciona la opcion generar copia para cada uno
            $rad->radi_lista_dest = '';
        else
            $rad->radi_lista_dest = $radi_lista_dest;
        $rad->ocultar_recorrido = (isset($_POST["chk_ocultar_recorrido"])) ? "1" : "0";
        $rad->usua_redirigido = (int)trim($_POST["txt_usua_redirigido"] ?? '');     // Categoría


        if ($ent == 1) { //Si es documento de salida
            $rad->flagRadiTexto = "0";
            $td = 0;
        } else { //Si es documento de entrada
            $rad->flagRadiTexto = "1";
            $td = 2;
        }

        $comparar_texto = "";
///////////////////////////	GUARDAR O MODIFICAR EL DOCUMENTO	//////////////////////
        if ($accion == "Nuevo" || $accion == "Responder" || $accion == "Copiar"  || $accion == "ResponderTodos" ) {
            $noRad = $rad->newRadicado($td, $usr["depe_codi"], $textrad);
            $mensaje = "Se ha registrado el documento No. $textrad";
            $codTx = 2;
            $mens_hist = "Documento Temporal No. $textrad";
            $nurad = $noRad;
            if ($accion == "Responder") { // Registra la respuesta si el documento fue asignado como tarea al usuario
                $tx->registrarDocumentoRespuestaTareas($radi_padre, $nurad, $mens_hist);
            }

        } else {
            $txt_old = 0;
            $txt_new = 0;
            $rs = $db->conn->Execute("select radi_texto from radicado where radi_nume_radi=$nurad");
            if (!$rs->EOF)
                $txt_old = $rs->fields["RADI_TEXTO"];
            $resultado = $rad->updateRadicado($nurad);
            $rs = $db->conn->Execute("select radi_texto from radicado where radi_nume_radi=$nurad");
            if (!$rs->EOF)
                $txt_new = $rs->fields["RADI_TEXTO"];
            $mensaje = "El documento ha sido modificado correctamente";
            $noRad = ($resultado) ? $nurad : $noRad;
            $codTx = 11;
            $mens_hist = "";
            $comparar_texto = "$txt_old, $txt_new";
        }

        if (!$noRad) {
            $mensaje = "Ha ocurrido un Problema. Verfique los datos e intente de nuevo";
        }

        if ($noRad == "-1") {
            $mensaje = "Error no se generó un Número de Secuencia o se guardó el documento";
            $noRad = "";
        }

        if ($noRad) {
//            if ($accion=="Editar") {
                $hist->insertarHistorico($noRad, $usr["usua_codi"], $usr["usua_codi"], $mens_hist, $codTx, $comparar_texto);
//            }

            if ($accion=="Responder" and trim($radi_padre) != "") {
                $padre = ObtenerCampoRadicado("radi_nume_temp", $radi_padre, $db);
                $hist->insertarHistorico($radi_padre, $_SESSION["usua_codi"], $_SESSION["usua_codi"], "", 12, $noRad);
                $hist->insertarHistorico($padre, $_SESSION["usua_codi"], $_SESSION["usua_codi"], "", 12, $noRad);
            }

            if ($accion=="Copiar" and trim($radi_padre_copia) != "") {               
                $hist->insertarHistorico($radi_padre_copia, $_SESSION["usua_codi"], $_SESSION["usua_codi"], "", 82, $noRad);
            }

            $tmp = "";

        ///////////////////////  COPIAR ANEXOS DEL DOCUMENTO PADRE  ///////////////////////

        if (isset($_POST["checkValue"])) {
            $i = 1;
            foreach ($_POST["checkValue"] as $key => $val) {
                // Support old keyed format: key = anex_codigo, val = 'chk_copiar_anexos'
                // and new value-array format: val = anex_codigo
                if ($val === "chk_copiar_anexos") {
                    $anex_codi = $key;
                } else {
                    $anex_codi = $val;
                }
                if (trim($anex_codi) == "") continue;
                $anexos = copiar_registro_en_arreglo($db, "anexos", "anex_codigo='".trim($anex_codi)."'");
                $anexos["ANEX_RADI_NUME"] = $noRad;
                $anexos["ANEX_CODIGO"] = $db->conn->qstr($noRad . "_" . str_pad($i, 5, "0", STR_PAD_LEFT));
                $anexos["ANEX_NUMERO"] = $i;
                $anexos["ANEX_USUA_CODI"] = $_SESSION["usua_codi"];
                $anexos["ANEX_FECHA"] = $db->conn->sysTimeStamp;
                $insertSQL = $db->conn->Replace("ANEXOS", $anexos, "", false, false, true, false);
                $anex_nombre = str_replace("'", "", str_replace("E'", "", $anexos["ANEX_NOMBRE"]));
                $hist->insertarHistorico($noRad, $_SESSION["usua_codi"], $_SESSION["usua_codi"], $anex_nombre, 66, "");
                $i++;
            }
        }



        //////////////////////   GRABAR ESTILOS DE IMPRESION    /////////////////////////

            $numeDest = 1; //Si es un solo destinatario
            //Determinar si el número de destinatarios es mayor a 1
            if(strpos($documento_us1, "--") !== false)
                $numeDest = 0;

            $estilos= array();
            $estilos["RADI_NUME_RADI"] = $nurad;

            //OCULTAR NUMERO DE DOCUMENTO
            $estilos["OPC_IMP_OCULTAR_NUME_RADI"] = '0';
            if(isset ($_POST["chk_documento"]) and $_POST["chk_documento"]!="")
                $estilos["OPC_IMP_OCULTAR_NUME_RADI"] = $_POST["chk_documento"];

            $estilos["OPC_IMP_MOSTRAR_PARA"] = '0';
            
            $estilos["OPC_IMP_TITULO_NATURAL"] = "null";
            $estilos["OPC_IMP_CARGO_CABECERA"] = "null";
            $estilos["OPC_IMP_FIRMANTES"] = "null";
            $estilos["OPC_IMP_EXT_INSTITUCION"] = "null";
            if($numeDest==1)
            {
                if(isset ($_POST["txt_opcFirmantes"]) and $_POST["txt_opcFirmantes"]!="")
                $estilos["OPC_IMP_FIRMANTES"] = $db->conn->qstr(limpiar_sql(trim($_POST["txt_opcFirmantes"])));
                if(isset ($_POST['radio_cabecera']) and $_POST['radio_cabecera']!="") //MOSTRAR EN CABECERA O EN PIE DE PAGINA DATOS DEL DESTINATARIO
                    $estilos["OPC_IMP_MOSTRAR_PARA"] = $_POST['radio_cabecera'];
                else
                    $estilos["OPC_IMP_MOSTRAR_PARA"]=1;
                if(isset ($_POST['txt_opcTitulo']) and trim($_POST['txt_opcTitulo'])!='Sin título') // Titulo
                    $estilos["OPC_IMP_TITULO_NATURAL"] = $db->conn->qstr(limpiar_sql(trim($_POST['txt_opcTitulo'])));
                if(isset ($_POST['txt_opcCargo']) and trim($_POST['txt_opcCargo'])!='Ninguno')
                    $estilos["OPC_IMP_CARGO_CABECERA"] = $db->conn->qstr(limpiar_sql(trim($_POST['txt_opcCargo'])));
                if(isset ($_POST['txt_ext_institucion']) and trim($_POST['txt_ext_institucion'])!='')
                    $estilos["OPC_IMP_EXT_INSTITUCION"] = $db->conn->qstr(limpiar_sql(trim($_POST['txt_ext_institucion'])));
            }

            //SALUDOS
            $estilos["OPC_IMP_DESTINO_DESTINATARIO"] = "null";
            if (isset ($_POST["txt_opcSaludo"]) and trim($_POST["txt_opcSaludo"])!='Sin dirección')
                $estilos["OPC_IMP_DESTINO_DESTINATARIO"] = $db->conn->qstr(limpiar_sql(trim($_POST["txt_opcSaludo"])));

            //JUSTIFICAR FIRMA
            $estilos["OPC_IMP_JUSTIFICAR_FIRMA"] = '0';
            if(isset ($_POST["radio_justifi"]) and $_POST["radio_justifi"]!="")
                $estilos["OPC_IMP_JUSTIFICAR_FIRMA"] = $_POST["radio_justifi"];

            //DESPEDIDA
            $estilos["OPC_IMP_DESPEDIDA"] = $db->conn->qstr(limpiar_sql(trim((string)($_POST["txt_opcDespedida"] ?? ''))));
            //FRASE DESPEDIDA
            $estilos["OPC_IMP_FRASE_REMITENTE"] = "null";
            if(isset ($_POST["txt_opcFrasedespedida"]) and trim($_POST["txt_opcFrasedespedida"]) != trim("Sin frase de despedida"))
                $estilos["OPC_IMP_FRASE_REMITENTE"] = $db->conn->qstr(limpiar_sql(trim($_POST["txt_opcFrasedespedida"])));

            //MOSTRAR FRASE DE DESPEDIDA
            $estilos["OPC_IMP_OCULTAR_FRASE_REM"] = '0';
            if(isset ($_POST["chk_opcFraseDespedida"]) and $_POST["chk_opcFraseDespedida"]!="")
                $estilos["OPC_IMP_OCULTAR_FRASE_REM"] = $_POST["chk_opcFraseDespedida"];

            //SI ES NOTA
            $estilos["OPC_IMP_TIPO_NOTA"] = '0';
            if(isset ($_POST["radio_nota"]) and $_POST["radio_nota"]!="")
                $estilos["OPC_IMP_TIPO_NOTA"] = $_POST["radio_nota"];

            //JUSTIFICAR FECHA
            $estilos["OPC_IMP_JUSTIFICAR_FECHA"] = '2';
            if(isset ($_POST["radio_nota"]) and $_POST["radio_nota"]==3){
                if($_POST["radio_just_fecha"]!="")
                    $estilos["OPC_IMP_JUSTIFICAR_FECHA"] = $_POST["radio_just_fecha"];
            }

             //OCULTAR ASUNTO
            $estilos["OPC_IMP_OCULTAR_ASUNTO"] = '0';
            if(isset ($_POST["chk_asunto"]) and $_POST["chk_asunto"]!="")
                $estilos["OPC_IMP_OCULTAR_ASUNTO"] = $_POST["chk_asunto"];
            if ($_POST['hidden_radi_actual']==6)
                $estilos["OPC_IMP_OCULTAR_ASUNTO"] = '0';
            
            //Tipo de letra italica
            $estilos["OPC_IMP_LETRA_ITALICA"] = '0';
            
            if(isset ($_POST["chk_italica"]) and $_POST["chk_italica"]!="")
                $estilos["OPC_IMP_LETRA_ITALICA"] = '1';
             if ($_POST['hidden_radi_actual']==6)
                $estilos["OPC_IMP_LETRA_ITALICA"] = '0';
            
            //OCULTAR ATENTAMENTE
            $estilos["OPC_IMP_OCULTAR_ATENTAMENTE"] = '0';
            
            if(isset ($_POST["chk_OcultaAtentamente"]) and $_POST["chk_OcultaAtentamente"]!="")
                $estilos["OPC_IMP_OCULTAR_ATENTAMENTE"] = '1';
            
            //OCULTAR REFERENCIA
            $estilos["OPC_IMP_OCULTAR_REFERENCIA"] = '0';
            if(isset ($_POST["chk_referencia"]) and $_POST["chk_referencia"]!="")
                $estilos["OPC_IMP_OCULTAR_REFERENCIA"] = '1';
            //OCULTAR ANEXOS
            $estilos["OPC_IMP_OCULTAR_ANEXO"] = '0';
            if(isset ($_POST["chk_anexo"]) and $_POST["chk_anexo"]!="")
                $estilos["OPC_IMP_OCULTAR_ANEXO"] = '1';
            //OCULTAR SUMILLAS
             $estilos["OPC_IMP_OCULTAR_SUMILLAS"] = '0';
            if(isset ($_POST["chk_sumillas"]) and $_POST["chk_sumillas"]!="")
                $estilos["OPC_IMP_OCULTAR_SUMILLAS"] = '1';
            //ACUERDOS - DADO EN
             $estilos["OPC_IMP_CIUDAD_DADO_EN"] = 'null';
             if (isset ($_POST["txt_opc_ciudad_dado_en"]) and trim($_POST["txt_opc_ciudad_dado_en"])!='') {
                 $estilos["OPC_IMP_CIUDAD_DADO_EN"] = $db->conn->qstr(limpiar_sql(trim($_POST["txt_opc_ciudad_dado_en"])));
             } elseif (isset ($_POST["txt_ciudad_dado_en"])) {
                 $estilos["OPC_IMP_CIUDAD_DADO_EN"] = $db->conn->qstr(limpiar_sql(trim($_POST["txt_ciudad_dado_en"])));
             }
             
            //Eliminar las opciones de impresion
            if ($_POST['hidden_tipo_anterior']!=$_POST['hidden_radi_actual']){//solo si son diferentes
             if ($nurad!=''){
                $sql_exit = "select * from opciones_impresion where radi_nume_radi=$nurad";
                $rs_ex=$db->conn->Execute($sql_exit);
                if(!$rs_ex->EOF){                        
                $sqlDel = "delete from opciones_impresion where radi_nume_radi = $nurad";
                $db->conn->Execute($sqlDel);                
                }
             }
            }
            $sql_oi = "select * from opciones_impresion where radi_nume_radi=$nurad";
            $rs_old = $db->conn->Execute($sql_oi);
            if ($_POST['bandera_cambiarop']==1)//si hace clic en la ficha impresion
            $okOpc = $db->conn->Replace("OPCIONES_IMPRESION", $estilos, "RADI_NUME_RADI", false,false,true,false);
            $rs_new = $db->conn->Execute($sql_oi);
            grabar_historico_opciones_impresion($rs_old, $rs_new, $db);
     
        } //fin if noRad
    } //fin if token
    //Envìa notificaciòn a los administradores de la instituciòn al que correspnde el remitente

  if (($_POST['opc_grab'] ?? '') == 2){//si puso aceptar 
    if (  (trim((string)($_POST['txt_Cargo'] ?? ''))!=trim((string)($_POST['txt_opcCargo'] ?? '')) and $rs_old->fields["OPC_IMP_CARGO_CABECERA"]!=$rs_new->fields["OPC_IMP_CARGO_CABECERA"])
       || (trim((string)($_POST['txt_titulo'] ?? ''))!=trim((string)($_POST['txt_opcTitulo'] ?? '')) and $rs_old->fields["OPC_IMP_TITULO_NATURAL"]!=$rs_new->fields["OPC_IMP_TITULO_NATURAL"])
       || (trim((string)($_POST['txt_opcFirmantes'] ?? ''))!='' and $rs_old->fields["OPC_IMP_FIRMANTES"]!=$rs_new->fields["OPC_IMP_FIRMANTES"])) {
       
        $remitente=$usr["nombre"];
        $remitente=$_SESSION['usua_nomb'];
        $cargo_cabecera=trim($_POST['txt_opcCargo']);
        $ciDest=trim($_POST["ci"]);
        $InstCodiDest=trim($_POST["inst_codidest"]);
        $titulo_nuevo=trim($_POST['txt_opcTitulo']);
        //OBTENER DATOS DEL DESTINARIO QUE HA SIDO MODIFICADO
        $sqlNombre="select (COALESCE(usua_nomb, ''::character varying)::text || ' '::text) || COALESCE(usua_apellido, ''::character varying)::text AS usua_nombre
        ,usua_cargo_cabecera,usua_titulo  from usuarios where usua_cedula='$ciDest'";
        
        $rs=$db->query($sqlNombre);
        $NomDestino= trim($rs->fields["USUA_NOMBRE"]);
        $cargoCAbecAnterior=trim($rs->fields["USUA_CARGO_CABECERA"]);
        $titulo_anterior=trim($rs->fields["USUA_TITULO"]);
        //ARMAR EL MAIL
        if ($_POST['inst_codidest']==0)
            $asuntoE="Quipux: Modificacion Ciudadano";
                else
            $asuntoE="Quipux: Modificacion Funcionario";
        $mail = "<!DOCTYPE html><title>Notificaci&oacute;n Quipux</title>";
        $mail .= "<body><center><h1>QUIPUX</h1><br /><h2>Sistema de Gesti&oacute;n Documental</h2><br /><br /></center>";
        $mail .= "Estimado Administrador.<br/><br/>";
        $mail .= "Se sugiere tomar en cuenta la modificaci&oacute;n realizada  en opciones de impresión en el documento que se está elaborando en $institucion: <br><br>";
        $mail .= " Documento número: <b>".$textrad."</b><br><br>";
        $mail .= " Elaborado por : <b> $remitente de $institucion</b><br><br>";
        if ($_POST['inst_codidest']==0)
            $mail .= " Se cambia datos del Ciudadano  &quot;<b>". $_POST['txt_nombre_ciu'] ."</b>&quot; con C.I. &quot;<b>".$ciDest."</b> en: <br><br>";
        else
            $mail .= " Se cambia datos del funcionario  &quot;<b>". $NomDestino ."</b>&quot; con C.I. &quot;<b>".$ciDest."</b> en: <br><br>";
        if (trim($_POST['txt_Cargo'])!=trim($_POST['txt_opcCargo']))        
        $mail .= " Puesto Cabecera de &quot;<b>". $cargoCAbecAnterior."</b>&quot;  a: &quot;<b>".$cargo_cabecera."</b>&quot;,</br>";        
        
        if (trim($_POST['txt_titulo'])!=trim($_POST['txt_opcTitulo'])){  
          if (trim($_POST['txt_titulo'])=='Sin título' || trim($_POST['txt_titulo'])=='')
              $mail .= " Título: ".$titulo_nuevo."</b>,<br>";
            else            
                $mail .= " Título de &quot;<b>".$_POST['txt_titulo']."</b>&quot;  a: &quot;<b>".$_POST['txt_opcTitulo']."</b>&quot;,<br>";                          
        }
        if (trim($_POST['txt_ext_institucion'])!='')
        $mail .= " Institución (se añadió) &quot;<b>".trim($_POST['txt_ext_institucion'])."</b>&quot;,<br>";
         if (trim($_POST['txt_opcFirmantes'])!='')
        $mail .= " Apellido (se añadió) &quot;<b>".trim($_POST['txt_opcFirmantes'])."</b>&quot;,<br>";        
        $mail .= "<br><br>Nota: Adicionalmente se solicita verificar las faltas ortogr&aacute;ficas de cada uno de los campos ingresados para los funcionarios de su Instituci&oacute;n.<br />";
        $mail .= "<br /><br />Saludos cordiales,<br /><br />";
        $mail .= "Soporte Quipux<br /><br />";
        $mail .= "<br><br /><br /><b>Nota: </b>Este mensaje fue enviado autom&aacute;ticamente por el sistema, por favor no lo responda.";
        $mail .= "<br>Servidor: ".$nombre_servidor;
        $mail .= "</body></html>";
        //ENVIO DE E-MAIL AL ADMINISTRADOR INSTITUCIONAL
       if ($_POST['inst_codidest']!=0){//solo si el destinatario
           //es funcionario entra a realizar esto.
            $sql="select usua_nomb, usua_apellido, usua_email, usua_codi, inst_codi
                  from usuarios
                  where usua_codi=0 or
                    (usua_esta=1 and inst_codi=$InstCodiDest and usua_codi in (select usua_codi from permiso_usuario where id_permiso=12))";

            $rs=$db->query($sql);
            while (!$rs->EOF) {
                $destinatario=trim($rs->fields["USUA_NOMB"]) ." ".trim($rs->fields["USUA_APELLIDO"]);
                $usr_email=trim($rs->fields["USUA_EMAIL"]);
                $usr_nombre=trim($rs->fields["USUA_NOMB"]) ." ".trim($rs->fields["USUA_APELLIDO"]);
                enviarMail($mail,$asuntoE, $usr_email, $usr_nombre, __DIR__);
                $rs->MoveNext();
            }
        }  
    }           
}

    if (trim((string)($_POST["radi_tipo_impresion"] ?? ''))=="999") {        
        copiar_documentos_temporales($db, $noRad, $rad, $hist);
    }    
    //REGISTROS OPCIONES DE IMPRESION
    
//    $acciones_datos=$_POST["hidden_acciones_datos"];//codigo accion, valor nuevo, valor anterior
//    $num_rad_accion = $_POST["num_rad"];
//    if ($num_rad_accion!=''){
//        actualizar_historico($db,$num_rad_accion,$acciones_datos);
//    }
    $txt_refeResponder=$_POST['txt_refeResponder'];
    ?>

<!DOCTYPE html>

<script type="text/javascript">
    function vista_previa() {
        
        windowprops = "top=100,left=100,location=no,status=no, menubar=no,scrollbars=yes, resizable=yes,width=500,height=300";
        url = <?="'../documento_online.php?verrad=$nurad&textrad=$textrad&menu_ver=3&irVerRad=1&ver_tipo=E'"?>;
        
        $ventana = "";
        var ventanaNombre = "QuipuxPreview";
        // window.open(url , "Vista_Previa_<?=$noRad?>", windowprops);    
        window.open(url , ventanaNombre, windowprops);     
        return;       
    }

    function ImprimirComprobante(val) {
        if (val == 0) {
            if (confirm('¿Desea imprimir el comprobante de registro?')) {
                URL = '<?php echo "plantillas/CodigoBarras.php?krd=$krd&nuevo=si&verrad=$noRad&tipo_comp=0"; ?>';
                window.open(URL);
            }
        } else {
            windowprops = "top=0,left=0,location=no,status=no, menubar=no,scrollbars=yes, resizable=yes,width=500,height=300";
            URL = 'comprobante.php?krd=<?php echo "$krd&verrad=$noRad&textrad=$textrad"; ?>';
            // window.open(URL, "Comprobante <?php echo $textrad; ?>", windowprops);
            window.open(URL, "QuipuxComprobante", windowprops);
        }
        return;
    }

</script>

<?php

    if ($opc_grab == 2) {
////////	Si pongo 0 en esta opción, unicamente me pregunta si deseo imprimir los comprobantes e imprime ambos de una vez caso contrario si pongo 1 me muestra una ventana en la q me permite imprimir por separado el codigo de barras y el comprobante.
        if ($ent == 2) {
            echo "<script language='javascript'>ImprimirComprobante(1); </script>";
        }
        $var_envio = "../verradicado.php?verrad=$nurad&textrad=$textrad&menu_ver=3&irVerRad=1&tipo_ventana=popup&refeResponder=$txt_refeResponder&carpeta=$carpeta";
    } else {
        if ($opc_grab!=3)
         echo "<script language='javascript'>vista_previa();</script>";
        
        $var_envio = "NEW.php?ent=$ent&nurad=$nurad&textrad=$textrad&radi_lista_dest=$radi_lista_dest&radi_lista_nombre=$radi_lista_nombre&accion=Editar&refeResponder=$txt_refeResponder";//&mensaje=$mensaje";
    }  
    echo "<script language='javascript'>window.location='$var_envio'; </script>";
?>

</html>   