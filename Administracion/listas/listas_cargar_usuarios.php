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
 * @package    listas
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
if (isset ($replicacion) && $replicacion && $config_db_replica_lst_listas_cargar_usuarios!="") {
    $db = new ConnectionHandler(__DIR__, $config_db_replica_lst_listas_cargar_usuarios);
}
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
require_once(dirname(__DIR__, 2).'/funciones.php'); // For limpiar_sql

$usuarios_lista = limpiar_sql($_POST["txt_usuarios_lista"] ?? '');
$lista_orden = 0 + (int)($_POST["txt_lista_orden"] ?? 0);
$descCargo = $descCargo ?? 'Cargo';

?>

<table class=borde_tab width="100%" cellpadding="0" cellspacing="4">
    <tr>
        <td colspan="9" align="right">
            <input class='botones_azul' title='Quitar todos los usuarios de la lista' type='button' value='Borrar Todos' onClick="borrar_usuario_lista('0');">
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        </td>
    </tr>
    <tr align="center" >
        <td width="10%"  class="titulos5">Tipo</td>
        <td width="15%" class="titulos5">Nombres</td>
        <td width="15%" class="titulos5">Instituci&oacute;n</td>
        <td width="5%"  class="titulos5">T&iacute;tulo</td>
        <td width="15%" class="titulos5"><?=$descCargo?></td>
        <td width="15%" class="titulos5">&Aacute;rea</td>
        <td width="10%" class="titulos5">E-mail</td>
        <td width="5%"  class="titulos5">Estado</td>
        <td width="10%" class="titulos5">Acci&oacute;n</td>
    </tr>


<?php
if ($lista_orden == 1) {
    $lista_usr = explode("-",$usuarios_lista);
    for($i=0 ; $i<=count($lista_usr)+1 ; $i++) {
        $cod_usr = 0 + trim($lista_usr[$i]);
        if ($cod_usr != 0) {
            $sql = "select * from usuario where usua_codi=$cod_usr";
            $rs=$db->conn->query($sql);
            mostrar_usuario();
        }
    }
} else {
    $lista_usr = trim(str_replace("-", "", str_replace("--", ",", $usuarios_lista)));
    if ($lista_usr == "") die ("");
    $sql = "select * from usuario where usua_codi in ($lista_usr) order by usua_esta asc, usua_nombre,inst_nombre asc";
    //echo $sql;
    $rs = $db->conn->query($sql);
    if (!$rs) die ("");
    while (!$rs->EOF) {
        mostrar_usuario();
        $rs->MoveNext();
    }
}
?>
</table>

<?php
function mostrar_usuario() {
    global $rs;
?>
        <tr onmouseover="this.style.background='#e3e8ec'" onmouseout="this.style.background='white', this.style.color='black'">
            <td><font size=1><?php  if ($rs->fields["TIPO_USUARIO"]==1) echo "Funcionario"; else echo "Ciudadano"; ?></font></td>
            <td><font size=1><?=$rs->fields["USUA_NOMBRE"] ?></font></td>
            <td><font size=1><?=$rs->fields["INST_NOMBRE"] ?></font></td>
            <td><font size=1><?=$rs->fields["USUA_TITULO"] ?></font></td>
            <td><font size=1><?=$rs->fields["USUA_CARGO"] ?> </font></td>
            <td><font size=1><?=$rs->fields["DEPE_NOMB"] ?></font></td>
            <td><font size=1><?=$rs->fields["USUA_EMAIL"] ?></font></td>
            <td align="center" valign="middle"><font size=1><?php  if ($rs->fields["USUA_ESTA"]==1) echo "Activo"; else echo "<font color='red'>Inactivo</font>"; ?></font></td>
            <td align="center" valign="middle" ><font size=1>
                <input class='botones_azul' title='Quitar usuario de la lista' type='button' value='Borrar' onClick="borrar_usuario_lista('<?=$rs->fields["USUA_CODI"]?>');"></font>
            </td>
        </tr>
<?php
    return;
}
?>