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
 * @package    usuarios
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
// Enable error reporting for debugging blank screen
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Includes first to ensure functions like html_error are available
include_once(dirname(__DIR__, 2).'/rec_session.php');
include_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once("mnuUsuariosH.php");
include_once("../ciudadanos/util_ciudadano.php");
require_once("../../include/db/ConnectionHandler.php");

// Extract variables - register_globals emulation
foreach ($_REQUEST as $key => $value) {
    if ($key != 'PHPSESSID' && $key != 'krd') {
        $$key = $value;
    }
}
$accion = isset($_REQUEST['accion']) ? $_REQUEST['accion'] : 0;
$usr_codigo = isset($_REQUEST['usr_codigo']) ? $_REQUEST['usr_codigo'] : 0;
$recargar = isset($_REQUEST['recargar']) ? $_REQUEST['recargar'] : null;

// Permission check
if ($_SESSION["usua_admin_sistema"] != 1) {
    die( html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.") );
}

$connHandler = new ConnectionHandler(dirname(__DIR__, 2));
$ciud = new Ciudadano($connHandler);

$depe_destino = (isset($depe_destino)) ? $depe_destino : $_SESSION['depe_codi'];

$usuarioSubr = "";
$read = "";
$read2 = "";
$read3 = "readonly";
$read4 = "";
$tiene_subrogacion = 0;
$size_txt = 45;

// Inicializar variables para evitar advertencias de Undefined variable
$usr_codigo = isset($usr_codigo) ? $usr_codigo : "";
$usr_perfil = isset($usr_perfil) ? $usr_perfil : "";
$usr_depe   = isset($usr_depe) ? $usr_depe : "";
$usr_cedula = isset($usr_cedula) ? $usr_cedula : "";
$usr_login  = isset($usr_login) ? $usr_login : "";
$usr_nombre = isset($usr_nombre) ? $usr_nombre : "";
$usr_apellido = isset($usr_apellido) ? $usr_apellido : "";
$usr_email  = isset($usr_email) ? $usr_email : "";
$usr_cargo  = isset($usr_cargo) ? $usr_cargo : "";
$usr_cargo_cabecera = isset($usr_cargo_cabecera) ? $usr_cargo_cabecera : "";
$usr_direccion = isset($usr_direccion) ? $usr_direccion : "";
$usr_telefono = isset($usr_telefono) ? $usr_telefono : "";
$usr_sumilla = isset($usr_sumilla) ? $usr_sumilla : "";
$usr_celular = isset($usr_celular) ? $usr_celular : "";
// Vigencia de la cuenta (fecha inicio / fin). En un alta nueva arranca hoy y sin fin.
$usr_vig_desde = isset($usr_vig_desde) ? $usr_vig_desde : ((0 + ($_REQUEST["accion"] ?? 0)) == 1 ? date("Y-m-d") : "");
$usr_vig_hasta = isset($usr_vig_hasta) ? $usr_vig_hasta : "";
$usr_obs    = isset($usr_obs) ? $usr_obs : "";
$usr_inst_nombre = isset($usr_inst_nombre) ? $usr_inst_nombre : "";
$codi_ciudad = isset($codi_ciudad) ? $codi_ciudad : 0;
$cmb_tit    = isset($cmb_tit) ? $cmb_tit : 0;
$usr_abr_titulo = isset($usr_abr_titulo) ? $usr_abr_titulo : "";
$usr_titulo = isset($usr_titulo) ? $usr_titulo : "";
$usr_firma_path = isset($usr_firma_path) ? $usr_firma_path : "";
$ban_compartida = isset($ban_compartida) ? $ban_compartida : "";
$perfil_inicio = isset($perfil_inicio) ? $perfil_inicio : "";
$area_inicio = isset($area_inicio) ? $area_inicio : "";
$cargo_id = isset($cargo_id) ? $cargo_id : 0;
$datosSubdo = isset($datosSubdo) ? $datosSubdo : array("nombre"=>"");
$usr_tipo_ident = isset($usr_tipo_ident) ? $usr_tipo_ident : 0;
$checked_tipo_identificacion = isset($checked_tipo_identificacion) ? $checked_tipo_identificacion : "";
$usr_codi_actualiza = isset($usr_codi_actualiza) ? $usr_codi_actualiza : "";
$nombarch = $nombarch ?? "";
$extarch = $extarch ?? "";
$usr_destino = isset($usr_destino) ? $usr_destino : 0;
$usr_tipo_certificado = isset($usr_tipo_certificado) ? $usr_tipo_certificado : 0;
$usuarioSubr = "";
$mensajeSubro = "";
$descDependencia = "Dependencia"; // Default value
$descEmpresa = "Empresa"; // Default value


if (!isset($recargar)) {
    if ($accion == 1) { //Nuevo        
        $tituloForm = "Creaci&oacute;n de ";
	$usr_nuevo = "checked";
	$usr_estado = "checked";
        
        /*if($_REQUEST['usr_area_responsable']==true )
            $usr_responsable_area=1;
        else
            $usr_responsable_area=0;*/

    } else {
        $sql = "select * from usuarios where usua_codi=$usr_codigo";        
        $rs = $connHandler->conn->Execute($sql);
        $usr_depe 	= $rs->fields["DEPE_CODI"];        
        $area_inicio    = $rs->fields["DEPE_CODI"];
        $usr_perfil 	= $rs->fields["CARGO_TIPO"];
        $perfil_inicio  = $rs->fields["CARGO_TIPO"];
        $usr_login 	= $rs->fields["USUA_LOGIN"];
        $usr_cedula 	= $rs->fields["USUA_CEDULA"];
        $usr_nombre 	= $rs->fields["USUA_NOMB"];
        $usr_apellido 	= $rs->fields["USUA_APELLIDO"];
        $usr_titulo     = $rs->fields["USUA_TITULO"];
        $usr_abr_titulo = $rs->fields["USUA_ABR_TITULO"];
        $usr_cargo      = $rs->fields["USUA_CARGO"];
        $usr_cargo_cabecera= $rs->fields["USUA_CARGO_CABECERA"];
        $usr_sumilla    = $rs->fields["USUA_SUMILLA"];
        $usr_inst_nombre = $rs->fields["INST_NOMBRE"];
        $usr_responsable_area=($rs->fields["USUA_RESPONSABLE_AREA"] == 1)? "checked" : "";
        //$puesto         = $rs->fields["PUESTO"];
        $cargo_id       = $rs->fields["CARGO_ID"];
        $usr_email      = $rs->fields["USUA_EMAIL"];

        $usr_obs        = $rs->fields["USUA_OBS"];
        $codi_ciudad    = $rs->fields["CIU_CODI"];

        $usr_nuevo      = ($rs->fields["USUA_NUEVO"] == 1) ? "" : "checked";
        $usr_estado     = ($rs->fields["USUA_ESTA"] == 0) ? "" : "checked";

        $usr_firma_path =$rs->fields["USUA_FIRMA_PATH"];

        //direccion y telefono
        $usr_direccion  = $rs->fields["USUA_DIRECCION"];
        $usr_telefono   = $rs->fields["USUA_TELEFONO"];
        //celular
        $usr_celular   = $rs->fields["USUA_CELULAR"];
        $usr_vig_desde = substr((string)($rs->fields["USUA_VIGENCIA_DESDE"] ?? ""), 0, 10);
        $usr_vig_hasta = substr((string)($rs->fields["USUA_VIGENCIA_HASTA"] ?? ""), 0, 10);

        //Datos del ultimo usuario que actualizó el registro
        $usr_codi_actualiza     = $rs->fields["USUA_CODI_ACTUALIZA"];
        $usr_fecha_actualiza    = $rs->fields["USUA_FECHA_ACTUALIZA"];
        $usr_obs_actualiza      = $rs->fields["USUA_OBS_ACTUALIZA"];
        $usr_subrogado      = 0+$rs->fields["USUA_SUBROGADO"];
        if ($usr_subrogado>=0)
            $datosSubdo = ObtenerDatosUsuario ($usr_subrogado, $connHandler);
        $usr_tipo_certificado   = $rs->fields["USUA_TIPO_CERTIFICADO"];
        $usr_tipo_ident = $rs->fields["TIPO_IDENTIFICACION"];
        $checked_tipo_identificacion = ($rs->fields["TIPO_IDENTIFICACION"]) ? "checked" : "";        
        
        
        // Si es Jefe consulta si tiene su bandeja compartida o no
        if($usr_perfil == '1')
        {
            $sqlCompartida = 'select * from bandeja_compartida where usua_codi_jefe = '.$usr_codigo;
            //echo $sqlCompartida;
            $rsComp = $connHandler->conn->Execute($sqlCompartida);
            //var_dump($rsComp->EOF);
            if(!$rsComp->EOF) // Si tiene bandeja compartida
                $ban_compartida = 'S';
            else // No tiene bandeja compartida
                $ban_compartida = 'N';
        }
        else // Si no es Jefe y van a cambiar al usuario de area eliminarlo de la bandeja compartida
        {
            //se verifica primero que no tenga bandeja compartida como jefe ya que hay usuarios que no son jefes y tienen carpeta compartida
            $sqlCompartida = 'select * from bandeja_compartida where usua_codi_jefe = '.$usr_codigo;
            $rsComp = $connHandler->conn->Execute($sqlCompartida);
            if(!$rsComp->EOF)
               $ban_compartida = 'S';
            else
            {
               $sqlCompartida = 'select * from bandeja_compartida where usua_codi = '.$usr_codigo;
               //echo $sqlCompartida;
               $rsComp = $connHandler->conn->Execute($sqlCompartida);
               //var_dump($rsComp->EOF);
               if(!$rsComp->EOF) // Si tiene bandeja compartida
                $ban_compartida = 'S';
               else // No tiene bandeja compartida
                $ban_compartida = 'N';
            }
        }
    }
} else {
        $usr_nuevo = (!isset($_POST["usr_nuevo"])) ? "" : "checked";
        $usr_estado = (!isset($_POST["usr_estado"])) ? "" : "checked";
        //$usr_area_responsable=(!isset($_POST['usr_area_responsable'])) ? "":"checked";
}

    if ($accion == 2) { //Editar
	$tituloForm = "Modificaci&oacute;n de ";
//VERIFICAR SI EL USUARIO ESTA EN SUBROGACION (SUBROGADO O SUBROGANTE)
        $mensajeSubro=usrMensajeSubrogacion($connHandler,$usr_codigo,"estado");
        $usuarioSubrEst=usrMensajeSubrogacion($connHandler,$usr_codigo,"perfil");
        
        if (trim($usuarioSubrEst)!=''){
            $read4 = "disabled";
            $tiene_subrogacion =1;
            //$usuarioSubr=str_replace(array("(", ")"),"", $usuarioSubrEst);
            $usuarioSubr=$usuarioSubrEst;
        }else
            $usuarioSubr='';

    }
    if ($accion == 3) {	//Consultar
    	$read = "readonly";
	$read2 = "disabled";
        $read4 = $read2;
	$tituloForm = "Consulta de ";
    }
    $tituloForm .= ($_SESSION["inst_codi"]>1) ? "Servidores P&uacute;blicos" : "Ciudadanos con Firma Electr&oacute;nica";

    if(isset($usr_codi_actualiza) && trim($usr_codi_actualiza)!='')
    {
        include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
        //Obtener datos del suncionario que actualizo por última ves al ciudadano
        $usua_actualiza = ObtenerDatosUsuario($usr_codi_actualiza, $connHandler);

        $usua_nombre_act = $usua_actualiza['usua_nombre'].' '.$usua_actualiza['usua_apellido'];
        $usua_institucion_act = $usua_actualiza['institucion'];
        $usua_email_act = $usua_actualiza['email'];
    }
//administracion por areas
//administracion por areas
$sql = "select count (depe_codi) as countdep from dependencia where inst_codi = ".$_SESSION['inst_codi'];
        $rs=$connHandler->conn->query($sql);
        if (!$rs->EOF) {
            $countdep=$rs->fields['COUNTDEP'];
        }
$var_habilitado = "";
        if($_SESSION["usua_codi"]!=0) $var_habilitado = "DISABLED";
echo "<!DOCTYPE html>".html_head(); /*Imprime el head definido para el sistema*/
require_once("../../js/ajax.js");

?>

<script type="text/javascript" src="../ciudadanos/adm_ciudadanos.js?v=<?=time()?>"></script>
<script type="text/javascript" src="../../js/formchek.js"></script>
<script type="text/javascript" src="../../js/validar_datos_usuarios.js"></script>
<script type="text/javascript" src="../../js/select_buscador.js?v=<?=time()?>"></script>
<script type="text/javascript">
    function ocultar_combo_usuario_des()
    {
        
        if (document.forms[0].usr_estado.checked) {
            document.getElementById("tr_estado").style.display="none";
            
            cod = document.getElementById("usr_codigo").value;
            if (document.getElementById("usr_cedula")){
                login = document.getElementById("usr_cedula").value;
                var pos = login.indexOf(cod);//busco en login el codigo para eliminarlo
                //alert(pos)
                var newcedula = login.replace('-'+cod,'');
                if (pos!=-1)             
                  document.getElementById("usr_cedula").value=newcedula;
              validar_cambio_cedula();
            } 
        } else {
            document.getElementById("tr_estado").style.display="";
        }
    }

    var bloquear_validar_informacion = false;
    function ValidarInformacion() {
        if (bloquear_validar_informacion) return false;
        bloquear_validar_informacion = true;
        bloquear_validar_informacion = fjs_validar_informacion();
        return true;
    }

    function fjs_validar_informacion() {

    //Cola de mensajes de error:
        msg = '';

        //Evalúa, en base a la condición, si agrega el mensaje entre los errores o no:
        function e(condicion, mensaje) {
            msg = (condicion) ? msg + mensaje + ' \n' : msg;
        }

        e(trim(document.getElementById('usr_depe').value) == '0', "Seleccione el Área del Usuario.");
        /*e(trim(document.forms[0].usr_login.value) == '', "El campo Login es obligatorio.");*/
        e(trim(document.forms[0].usr_nombre.value) == '', "Ingrese los nombres del usuario.");
        e(trim(document.forms[0].usr_apellido.value) == '', "Ingrese los apellidos del usuario.");

<?php
if (substr($usr_login,0,6)!="UADM") { ?>
    if(!document.frmCrear.usr_tipo_ident.checked){
        document.forms[0].usr_cedula.value = trim(document.forms[0].usr_cedula.value);
        e((trim(document.forms[0].usr_cedula.value) == "") || document.forms[0].usr_estado.checked
           && (!validarCedula(document.getElementById('usr_cedula').value) && !document.frmCrear.usr_tipo_ident.checked), 'Ingrese una cédula válida.');
    }
<?php  } ?>

        e(!document.getElementById('cargo_id') || trim(document.getElementById('cargo_id').value) == '' || document.getElementById('cargo_id').value == '0',
            "Seleccione el puesto del usuario del catálogo del área. Si el área no tiene el puesto, regístrelo primero en Áreas › Puestos.");
        e(trim(document.forms[0].usr_sumilla.value) == '' && '<?php  if ($_SESSION["inst_codi"]>1) echo "1";?>'=='1',
            "Ingrese la sumilla del usuario a mostrar en el pie de página del documento.");
        e(trim(document.forms[0].usr_email.value) == '', "Ingrese el correo electrónico.");
        e(!isEmail(document.forms[0].usr_email.value, true), "El correo electrónico no tiene formato correcto.");
        e(document.getElementById('codi_ciudad').value == 0 || trim(document.getElementById('codi_ciudad').value) == '',
            "Ingrese la ciudad a la que pertenece el usuario.");

        //Si hubieron errores, despliega los mensajes:
        if (msg != '') {
            ver_datos();
            alert(msg);
        }

        // Si al Jefe le cambian de perfil de usuario de Jefe a Normal
        if(msg == '' && document.forms[0].ban_compartida.value=='S' && document.forms[0].perfil_inicio.value!=document.forms[0].usr_perfil.value) {
            if(confirm("El usuario tiene Compartida su Bandeja de Documentos Recibidos, si cambia el Perfil se eliminará la configuración de Bandeja Compartida, desea cambiar el Perfil del usuario?") && msg=='') {
                //alert('Eliminar bandejas');
                document.forms[0].eliminar_compartida.value = 'S';
            } else {
                //alert('No eliminar bandejas');
                document.forms[0].eliminar_compartida.value = 'N';
                document.forms[0].usr_perfil.value = '1';
                return false;
            }
        }

        // Si al usuario le cambian de área
        if(msg == '' && document.forms[0].ban_compartida.value=='S' && document.forms[0].area_inicio.value!=document.forms[0].usr_depe.value) {
            if(confirm("El usuario tiene Compartida la Bandeja de Documentos Recibidos del Jefe de Área, si se cambia de área se eliminara la configuración de Bandeja Compartida, desea cambiar el área del usuario?") && msg=='')
            {
                //alert('Eliminar bandejas');
                document.forms[0].eliminar_compartida.value = 'S';
            }
            else
            {
                //alert('No eliminar bandejas');
                document.forms[0].eliminar_compartida.value = 'N';
                document.forms[0].usr_perfil.value = '1';
                return false;
            }
        }
        if (msg == '' && !validar_datos_registro_civil('usr_nombre','usr_apellido')) return false; // Lanza automaticamente el confirm

        // No permitir dos cuentas de la misma cédula con el mismo puesto.
        if (msg == '' && !avisar_puesto_duplicado()) return false;

        // Vigencia: la fecha fin no puede ser anterior a la fecha inicio.
        var vd = document.getElementById('usr_vig_desde'), vh = document.getElementById('usr_vig_hasta');
        if (msg == '' && vd && vh && vd.value != '' && vh.value != '' && vh.value < vd.value) {
            alert('La fecha fin no puede ser anterior a la fecha inicio.');
            return false;
        }

        if (msg == '') {
            if (window.bloquearPantalla) bloquearPantalla('Guardando usuario...');
            document.forms[0].action = 'grabar_usuario.php?accion=<?php echo (int)$accion; ?>';
            document.forms[0].submit();
            /*datos=cargarDatosAjax();
            document.getElementById('div_guardar_usr').style.display='';
            nuevoAjax('div_guardar_usr', 'POST', 'grabar_usuario.php', datos);
           
            
            timerID = setTimeout("refresacar()", 2000);
            */
            
            return true;
        }
        return false;
    }
function refresacar(){  
    usr_codigo = document.getElementById('usr_codigo').value;
    if (usr_codigo=='')
    if (document.getElementById('nuevo_codigo_usr'))
        usr_codigo=document.getElementById('nuevo_codigo_usr').value;
        window.location='adm_usuario.php?usr_codigo='+usr_codigo+'+&accion=2';
}
function cargarDatosTit(){
    var datosTit = document.frmCrear.cmb_tit.options[document.frmCrear.cmb_tit.selectedIndex].text.split(" - ");
    
    datosCarg = document.frmCrear.cmb_tit.value;
    
    dato1 = datosTit[1]; //abr
    dato2 = datosTit[0]; //tit
    if (typeof dato1=='undefined')
         datosTit[1]='';
      if (typeof dato2=='undefined' || datosCarg==0)
           datosTit[0]='';      
    document.frmCrear.usr_abr_titulo.value = datosTit[1];    
    document.frmCrear.usr_titulo.value = datosTit[0];

}

function cargarCiudad(){
    var area = document.getElementById('usr_depe').value;
    var codigo = document.getElementById('codi_ciudad').value;
    if (codigo==0)
        nuevoAjax('usr_ciu', 'GET', 'ciudad_ajax.php', 'area='+area+'&codigo='+codigo);
    return;
}

// Combo de puestos del área (catálogo 'cargo'). Al cambiar de área se recargan
// sus puestos; al elegir uno se rellenan el puesto, la cabecera y el perfil.
function cargarPuestos(){
    var area = document.getElementById('usr_depe').value;
    var cid  = document.getElementById('cargo_id') ? document.getElementById('cargo_id').value : 0;
    nuevoAjax('div_cmb_puesto', 'GET', 'puestos_combo_ajax.php', 'area='+area+'&cargo_id='+cid);
    return;
}
function aplicarPuesto(sel){
    var o = sel.options[sel.selectedIndex];
    var cid = o.value;
    var perfilSel = document.getElementById('usr_perfil');
    if (document.getElementById('cargo_id'))
        document.getElementById('cargo_id').value = (cid && cid != '0') ? cid : '';
    if (cid && cid != '0') {
        document.getElementById('usr_cargo').value = o.getAttribute('data-nombre') || '';
        var cab = o.getAttribute('data-cabecera');
        document.getElementById('usr_cargo_cabecera').value = (cab && cab != '') ? cab : (o.getAttribute('data-nombre') || '');
        var tipo = o.getAttribute('data-tipo');
        // El perfil (Jefe/Normal) lo define el puesto: se fija y se bloquea.
        if (perfilSel && tipo !== null && tipo !== '') {
            perfilSel.value = tipo;
            perfilSel.disabled = true;
        }
        avisar_puesto_duplicado();
    } else {
        // Sin puesto elegido, el perfil vuelve a estar disponible.
        if (perfilSel) perfilSel.disabled = false;
    }
    return;
}

nuevoAjax('usr_ciu', 'GET', 'ciudad_ajax.php', 'area=<?php echo (int)$usr_depe; ?>&codigo=<?php echo (int)$codi_ciudad; ?>');
nuevoAjax('div_cmb_puesto', 'GET', 'puestos_combo_ajax.php', 'area=<?php echo (int)$usr_depe; ?>&cargo_id=<?php echo (int)($cargo_id ?? 0); ?>');

// Buscadores para los combos de Dependencia y Puesto. El de dependencia es fijo;
// el de puesto se re-mejora solo cada vez que el AJAX recarga su combo.
if (typeof mejorarSelect === 'function') {
    mejorarSelect(document.getElementById('usr_depe'));
    observarSelect('div_cmb_puesto', 'cmb_puesto');
}

// Al cargar (edición), si el usuario ya tiene un puesto del catálogo, el perfil
// se muestra bloqueado (lo gobierna el puesto).
(function () {
    var cid = document.getElementById('cargo_id');
    var perfilSel = document.getElementById('usr_perfil');
    if (perfilSel && cid && cid.value && cid.value != '0') perfilSel.disabled = true;
})();

    // Validar el tipo de archivo que se esta ingresando y copiar su nombre

    var tipo= new Array();
//    tipo[0]='png';
    tipo[0]='gif';
    tipo[1]='jpg';
    tipo[2]='jpeg';

     var marcado = 0,marcadoF="";
    function Obtener_val(formulario){
            marcado=formulario.checked
            
            if(marcado==true)
                document.getElementById("usr_area_responsable").value=1;
            else(marcado==false)
                document.getElementById("usr_area_responsable").value=0;
            //marcadoF=marcado;
            //alert(document.getElementById("usr_area_responsable").value);
        }

    function escogio_archivo()	//ALMACENA EL NOMBRE DEL ARCHIVO Y MUESTA UNA NUEVA FILA
    {
        mensaje = '';
        //var valArchivo = '0';
        arch = document.getElementById('firmaDigitalizada').value.toLowerCase();
        arch = arch.replace(/.p7m/g, "");
        arr_ext = arch.split('.');
        cadena = arr_ext[arr_ext.length-1].toLowerCase();
        flag=true;
        for (j = 0;j < tipo.length; ++j) {
            if (tipo[j]==cadena) {
                flag=false;
                document.getElementById('extarch').value = cadena;
            }
        }
        if (flag) {
            alert ('No está permitido anexar archivos con extensión '+cadena+'.\n'+mensaje+' Consulte con su administrador del sistema.');
            document.getElementById('firmaDigitalizada').value = '';
            document.getElementById('nombarch').value = '';
            return;
        }
        document.getElementById('nombarch').value = document.getElementById('firmaDigitalizada').value;
        return;
    }
    
   
    function copiar_cargo_cabecera() {
        document.getElementById('usr_cargo_cabecera').value=ulCase(document.getElementById('usr_cargo').value);
    }

    function cambiar_sumilla() {
        var sumilla = trim(document.getElementById('usr_sumilla').value);
        if (sumilla.length<=2) {
            sumilla = trim(document.getElementById('usr_nombre').value).substring(0,1);
            sumilla += trim(document.getElementById('usr_apellido').value).substring(0,1);
            document.getElementById('usr_sumilla').value = sumilla.toLowerCase();
        }
        return;
    }

    function validar_cambio_cedula() {
        cedula = document.getElementById('usr_cedula').value;
        if(document.frmCrear.usr_tipo_ident.checked){
            var_tipo_identificacion = 1;
            document.getElementById('usr_cedula').maxLength = 19;
        }
        else{
            var_tipo_identificacion = 0;
            document.getElementById('usr_cedula').maxLength = 10;
        }
        document.getElementById('usr_tipo_id').value = var_tipo_identificacion;
        if (trim(cedula)!='') {
            //nuevoAjax('div_datos_registro_civil', 'POST', 'validar_datos_registro_civil.php', 'cedula='+cedula+'&tipo_identificacion='+var_tipo_identificacion);
            nuevoAjax('div_datos_usuario_multiple', 'POST', 'validar_datos_usuario_multiple.php', 'usr_codigo=<?php echo (int)$usr_codigo; ?>&cedula='+cedula);
            autocompletar_por_cedula(trim(cedula));
        }
    }

    // Puestos (cargo_id) que la cédula ya ocupa (para avisar de duplicado).
    var cargos_usados_cedula = [];
    // Puesto activo de la persona en la estructura orgánica (texto) o ''. El
    // bloqueo se valida al grabar, sólo si el área elegida es de la estructura.
    var puesto_estructura_cedula = '';

    // Si la cédula ya corresponde a un usuario interno, trae y rellena sus datos
    // personales (misma persona, otra cuenta). No toca el área ni el puesto.
    function autocompletar_por_cedula(cedula) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'datos_usuario_por_cedula.php?usr_codigo=<?php echo (int)$usr_codigo; ?>&cedula=' + encodeURIComponent(cedula), true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState != 4 || xhr.status != 200) return;
            var d;
            try { d = JSON.parse(xhr.responseText); } catch (e) { return; }
            if (!d || !d.existe) { cargos_usados_cedula = []; puesto_estructura_cedula = ''; return; }
            cargos_usados_cedula = d.cargos_usados || [];
            puesto_estructura_cedula = d.puesto_estructura || '';
            // Rellena sólo los campos vacíos, para no pisar lo ya escrito.
            fijar_si_vacio('usr_nombre',   d.nombre);
            fijar_si_vacio('usr_apellido', d.apellido);
            fijar_si_vacio('usr_titulo',   d.titulo);
            fijar_si_vacio('usr_abr_titulo', d.abr_titulo);
            fijar_si_vacio('usr_email',    d.email);
            fijar_si_vacio('usr_direccion', d.direccion);
            fijar_si_vacio('usr_telefono', d.telefono);
            fijar_si_vacio('usr_sumilla',  d.sumilla);
            var ciu = document.getElementById('codi_ciudad');
            if (ciu && (ciu.value == '0' || ciu.value == '') && d.ciu_codi) ciu.value = d.ciu_codi;
            avisar_puesto_duplicado();
        };
        xhr.send(null);
    }
    function fijar_si_vacio(id, val) {
        var el = document.getElementById(id);
        if (el && (el.value == null || trim(el.value) == '') && val != null && val != '') el.value = val;
    }
    // Avisa si la cédula ya ocupa el puesto elegido (mismo cargo = duplicado).
    function avisar_puesto_duplicado() {
        var cg = document.getElementById('cargo_id');
        if (!cg) return true;
        var cargo = 0 + cg.value;
        if (cargo > 0 && cargos_usados_cedula.indexOf(cargo) >= 0) {
            alert('Esta cédula ya tiene una cuenta con este puesto. Elija otro puesto: no puede haber dos cuentas de la misma persona en el mismo puesto.');
            return false;
        }
        return true;
    }
    function verificarResponsable(area,tipo){
      
        if (area!=0)
        nuevoAjax('div_responsable_area', 'GET', 'adm_responsable_area.php', 'dependenciaRes='+area);      
    }
    function eliminarResponsable(usuario,area){
        
        if (usuario!=0)
            nuevoAjax('div_responsable_area', 'GET', 'adm_quitar_responsable_area.php', 'usrResponsable='+usuario);
        verificarResponsable(area);
    }
function administrar(obj, contador){
       usr_codigo = document.getElementById('usr_codigo').value;
       
       if (usr_codigo!=''){
           if (contador<=300)
        ventana_abrir='../usuarios_dependencias/arbol_areascheckbox.php?usr_codigo=' + usr_codigo;
    else
        ventana_abrir='../usuarios_dependencias/cuerpoAreas.php?usr_codigo=' + usr_codigo;
       if (obj.checked==true)  
        window.open(ventana_abrir, 'Administrar Áreas', 'left=150, top=300, width=1050, height=500,scrollbars=yes');
        }
    }
function cargarDatosAjax(){
    accion = <?php echo (int)$accion; ?>;
    usr_codigo = document.getElementById("usr_codigo").value;
    usr_login = document.getElementById("usr_login").value;
    ban_compartida = document.getElementById("ban_compartida").value;
    perfil_inicio = document.getElementById("perfil_inicio").value;
    area_inicio = document.getElementById("area_inicio").value;
    eliminar_compartida = document.getElementById("eliminar_compartida").value;
    cargo_id = document.getElementById("cargo_id").value;
    tiene_subrogacion = document.getElementById("tiene_subrogacion").value;
    usr_cedula = document.getElementById("usr_cedula").value;
    usr_tipo_ident = document.getElementById("usr_tipo_ident").value;
    usr_tipo_id = document.getElementById("usr_tipo_id").value;
    usr_nombre = document.getElementById("usr_nombre").value;
    usr_apellido = document.getElementById("usr_apellido").value;
    //if (document.getElementById("usr_depe"))
     usr_depe = document.getElementById("usr_depe").value;
    
    usr_inst_nombre = document.getElementById("usr_inst_nombre").value;
    codi_ciudad = document.getElementById("codi_ciudad").value;
    cmb_tit = document.getElementById("cmb_tit").value;
    usr_abr_titulo = document.getElementById("usr_abr_titulo").value;
    usr_titulo = document.getElementById("usr_titulo").value;
    usr_email = document.getElementById("usr_email").value;
    usr_cargo = document.getElementById("usr_cargo").value;
    usr_cargo_cabecera = document.getElementById("usr_cargo_cabecera").value;
    usr_direccion = document.getElementById("usr_direccion").value;
    usr_telefono = document.getElementById("usr_telefono").value;
    usr_perfil = document.getElementById("usr_perfil").value;
    usr_sumilla = document.getElementById("usr_sumilla").value;
    usr_celular = document.getElementById("usr_celular").value;
    usr_estado = document.getElementById("usr_estado").value;
    usr_contrasena=0;
    if (document.getElementById("usr_contrasena").checked==true)
        usr_contrasena=1;
    codigo_permisos = document.getElementById("codigo_permisos").value;
    codigo_permisos_eli = document.getElementById("codigo_permisos_eli").value;
    
    if (document.getElementById("firmaDigitalizada"))
     firmaDigitalizada = document.getElementById("firmaDigitalizada").value;
    else
        firmaDigitalizada='';
    usr_obs = document.getElementById("usr_obs").value;
    if (codigo_permisos!='')
    datos="accion="+accion+"&usr_codigo="+usr_codigo+"&usr_login="+usr_login+
        "ban_compartida="+ban_compartida+"&perfil_inicio="+perfil_inicio+
        "&area_inicio="+area_inicio+"&eliminar_compartida="+eliminar_compartida+
        "&cargo_id="+cargo_id+"&tiene_subrogacion="+tiene_subrogacion+"&usr_cedula="+usr_cedula+
        "&usr_tipo_ident="+usr_tipo_ident+"&usr_tipo_id="+usr_tipo_id+"&usr_nombre="+usr_nombre+
        "&usr_apellido="+usr_apellido+"&usr_depe="+usr_depe+"&usr_inst_nombre="+usr_inst_nombre+
        "&codi_ciudad="+codi_ciudad+"&cmb_tit="+cmb_tit+"&usr_abr_titulo="+usr_abr_titulo+
        "&usr_titulo="+usr_titulo+"&usr_email="+usr_email+"&usr_cargo="+usr_cargo+
        "&usr_cargo_cabecera="+usr_cargo_cabecera+"&usr_direccion="+usr_direccion+
        "&usr_telefono="+usr_telefono+"&usr_perfil="+usr_perfil+"&usr_sumilla="+usr_sumilla+
        "&usr_celular="+usr_celular+"&firmaDigitalizada="+firmaDigitalizada+"&usr_obs="+usr_obs+
        "&usr_contrasena="+usr_contrasena+"&usr_estado="+usr_estado+"&codigo_permisos="+codigo_permisos+
        "&codigo_permisos_eli="+codigo_permisos_eli;
    else{
        alert("No ha seleccionado los permisos para el usuario");
        document.getElementById('div_informacion_usr').style.display='none';
        document.getElementById('div_permisos_desp').style.display='';
    }
       
    return datos;
}
function cargarPermiso(obj,id_permiso,objCodigoGuardar,objCodigoEli){  
    
    codigo_permisos = document.getElementById(objCodigoGuardar).value;
    codigo_permisos_eli = document.getElementById(objCodigoEli).value;
    
    if (obj.checked)
    accion_permiso = 1;
    else
        accion_permiso=0;
    
    usr_codigo = document.getElementById("usr_codigo").value;
    msj=document.getElementById(objCodigoGuardar).value;
    var pos = msj.indexOf(","+id_permiso+",");
    
    if (pos==-1){
        document.getElementById(objCodigoGuardar).value = codigo_permisos+','+id_permiso+',';
        msj=document.getElementById(objCodigoEli).value;
        var n = msj.replace(","+id_permiso+",",'');                  
        document.getElementById(objCodigoEli).value = n;
        //var pos2 = msj.indexOf(","+id_permiso+",");
        //alert(pos2)
    }
    else{
        
        msj=document.getElementById(objCodigoGuardar).value;
        pos = msj.indexOf(","+id_permiso+",");
        
            var n = msj.replace(","+id_permiso+",",'');
        document.getElementById(objCodigoGuardar).value = n;
        document.getElementById(objCodigoEli).value = codigo_permisos_eli+','+id_permiso+',';
    }
        
//    if (usr_codigo!=''){
//        datos="accion_permiso="+accion_permiso+"&id_permiso="+id_permiso+"&usr_codigo="+usr_codigo;
//        document.getElementById("codigo_permisos").value = codigo_permisos+','+id_permiso;
//    }
    //nuevoAjax('div_guardar_usr', 'POST', 'ajax_permiso.php', datos);
}
function desactivar(codigo_subrogante,codigo_subrogado){
    desde = "";
    datos = "codigo_subrogante="+ codigo_subrogante+"&codigo_subrogado="+codigo_subrogado;
    if (codigo_subrogante!=''){
        var respuesta = confirm("Desea Desactivar la Subrogación?")
	if (respuesta){
            document.getElementById('div_guardar_usr').style.display='';
            
		nuevoAjax('div_guardar_usr', 'GET', '../subrogacion/desactivar_usuario_subrogante.php', datos);
                
                document.getElementById('div_informacion_usr').style.display='none';
                document.getElementById('div_permisos_desp').style.display='none';
                document.getElementById('botones_accion').style.display='none';
	}	
    }    
}


</script>
<body onload="verificarResponsable(<?php echo (int)$area_inicio; ?>,1);mostrar_div('div_datos_registro_civil');">
  <?php dibujar_loader_pantalla('Guardando usuario...', '/imagenes/escudo_blanco.png'); ?>
  <form name='frmCrear' action="#" method="post" ENCTYPE='multipart/form-data'>
    <?php 
    //obtener los permisos del usuario a editar
    if ($usr_codigo!='')
    $permisos = $ciud->permisosUsr($usr_codigo);
    graficarMenu($usr_codigo,$tiene_subrogacion,$usr_perfil,$usr_depe);
    ?>
    <table width="100%" border="0" align="center" class="t_bordeGris" id="usr_datos">
  	
    <?php 
    //if ($usr_cedula!='' and strlen($usr_cedula)==10)
    echo $ciud->divsInformacionUsrCiud($usr_cedula);?>
        
    <input type='hidden' id="usr_codigo" name='usr_codigo' value='<?=$usr_codigo?>'>
    <input type="hidden" name="usr_login" id="usr_login" value='<?=$usr_login?>'>
    <input type="hidden" name="ban_compartida" id="ban_compartida" value='<?=$ban_compartida?>'>
    <input type="hidden" name="perfil_inicio" id="perfil_inicio" value='<?=$perfil_inicio?>'>
    <input type="hidden" name="area_inicio" id="area_inicio" value='<?=$area_inicio?>'>
    <input type="hidden" name="eliminar_compartida" id="eliminar_compartida" value="N">
    <input type="hidden" name="cargo_id" id="cargo_id" value='<?=(int)($cargo_id ?? 0)?>'>
    <input type="hidden" name="tiene_subrogacion" id="tiene_subrogacion" value='<?=$tiene_subrogacion?>'>
    <input type="hidden" name="codigo_permisos" id="codigo_permisos" value='<?=$permisos?>'/>
    <input type="hidden" name="codigo_permisos_eli" id="codigo_permisos_eli" value=""/>
    
    <tr id="tr_informacion_usr" name="tr_informacion_usr"><td colspan="6" >
    
    <div id="div_informacion_usr" name="div_informacion_usr">
        <table width="100%" border="0" class="borde_tab">
            <tr style="height: 8px;">
                <td class="listado2">
                    <?php graficarTabsMenuUsr($usr_codigo,$tiene_subrogacion,$usr_perfil,$usr_depe,1); ?>
                </td>
            </tr>
            <tr>
                <td>
                    <table width="100%" class="borde_tab">
            <tr>
                            <td width="10%">
                                <input type='checkbox' id='usr_contrasena' name='usr_contrasena' <?=$usr_nuevo?> <?=$read2?> > 
                            </td><td width="30%">Cambio de Contrase&ntilde;a</td><td>Permite Reiniciar la Contraseña</td>
                        </tr>
                        <tr>
                            <td width="10%">
                                <input type='checkbox' id='usr_estado' name='usr_estado' value='1' <?=$usr_estado?> <?=$read4?> onclick='ocultar_combo_usuario_des()'>
                            </td>
                            <td width="30%">                            
                               Usuario Activo <?=$usuarioSubr?> <b>
                            </td><td >Activa Usuario/ Desactiva Usuario </td>

                        </tr>
                        <?php  $usr_codigo = isset($usr_codigo) ? $usr_codigo : "";
                    $usr_depe = isset($usr_depe) ? $usr_depe : "";
                    //inicio IF
                    
                    if (trim($usr_codigo) != "" and trim($usr_depe) != "") { ?>
                        <tr id="tr_estado" name="tr_estado">
                            <td colspan="2">
                            <?php
                                $sql = "select usua_nombre, usua_codi from usuario where depe_codi=$usr_depe and usua_esta=1 and usua_codi<>$usr_codigo and usua_login not like 'UADM%' order by 1 asc";
                    //              echo $sql;
                                $rsCmb = $connHandler->conn->Execute($sql);
                                echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.$rsCmb->GetMenu2("usr_destino", $usr_destino, "0:&lt;&lt; seleccione &gt;&gt;", false,0,"class='select' $read2");
                            ?>
                            </td>
                            <td class="">Usuario al que desea reasignar los documentos pertenecientes al usuario desactivado.</td>
                        </tr>
                    <script type="text/javascript">
                        ocultar_combo_usuario_des();
                    </script>
                    <?php
                    }//fin IF
                    ?>
        </table>
                </td>
            </tr>
        <tr>
        
        <td width="100%" colspan="3" class="listado2">
            <table width="100%" border="0" class="borde_tab">
            <?php
            $nombreSubdo = isset($datosSubdo["nombre"]) ? $datosSubdo["nombre"] : "";
            if ($tiene_subrogacion!=0)
            echo "<tr><td colspan='5' class='listado2'>
            <font size='2'>Usuario $usuarioSubr de ($nombreSubdo)</font></td></tr>";
            ?>
            
        <tr><td colspan="4" class="titulos4" style="text-align:left;padding:4px 8px">Identificaci&oacute;n</td></tr>
            <tr>
                <td class="titulos2" width="15%">* C&eacute;dula </td>
                <td class="listado2" width="35%">
                    <input type="text" name="usr_cedula" id="usr_cedula" value='<?php echo isset($usr_cedula) ? $usr_cedula : ""; ?>' size="<?=$size_txt?>" onchange="validar_cambio_cedula();" maxlength="10" <?php echo $read; ?> >
                    <br>Es Pasaporte<input type="checkbox" name="usr_tipo_ident" id="usr_tipo_ident" value="0" <?php echo $checked_tipo_identificacion?> onchange="validar_cambio_cedula()" <?php echo $var_habilitado?>>                
                    <input type="hidden" name="usr_tipo_id" id="usr_tipo_id" value='<?=$usr_tipo_ident?>' <?php echo $checked_tipo_identificacion?>>
                </td>
                <td class="titulos2" width="15%"> Usuario </td>
                <td class="listado2" width="35%"><?php //if($usr_estado=='checked') echo substr($usr_login,1); else echo "Usuario Inactivo"; ?>
                <?=($usr_estado=='checked') ? substr($usr_login,1):"Usuario Inactivo"; ?>
                </td>
            </tr>
        <tr><td colspan="4" class="titulos4" style="text-align:left;padding:4px 8px">Datos personales</td></tr>
            <tr>
                <td class="titulos2" width="15%">* Nombre &nbsp;&nbsp;&nbsp;
                    <img src="../../iconos/copy.gif" alt="copiar" title="Copiar datos del Registro Civil" onclick="copiar_datos_registro_civil('nombre', 'usr_nombre')">
                </td>
                <td class="listado2" width="35%">
                    <input type="text" name="usr_nombre" id="usr_nombre" onblur="this.value=ulCase(this.value); cambiar_sumilla();" value='<?php echo $usr_nombre; ?>' size="<?=$size_txt?>"  maxlength="100" <?php echo $read; ?>>
                </td>
                <td class="titulos2" width="15%">* Apellido &nbsp;&nbsp;&nbsp;
                    <img src="../../iconos/copy.gif" alt="copiar" title="Copiar datos del Registro Civil" onclick="copiar_datos_registro_civil('nombre', 'usr_apellido')">
                </td>
                <td class="listado2" width="35%">
                    <input type="text" name="usr_apellido" id="usr_apellido" onblur="this.value=ulCase(this.value); cambiar_sumilla();" value='<?php echo $usr_apellido; ?>' size="<?=$size_txt?>"  maxlength="100" <?php echo $read; ?>>
                </td>
            
            </tr>
            <tr>
                <td class="titulos2" width="15%"> Abr. y T&iacute;tulo </td>
                <td class="listado2" width="35%">
                <?php
                $sql_tit = "select tit_nombre || ' - ' || tit_abreviatura, tit_codi from titulo order by split_part(tit_nombre, ' ', 1) asc, split_part(tit_nombre, ' ', 2) asc, tit_nombre asc";
                //$sql_tit = "select tit_nombre, tit_codi from titulo order by split_part(tit_nombre, ' ', 1) asc, split_part(tit_nombre, ' ', 2) asc, tit_nombre asc";

                $prevModeTit = $connHandler->conn->fetchMode;
                $connHandler->conn->SetFetchMode(ADODB_FETCH_NUM);
                $rs_tit = $connHandler->conn->Execute($sql_tit);
                $connHandler->conn->SetFetchMode($prevModeTit);
                    echo $rs_tit->GetMenu2("cmb_tit", (int)($cmb_tit ?? 0), "0:&lt;&lt; seleccione &gt;&gt;", false,0,"id='cmb_tit' class='select' $read3 onchange='cargarDatosTit()'");
                ?>
                    <br>               
                    <input type="text" name="usr_abr_titulo" id="usr_abr_titulo" value='<?=$usr_abr_titulo?>' size="4" maxlength="30" <?php if($_SESSION["usua_codi"]!=0) echo $read3;?>>

                    <input type="text" name="usr_titulo" id="usr_titulo" value='<?php echo $usr_titulo; ?>' size="<?=$size_txt?>"  maxlength="100" <?php echo $read3;?>>
                </td>
                <td class="titulos2" width="15%"> * Correo electr&oacute;nico </td>
                <td class="listado2" colspan="<?=$colspan?>">
                    <input type="text" name="usr_email" id="usr_email" value='<?=$usr_email?>' size="<?=$size_txt?>"  maxlength="50" <?php echo $read; ?> >
                    <!-- onChange="nuevoAjax('div_validar_email', 'POST', 'validar_email.php', 'txt_email='+this.value);" -->
                </td>
            </tr>
        <tr><td colspan="4" class="titulos4" style="text-align:left;padding:4px 8px">Contacto</td></tr>
        <tr>
            <td class="titulos2" width="15%"> Direcci&oacute;n &nbsp;&nbsp;&nbsp;
                <img src="../../iconos/copy.gif" alt="copiar" title="Copiar datos del Registro Civil" onclick="copiar_datos_registro_civil('direccion', 'usr_direccion')">
            </td>
            <td class="listado2" width="35%">
                <input type="text" name="usr_direccion" id="usr_direccion" onblur="this.value=ulCase(this.value)"  value='<?=$usr_direccion?>' size="<?=$size_txt?>"  maxlength="50" <?php echo $read; ?>>
            </td>
            <td class="titulos2" width="15%"> Tel&eacute;fono </td>
            <td class="listado2" width="35%">
                <input type="text" name="usr_telefono" id="usr_telefono" value='<?=$usr_telefono?>' size="<?=$size_txt?>"  maxlength="50" <?php echo $read; ?>>
            </td>
        </tr>
        <tr>
             <td class="titulos2" width="15%">Celular</td>
             <td colspan="3" class="listado2">
                <input type="text" name="usr_celular" id="usr_celular" value='<?=$usr_celular?>' size="10" maxlength="10" <?php echo $read; ?>/>
             </td>
        </tr>
        <tr><td colspan="4" class="titulos4" style="text-align:left;padding:4px 8px">&Aacute;rea y puesto</td></tr>
            <tr>
                <td class="titulos2" width="15%"> <?php echo ($_SESSION["inst_codi"]==1) ? $descEmpresa : "* $descDependencia";?></td>
                <td class="listado2" width="35%">
               <?php
                $depe_codi_admin = obtenerAreasAdmin($_SESSION["usua_codi"],$_SESSION["inst_codi"],$_SESSION["usua_admin_sistema"],$connHandler);
                // Se muestra la ruta jerárquica (depe_ruta) para distinguir una sub área
                // (p. ej. "FCM - VICEDECANATO") de otra con el mismo nombre corto.
                $sql = "select depe_ruta(depe_codi) as depe_nomb, depe_codi from dependencia where depe_estado=1 and inst_codi=".$_SESSION["inst_codi"];
                 if (!empty($depe_codi_admin))
                $sql.=" and depe_codi in ($depe_codi_admin)";
                $sql.=" order by depe_ruta(depe_codi) asc";

                $prevMode = $connHandler->conn->fetchMode;
                $connHandler->conn->SetFetchMode(ADODB_FETCH_NUM);
                $rs = $connHandler->conn->Execute($sql);
                $connHandler->conn->SetFetchMode($prevMode);
                $mostrar_inst = ($_SESSION["inst_codi"]==1) ? "style='display:none'" : "";
                if ($rs && !$rs->EOF) {
                    echo $rs->GetMenu2("usr_depe", (int)($usr_depe ?? 0), "0:&lt;&lt; seleccione &gt;&gt;", false,0,"id='usr_depe' style='width:350px;' class='select' $read2 onchange='cargarCiudad(); cargarPuestos(); verificarResponsable(this.value,1);' $mostrar_inst");
                } else {
                     echo "<input type='hidden' name='usr_depe' id='usr_depe' value='0'>";
                     echo "<span style='color:red'>No user areas found. Please create an area first.</span>";
                }
                $mostrar_inst = ($_SESSION["inst_codi"]==1) ? "" : "style='display:none'";
                ?>
                    <input type="text" name="usr_inst_nombre" id="usr_inst_nombre" value='<?php echo $usr_inst_nombre; ?>' size="<?=$size_txt?>"  maxlength="200" <?php echo "$read $mostrar_inst"; ?>>
                </td>
                <td class="titulos2" width="15%"> * Ciudad </td>
                <td class="listado2" width="35%">
                    <div id='usr_ciu'>
                        <?php
                            $sqlCmbCiu = "select id, nombre from ciudad order by 2";
                            $rsCmbCiu = $connHandler->conn->Execute($sqlCmbCiu);
                            // Manual generation to ensure correct Value/Text mapping
                            echo "<select name='codi_ciudad' id='codi_ciudad' class='select'>";
                            echo "<option value='0'>&lt;&lt; seleccione &gt;&gt;</option>";
                            while ($rsCmbCiu && !$rsCmbCiu->EOF) {
                                // Robust access: Try Associative keys first (Upper/Lower), then Numeric
                                $f = $rsCmbCiu->fields;
                                if (isset($f['ID'])) $val = $f['ID'];
                                elseif (isset($f['id'])) $val = $f['id'];
                                else $val = $f[0];

                                if (isset($f['NOMBRE'])) $txt = $f['NOMBRE'];
                                elseif (isset($f['nombre'])) $txt = $f['nombre'];
                                else $txt = $f[1];

                                $selected = ($val == (int)($codi_ciudad ?? 0)) ? "selected" : "";
                                echo "<option value='$val' $selected>$txt</option>";
                                $rsCmbCiu->MoveNext();
                            }
                            echo "</select>";
                        ?>
                    </div>
                </td>
            </tr>
            <tr>
            <td class="titulos2" width="15%"> * Puesto </td>
            <td class="listado2" width="35%">
                <div id="div_cmb_puesto" style="margin-bottom:4px;"></div>
                <!-- El puesto sólo se elige del combo (catálogo del área); estos campos
                     son de sólo lectura y los rellena aplicarPuesto(). -->
                <input type="text" name="usr_cargo" id="usr_cargo" value='<?=$usr_cargo?>' size="<?=$size_txt?>"  maxlength="200" title="Nombre del puesto que se visualizará en el pie de firma del documento" readonly class="tex_area_ro">
            </td>
            <td class="titulos2" width="15%"> * Puesto Cabecera </td>
             <td class="listado2" width="35%">
                 <input type="text" name="usr_cargo_cabecera" id="usr_cargo_cabecera" value='<?=$usr_cargo_cabecera?>' size="<?=$size_txt?>"  maxlength="200"  title="Nombre del puesto que se visualizará en la cabecera del documento" readonly class="tex_area_ro">
            </td>
        </tr>
         <tr <?php if ($_SESSION["inst_codi"]==1) echo "style='display:none'" ?>>
            <td class="titulos2" width="15%">* Perfil</td>
            <td class="listado2" width="35%">
            <select id="usr_perfil" name="usr_perfil" <?php if ($mensajeSubro=='') echo "class='select'";?> <?=$read4?>>
                <option value='0' <?php if ($usr_perfil==0) echo "selected"?>> Normal </option>
                <option value='1' <?php if ($usr_perfil==1) echo "selected"?>> Jefe </option>
<!--                <option value='2' <?php if ($usr_perfil==2) echo "selected"?>> Asistente </option>-->
            </select>
            </td>
            <td class="titulos2" width="15%"> * Iniciales Sumilla </td>
            <td class="listado2" width="35%">
                <input type="text" name="usr_sumilla" id="usr_sumilla" value='<?=$usr_sumilla?>' size="5" maxlength="5" <?php echo $read; ?>>
                <div id="div_responsable_area" style="width: 100%;"></div>
<!--                <input type="checkbox" name="usr_area_responsable" id="usr_area_responsable" value="0" onclick="Obtener_val(this)" title="Iniciales del usuario que se visualizará el pie de página (mayùculas) de un documento" <?php echo $usr_responsable_area ."  " .$read; ?>/>Responsable de Área-->
               
            </td>      
        </tr>
        <tr><td colspan="4" class="titulos4" style="text-align:left;padding:4px 8px">Vigencia de la cuenta</td></tr>
        <tr>
            <td class="titulos2" width="15%">Fecha inicio</td>
            <td class="listado2" width="35%">
                <input type="date" name="usr_vig_desde" id="usr_vig_desde" value='<?=htmlspecialchars((string)$usr_vig_desde, ENT_QUOTES)?>' <?php echo $read; ?>>
            </td>
            <td class="titulos2" width="15%">Fecha fin</td>
            <td class="listado2" width="35%">
                <input type="date" name="usr_vig_hasta" id="usr_vig_hasta" value='<?=htmlspecialchars((string)$usr_vig_hasta, ENT_QUOTES)?>' <?php echo $read; ?>>
                <br><font size="1">Vac&iacute;o = sin fecha de fin. Fuera de estas fechas la cuenta no puede usarse, y al pasar la fecha fin se desactiva autom&aacute;ticamente.</font>
            </td>
        </tr>
        <tr><td colspan="4" class="titulos4" style="text-align:left;padding:4px 8px">Otros datos</td></tr>
        <?php if($_SESSION['usua_codi'] == '0' and $_SESSION["inst_codi"]>1) { ?>
        <tr>
            <td class="titulos2"> Firma digitalizada </td>
            <td class="listado2" >
                <input id="firmaDigitalizada" name="firmaDigitalizada" type="file" class="tex_area" onChange="escogio_archivo();" size="<?=$size_txt?>" >
                <input type="hidden" name="nombarch" value='<?=$nombarch?>' id="nombarch">
                <input type="hidden" name="extarch" value='<?=$nombarch?>' id="extarch">
            </td>
            <td class="listado2" colspan="2">
                <center>
                <?php if($usr_firma_path!="" and $usr_firma_path!=NULL)  {
                        $urlFirma = $nombre_servidor.'/'.$usr_firma_path;
                ?>
                    <img src="<?=$urlFirma?>" alt="" title="">
                <?php } ?>
                </center>
            </td>
        </tr>        
        <?php } ?>
        <tr valign="middle">
        <td class="titulos2"> Observaci&oacute;n<br>&nbsp;<br>&nbsp;
        </td>
            <td colspan="3">
                <textarea name="usr_obs" id="usr_obs" cols="100" rows="3"><?=$usr_obs?></textarea>
            </td>
        </tr>
            </table>
        </td>
        
        </tr>
        
         <tr><td colspan="3" width="100%" class=""></td></tr>
        </table>
        
    </div>
    </td>
   </tr>
    <tr id="tr_permisos_usr" name="tr_permisos_usr"><td colspan="6" >
    <div id="div_permisos_desp" name="div_permisos_desp" style="display:none">
        <table width="100%" border="0" class="borde_tab">
            <tr>
        <td width="100%" colspan="3" class="listado2">
            <?php graficarTabsMenuUsr($usr_codigo,$tiene_subrogacion,$usr_perfil,$usr_depe,2); ?>
        </td></tr>
        <tr>
        <td width="100%" colspan="3">
            <table width="100%" border="1">
                
                <tr>
                    <td colspan="2" width="50%" rowspan="8" id="usr_permisos">                        
                    <table width="100%">
                        <tr bgcolor="#6A819D">
                            <td  width="10%"><font color="white">Activar</font></td>
                            <td  width="30%"><font color="white">Permiso</font></td>
                            <td><font color="white">Descripción</font></td>
                        </tr>
                        
                        
                    </table>
                    <?php 
                    echo $ciud->dibPerfiles($usr_codigo,$ciud,$countdep,$usr_nuevo,$usr_estado,$usuarioSubr,$read4,$read2);
                    
                    ?>
                    </td>
                </tr>                
            </table>
        </td>
       
        </tr>
        <tr><td colspan="3" width="100%" class=""></td></tr>
        </table>
    </div>
            
           
   </tr></td>
    <tr id="tr_backup" name="tr_backup"><td colspan="6" >
           <div id="div_backup" name="div_backup" style="display:none;">
               <?php include_once(dirname(__DIR__,2).'/backup/respaldo_lista_tab.php');?>
            </div>
            </td>
            </tr>
   <tr><td colspan="6">
           <div id="div_recorrido" name="div_recorrido" style="display:none;">
               <?php include_once "recorrido_usr.php";?>
            </div>
    </td>
    </tr>
    
    </table>
    
    <table width="100%"  border="0" cellpadding="0" cellspacing="0" id="botones_accion" name="botones_accion" >
     
      <tr>
    	
	<td width="<?=$sizebnt?>" align="center">
	    <?php  if ($accion != 3) { ?>
	    	<input name="btn_aceptar" id="btn_aceptar" type="button" class="botones" value="Grabar" onClick="ValidarInformacion();"/>                
	    <?php  } ?>&nbsp;
	</td>
        <?php if ($tiene_subrogacion==1){?>
	<td  width="<?=$sizebnt?>" align="center">
            <?php if ($usr_perfil!=1 and $usr_subrogado>0){?>
	    <input  name="btn_accion" type="button" class="botones_largo" value="Desactivar Subrogación" onclick="desactivar(<?=$usr_codigo?>,<?=$usr_subrogado?>);"/>
            <?php }?>
	</td>
        <?php } ?>
        <td  width="<?=$sizebnt?>" align="center">
	    <input  name="btn_accion" type="button" class="botones" value="Regresar" onclick="window.location='./cuerpoUsuario.php?accion=2.php'"/> <!--location='./mnuUsuarios.php'-->
	</td>
      </tr>
           
    </table> 
      <table width='100%'>
                  <tr><td colspan="6"><div id="div_guardar_usr" name="div_guardar_usr" style="display:none"></div></td></tr>
              </td></tr></table>
    
  </form>
    <script type="text/javascript">
	function ver_datos() {
	    document.getElementById('usr_datos').style.display='';
	    //document.getElementById('usr_permisos').style.display='none';
        document.getElementById('usr_permisos').style.display='';
	    //document.getElementById('btn_anterior').style.display='none';
	    //document.getElementById('btn_siguiente').style.display='';
	    <?php  if ($accion != 3) { ?>
	    	document.getElementById('btn_aceptar').style.display='';
	    <?php  } ?>
	}
	function ver_permisos() {
	    /*if (ValidarInformacion()) {*/
	    	document.getElementById('usr_datos').style.display='none';
	    	document.getElementById('usr_permisos').style.display='';
	    	//document.getElementById('btn_anterior').style.display='';
	    	//document.getElementById('btn_siguiente').style.display='none';
	    	<?php  if ($accion != 3) { ?>
		    document.getElementById('btn_aceptar').style.display='';
	    	<?php  } ?>
	    /*}*/
	}
        
	function administracion_viajes() {          
            var x = (screen.width - 1100) / 2;
            var y = (screen.height - 600) / 2;
            ventana = window.open('<?=(isset($servidor_viajes)?$servidor_viajes:"")?>/administracion/usuario_permiso_session.php?datos=<?=(isset($_SESSION["usua_doc"])?$_SESSION["usua_doc"]:"")."¬".(isset($_SESSION["drde"])?$_SESSION["drde"]:"")."¬";?>'+document.getElementById('usr_cedula').value,'Solicitud de Viajes','toolbar=no,dire ctories=no,menubar=no,status=no,scrollbars=yes, width=1100, height=600');
            ventana.moveTo(x, y);
            ventana.focus();
	}

        function seleccionar_permiso(permiso) {
            if (document.getElementById('usr_permiso_'+permiso).checked){
                document.getElementById('div_permiso_'+permiso).style.display = '';                
            }
            else
                document.getElementById('div_permiso_'+permiso).style.display = 'none';

            if(permiso == 33)
                permiso_respaldo(permiso);
            
            return;
        }

        function permiso_respaldo(permiso) {
            
            var x = (screen.width - 700) / 2;
            var y = (screen.height - 800) / 2;
            usr_codigo = document.getElementById('usr_codigo').value;
            if (usr_codigo!=''){
                ventana = window.open('../../backup/permiso_autoriza.php?dat='+usr_codigo+'&per='+permiso,'Permiso de respaldos','toolbar=no,dire ctories=no,menubar=no,status=no,scrollbars=yes, width=600, height=700');
                ventana.moveTo(x, y);
                ventana.focus();
            }else{
                //se procede a guardar el usuario
                alert("Por favor hagla clic en guardar");
            }
                
        }

<?php
    $sql = "select descripcion, tipo_cert_codi from tipo_certificado where estado=1 and tipo_cert_codi>0 order by 2 asc";
    $prevModeTc = $connHandler->conn->fetchMode;
    $connHandler->conn->SetFetchMode(ADODB_FETCH_NUM);
    $rs_tc = $connHandler->conn->Execute($sql);
    $connHandler->conn->SetFetchMode($prevModeTc);
    $cmb_tipo_certificado = $rs_tc->GetMenu2("usr_tipo_certificado", (int)($usr_tipo_certificado ?? 0), "", false,0,"class='select' $read2");
    $cmb_tipo_certificado = str_replace("\n"," ",str_replace("'",'"',$cmb_tipo_certificado));
?>

        function cargar_combo_firma() {
            if (document.getElementById('usr_permiso_19').checked) {
                document.getElementById('div_permiso_19').style.display = '';
            }
            document.getElementById('div_permiso_19').innerHTML =
                '<table border="0" width="100%"><tr><td>Seleccione el tipo de certificado:</td><td><?=$cmb_tipo_certificado?></td></tr></table>';
        }
        
	ver_datos();
        validar_cambio_cedula();
        cargar_combo_firma();        

    </script>
</body>
</html>