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

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
require_once(dirname(__DIR__)."/funciones.php");
include_once(dirname(__DIR__)."/Administracion/ciudadanos/util_ciudadano.php");

$ciud = new Ciudadano($db);
p_register_globals();

$codTx = $codTx ?? 0;

if ($codTx == 30) { //Tareas
    include_once(dirname(__DIR__)."/tareas/tareas_form_tx.php");
    die ("");
}

$ver="0";
$firma=0;
$docExterno=0;

//Para validar que no se reasigne a sí mismo
if(trim($carpeta) == "")
    $carpeta = -1;

// Se incluyo por register globals
//$fechaAgenda = $_POST['fechaAgenda'];
//$depsel8 = $_POST['depsel8'];
//$depsel = $_POST['depsel'];

$mensaje_error = "";
//$db = new ConnectionHandler(__DIR__,"busqueda");
include_once(dirname(__DIR__).'/include/sumillas/Sumillas.php'); // Árbol de sumillas por motivo
include_once(dirname(__DIR__).'/obtenerdatos.php');
include_once(dirname(__DIR__).'/seguridad_documentos.php'); // Valida estados de los documentos y otras reglas dependiendo de la transacción realizada
include_once(dirname(__DIR__).'/include/periodos/Jerarquia.php'); // Reglas de reasignación de periodo jerárquico
$mensaje_error = "";
$mensaje_error = "";
$whereFiltro= "0";
$contAcc = 0;
$encabezado = "";
$linkPagina = "";
$codTx = $codTx ?? 0;
$carpeta = $carpeta ?? 0;
$cantidad_tareas = $cantidad_tareas ?? 0;
$radiNumeAsociados = $radiNumeAsociados ?? [];
if (!isset($_SESSION["usua_codi_jefe"])) $_SESSION["usua_codi_jefe"] = 0;
if (!isset($_SESSION["cargo_tipo"])) $_SESSION["cargo_tipo"] = 0;



/**
* FILTRO DE DATOS
*/
if(isset($_POST['checkValue'])) {               //Si se escogieron radicados de la lista
    // Use POST values not keys to avoid numeric overflow issues
    foreach ($_POST['checkValue'] as $chk_key => $chk) {
        $radi_nume = is_numeric(trim($chk)) ? trim($chk) : trim((string)$chk_key);
        if ($radi_nume!="") {
            $flag = validar_transacciones($codTx, $radi_nume, $db);
            if ($flag == "")
                $whereFiltro .= "," . $radi_nume;
            else
                $mensaje_error .= $flag;
        }
    }
    if ($mensaje_error != "") 
            $mensaje_error = "<br><center><span style='color: red; font-weight: bold;'>Existieron inconvenientes al realizar esta acci&oacute;n con los siguientes documentos:<br><br></span></center>" . $mensaje_error . "<br>";
} else {        //Si no se escogio ningun radicado
        $mensaje_error .= "<br><center><span style='color: red; font-weight: bold;'>No hay documentos seleccionados.</span></center><br>";
}
require_once(dirname(__DIR__).'/tipo_documental/obtener_datos_trd.php');
include_once(dirname(__DIR__).'/funciones_interfaz.php');       
echo "<!DOCTYPE html><html>".html_head();
require_once dirname(__DIR__)."/js/ajax.js";

//echo "---".$_SESSION["existe_radi_path"];
if ($codTx == 70) { //Imprimir sobres
    include_once "accion_imprimir_sobre.php";
    die ("");
}

//Se consulta si existen documentos antecedentes al reasignar el o los documentos de respuesta
if ($codTx == 9) {
    $isql = "select radi_nume_asoc, esta_codi from radicado where radi_nume_radi in ($whereFiltro)";
    $rs = $db->conn->Execute($isql);
    while (!$rs->EOF) {
        $radi_nume_asoc = $rs->fields["RADI_NUME_ASOC"];
        $estado = $rs->fields["ESTA_CODI"];       
        if($radi_nume_asoc != "" and $estado ==1)
        {           
            $flag = validar_transacciones($codTx, $radi_nume_asoc, $db);
            if ($flag == "")
                $radiNumeAsociados[] = $rs->fields["RADI_NUME_ASOC"];
        }
        $rs->MoveNext();
    }
}

//Se consulta si los documentos a Archivar tienen tareas pendientes
$cantidad_tareas = 0;
if ($codTx == 13) {
    $isq_tarea = "select count(tarea_codi) as cant_tareas from tarea where radi_nume_radi in ($whereFiltro) and estado = 1";
    $rs_tarea = $db->conn->Execute($isq_tarea);
    $cantidad_tareas = $rs_tarea->fields["CANT_TAREAS"];     
}
require_once dirname(__DIR__)."/js/ajax.js";
?>
<script type="text/javascript" language="JavaScript" src="../Administracion/ciudadanos/adm_ciudadanos.js"></script>
<script type="text/javascript" language="JavaScript" src="../js/shortcut.js"></script>
<?php echo sumillas_javascript(); ?>
<script type="text/javascript">
    function borrarCaja(){
        document.realizarTx.observa.value="";
        sumillas_limpiar();
        formEnvio_contador_caracteres();
    }

    var marcado = 0,marcadoF="";
    function Obtener_val(formulario){
        marcado=formulario.value
        document.getElementById("opcDoc").value=marcado;
        marcadoF=marcado;
        //alert(marcado);
    }


    function verificar_chk() {
        for(i=0;i<document.realizarTx.elements.length;i++) {
            if(document.realizarTx.elements[i].checked==1 )
                return true;
        }
        return false;
    }

    function verificar_combo(nombre)
    {
        for(i=0;i<document.getElementById(nombre).options.length;i++)
        {
            // Solo se comparaba contra '0'. Un <option value=""> (combo generado sin
            // values) daba "" != "0" == true y pasaba la validacion, con lo que el
            // destinatario vacio llegaba al servidor. Se rechaza tambien la cadena
            // vacia, conservando valores como -1 (slc_lista = "todos").
            var opt = document.getElementById(nombre).options[i];
            if (opt.selected && opt.value !== '' && opt.value != '0')
                return true;
        }
        return false;
    }

    var accion="";

    function guarda_combo_accion(nombre)
    {
        var j=1;
        for(i=0;i<document.getElementById(nombre).options.length;i++)
        {
            if(document.getElementById(nombre).options[i].selected && document.getElementById(nombre).options[i].value!='0')
                accion+= j++ +".-"+document.getElementById(nombre).options[i].text + " ";
        }
        return true;
    }

    function markAll(noRad) {
        if( noRad >=1) {
            for(i=3;i<document.realizarTx.elements.length;i++)
                document.realizarTx.elements[i].checked=1;
        } else {
            for(i=3;i<document.realizarTx.elements.length;i++)
                document.realizarTx.elements[i].checked=0;
        }
        
        document.realizarTx.chk_reasigna_padre.checked=0;
    }

var estado='';
var estadoF='';
var esExterno=true;

var var_ejecutar_okTx = true;
    function regresarTx(){
        try { if (window.opener && !window.opener.closed) { window.close(); return; } } catch(e){}
        var c = '<?=(isset($carpeta) && $carpeta!=="") ? $carpeta : 1?>';
        window.location = '../cuerpo.php?carpeta=' + c + '&adodb_next_page=1';
    }

    function okTx(var1,var4) {
        if (!var_ejecutar_okTx) return;
         // Verificamos que existan documentos seleccionados
        if(!verificar_chk()) {
            alert ('No existen documentos seleccionados.');
            return false;
        }
//alert ("codtx: " + <?=$codTx?> +" documento externo: " + var4 + " variable 1: " + var1); //carmita
//alert ("variable1: "+var1);
        //alert ("variable1: "+var1);
        if('<?=$codTx?>' == '11'  && var1=='0' && var4=='2' ){
            var resultado = confirm("Este documento no tiene imagen asociada, ¿Está seguro de enviar?");
        }else if('<?=$codTx?>' == '3' && var1=='0' && var4=='0' ){
              var resultado = confirm("Este documento no tiene imagen asociada, ¿Está seguro de enviar?");
        }else{
            var resultado=esExterno;
        }

        if(resultado==true){
            //Si es Enviar Físico
            if ('<?=$codTx?>' == '69'){
                // Verificamos que existan usuarios seleccionados
                if(!verificar_combo('usCodSelect')) {
                    alert ('Seleccione el usuario al que enviara el archivo físico');
                    return false;
                }
                //Verifico que hayan ingresado Responsable de Traslado
                if(trim(document.getElementById('nombre').value) =='') {
                    document.getElementById('nombre').value='';
                    alert("Ingrese el responsable del traslado");
                    return false;
                }
                //Armo estado del documento
                if (marcadoF=='B'){
                    estado="Bueno";
                }
                else if (marcadoF=='M' || marcadoF==''){
                    estado="Malo";
                }
                else if (marcadoF=='R'){
                    estado="Regular";
                }
                estadoF="/Estado del archivo enviado físicamente :"+estado;
            }
            // Si es reasignar
            if ('<?=$codTx?>' == '9') {
                    // Verificamos que existan usuarios seleccionados
                    if(!verificar_combo('usCodSelect')) {
                        alert ('Seleccione el usuario al que reasignará el documento.');
                        return false;
                    }                    
     
                    //Se valida que no se reasigne el documento al mismo usuario
                    //Se valida que no se reasigne el documento al mismo usuario
                    if('<?=$carpeta?>' == "14"){ //Bandeja Compartida
                        if(document.getElementById("usCodSelect").value == '<?=$_SESSION["usua_codi_jefe"]?>'){
                           alert ('Esta tratando de reasignar el documento al usuario actual del mismo, por favor seleccione uno diferente.');
                           return false;    
                        }
                    }
//                      else{
//                        if(document.getElementById("usCodSelect").value == $_SESSION["usua_codi"]){
//                            alert ('Esta tratando de reasignar el documento a su propio usuario, por favor seleccione uno diferente.');
//                            return false;  
//                        }
//                    } 
                 
                    //Para tomar valor al reasignar documentos padre
                    document.getElementById("chk_reasigna_padre").value = document.getElementById("chk_reasigna_padre").checked;
                    
                    // Verificamos la fecha de reasignación
                    var now = new Date();
                    var fechaActual = new Date(now.getFullYear(), now.getMonth(), now.getDate());
                    fecha_doc = document.realizarTx.fecha_doc.value;
                    var fecha = new Date(fecha_doc.substring(6,10), fecha_doc.substring(3,5) - 1, fecha_doc.substring(0,2));
                    var tiempoRestante = fecha.getTime() - fechaActual.getTime();
                    var dias = Math.floor(tiempoRestante / (1000 * 60 * 60 * 24));
                    if (dias < 0) {
                        alert("La fecha máxima de trámite debe ser mayor a la fecha actual");
                        return false;
                    }
                  
                }

                if ('<?=$codTx?>' == '8')
                {
                    // Verificamos que existan usuarios seleccionados
                    if(!verificar_combo('usCodSelect') && !verificar_combo('slc_lista')) {
                        alert ('Seleccione el usuario al que desea informar sobre este documento.');
                        return false;
                    }
                }
                
                // Se valida si el documento a Archivar tiene tareas pendientes
                if ('<?=$codTx?>' == '13' && <?=$cantidad_tareas?> > 0)
                {
                    if(!confirm ('El documento seleccionado tiene tareas pendientes. ¿Está seguro que desea archivar?'))
                        return false;
                }
                if (document.getElementById("inputString"))
                if (document.getElementById("inputString").value=='')
                    document.getElementById("txt_check_carpeta").value=0;
                if ('<?=$codTx?>' == '88'){                    
                    txtcodTrd = document.getElementById("txt_check_carpeta").value;                   
                        if(txtcodTrd<=0){
                        alert("Seleccione la Carpeta Virtual de la lista");
                        return false;
                    }
                }
                var_ejecutar_okTx = false;  
                document.realizarTx.observa.value = document.realizarTx.observa.value.substr(0,550)+estadoF;
                document.realizarTx.action = "realizarTx.php";
                document.realizarTx.submit();

                    
        }
    }

        function cambiar_combo_usuarios() {
            var area = '';
            var coma = '';
            for(i=0;i<document.getElementById('depsel').options.length;i++) {
                if (document.getElementById('depsel').options[i].selected) {
                    area += coma +document.getElementById('depsel').options[i].value;
                    coma = ',';
        }
    }
            if (area != '')
                nuevoAjax('mnu_usr', 'GET', 'formEnvio_ajax.php', 'area='+area+'&codTx=<?=$codTx?>' + (jerarquia_copias ? '&jer=1' : ''));
            jerarquia_mostrar_copia();
            return;
   }

        // Periodo jerárquico: regla aplicada y usuarios que recibirán copia (informados)
        // según el destinatario elegido. null si no aplica; se llena junto al combo de reasignar.
        var jerarquia_copias = null;
        function jerarquia_mostrar_copia() {
            var div = document.getElementById('div_jerarquia_copia');
            if (!div || !jerarquia_copias) return;
            var sel = document.getElementById('usCodSelect');
            var d = sel ? jerarquia_copias[sel.value] : null;
            if (!d) { div.innerHTML = ''; return; }
            var txt = 'Regla: ' + d.regla + '.';
            if (d.copias != '') txt += ' <b>Se informar&aacute; en copia a: ' + d.copias + '</b>';
            div.innerHTML = txt;
        }

        function Start(URL,ci) {
            var x = (screen.width - 1100) / 2;
            var y = (screen.height - 540) / 2;
            var nombre ='';
            //if(document.formu1.lista_usr.value!='0')
            //{
                //nombre = document.formu1.lista_usr.options[document.formu1.lista_usr.selectedIndex].text;
                //alert(nombre);
                windowprops = "top=0,left=0,location=no,status=no, menubar=no,scrollbars=yes, resizable=yes,width=1100,height=540";
                //URL = URL + '?lst_codigo=' + document.formu1.lista_usr.value + '&lst_nombre=' + nombre + '&accion=2';
                //URL = URL + '?codigo=ciu_s'+'&buscar_nom=' + document.getElementById('nomCiuFun').value +  '&accion=2';
                URL = URL + '?codigo=ciu_s'+'&buscar_nom='+ci+'&accion=2';
                //alert(URL);
                preview = window.open(URL , "editar_ciudadano", windowprops);
                preview.moveTo(x, y);
                preview.focus();
            //}
            //else
              //  alert("Por favor, seleccione una lista");
        }


function llamarListado(nombreCarpeta, codigoCarpeta){
     location.href= '/cuerpo.php?nomcarpeta='+nombreCarpeta+'&carpeta='+codigoCarpeta+'&adodb_next_page=1';
     document.getElementById('btn_Buscar').focus();
}

var formEnvio_contador_caracteres_TimerId = 0;

function limita(elEvento) {
    var elemento = document.getElementById("observa");
    var maxCaracteres=550;
    if ('<?=$codTx?>'=='69') maxCaracteres=110;
    formEnvio_contador_caracteres_TimerId = setTimeout("formEnvio_contador_caracteres()", 50);
    // Obtener la tecla pulsada
    var evento = elEvento || window.event;
    var codigoCaracter = evento.charCode || evento.keyCode;
    // Permitir utilizar las teclas con flecha horizontal
    if(codigoCaracter >= 37 && codigoCaracter <= 40) return true;
    // Permitir borrar con la tecla Backspace y con la tecla Supr.
    if(codigoCaracter == 8 || codigoCaracter == 46) return true;

    if(elemento.value.length >= maxCaracteres ) return false;

    document.getElementById("spn_numero_caracteres_disponibles").innerHTML = elemento.value.length.toString() + ' de ' + maxCaracteres.toString();
    return true;
}
function formEnvio_contador_caracteres() {
    var elemento = document.getElementById("observa");
    var maxCaracteres=550;
    if ('<?=$codTx?>'=='69') maxCaracteres=110;
    if(elemento.value.length >= maxCaracteres) 
        elemento.value = elemento.value.substr(0, maxCaracteres);
    document.getElementById("spn_numero_caracteres_disponibles").innerHTML = elemento.value.length.toString() + ' de ' + maxCaracteres.toString();
    return;
}


function init() {

    var nomCarpeta = ""; //Nombre de la bandeja que esta en la base de datos
    var codCarpeta = ""; //Codigo de la bandeja que esta en la base de datos (Primary Key)
    shortcut.add("Alt+b", function() {
        nomCarpeta = "En Elaboración";
        codCarpeta = "1";
        llamarListado(nomCarpeta, codCarpeta)
        window.top.window.leftFrame.cambioMenuAsociado(nomCarpeta);
    });
    shortcut.add("Alt+r", function() {
        nomCarpeta = "Recibidos";
        codCarpeta = "2";
        llamarListado(nomCarpeta, codCarpeta)
        window.top.window.leftFrame.cambioMenuAsociado(nomCarpeta);
    });
    shortcut.add("Alt+c", function() {
        nomCarpeta = "Eliminados";
        codCarpeta = "6";
        llamarListado(nomCarpeta, codCarpeta)
        window.top.window.leftFrame.cambioMenuAsociado(nomCarpeta);
    });
    shortcut.add("Alt+n", function() {
        nomCarpeta = "No Enviados";
        codCarpeta = "7";
        llamarListado(nomCarpeta, codCarpeta)
        window.top.window.leftFrame.cambioMenuAsociado(nomCarpeta);
    });
    shortcut.add("Alt+e", function() {
        nomCarpeta = "Enviados";
        codCarpeta = "8";
        llamarListado(nomCarpeta, codCarpeta)
        window.top.window.leftFrame.cambioMenuAsociado(nomCarpeta);
    });
    shortcut.add("Alt+p", function() {
        nomCarpeta = "Reasignados";
        codCarpeta = "12";
        llamarListado(nomCarpeta, codCarpeta)
        window.top.window.leftFrame.cambioMenuAsociado(nomCarpeta);
    });
    shortcut.add("Alt+a", function() {
        nomCarpeta = "Archivados";
        codCarpeta = "10";
        llamarListado(nomCarpeta, codCarpeta)
        window.top.window.leftFrame.cambioMenuAsociado(nomCarpeta);
    });
    shortcut.add("Alt+i", function() {
        nomCarpeta = "Informados";
        codCarpeta = "13";
        llamarListado(nomCarpeta, codCarpeta)
        window.top.window.leftFrame.cambioMenuAsociado(nomCarpeta);
    });
    shortcut.add("Alt+t", function() {
        nomCarpeta = "Tareas Recibidas";
        codCarpeta = "15";
        llamarListado(nomCarpeta, codCarpeta)
        window.top.window.leftFrame.cambioMenuAsociado(nomCarpeta);
    });
    shortcut.add("Alt+s", function() {
        nomCarpeta = "Tareas Enviadas";
        codCarpeta = "16";
        llamarListado(nomCarpeta, codCarpeta)
        window.top.window.leftFrame.cambioMenuAsociado(nomCarpeta);
    });
}
function agregarTodos()
{
    var optn = document.createElement("OPTION");
    optn.text = 'Todos los Usuarios Activos de la Institución';
    optn.value = -1;
    slc_lista.options.add(optn);
}
function MostrarFila(fila, ruta_raiz){
            var elemento=document.getElementsByName(fila);
            imgAgregar = "agregar.png";
            imgQuitar = "quitar.png";

            for (var i=0; i<elemento.length; i++){

                if (elemento[i].style.display=='none')
                {
                    if(document.getElementById("spam_"+fila)!=null)
                       document.getElementById("spam_"+fila).innerHTML = '<img src='+ruta_raiz+'/imagenes/'+  imgQuitar +' border="0" height="15px" width="15px">';
                    elemento[i].style.display='';
                }
                else{
                   if(document.getElementById("spam_"+fila)!=null)
                        document.getElementById("spam_"+fila).innerHTML = '<img src='+ruta_raiz+'/imagenes/'+ imgAgregar  +' border="0" height="15px" width="15px">';
                   elemento[i].style.display='none';
                }
               MostrarFila(elemento[i].id, ruta_raiz);
            }
	}
window.onload=init;
var contador = 0;//para que haga la busqueda cada 3 caracteres
function lookupTrd(obj,e) {
           inputString = obj.value+" ";
        contador++;
        if (!e) var e = window.event;
	if (e.keyCode) code = e.keyCode;
	else if (e.which) code = e.which;
	
      
                     if (contador==3 || code==32 || code==13) //palabra es igual a 3 caracteres ejecuta y es barra espaciadora
			
                        $.post("../tipo_documental/ajax_obtener_trd.php", {queryString: ""+inputString+""}, function(data){
				if(data.length >0) {
                                        contador=0;//si encuentra se pone en 0 para que realice nuevamente la busqueda                                    
					$('#suggestions').show();
					$('#autoSuggestionsList').html(data);
                                        //document.getElementById('carpeta_seleccionada').value="";
                                        document.getElementById('carpeta_seleccionada').innerHTML="";
                                }
			});
                        
//		}
} // lookup
function fill(thisValue) { 
    
        $('#inputString').val(thisValue);
        //$('#carpeta_seleccionada').val(thisValue);
         document.getElementById('carpeta_seleccionada').innerHTML=thisValue;
        setTimeout("$('#suggestions').hide();", 10);
}
function codigoFusC(idTrd){    
    document.getElementById("txt_check_carpeta").value=idTrd;

}
function limpiar(){ 
    if (document.getElementById('inputString').value=='')
         document.getElementById('carpeta_seleccionada').innerHTML="";
        //document.getElementById('carpeta_seleccionada').value="";
}
</script>
<?php

$usrPermiso = $_SESSION['usua_perm_email_all'] ?? 0;//= ObtenerPermisoUsuario($_SESSION["usua_codi"], 31, $db);//ObtenerDatosUsuario($_SESSION["usua_codi"], $db);

?>
<body onLoad="markAll(1); <?php if ($usrPermiso==1 and $codTx==8){ ?> agregarTodos();<?php } ?>">
  <div id="spiffycalendar" class="text"></div>
  <link rel="stylesheet" type="text/css" href="../js/spiffyCal/spiffyCal_v2_1.css">
  <script type="text/javascript" src="../js/spiffyCal/spiffyCal_v2_1.js"></script>
  <script type="text/javascript" src="../Administracion/ciudadanos/jquerysubir/jquery-1.3.2.min.js"></script>
  <script type="text/javascript">
    <?php  $fecha_doc = $fecha_doc ?? date("d-m-Y");  ?>
        var dateAvailable1 = new ctlSpiffyCalendarBox("dateAvailable1", "realizarTx", "fecha_doc","btnDate1","<?=$fecha_doc?>",scBTNMODE_CUSTOMBLUE);
  </script>
  <br/>

  <center>

<?php
    //Si hay algun error, se muestra mensaje donde se indica que no se puede archivar el(los) radicado(s)
    if ($mensaje_error != "" )
        echo ("<table class='borde_tab' width='100%' cellspacing=0><tr class='listado2'><td width='30%'>&nbsp;</td><td width='40%'>$mensaje_error</td><td width='30%'>&nbsp;</td></tr></table></center>");
    if ($codTx == 9 or $codTx == 69) {  //Buscamos las áreas que se desplegarán en los combos de reasignar e informar
        if ($_SESSION["perm_saltar_organico_funcional"]==1)
            $where_area = "inst_codi=".$_SESSION["inst_codi"];
        elseif($_SESSION["cargo_tipo"]!=1 && $_SESSION["usua_publico"] !=1)
            $where_area = "depe_codi=".$_SESSION["depe_codi"];
        else {
            // Obtenermos el área padre del área actual
            $sql = "select coalesce(depe_codi_padre, depe_codi) as depe_codi from DEPENDENCIA WHERE depe_codi=".$_SESSION["depe_codi"];
            $rs = $db->conn->Execute($sql);
            $where_area = $rs->fields["DEPE_CODI"];
            if ($where_area != $_SESSION["depe_codi"]) {
                $where_area .= "," . $_SESSION["depe_codi"];
            }
//            if ($_SESSION["perm_saltar_organico_funcional"]==1) {
//                // Si el usuario tiene permisos para saltar el organico funcional, muestra un nivel mas.
//                $sql = "select depe_codi from dependencia where depe_codi_padre=".$_SESSION["depe_codi"];
//                
//                $rs = $db->conn->Execute($sql);
//                while(!$rs->EOF) {
//                    $where_area .= "," . $rs->fields['DEPE_CODI'];
//                    $rs->MoveNext();
//                }
//            }
            $where_area = "coalesce(depe_codi_padre, depe_codi) in ($where_area) or depe_codi in ($where_area)";
        }
        $sql = "select distinct depe_nomb, depe_codi from dependencia where depe_estado=1 and ($where_area) order by 1";
        
        $rs_area = $db->query($sql);
        //Por David Gamboa
        //$sql = "select usua_nombre, usua_codi from datos_usuarios where usua_esta=1 and depe_codi=".$_SESSION["depe_codi"]." order by 1";
        //El cambio lo hago por la incidencia 2049
//        $sql = "select usua_nomb || ' ' || usua_apellido || 
//                    case when usua_subrogado<>1 then ' (Subrogante)' else '' 
//                    end as usua_nombre
//                    , usua_codi from usuario where usua_esta=1 and usua_login not like 'UADM%' and depe_codi=".$_SESSION["depe_codi"]." order by 1";
        //echo $sql;
        //SUBROGADO SUBROGANTE
        $sql=utilSqlSubrogacion($_SESSION["depe_codi"]);
        $rs_usr = $db->conn->Execute($sql);
    }

    // Documentos de periodo jerárquico: si quien reasigna tiene nivel en su puesto,
    // las áreas y usuarios ofrecidos son sólo los que permiten las reglas por nivel
    // (jerarquia_destinos). realizarTx.php lo vuelve a validar al grabar.
    $jer_activo = false;
    $jer_destinos = array();
    $jer_nivel = null;
    if ($codTx == 9 && jerarquia_hay_documentos_jerarquicos($db, $whereFiltro)) {
        $jer_nivel = jerarquia_nivel_usuario($db, $_SESSION["usua_codi"]);
        if ($jer_nivel !== null) {
            $jer_activo = true;
            $jer_destinos = jerarquia_destinos($db, $_SESSION["usua_codi"]);
            $jer_areas = array();
            foreach ($jer_destinos as $d) $jer_areas[$d['depe_codi']] = 1;
            $jer_areas = implode(',', array_keys($jer_areas));
            $rs_area = $db->query("select depe_ruta(depe_codi) as depe_nomb, depe_codi from dependencia
                                    where depe_codi in (".($jer_areas == '' ? '0' : $jer_areas).") order by 1");
            // Área inicial: la propia si tiene destinos; si no, la primera disponible.
            $jer_area_ini = isset(array_flip(explode(',', $jer_areas))[$_SESSION["depe_codi"]])
                          ? $_SESSION["depe_codi"] : (int)explode(',', $jer_areas)[0];
            // Para cada destino, a quién se informará en copia (se muestra al elegirlo).
            $jer_copias_js = array();
            foreach ($jer_destinos as $u => $d) {
                $jer_copias_js[$u] = array('regla' => $GLOBALS['JERARQUIA_REGLAS'][$d['regla']] ?? $d['regla'],
                                           'copias' => jerarquia_nombres($db, $d['copias']));
            }
        }
    }
$accion = "";
switch ($codTx)
{
        case 2:
            $accion = "Acci&oacute;n: Eliminar Documentos ";
                break;
        case 4:
            $accion = "Env&iacute;o Electr&oacute;nico de Documentos ";
                break;
        case 3:
            $ver=$_SESSION["existe_radi_path"];
            $firma=$_SESSION["firma_digital"];
            $accion = "Acci&oacute;n: Enviar Documentos Manualmente";
                break;
        case 5:
            $accion = "Acci&oacute;n: Env&iacute;o Manual de Documentos ";
                break;
        case 6:
            $accion = "Acci&oacute;n: Reestablecer Documentos Eliminados ";
                break;
        case 7:
            $accion = "ACCI&Oacute;N: Borrar Informados ";
                break;
        case 8: //Informar
            $sql = "select distinct depe_nomb, depe_codi from dependencia where depe_estado=1 and inst_codi=".$_SESSION["inst_codi"]." order by 1";            
            $rs_area = $db->query($sql);
            $sql=utilSqlSubrogacion($_SESSION["depe_codi"]);
            //echo $sql;
            $rs_usr = $db->conn->Execute($sql);
            $sql = "select lista_nombre, lista_codi from lista where lista_estado = 1 and (usua_codi=0 and inst_codi=".$_SESSION["inst_codi"].") or usua_codi=".$_SESSION["usua_codi"]." order by 1";
            $rs_lista = $db->conn->Execute($sql);
            $menu_area = $rs_area->GetMenu2('depsel[]', $_SESSION["depe_codi"], false, true, 8, " id='depsel' class='select' style='height:85px;' onChange='cambiar_combo_usuarios()' ");            
            //usuarios
            $menu_usr  = $rs_usr->GetMenu2("usCodSelect[]", 0, false, true, 8," id='usCodSelect' class='select' style='height:85px; overflow: auto'" );            
            $menu_lista  = $rs_lista->GetMenu2("slc_lista[]", 0, false, true, 8," id='slc_lista' class='select' style='height:85px;'" );            
            $accion = "<table width='100%' border='0' cellspacing='1' class='borde_tab_blanco'>";
            $accion .= "<tr class='titulos4'><td colspan='4'><center>Acci&oacute;n: Informar Documentos</center></td></tr>";
            $accion .= "<tr><td>Área:</td><td>&nbsp;</td><td>Servidor Público:</td><td>&nbsp;</td></tr>";
            $accion .= "<tr><td colspan=2>$menu_area</td><td colspan=2><div name='mnu_usr' id='mnu_usr'>$menu_usr</div></td></tr>
                        <tr><td colspan=4><hr></td></tr>
                        <table width='100%' border='0' cellspacing='1' class='borde_tab_blanco'>
                        <tr><td width='35%'>&nbsp;</td><td colspan=2>Listas: </td><td>&nbsp;</td></tr>
                        <tr><td width='35%'>&nbsp;</td><td colspan=2>$menu_lista</td><td>&nbsp;</td></tr>
                        </tr><tr><td colspan=4><hr></td></tr></table>";
            //$accion .= "<tr class='listado1'><tr>";
                break;
        case 9: // Reasignar
            $menu_area = $rs_area->GetMenu2('depsel', $_SESSION["depe_codi"], false, false, 0,
                                            " id='depsel' class='select' onChange='cambiar_combo_usuarios()' ");
            if($carpeta == 14)
                $codi_usuario = $_SESSION['usua_codi'];
            else
                $codi_usuario = 0;
            $menu_usr  = $rs_usr->GetMenu2("usCodSelect", $codi_usuario, "0:&lt;&lt; Seleccione Usuario &gt;&gt;", false,0," id='usCodSelect' class='select'" );
            $aviso_jer = "";
            if ($jer_activo) {
                $menu_area = $rs_area->GetMenu2('depsel', $jer_area_ini, false, false, 0,
                                                " id='depsel' class='select' onChange='cambiar_combo_usuarios()' ");
                $menu_usr  = jerarquia_combo_usuarios($jer_destinos, $jer_area_ini, $codi_usuario);
                $aviso_jer = "<tr class='listado2'><td colspan='3'><b>Documento de periodo jer&aacute;rquico.</b>
                              Usted es nivel $jer_nivel: s&oacute;lo se muestran los usuarios a los que puede reasignar seg&uacute;n su nivel.
                              <div id='div_jerarquia_copia' style='margin-top:4px;'></div>
                              <script type='text/javascript'>jerarquia_copias = "
                              .json_encode((object)$jer_copias_js, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)
                              .";</script></td></tr>";
                if (empty($jer_destinos))
                    $aviso_jer .= "<tr class='listado2'><td colspan='3'><span style='color:red'>No hay usuarios a los que pueda reasignar este documento.</span></td></tr>";
            }
            $accion = "<table width='100%' border='0' cellspacing='1'>";
            $accion .= "<tr class='titulos4'><td>Acci&oacute;n:</td><td>Área:</td><td>Usuario:</td></tr>";
            $accion .= "<tr class='listado1'><td valign='top'>Reasignar Documentos</td><td>$menu_area</td><td>
                        <div name='mnu_usr' id='mnu_usr'>$menu_usr</div></td><tr>$aviso_jer</table>";
                break;
        case 11:
            
            $ver = $_SESSION['existe_radi_path'] ?? '';
            $firma = $_SESSION['firma_digital'] ?? '';
            $docExterno = $_SESSION['radi_tipo_doc'] ?? '';
            $accion = "Acci&oacute;n: Firmar y Enviar Documentos";
                break;
        case 13:
            $accion = "Acci&oacute;n: Archivar Documentos";
            break;
        case 17:
            $accion = "Acci&oacute;n: Reestablecer Documentos Archivados";
            break;
        case 18:
            $accion = "Acci&oacute;n: Comentar Documentos";
            break;
        case 20:
            $accion = "Acci&oacute;n: Devoluci&oacute;n de Documentos";
            break;
         case 69://Enviar Físico
            $accion = "Acci&oacute;n: Enviar F&iacutesico";
            //Defino permiso de Bandeja de Entrada
            $permiso="";
            $sql="select id_permiso from permiso_usuario where id_permiso=5 and usua_codi=".$_SESSION["usua_codi"];            
            $rs_perm = $db->query($sql);
            while(!$rs_perm->EOF) {
                $permiso=$rs_perm->fields['ID_PERMISO'];
                $rs_perm->MoveNext();
            }
            if(isset($permiso) and ($permiso=="")){
                $menu_area = $rs_area->GetMenu2('depsel',$_SESSION["depe_codi"],false,false,0," id='depsel' class='select' onChange='cambiar_combo_usuarios()' ");
                $permiso="";
            }else{
                $sqlP = "select distinct depe_nomb, depe_codi from dependencia where depe_nomb<>'' and depe_estado = 1 and inst_codi=".$_SESSION["inst_codi"]." order by depe_nomb";
                //echo $sqlP;
                $rs_area = $db->query($sqlP);
                $menu_area = $rs_area->GetMenu2('depsel',$_SESSION["depe_codi"],false,false,0," id='depsel' class='select' onChange='cambiar_combo_usuarios()' ");
                $sqlP="";
            }
            $menu_usr  = $rs_usr->GetMenu2("usCodSelect", 0, "0:&lt;&lt; Seleccione Usuario &gt;&gt;", false,0," id='usCodSelect' class='select'" );
//            echo "</td></tr>";
            $accion = "<table width='100%' border='0' cellspacing='1'>";            
            $accion .= "<tr class='titulos4'><td>Acci&oacute;n:</td><td>Area:</td><td>Usuario:</td><td>Responsable Traslado:</td><td>Estado Documento:</td></tr>";
            $accion .= "<tr class='listado1'><td valign='top'>Enviar Físico</td><td>$menu_area</td><td><div name='mnu_usr' id='mnu_usr'>$menu_usr</div></td><td><input type=\"text\" name=\"nombre\" id=\"nombre\" maxlength=\"100\"><input type=\"hidden\" name=\"texto\" id=\"texto\"value=\"\"></td><td><input type=\"radio\" value=\"B\" checked name=\"estadoF\" onclick=\"Obtener_val(this)\" >Bueno<input type=\"radio\" value=\"R\" name=\"estadoF\" onclick=\"Obtener_val(this)\" checked>Regular<input type=\"radio\" value=\"M\" name=\"estadoF\" onclick=\"Obtener_val(this)\" checked>Mala</td><tr></table> <input type=\"hidden\" name=\"opcDoc\" id=\"opcDoc\" value=\"\" checked>" ;            
            
            break;
        case 83: //Recupera Documentos
            $accion = "Acci&oacute;n: Recuperar Documentos";
            break;
         case 88: //Asociar a Carpetas Virtuales
            $accion = "Acci&oacute;n: Incluir en Carpeta Virtual";  
            
            //echo $accion2;
            break;
        case 90:
            $accion = "Acci&oacute;n: Enviar Documentos Firmados Electr&oacute;nicamente por Ciudadanos";
            break;
        }

  ?>

<style>a:link, a:visited, a:hover {color: blue;}</style>
    <form action="javascript:;" name="realizarTx" method='post' enctype='multipart/form-data'>
        <input type='hidden' name="carpeta" value="<?=$carpeta?>">
        <input type='hidden' name="codTx" value="<?=$codTx?>">  
        <?php if($codTx != 9){?>  
            <input type="hidden" name="chk_reasigna_padre" id="chk_reasigna_padre" value="0" />
        <?php }?> 
            
        <table width="100%" border="0" cellpadding="0" cellspacing="5" class="borde_tab">
            <tr>
                <td class="titulos4" colspan="4" width='100%' align='center'><?=$accion?></td>
            </tr>
        <?php if ($codTx==88){
            
            echo '<input type="hidden" name="txt_check_carpeta" id="txt_check_carpeta" value="0" />';
            //  comento la funcionalidad de arbol y reemplazo por autocomplete
            
            ?>
                        <tr>
                            <td width="100%">
                                
                                <table width="100%" class="borde_tab" border="0">
                                <tr>
                                    <td class="titulos2" width="25%">
                                    Buscar Carpeta Virtual (Nombre):</td>
                                    <td class="listado2" colspan="3">
                                    <input type="text" size="30" value="" id="inputString" name="inputString" onkeypress="lookupTrd(this,event);" onblur="limpiar()" autocomplete="off"/>                                    
                                    <font size="1">Ingrese los primeros caracteres del nombre de la Carpeta y seleccione de la lista.</font>
                                    <div class="suggestionsBox" id="suggestions" style="display:none; width:300px; height:100px; overflow-x:hidden; autoflow-y:scroll;">
                                    <div class="suggestionList" id="autoSuggestionsList">
                                    &nbsp;
                                    </div>
                                    </div>
                                    </td>                                    
                                    </tr>
                                    <tr><td class="titulos2" width="25%">
                                    Agregar en la Carpeta:</td>
                                        <td class="listado2" colspan="3">
                                            <div id="carpeta_seleccionada" name="carpeta_seleccionada"/></div>
                                        </td>
                                        
                                    </tr>
                                </table>
                            </td>
                        </tr>
                
            
        <?php }?>


<?php      if ($codTx==9) {        //Muestra la fecha maxima de tramite para reasignar documentos y firmar y enviar ?>
            <tr align="center">
                <td colspan="2" align=center>
                    <br /><span ><b>Fecha M&aacute;xima de Tr&aacute;mite dd/mm/aaaa: </b></span>
                    <script type="text/javascript">
                        dateAvailable1.date = "<?=date('Y-m-d');?>";
                        dateAvailable1.writeControl();
                        dateAvailable1.dateFormat="dd-MM-yyyy";
                    </script><br>
                </td>
            </tr>
<?php	}
	if($_SESSION["firma_digital"]==1 and $codTx == 11) { //Solicita campos necesarios para firma digital
            if ($_SESSION["tipo_usuario"]==2) {
                echo '<input type="hidden" name="chk_firma" id="chk_firma" value="1">';
            } else {
?>
            <tr align="center">
                <td colspan="2" class="celdaGris" align=center>
                    <br />
                    <input type="checkbox" name="chk_firma" id="chk_firma" checked class="ebutton" value="1">
                        <span><b>&#191;Firmar digitalmente el documento?</b></span><br/>
                </td>
            </tr>
<?php          }
        }

// --- SMART-SIGN: Campos para cargar certificado .p12 y contraseña ---
// Leer $usar_smart_sign desde config.php en un scope aislado para no sobreescribir $db
$usar_smart_sign = (function() {
    include dirname(__DIR__) . '/config.php';
    return $usar_smart_sign ?? false;
})();
if ($usar_smart_sign && $codTx == 11 && ($_SESSION['firma_digital'] ?? 0) == 1) {
?>
            <tr>
                <td colspan="2" class="celdaGris" align="center" style="padding: 10px;">
                    <table border="0" cellpadding="5" cellspacing="0">
                        <tr>
                            <td align="right"><b>Certificado Digital (.p12):</b></td>
                            <td><input type="file" name="certificado_p12" id="certificado_p12" accept=".p12,.pfx" class="ecajasfecha" required></td>
                        </tr>
                        <tr>
                            <td align="right"><b>Contraseña del Certificado:</b></td>
                            <td><input type="password" name="certificado_password" id="certificado_password" class="ecajasfecha" size="30" required></td>
                        </tr>
                    </table>
                </td>
            </tr>
<?php }
// --- FIN SMART-SIGN ---

if ($codTx==9){
    $contAcc=0;
    //Verifico permiso de acceso
/*    $sql="SELECT A.ID_PERMISO FROM PERMISO_USUARIO A,PERMISO B
     WHERE A.ID_PERMISO=B.ID_PERMISO AND A.ID_PERMISO=4 AND B.ESTADO=1 AND A.USUA_CODI=".$_SESSION["usua_codi"];
    $rs1=$db->query($sql); /* */
    if($_SESSION["perm_acti_accion"]==1){

            // Sumillas de la institución, clasificadas por el motivo por el que se
            // emiten. $contAcc son las hojas activas: las categorías agrupan pero
            // no se pueden marcar, así que no cuentan como opciones disponibles.
            $contAcc     = sumillas_contar_seleccionables($db, $_SESSION['inst_codi']);
            $menu_accion = ($contAcc > 1) ? sumillas_dibujar_arbol($db, $_SESSION['inst_codi']) : "";?>
    <?php  if($contAcc > 1){?>
        <table border="1" align="center" width="100%">
        <tr>
            <td WIDTH=5% valign='top' class='titulos2'>Sumilla:</td>
            <td WIDTH=28% valign='top'><?php echo $menu_accion;?></td>
            <td WIDTH=67% align='center' valign='middle'>
                <b>Comentario: &nbsp;</b>
            <textarea id="observa" name=observa cols=70 rows=3 class=ecajasfecha onkeypress="return limita(event);"></textarea>            
            <span id="spn_numero_caracteres_disponibles"></span>
            <table >
                <?php  if(sizeof($radiNumeAsociados) > 0){?>                
                <tr>
                    <td align ="center">
                        <input type="checkbox" name="chk_reasigna_padre" id="chk_reasigna_padre" value="0" />¿Desea reasignar los documentos antecedentes?                        
                    </td>
                </tr>     
                 <?php } else{?>  
                    <input type="hidden" name="chk_reasigna_padre" id="chk_reasigna_padre" value="0" />
                <?php }?>     
                <tr>&nbsp;</tr>
                <tr>
                <td>
                <input type='button' value='Aceptar' onClick="okTx('<?=$ver?>','<?=$docExterno?>');" name='enviardoc' class='botones' id='REALIZAR'>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type='button' value='Regresar' onClick='regresarTx();' name='enviardoc' class='botones' id='Cancelar'>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type='button' value='Borrar' onClick='borrarCaja();' name='enviardoc' class='botones' id='Borrar'>
                </td>
                </tr>
            </table>
            </td>
        </tr>
        </table>
   <?php }else{?>
             <table border="1" align="center" width="100%">
            <tr>
            
            <td WIDTH=80% align='center' valign='middle'>
            <b>Comentario: &nbsp;</b>
            <textarea id="observa" name=observa cols=70 rows=3 class=ecajasfecha onkeypress="return limita(event);"></textarea>
            <span id="spn_numero_caracteres_disponibles"></span>
            <table >
                <?php  if(sizeof($radiNumeAsociados) > 0){?>                
                <tr>
                    <td align ="center">
                        <input type="checkbox" name="chk_reasigna_padre" id="chk_reasigna_padre" value="0" />¿Desea reasignar los documentos antecedentes?                        
                    </td>
                </tr>     
                 <?php } else{?>  
                    <input type="hidden" name="chk_reasigna_padre" id="chk_reasigna_padre" value="0" />
                <?php }?>     
                <tr>&nbsp;</tr>
                <tr>
                <td>
                <input type='button' value='Aceptar' onClick="okTx('<?=$ver?>','<?=$docExterno?>');" name='enviardoc' class='botones' id='REALIZAR'>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type='button' value='Regresar' onClick='regresarTx();' name='enviardoc' class='botones' id='Cancelar'>
                <!--&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <input type='button' value='Borrar' onClick='borrarCaja();' name='enviardoc' class='botones' id='Borrar'>-->
                </td>
                </tr>
            </table>
            </td>
        </tr>
        </table>

      <?php }
   }

    if($_SESSION["perm_acti_accion"]!=1 && ($codTx!==9)){ ?>
           <tr align="center">
                <td width='25%' align='right' valign='middle'><br/>
                    <b>Comentario: &nbsp;</b>
                </td>
                <td width='75%' align='left' valign='middle'><br/>
                    <textarea id="observa" name=observa cols=70 rows=3 class=ecajasfecha onkeypress="return limita(event);"></textarea>
                    <span id="spn_numero_caracteres_disponibles"></span>
                </td>
            </tr>           
                <?php  if(sizeof($radiNumeAsociados) > 0){?>                
                <tr>
                     <td  colspan="2" align='center'>
                        <input type="checkbox" name="chk_reasigna_padre" id="chk_reasigna_padre" value="0" />¿Desea reasignar los documentos antecedentes?                        
                    </td>
                </tr>     
                 <?php } else{?>  
                 <tr>
                     <td  colspan="2" align='center'>
                        <input type="hidden" name="chk_reasigna_padre" id="chk_reasigna_padre" value="0" />
                     </td>
                </tr>   
                <?php }?>     
                <tr>&nbsp;</tr>
                 
            <tr>
                    <td  colspan="2" align='center'>
<?php  if ($whereFiltro !=="0") { ?>
                        <input type='button' value='Aceptar' onClick="okTx('<?=$ver?>','<?=$docExterno?>');" name='enviardoc' class='botones' id='REALIZAR'>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<?php  } ?>
                        <input type='button' value='Regresar' onClick='regresarTx();' name='enviardoc' class='botones' id='Cancelar'>
                    </td>
            </tr>
   <?php }

}
elseif ($codTx!=9){

    if($codTx==18){
          
            //Datos de acciones
            if($_SESSION["perm_acti_accion"]==1){

            // Sumillas de la institución, clasificadas por el motivo por el que se
            // emiten. $contAcc son las hojas activas: las categorías agrupan pero
            // no se pueden marcar, así que no cuentan como opciones disponibles.
            $contAcc     = sumillas_contar_seleccionables($db, $_SESSION['inst_codi']);
            $menu_accion = ($contAcc > 1) ? sumillas_dibujar_arbol($db, $_SESSION['inst_codi']) : "";
            
     }
    }?>
    <tr align="center">
    <?php  if($contAcc > 1){?>   
        <td width='80%' align='right' valign='middle'>
        <table border="0" align="center" width="100%">
        <tr>
            <td WIDTH=5% valign='top' class='titulos2'>Sumilla:</td>
            <td WIDTH=28% valign='top'><?php echo $menu_accion;?></td>
            <td width='10%' align='right' valign='middle'>
                <br/>
                    <b>Comentario: &nbsp;</b>
                </td>
                <td width='65%' align='left' valign='middle'><br/>
                    <textarea id="observa" name=observa cols=70 rows=3 class=ecajasfecha onkeypress="return limita(event);"></textarea>
                    <span id="spn_numero_caracteres_disponibles"></span>
                </td>
        </tr>
        </table>
        </td>
    <?php  }else{ ?>    
        <tr>        
        <td align="center">
              <table border="0" align="center" width="100%">  
                  <tr><td>
                <br/>
                    <b>Comentario: &nbsp;</b>
                </td>
       
                <td width='75%' align='left' valign='middle'><br/>
                    <textarea id="observa" name=observa cols=70 rows=3 class=ecajasfecha onkeypress="return limita(event);"></textarea>
                    <span id="spn_numero_caracteres_disponibles"></span>
                </td>
        </table>
            </tr>
            <?php  } ?>
            <tr>
                <td  colspan="2" align='center'>
<?php  if ($whereFiltro !=="0") { ?>
                    <input type='button' value='Aceptar' onClick="okTx('<?=$ver?>','<?=$docExterno?>');" name='enviardoc' class='botones' id='REALIZAR'>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<?php  } ?>
                    <input type='button' value='Regresar' onClick='regresarTx();' name='enviardoc' class='botones' id='Cancelar'>
                </td>
            </tr>
<?php  } ?>
        </table>
    	<br />
<?php
	/*  GENERACION LISTADO DE RADICADOS
	 *  Aqui utilizamos la clase adodb para generar el listado de los radicados
         *  Esta clase cuenta con una adaptacion a las clases utiilzadas de orfeo.
         *  el archivo original es adodb-pager.inc.php la modificada es adodb-paginacion.inc.php
         */

        include_once(dirname(__DIR__).'/include/query/tx/queryFormEnvio.php');

        $pager = new ADODB_Pager($db->conn,$isql,'adodb', false, $orderNo,$orderTipo);
        $pager->toRefLinks = $linkPagina;
        $pager->toRefVars = $encabezado;
        $pager->checkAll = true;
        $pager->checkTitulo = false;
        $pager->Render($rows_per_page=200,$linkPagina,$checkbox="chkAnulados");

?>
    </form>
</center>
</body>
</html>
