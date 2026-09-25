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
 * @package    cron
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

	if ($_SESSION["usua_tipo"]==2) $carpeta = 80;

	if (!$carpeta)
	{
		$carpeta = "0";
		$nomcarpeta = "Nuevos";
		$desccarpeta = "Documentos recibidos que aún no se han leído";
	} else {
		$sql = "select * from carpeta where carp_codi=$carpeta";
		$rs = $db->conn->Execute($sql);
		$nomcarpeta = $rs->fields("CARP_NOMBRE");
		$desccarpeta = $rs->fields("CARP_DESCRIPCION");
		if ($carpeta == 13) {
		    $nomcarpeta = "Informados";
		    $desccarpeta = "Documentos para su informaci&oacute;n";
		}
		if ($carpeta == 12) {
		    $nomcarpeta = trim($_SESSION['descAgendado'])."s";
		    $desccarpeta = "Documentos ".trim($_SESSION['descAgendado'])."s";
		}
		if ($carpeta == 80) {
		    $nomcarpeta = "Documentos Enviados";
		    $desccarpeta = "Documentos enviados por el ciudadano a cualquier Instituci&oacute;n P&uacute;blica";
		}
		if ($carpeta == 81) {
		    $nomcarpeta = "Documentos Recibidos";
		    $desccarpeta = "Documentos recibidos por el ciudadano desde cualquier Instituci&oacute;n P&uacute;blica";
		}
		if ($carpeta == 98) {
		    $nomcarpeta = "Cargar Documentos Digitalizados";
		    $desccarpeta = "Documentos impresos que llegaron o se generaron en la institución, que deben ser digitalizados 
			y asociados al registro del documento";
		}
		if ($carpeta == 99) {
		    $nomcarpeta = "Por Imprimir";
		    $desccarpeta = "Documentos que se deber&aacute;n imprimir para su env&iacute;o Manual";
		}
	}
?>

<table BORDER=0  cellpad=2 cellspacing='0' WIDTH=100% class='borde_tab' valign='top' align='center' >
    <tr>
    	<td width='100%' height="35" valign='center'>
	    <font size="2"><b>Documentos de la carpeta <?=$nomcarpeta?></b> (<?=$desccarpeta?>)</font>
	</td>
    </tr>
</table>

