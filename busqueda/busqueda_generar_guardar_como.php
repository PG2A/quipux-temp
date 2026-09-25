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
 * @package    busqueda
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
require_once(dirname(__DIR__)."/funciones.php");
include(dirname(__DIR__)."/config.php");

//p_register_globals($_POST);

$path_archivo = "/tmp/reporte_pdf_".$_SESSION["usua_codi"].".html";
$html = file_get_contents(dirname(__DIR__)."/bodega$path_archivo");

//$html = base64_decode(base64_decode($reporte));


switch ($tipo) {
    case "PDF":
        require_once(dirname(__DIR__).'/interconexion/generar_pdf.php');
        require_once(dirname(__DIR__).'/obtenerdatos.php');

        $html = preg_replace(':<a .*?>:is', "", $html);
        $html = str_replace("</a>", "", $html);
        $html = "<html><head><meta http-equiv='Content-Type' content='text/html; charset=UTF-8'></head><body>$html</body></html>";
        $area = ObtenerDatosDependencia($_SESSION["depe_codi"],$db);
        $plantilla = ObtenerRutaMembrete($_SESSION["depe_codi"], 0, $_SESSION["inst_codi"], $db)["ruta"];
        $pdf = ws_generar_pdf($html, $plantilla, $servidor_pdf, "", "", "", 100,"R");

        $path_archivo = "/tmp/reporte_pdf_".$_SESSION["usua_codi"].".pdf";
        file_put_contents(dirname(__DIR__)."/bodega$path_archivo", $pdf);
        $path_descarga = dirname(__DIR__)."/archivo_descargar.php?path_arch=$path_archivo&nomb_arch=reporte.pdf";
        

        break;

    case "XLS":
        $html = preg_replace(':<a.*?>:is', '', $html);
        $html = str_replace("</a>", "", $html);
        $html = reemplaza_caracteres_html($html);
        $path_archivo = "/tmp/reporte_".$_SESSION["usua_codi"].".xls";
        file_put_contents(dirname(__DIR__)."/bodega$path_archivo", $html);
        $path_descarga = dirname(__DIR__)."/archivo_descargar.php?path_arch=$path_archivo&nomb_arch=reporte.xls";
        break;

    default:
        die("");
        break;
}
?>

<iframe  name="ifr_descargar_archivo" id="ifr_descargar_archivo" style="display: none" src="<?=$path_descarga?>">
            Su navegador no soporta iframes, por favor actualicelo.</iframe>
