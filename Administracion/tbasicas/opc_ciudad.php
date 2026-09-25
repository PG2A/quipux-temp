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
 * @package    tbasicas
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php"); //para traer funciones p_get y p_post
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');

if (isset($_GET["code"]))
$id_codigo = 0+ limpiar_numero($_GET['code']);
else
    $id_codigo=0;
if (isset($_GET["cod_ciu"]))
$ciu_ciudad = 0+ limpiar_numero($_GET['cod_ciu']);
else
    $ciu_ciudad=0;
if ($id_codigo!=0){    
$sql="select nombre, id from ciudad where id_padre = $id_codigo";
//echo $sql;
$rsCmbPais = $db->conn->Execute($sql);
echo $rsCmbPais->GetMenu2('ciu_ciudad',$ciu_ciudad,"0:&lt;&lt seleccione &gt;&gt;",false,"","onchange='buscarDep(3);' id='ciu_ciudad' Class='select' $deshabilitar_campos");
}
?>