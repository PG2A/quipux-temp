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
 * @package    subrogacion
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function restaFechas($dFecIni, $dFecFin)
{
    $dFecIni = str_replace("-","",$dFecIni);
    $dFecIni = str_replace("/","",$dFecIni);
    $dFecFin = str_replace("-","",$dFecFin);
    $dFecFin = str_replace("/","",$dFecFin);

    $aFecIni = [];
    $aFecFin = [];
    preg_match( "/([0-9]{1,2})([0-9]{1,2})([0-9]{2,4})/", $dFecIni, $aFecIni);
    preg_match( "/([0-9]{1,2})([0-9]{1,2})([0-9]{2,4})/", $dFecFin, $aFecFin);

    $date1 = mktime(0,0,0,$aFecIni[2], $aFecIni[1], $aFecIni[3]);
    $date2 = mktime(0,0,0,$aFecFin[2], $aFecFin[1], $aFecFin[3]);

    return round(($date2 - $date1) / (60 * 60 * 24));
}

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
include_once(dirname(__DIR__, 2)."/obtenerdatos.php");
include_once(dirname(__DIR__, 2)."/funciones.php");
if (file_exists(dirname(__DIR__, 2)."/functions.php")) include_once(dirname(__DIR__, 2)."/functions.php");




// Validar funciones de limpieza
if (!function_exists('limpiar_sql')) {
    function limpiar_sql($val) {
        $val = trim($val ?? '');
        // Basic sanitization similar to what is expected
        $val = str_replace(array("'", '"', ";", "--"), "", $val);
        return $val;
    }
}

// Get parameters
$buscar_nom = trim(limpiar_sql($_POST["buscar_nom"] ?? ''));
$buscar_car = trim(limpiar_sql($_POST["buscar_car"] ?? ''));
$buscar_inst = trim(limpiar_sql($_POST["buscar_inst"] ?? '0'));
$buscar_depe = trim(limpiar_sql($_POST["buscar_depe"] ?? '0'));
$buscar_tipo = trim(limpiar_sql($_POST["buscar_tipo"] ?? '0'));
$lista_usr = trim(limpiar_sql($_POST["lista_usr"] ?? '0'));
$descCargo = "Cargo"; // Initialize for display

if (!$buscar_inst) $buscar_inst="0";
if (!$buscar_depe) $buscar_depe="0";
if (!$lista_usr) $lista_usr="0";
$usuarios_lista = "";

?>

<table class=borde_tab width="100%" cellpadding="0" cellspacing="4">
    <tr class="grisCCCCCC" align="center">
        <td width="2%" class="titulos5">Tipo</td>
        <td width="10%" class="titulos5">Nombres</td>
        <td width="10%" class="titulos5">Instituci&oacute;n</td>
        <td width="5%"  class="titulos5">Estado</td>
        <td width="5%"  class="titulos5">T&iacute;tulo</td>
        <td width="10%"  class="titulos5"><?=$descCargo?></td>
        <td width="10%" class="titulos5">&Aacute;rea</td>
        <td width="8%"  class="titulos5">E-mail</td>
        <td colspan="5" class="titulos5">Definir como</td>
    </tr>

<?php


$sql="";

$depe_codi_admin = obtenerAreasAdmin($_SESSION["usua_codi"],$buscar_inst,$_SESSION["usua_admin_sistema"],$db);

if (($buscar_nom!="" or $buscar_car!="" or $buscar_inst!="0" or $buscar_depe!="0") and $lista_usr=="0") {

    $sql = "select u.*, i.inst_nombre, i.inst_sigla, d.depe_nomb
            from usuario u 
            left outer join usuarios_sesion g on u.usua_codi=g.usua_codi
            left outer join institucion i on u.inst_codi=i.inst_codi
            left outer join dependencia d on u.depe_codi=d.depe_codi
            where  ";
    // Manually qualify columns to avoid ambiguity
    $sql .= ' ((' . buscar_cadena($buscar_nom, "u.usua_nombre") . ') or (' . buscar_cadena($buscar_nom, "u.usua_cedula") . '))';
    
    $sql .= ' and ' . buscar_cadena($buscar_car, "u.usua_cargo");    
    if ($buscar_tipo != 0) 
        $sql .= " and u.tipo_usuario='$buscar_tipo' ";

    if ($buscar_inst != "0" && ($buscar_tipo == 1 || $buscar_tipo == 0)) {
        $sql .= " and u.inst_codi=$buscar_inst";
    }

    if ($buscar_depe != 0) 
        $sql .= " and u.depe_codi=$buscar_depe";
    else
        // Ensure depe_codi_admin is not empty or equal to 0 before adding the IN clause
        if (!empty($depe_codi_admin) && $depe_codi_admin != "0")
        $sql .= " and u.depe_codi in ($depe_codi_admin)";
    
    $sql .= " and upper(u.usua_login) not like 'UADM%' and u.usua_codi>0";
   
    $sql .= " order by u.depe_codi,u.usua_nombre";
    
//echo $sql;
}else{
     echo "<tr><td colspan=12><font color='red'><center>Ingrese Cédula o Nombre</center></font></td>";
}


if ($sql!="") {
    $rs=$db->query($sql);
    if (!$rs) {
        $error_msg = $db->conn->ErrorMsg();
        echo "<tr><td colspan=12><span class='titulosError'>Error en la consulta: $error_msg <br> SQL: $sql</span></td></tr>";
    } else {
    $i=0;

    if ($rs->EOF) {
        if ($lista_usr=="0")
            echo "<tr><td colspan=12><center><span class='titulosError'>No se encontraron Usuarios con ese Nombre o número de CI</span></center></td></tr>";
        else
            echo "<tr><td colspan=12><center><span class='titulosError'>La lista se encuentra vac&iacute;a</span></center></td></tr>";
    }
  $enSubrogacion = "";
  $dependencia_color = ""; // Initialize variable
    while(!$rs->EOF)
    {
        $codigo = trim($rs->fields["USUA_CODI"] ?? '');
        $usua_login = trim ($rs->fields['USUA_LOGIN'] ?? '');
        $inactivo_sub = trim ($rs->fields['VISIBLE_SUB'] ?? '');
        //COMPROBAR SI NO ESTA EN LA TABLA DE SUBROGACION COMO ACTIVO (VISIBLE = 1)
        $sqlSubrogante = "select usua_subrogante from usuarios_subrogacion where usua_subrogante = $codigo and usua_visible = 1";
        //echo $sqlSubrogante."<br>";
        $rsSubrogante=$db->conn->query($sqlSubrogante);
        $subrogante_activo = ($rsSubrogante && !$rsSubrogante->EOF) ? trim($rsSubrogante->fields["USUA_SUBROGANTE"] ?? '') : '';
        
        $sqlSubrogado = "select usua_subrogado from usuarios_subrogacion where usua_subrogado = $codigo and usua_visible = 1";
        $rsSubrogado=$db->conn->query($sqlSubrogado);
        //echo $sqlSubrogado."<br>";
        $subrogado_activo = ($rsSubrogado && !$rsSubrogado->EOF) ? trim($rsSubrogado->fields["USUA_SUBROGADO"] ?? '') : '';
       
        
            $tipous = trim($rs->fields["USUA_TIPO"] ?? '');
            $cargo_tipo = trim ($rs->fields["CARGO_TIPO"] ?? '');
            $dependencia_cod = trim($rs->fields["DEPE_CODI"] ?? '');
            $usuarios_lista .= '-'.$codigo.'-';
            $img_imagen = "<img src='../../imagenes/internas/user3.jpg' name='img_uso' border='0' title='Sin Uso'>";
            // Check for USO existence before using
            $uso = $rs->fields["USO"] ?? 999;
            if ($uso <= 50)
                $img_imagen = "<img src='../../imagenes/internas/user2.jpg' name='img_uso' border='0' title='".$uso." d&iacute;as sin uso'>";
            if ($uso <= 7)
                $img_imagen = "<img src='../../imagenes/internas/user1.jpg' name='img_uso' border='0' title='".$uso." d&iacute;as sin uso'>";

            //Imprimir en resultado siglas de la institución a la que pertenece el usuario
            if(($rs->fields["INST_SIGLA"] ?? "") != "")
                $sigla_institucion = ' / '.$rs->fields["INST_SIGLA"];
            else
                $sigla_institucion = "";
            if (($rs->fields["USUA_ESTA"] ?? 0)==1){
                    $estadoUsr = "Activo"; 
                    $colorus='black';
                    $estadoAsubrogar=1;
            }
            else{ 
                $estadoUsr = "Inactivo";
                $colorus='red';
                $estadoAsubrogar=0;
                if ($inactivo_sub==0){
                    $estadoUsr = $estadoUsr." Por Subrogación";
                    $estadoAsubrogar=1;
                    $colorus='black';
                }
            }

            
    ?>
        <tr onmouseover="this.style.background='#e3e8ec'" onmouseout="this.style.background='white', this.style.color='black'">
            <?php if ($dependencia_cod!=$dependencia_color and $i!=0){?>
                <tr bgcolor="#E2E7EB"><td colspan="10"><hr></td></tr>
            <?php }?>
            <td><font size=1><?php  if (($rs->fields["TIPO_USUARIO"] ?? 1)==1) echo "<i>(Serv.)</i>"; else echo "<i>(Ciu.)</i>";?></font></td>
            <td><font size=1><?=substr($rs->fields["USUA_NOMBRE"] ?? '',0,120).$sigla_institucion ?></font></td>
            <td><font size=1><?=substr($rs->fields["INST_NOMBRE"] ?? '',0,100) ?></font></td>
            <td><font size=1 color='<?=$colorus?>'><?=$estadoUsr?></font></td>
            <td><font size=1><?=substr($rs->fields["USUA_TITULO"] ?? '',0,70) ?></font></td>
            <td><font size=1><?=$rs->fields["USUA_CARGO"] ?? '' ?> </font></td>
            <td><font size=1><?=$rs->fields["DEPE_NOMB"] ?? ''?></font></td>
            <td><font size=1><?=$rs->fields["USUA_EMAIL"] ?? '' ?></font></td>
            
            <?php 
           
            if (trim($dependencia_cod)!=''){//si tiene area
                if (($subrogante_activo==$codigo) || $subrogado_activo==$codigo){//subroante = codigo
                    echo '<td colspan=2 width="6%" align="center" valign="middle" ><b>En Subrogación</b></td>';
                }else{ ?>
                <td width="6%" align="center" valign="middle" ><font size=1>
                        <?php                 
                        if ($cargo_tipo==1){ ?>
                        <input class='botones_azul' title='Subrogado' type='button' value='Subrogado' onClick="pasar('<?=$codigo?>','1','<?=$dependencia_cod?>','<?=$cargo_tipo?>');"></font>
                        <?php } ?>
                 </td>
                 <td width="6%" align="center" valign="middle" ><font size=1>
                    <?php 
                    if ($estadoAsubrogar==1)
                       echo "<input class='botones_azul' title='Subrogante' type='button' value='Subrogante' onClick=\"pasar('$codigo','2','$dependencia_cod','$cargo_tipo');\">";
                    else
                        echo "Debe estar Activo";
                    ?></font>
                       
                 </td>
                 <?php } 
            }else{
               echo "<td colspan='2'><font color='red'><center>No tiene área</center></font></td>"; 
            }
           ?>
        </tr>
  <?php
       
        $i++;
        $dependencia_color = trim($rs->fields["DEPE_CODI"]);
        $rs->MoveNext();
        
    } // End while
    } // End else (rs query success)
}
?>

</table>
<textarea id="usuarios_lista" name="usuarios_lista" style="display: none" cols="1" rows="1"><?=$usuarios_lista?></textarea>
