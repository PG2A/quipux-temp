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
include_once(dirname(__DIR__).'/rec_session.php');
if (isset ($replicacion) && $replicacion && $config_db_replica_buscar_usuario_de_para!="") {
    $db = new ConnectionHandler(dirname(__DIR__), $config_db_replica_buscar_usuario_de_para);
}
include_once(dirname(__DIR__)."/obtenerdatos.php");
include_once("usuarios_lista_modificada.php");


$documento_us1 = $_GET['documento_us1'] ?? $_POST['documento_us1'] ?? '';
$documento_us2 = $_GET['documento_us2'] ?? $_POST['documento_us2'] ?? '';
$concopiaa     = $_GET['concopiaa'] ?? $_POST['concopiaa'] ?? '';
$lista_destino = $_GET['lista_destino'] ?? $_POST['lista_destino'] ?? '';
$descCargo     = $descCargo ?? 'Cargo';
$descEmpresa   = $descEmpresa ?? 'Institución';
$html_incopia  = '';
$html_copia    = '';
$usuarios_eliminados = '';

if ($documento_us1!='' and $lista_destino!=''){
    $usuarios_eliminados = eliminadosDeLista($lista_destino,$documento_us1,$db,0);
    $usuarios_eliminados = substr($usuarios_eliminados,1);
}

?>

<table class=borde_tab width="100%" cellpadding="0" cellspacing="4">
    <tr>
        <td colspan="7" align="right">
            <input type="button" name="btn_borrarPara" value="Borrar Para" onClick='borrarTodos("D");' class="botones_azul" title="Borrar Para"/>
            <!--<a class='vinculos' href='#' onclick="borrarTodos('D')"><font size=2>Borrar Para</font></a>-->
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <input type="button" name="btn_borrarCopia" value="Borrar Copia a" onClick='borrarTodos("C");' class="botones_azul" title="Borrar Copia a"/>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <!--<a class='vinculos' href='#' onclick="borrarTodos('C')"><font size=2>Borrar Copia a</font></a>-->
        </td>
    </tr>
    <tr align="center" >
        <td width='6%' >&nbsp;</td>
        <td width='2%'  class=titulos5 >Tipo</td>
        <td width='20%' class=titulos5 >Nombre</td>
        <td width='20%' class=titulos5 >T&iacute;tulo</td>
        <td width='22%' class=titulos5 ><?=$descCargo ?></td>
        <td width='22%' class=titulos5 ><?=$descEmpresa ?></td>
        <td width='8%'  class=titulos5>Acci&oacute;n</td>
    </tr>


<?php
    // $flag  = 1 solo si TODOS los destinatarios de "Para" y "Copia a" son de otra
    //          institucion (NEW.php lo usa para sugerir Oficio).
    // $flagM = 0 solo si TODOS los destinatarios del "Para" son de la propia
    //          institucion (NEW.php lo usa para sugerir Memorando).
    // Ambas son condiciones sobre el conjunto completo, no sobre el ultimo
    // destinatario procesado, y exigen que haya al menos un destinatario: con la
    // lista vacia no se debe sugerir ningun cambio de tipo de documento.
    $hay_dest = false;            // algun destinatario en "Para" o "Copia a"
    $hay_dest_para = false;       // algun destinatario en el "Para"
    $todos_externos = true;
    $todos_internos_para = true;
    for($j=0;$j<3;$j++) {
        if ($j==0) { 	$cca = explode("-",$documento_us1);     $nom="Para:";    $tip="D";	}
      	if ($j==1) { 	$cca = explode("-",$documento_us2);     $nom="De:";      $tip="R";	}
      	if ($j==2) { 	$cca = explode("-",$concopiaa);		$nom="Copia a:"; $tip="C";	}
        for($i=0;$i<=count($cca)+1;$i++)
        {//for
            $tmp = $cca[$i] ?? '';
            if (trim((string)$tmp)!=""){//temp
                $usr = ObtenerDatosUsuario(trim($tmp),$db);
                //$boton="<a class=vinculos href=javascript:borrarCCA(".$usr["usua_codi"].",'$tip')>Borrar</a>";
                $boton = "<input class='botones_azul' title='Borrar' type='button' value='Borrar' onClick=\"borrarCCA(".$usr["usua_codi"].",'$tip');\">";
                if ($usr["tipo_usuario"]==1) {
                    $tipo_usr = "<i>(Serv.)</i>";
                } else {
                    $tipo_usr = "<i>(Ciu.)</i>";
                    if (($_SESSION["usua_admin_sistema"]==1 or $_SESSION["usua_perm_ciudadano"]==1) and $usr["inst_codi"]==0)
                        $usr["nombre"] = "<a href=\"javascript:crear_ciudadano('".$usr["usua_codi"]."');\" style='color:black;' title='Editar ciudadano'>".$usr["nombre"]."</a>";
                }
                if ($j===1 and $_SESSION["tipo_usuario"]==2) $boton = "";
                 if ($usr["usua_estado"]==0){
                  $color="#F7BE81";
                  $inactivo="<b>(Inactivo)</b>";
                 }else {
                    
                          $color="";
                          $inactivo="";
                        
                   
                  }
                   if (($lista_destino!='' || $documento_us1!='') and ($j==0 || $j==2)){
                    if ($usuarios_eliminados!='')//pintar eliminados de lista
                      foreach (explode(',',$usuarios_eliminados) as $usr_el) {                          
                          if ($usr_el == $tmp)
                              if ($color =="" and $inactivo==''){
                              $color="#F5DA81";
                              $inactivo ="<b>(No está en lista(s))</b>";
                              }
                          
                      }
                  }
                  
                 if ($usr["usua_estado"]==0){//para inactivos
                     $html_incopia.= "<tr onmouseover=\"this.style.background='#e3e8ec'\" onmouseout=\"this.style.background='white', this.style.color='black'\">
                        <td bgcolor='white'><font size=1>".$nom."</font></td>
                        <td bgcolor='white'><font size=1>$tipo_usr</font></td>
                        <td bgcolor='$color'><font size=1>".$inactivo." ".$usr["nombre"]."</font></td>
                        <td bgcolor='$color'><font size=1>".$usr["titulo"]."</font></td>
                        <td bgcolor='$color'><font size=1>".$usr["cargo"]."</font></td>
                        <td bgcolor='$color'><font size=1>".$usr["institucion"]."</font></td>
                        <td bgcolor='white'><font size=1><center>".$boton."</center></font></td>
                    </tr>";
                 }else{
                     $html_copia.= "<tr onmouseover=\"this.style.background='#e3e8ec'\" onmouseout=\"this.style.background='white', this.style.color='black'\">
                        <td bgcolor='white'><font size=1>".$nom."</font></td>
                        <td bgcolor='white'><font size=1>".$tipo_usr."</font></td>
                        <td bgcolor='$color'><font size=1>".$inactivo." ".$usr["nombre"]."</font></td>
                        <td bgcolor='$color'><font size=1>".$usr["titulo"]."</font></td>
                        <td bgcolor='$color'><font size=1>".$usr["cargo"]."</font></td>
                        <td bgcolor='$color'><font size=1>".$usr["institucion"]."</font></td>
                        <td bgcolor='white'><font size=1><center>".$boton."</center></font></td>
                    </tr>";
                 }
                $nom = "";                
                if($j==0 || $j==2){
                    $es_externo = ($usr["inst_codi"]!=$_SESSION["inst_codi"]);
                    $hay_dest = true;
                    if (!$es_externo)
                        $todos_externos = false;
                    if($j==0) { //Memorando: Alerta sólo para destinatarios del "Para"
                        $hay_dest_para = true;
                        if ($es_externo)
                            $todos_internos_para = false;
                    }
                }
            }//temp
        }//for
    }
    $flag  = ($hay_dest && $todos_externos) ? 1 : 0;
    $flagM = ($hay_dest_para && $todos_internos_para) ? 0 : 1;

    echo $html_incopia.$html_copia;
    echo "<input type='hidden' name='flag_inst' id='flag_inst' value='$flag'>";
    echo "<input type='hidden' name='flag_inst_m' id='flag_inst_m' value='$flagM'>";
?>
</table>
