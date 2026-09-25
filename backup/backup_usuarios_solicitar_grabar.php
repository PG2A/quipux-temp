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
if($_SESSION["usua_perm_backup"]!=1) {
    echo html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
    die("");
}
include_once(dirname(__DIR__).'/rec_session.php');
require_once(dirname(__DIR__).'/funciones.php'); //para traer funciones p_get y p_post
include_once(dirname(__DIR__).'/funciones_interfaz.php');


$txt_usua_codi = trim(limpiar_sql($_POST["txt_usua_codi"]));

// guarda solo nuevas solicitudes, si la solicitud ya ha sido eliminada o ha finalizado
$sql = "select * from respaldo_usuario where usua_codi=$txt_usua_codi and fecha_fin is null and fecha_eliminado is null";
$rs = $db->query($sql);

if ($rs->EOF) {
    $record = array();
    unset($record);
    $record["USUA_CODI"] = $txt_usua_codi;
    $record["FECHA_SOLICITA"] = $db->conn->sysTimeStamp;
    $db->conn->Replace("RESPALDO_USUARIO", $record, "", false,false,true,false);
}

echo "<!DOCTYPE html>".html_head();
$rs = $db->query("select usua_nombre from usuario where usua_codi=$txt_usua_codi");
echo "<center><br>
        Se ha solicitado un respaldo de la documentaci&oacute;n del usuario &quot;".$rs->fields["USUA_NOMBRE"]."&quot;.
      </center>";
?>
  </body>
</html>
