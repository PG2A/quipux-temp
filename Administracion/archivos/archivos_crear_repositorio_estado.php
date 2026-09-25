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
 * @package    archivos
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
if($_SESSION["perm_actualizar_sistema"]!=1) {
    die("Usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
}
require_once(dirname(__DIR__, 2).'/rec_session.php');
$dba =  new ConnectionHandler(dirname(__DIR__, 2), "bodega");

$sql = "select coalesce(max(indi_codi),0) as codigo from indice";
$rs = $dba->conn->query($sql);
$repos_codigo = 1+$rs->fields["CODIGO"];
$repos_tabla = "archivo_" . str_pad($repos_codigo, 4, "0", STR_PAD_LEFT);


$rs = $dba->conn->query("SELECT spcname as nombre, spcname as codi FROM pg_tablespace where spcname != 'pg_global' order by spcname");
$slc_repos_tablespace = $rs->GetMenu2("slc_repos_tablespace", "0", "0:&lt;&lt Seleccione un tablespace &gt;&gt;", false,"",
        "id='slc_repos_tablespace' class='select'");

?>
<table width="100%" cellpadding="0" cellspacing="3" border="0">
    <tr>
        <td width="20%">No. de Repositorio:</td>
        <td width="80%"><b><?=$repos_codigo?></b></td>
    </tr>
    <tr>
        <td>Nombre de la Tabla:</td>
        <td><b><?=$repos_tabla?></b></td>
    </tr>
    <tr>
        <td>Nombre del Tablespace:</td>
        <td><b><?=$slc_repos_tablespace?></b></td>
    </tr>
    <tr>
        <td>Tama&ntilde;o M&aacute;ximo del Repositorio <b>(Gb)</b>:</td>
        <td><input name="txt_repos_tamanio" id="txt_repos_tamanio" type="text" class="tex_area" value="100" onkeypress="return fjs_validar_ingreso_numeros_enteros(event)" maxlength="5"></td>
    </tr>
</table>
