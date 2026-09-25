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
 * @package    tareas
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
require_once(dirname(__DIR__)."/funciones.php");
include_once(dirname(__DIR__).'/anexos_grabar.php');
include_once(dirname(__DIR__).'/obtenerdatos.php');

p_register_globals(array());
$encabezado = "depeBuscada=$depeBuscada&filtroSelect=$filtroSelect&tpAnulacion=$tpAnulacion";
$txt_depe_codi = str_replace("′","'",$txt_depe_codi);
$txt_fecha_desde = str_replace("′","'",$txt_fecha_desde);
$txt_fecha_hasta = str_replace("′","'",$txt_fecha_hasta);
?>
<script>
function regresar(){
    var txt_depe_codi = <?=$txt_depe_codi?>;
    var txt_fecha_desde = <?=$txt_fecha_desde?>;
    var txt_fecha_hasta = <?=$txt_fecha_hasta?>;
    var txt_usua_codi = <?=$txt_usua_codi?>;
    document.location.href = "cargar_doc_digitalizado.php?txt_depe_codi="+txt_depe_codi+"&txt_fecha_desde="+txt_fecha_desde+"&txt_fecha_hasta="+txt_fecha_hasta+"&txt_usua_codi="+txt_usua_codi;
}
</script>
<?php
include_once(dirname(__DIR__).'/funciones_interfaz.php');
echo "<!DOCTYPE html>".html_head();

?>

<body>
    <br>
<?
    if (!isset($chk_fisico)) $chk_fisico=0;

    $nombarch = $_FILES['upload']['name'];
    $userfile = $_FILES['upload']['tmp_name'];
    $info = $_FILES['upload']['name']."<br>".$_FILES['upload']['type'];//."<br>".$_FILES['upload']['size'];
    $descarch = "Asociación de la imagen digitalizada del documento.";
    $anex = GrabarAnexo($db, $valRadio, $userfile, $nombarch, $descarch, $usua_codi, $ruta_raiz,$chk_fisico,1);

    if ($anex == 0) {
	die(html_error("Ocurri&oacute; un error al asociar la imagen del documento."));
    } else {
	include(dirname(__DIR__)."/include/tx/Historico.php");
	$hist = new Historico($db);
	$hist->insertarHistoricoTemporal($valRadio, $_SESSION["usua_codi"], $_SESSION["usua_codi"], $observa, 42);
    }
?>

<table cellspace=2 WIDTH=60% id=tb_general class="borde_tab">
    <tr>
	<td colspan="2" class="titulos4">ASOCIACION DE IMAGEN AL <?=strtoupper($descRadicado)?></td>
    </tr>
    <tr>
	<td width="35%" align="right" bgcolor="#CCCCCC" height="25" class="titulos2"><?=trim(strtoupper($descRadicado))?>S INVOLUCRADOS :</td>
	<td width="65%" height="25" class="listado2_no_identa"><?=ObtenerCampoRadicado("radi_nume_text",$valRadio,$db);?></td>
    </tr>
    <tr>
	<td align="right" bgcolor="#CCCCCC" height="25" class="titulos2">IM&Aacute;GEN ASOCIADA :</td>
	<td height="25" class="listado2_no_identa"><?=$info?></td>
    </tr>
    <tr>
	<td align="right" bgcolor="#CCCCCC" height="25" class="titulos2">FECHA Y HORA :</td>
	<td height="25" class="listado2_no_identa"><?=date("Y-m-d  H:i:s")?></td>
    </tr>
    <tr>
	<td align="right" bgcolor="#CCCCCC" height="25" class="titulos2">USUARIO :</td>
	<td  width="65%" height="25" class="listado2_no_identa"><?=$_SESSION['usua_nomb']?></td>
    </tr>
    <tr>
	<td align="right" bgcolor="#CCCCCC" height="25" class="titulos2">AREA :</td>
	<td height="25" class="listado2_no_identa"><?=$_SESSION['depe_nomb']?></td>
    </tr>
    <tr><td colspan="2" align="right" bgcolor="#CCCCCC" height="25" class="titulos2">
            <input align='right' class='botones' value='Regresar' onclick='regresar();'>
        </td>
        
    </tr>
</table>

<br/>
</form>
</body>
</html>
