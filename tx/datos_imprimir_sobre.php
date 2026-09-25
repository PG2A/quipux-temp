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
require_once(dirname(__DIR__)."/funciones.php");
include_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__).'/obtenerdatos.php');

p_register_globals($_POST);

$usua_dest = ObtenerDatosUsuario($_GET['txt_usua_codi'], $db);

$registroOpcImpr = ObtenerDatosOpcImpresion($_GET['nume_radi_temp'], $db);
$radicadoSobre=ObtenerDatosRadicado($_GET['nume_radi_temp'], $db);
//Si es funcionario = 1 o ciudadano = 2
$tipoUsuario = $usua_dest["tipo_usuario"] ?? 0;
//verificar si el ciudadano tiene permisos de edicion
$inst_codi_ciu = $usua_dest["inst_codi"] ?? 0;

// Solo se asignaba dentro de las ramas de tipoUsuario 1 y 2; para cualquier otro
// destinatario quedaba indefinida y se imprimia como variable no declarada.
$codi_ciudad = 0;

if($tipoUsuario==1) //Obtener ID de ciudad para funcionario
{
    $sqlCiu = "select ciu_codi from usuarios where usua_codi = ".((int)($_GET['txt_usua_codi'] ?? 0));
    
    $rsCiu = $db->query($sqlCiu);
    $codi_ciudad = ($rsCiu && !$rsCiu->EOF) ? ($rsCiu->fields["CIU_CODI"] ?? 0) : 0;
    if ($codi_ciudad > 0) {
        $sqlCiuNom = "Select nombre from ciudad where id = ".$codi_ciudad;
        $rsCiuNom = $db->query($sqlCiuNom);
        $ciudad = ($rsCiuNom && !$rsCiuNom->EOF) ? ($rsCiuNom->fields["NOMBRE"] ?? '') : '';
    } else {
        $ciudad = '';
    }
}
else if($tipoUsuario==2) //Obtener ID de ciudad para ciudadano
    {
        $sqlCiu = "select ciudad_codi from ciudadano where ciu_codigo = ".((int)($_GET['txt_usua_codi'] ?? 0));
        
        $rsCiu = $db->query($sqlCiu);
        $codi_ciudad = ($rsCiu && !$rsCiu->EOF) ? ($rsCiu->fields["CIUDAD_CODI"] ?? 0) : 0;
        if ($codi_ciudad > 0) {
            $sqlCiuNom = "Select nombre from ciudad where id = ".$codi_ciudad;
            $rsCiuNom = $db->query($sqlCiuNom);
            $ciudad = ($rsCiuNom && !$rsCiuNom->EOF) ? ($rsCiuNom->fields["NOMBRE"] ?? '') : '';
        } else {
            $ciudad = '';
        }
    }
//Para rescatar el parametro del codigo de ciudadano


//Opciones de Impresión
$titulo = $usua_dest['titulo'] ?? '';
if (trim($titulo)=='')
    $titulo='Sin título';
$nombre = trim(($usua_dest['usua_nombre'] ?? '') . ' ' . ($usua_dest['usua_apellido'] ?? ''));
if (trim($ciudad ?? '')=='')
 $ciudad = "Sin ciudad";
$telefono = "Sin tel&eacute;fono";
$cargo = "Sin Puesto";
$empresa = $usua_dest['institucion'] ?? '';

//Tomar datos directos del usuario o ciudadano
if(trim($usua_dest['direccion'] ?? '')!='')
    $direccion = $usua_dest['direccion'] ?? '';

//if(trim($usua_dest['ciudad'] ?? '')!='')
//    $ciudad = $usua_dest['ciudad'];

if(trim($usua_dest['telefono'] ?? '')!='')
    $telefono = $usua_dest['telefono'] ?? '';

if($tipoUsuario==1){//Funcionario
    if (($radicadoSobre["radi_tipo"] ?? '')==1){
       if(trim($usua_dest['cargo_cabecera'] ?? '')!='')
            $cargo = $usua_dest['cargo_cabecera'] ?? '';
    }else{
        if(trim($usua_dest['cargo'] ?? '')!='')
            $cargo = $usua_dest['cargo'] ?? '';
    }
}elseif($tipoUsuario==2){//Ciudadano
    if(trim($usua_dest['cargo'] ?? '')!='')
        $cargo = $usua_dest['cargo'] ?? '';
}

//Si tiene registro en opciones de impresion tomar informacion del registro encontrado
if($registroOpcImpr)
{
   
    if(($registroOpcImpr["TITULO_NATURAL"] ?? '')!="")
        $titulo = $registroOpcImpr["TITULO_NATURAL"].' ';

    if(($registroOpcImpr["FIRMANTES"] ?? '')!="")
        $nombre .= " ".$registroOpcImpr["FIRMANTES"];

    if(($registroOpcImpr["EXT_INSTITUCION"] ?? '')!="")
        $empresa = $empresa.' '.$registroOpcImpr["EXT_INSTITUCION"];

    if(($registroOpcImpr["DIRECCION"] ?? '')!="")
        $direccion = $registroOpcImpr["DIRECCION"];
    else //if(trim($registroOpcImpr["DESTINO_DESTINATARIO"] ?? '')!="" and trim($direccion ?? '')=="")
        $direccion = $registroOpcImpr["DESTINO_DESTINATARIO"] ?? '';
    if(trim($direccion ?? '')=="")
    $direccion = $usua_dest['direccion'] ?? '';
    
    if(($registroOpcImpr["CIUDAD"] ?? '')!="")
        $ciudad = $registroOpcImpr["CIUDAD"];
    
    if(($registroOpcImpr["TELEFONO"] ?? '')!="")
        $telefono = $registroOpcImpr["TELEFONO"];
    if (($registroOpcImpr["CARGO_CABECERA"] ?? '')!="")
        $cargo=$registroOpcImpr["CARGO_CABECERA"];
}
  if(trim($direccion ?? '')=="")
    $direccion = "Sin dirección";


//Si tiene informacion del usuario para opciones de impresion de sobre tomar los datos del registro
if(trim($_GET['nume_radi_temp'] ?? '')!="" and $tipoUsuario==1)
{
    //Opciones de impresion para sobre
    $usuaCodi = $_GET['txt_usua_codi'] ?? 0;
    
    $rsOpcImpSobre = ObtenerDatosOpcImpresionSobre($_GET['nume_radi_temp'],$usuaCodi,$db);

    if($rsOpcImpSobre && ($rsOpcImpSobre["OPC_IMP_SOB_CODI"] ?? null)){

        $opcImpSobCodi = $rsOpcImpSobre["OPC_IMP_SOB_CODI"];

        if(($rsOpcImpSobre["DIRECCION"] ?? '')!="")
            $direccion = $rsOpcImpSobre["DIRECCION"];

        if(($rsOpcImpSobre["CIUDAD"] ?? '')!="")
            $ciudad = $rsOpcImpSobre["CIUDAD"];
      

        if(($rsOpcImpSobre["TELEFONO"] ?? '')!="")
            $telefono = $rsOpcImpSobre["TELEFONO"];
    }
}
  // Esta comprobacion miraba $codi_ciudad -la ciudad de la ficha del destinatario- y
  // sobrescribia $ciudad, que para entonces ya podia venir resuelta desde las opciones
  // de impresion del documento o del sobre. El resultado era "Sin ciudad" en el sobre
  // aunque el dato correcto ya estuviera calculado. El respaldo debe mirar el valor que
  // realmente se va a imprimir.
  if (trim((string)($ciudad ?? ''))=='')
      $ciudad='Sin ciudad';
?>
<table border="0" cellpadding="5" width="99%">
    <tbody>
        <tr>
            <td width="100%">
            <fieldset class="borde_tab">
                <legend> Datos a Imprimir </legend>
                <table width="100%" border="0" cellspacing="0" cellpadding="3" rules="rows">                    
                    <input id="hd_ciu_codi" type="hidden" value="<?=$codi_ciudad?>">
                    <input id="hd_tipo_usuario" type="hidden" value="<?=$tipoUsuario?>">
                    <input id="hd_usua_direccion" type="hidden" value="<?=$direccion?>">
                    <input id="hd_usua_ciudad" type="hidden" value="<?=$ciudad?>">
                    <input id="hd_usua_telefono" type="hidden" value="<?=$telefono?>">
                    <input id="hd_inst_codi" type="hidden" value="<?=$inst_codi_ciu?>">                    
                    <tr>
                        <td class='listado1' width="15%">
                            <input type="checkbox" name="chk_datos_sobre" id="chk_datos_sobre" value="chk_titulo" checked>T&iacute;tulo
                        </td>
                        <td class='listado1' width="25%">
                            <input id="usua_titulo" class="text_transparente" type="text" value="<?=$titulo?>" size="50" readonly>
                        </td>
                        <td class='listado1' width="7%">
                            <input type="checkbox" name="chk_datos_sobre" id="chk_datos_sobre" value="chk_direccion" checked>Direcci&oacute;n                           
                        </td>
                        <td class='listado1' width="7%">
                            <img src="../imagenes/internas/application_home.png" name="Image1" align="middle" border="0" title="Añade la ciudad ó dirección del funcionario ó ciudadano (Destinatario)" onclick="habilitaObj('ver')">
                            <?php //if ($inst_codi_ciu==0){ ?>
                            <img src="../imagenes/internas/pencil_add.png" onclick="habilitaObj('d');" name="Image1" align="middle" border="0" title="Editar dirección" alt="Editar">
                            <?php //} ?>
                        </td>
                        <td class='listado1' width="50%">
                            <input id="usua_direccion_original" type="hidden" value="<?=$usua_dest['direccion']?>">
                            <input id="usua_direccion" class="text_transparente" type="text" value="<?=$direccion?>" size="50" maxlength="350" readonly onblur="deshabilitaObj('d'); cambiar_editor_sobre(1); imprimir_sobre(2);">
                        </td>
                    </tr>
                    <tr>
                        <td class='listado1'>
                            <input type="checkbox" name="chk_datos_sobre" id="chk_datos_sobre" value="chk_nombre" checked>Nombre Completo
                        </td>
                        <td class='listado1'>
                            <input id="usua_nombre" class="text_transparente" type="text" value="<?=$nombre?>" size="50" readonly>
                        </td>
                        <td class='listado1'>
                            <input type="checkbox" name="chk_datos_sobre" id="chk_datos_sobre" value="chk_ciudad"    checked>Ciudad
                        </td>
                        <td class='listado1'>
                            <?php //if ($inst_codi_ciu==0){ ?>
                            <img src="../imagenes/internas/pencil_add.png" onclick="habilitaObj('c')" name="Image1" align="middle" border="0" title="Editar ciudad" alt="Editar">
                            <?php //} ?>
                        </td>
                        <td class='listado1'>
                            <input id="usua_ciudad" class="text_transparente" type="text" value="<?=$ciudad?>" size="50" readonly onblur="deshabilitaObj('c');">
                            <?php               
                            
                            $sqlCmbCiu = "select nombre, id from ciudad order by 1";                            
                            $rsCmbCiu = $db->conn->Execute($sqlCmbCiu);
                            
                            //solo guarda el tipo 2 en la funcion imprimir_sobre
                            $cod_ciu_sel = $codi_ciudad ?? 0;
                            echo "<select id='codi_ciudad' name='codi_ciudad' class='select' style='display:none' onblur='cambiar_editor_sobre(2); imprimir_sobre(2); deshabilitaObj(\"c\");'>";
                            echo "<option value='0'>&lt;&lt; seleccione &gt;&gt;</option>";
                            if ($rsCmbCiu && !$rsCmbCiu->EOF) {
                                while (!$rsCmbCiu->EOF) {
                                    $cid = $rsCmbCiu->fields['ID'] ?? $rsCmbCiu->fields[1] ?? 0;
                                    $cnom = $rsCmbCiu->fields['NOMBRE'] ?? $rsCmbCiu->fields[0] ?? '';
                                    $sel = ($cid == $cod_ciu_sel) ? "selected" : "";
                                    echo "<option value='$cid' $sel>$cnom</option>";
                                    $rsCmbCiu->MoveNext();
                                }
                            }
                            echo "</select>";
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class='listado1'>
                            <input type="checkbox" name="chk_datos_sobre" id="chk_datos_sobre" value="chk_cargo"  checked>Puesto
                        </td>
                        <td class='listado1'>
                            <input id="usua_cargo" class="text_transparente" type="text" value="<?=$cargo?>" size="50" readonly>
                        </td>
                        <td class='listado1'>
                            <input type="checkbox" name="chk_datos_sobre" id="chk_datos_sobre" value="chk_telefono"  checked>Tel&eacute;fono
                        </td>
                        <td class='listado1'>
                            <?php //if ($inst_codi_ciu==0){ ?>
                            <img src="../imagenes/internas/pencil_add.png" onclick="habilitaObj('t')" name="Image1" align="middle" border="0" title="Editar teléfono" alt="Editar">
                            <?php //} ?>
                        </td>
                        <td class='listado1'>
                            <input id="usua_telefono" class="text_transparente" type="text" value="<?=$telefono?>" size="50" maxlength="250" readonly onblur="deshabilitaObj('t'); cambiar_editor_sobre(1); imprimir_sobre(2);">
                        </td>
                    </tr>
                    <tr>
                        <td class='listado1'>
                            <input type="checkbox" name="chk_datos_sobre" id="chk_datos_sobre" value="chk_empresa"   checked>Empresa
                        </td>
                        <td class='listado1'>
                            <input id="usua_empresa" class="text_transparente" type="text" value="<?=$empresa?>" size="50" readonly>
                        </td>
                        <td class='listado1' colspan="3"></td>
                    </tr>
                </table>
            </fieldset>
            </td>
        </tr>
    </tbody>
</table>