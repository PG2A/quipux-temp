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

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
require_once(dirname(__DIR__)."/funciones.php"); //para traer funciones p_get y p_post
include_once("obtener_datos_archivo.php");

p_register_globals(array());

$ubicacion = ObtenerUbicacionFisica($_GET["arch_codi"],$db);

?>

<!DOCTYPE html>
<head>
<title>Verificacion de Documento</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link href="../estilos/light_slate.css" rel="stylesheet" type="text/css">
<link href="../estilos/splitmenu.css" rel="stylesheet" type="text/css">
<link href="../estilos/template_css.css" rel="stylesheet" type="text/css">
</head>
<body>
    <br/><br/><center>
    <table cellspace=2 cellpad=2 WIDTH=70%  class="borde_tab" id=tb_general >
	<tr><td colspan="2" align="center" class="titulos2">Ubicaci&oacute;n F&iacute;sica de Documentos</td></tr>
	<tr>
	    <td colspan="2" align="center" class="listado2">
		<center>El Documento No. <?=$radi_nume?><br/>se encuentra ubicado en <?=$ubicacion?></center>
	    </td>
	</tr>
	<!--tr>
	    <td colspan="2" align="center" class="listado2">
		<center><a href="<?='../bodega'.str_replace('.p7m','',$arch_path)?>" class='vinculos'>Ver Documento</a></center>
	    </td>
	</tr-->
    </table>
    </center>
    <br/>
    <center><input type='button' onClick='window.close();' name='cerrar' value="Cerrar " class="botones_largo"></center>
</body>
</html>

