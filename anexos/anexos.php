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

if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__).'/seguridad/obtener_nivel_seguridad.php');
include_once(dirname(__DIR__).'/funciones.php');

$nurad = $nurad ?? $_GET['nurad'] ?? $_POST['nurad'] ?? '';
$verrad = $verrad ?? $_GET['verrad'] ?? $_POST['verrad'] ?? '';
$radi_nume = (trim($nurad)=="") ? limpiar_numero($verrad) : limpiar_numero($nurad);
if ($radi_nume == "") die ("");

$nivel_seguridad_documento = obtener_nivel_seguridad_documento($db, $radi_nume);
if ($nivel_seguridad_documento<2 and !($_SESSION["usua_perm_digitalizar"]==1 and isset($_POST["asocImgRad"])))
    die ("Usted no tiene los permisos suficientes para visualizar estos archivos");

$chk_asociar_imagen = 0;
if (substr($radi_nume, -1)=="2" and ($nivel_seguridad_documento==7 or ($nivel_seguridad_documento==5 and $datosrad["estado"]==1)))
    $chk_asociar_imagen = 1;
elseif ($_SESSION["usua_perm_digitalizar"]==1 and isset($_POST["asocImgRad"]) and $_POST["asocImgRad"]=="1") {
    $sql = "select count(1) as num from radicado
            where (radi_nume_radi=$radi_nume or radi_nume_temp=$radi_nume)
                and radi_inst_actu=".$_SESSION["inst_codi"]."
                and radi_fech_firma is null and esta_codi not in (0)
                and not (radi_nume_radi::text like '%0' and esta_codi in (1,3,4,7,8))";
    $rs = $db->query($sql);
    if ($rs and $rs->fields["NUM"]>0) $chk_asociar_imagen = 1;
}

include_once(dirname(__DIR__)."/anexos/anexos_js.php");

?>

<center>
    <div id="div_anexos_acciones" style="width: 100%; border: none;"></div>
    <div id="div_anexos_lista_archivos" style="width: 100%; border: none;"></div>
    <div id="div_anexos_cargar_nuevo_archivo" style="width: 100%; border: none;"></div>
    <div id="div_anexos_cargar_nuevo_archivo_estado" style="width: 100%; text-align: center; display: none;" class="borde_tab">
        <br><img src="../imagenes/progress_bar.gif" alt="Cargando..."><br><br>
        Por favor espere mientras se carga el archivo <span id="lbl_nombre_archivo_nuevo">Seleccionado</span><br>&nbsp;
    </div>
<?php if (!isset($_POST["asocImgRad"])) { ?>
    <br>
    <b>Puede firmar electr&oacute;nicamente sus archivos desde la aplicaci&oacute;n
    &quot;<a href="javascript:;" onclick="window.parent.topFrame.popup_firma_digital();" style='color:blue'>Firma Digital</a>&quot;.</b>
<?php } ?>
    <br>&nbsp;
</center>

<script type="text/javascript">
    anexos_cargar_div_lista_anexos();
    <?php  if ($nivel_seguridad_documento>=4 or ($_SESSION["usua_perm_digitalizar"]==1 and isset ($_POST["asocImgRad"])))
        echo "anexos_cargar_div_nuevo_anexo();";
    ?>
</script>
