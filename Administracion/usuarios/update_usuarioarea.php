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
 * @package    usuarios
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
if($_SESSION["usua_admin_sistema"]!=1) {
    die("");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');

$db = new ConnectionHandler(dirname(__DIR__, 2));

$path_to_classes = "class_control/";
//echo $path_to_classes;

//////////////////////////////////////
//                                  //
//     A partir de aqu� no tocar    //
//                                  //
//////////////////////////////////////

//define ( "ABS_PATH", "/var/www/orfeo" ."/" );
//define ( "REL_CLASS_PATH", $path_to_classes );
//define ( "ABS_CLASS_PATH", ABS_PATH . REL_CLASS_PATH );
//echo (ABS_CLASS_PATH . "JSON.php");
if ( empty($_POST) ) exit( "Error, no se han pasado variables" );

$clazz = $_POST["__class"];
$action = $_POST["__action"];

//require_once ( ABS_CLASS_PATH . "JSON.php" );
require_once(dirname(__DIR__, 2)."/class_control/JSON.php");
//$clazz = "usuarios";
//require_once ( ABS_CLASS_PATH . $clazz . ".php" );
require_once(dirname(__DIR__, 2)."/class_control/". $clazz . ".php");
//echo (ABS_CLASS_PATH . "JSON.php");
//$clazz = $_POST["__class"];
//$clazz = "usuario";
//$action = $_POST["__action"];
//$db = new ConnectionHandler("/var/www/orfeo_multiempresa/");
//$instance = new $clazz ($db);
//var_dump($instance );
//$result = $instance->$action ( $_POST );
$instance = new $clazz ($db);
$result = $instance->$action ( $_POST );
//var_dump( $_POST);
$json = new Services_JSON();

$res = $json->encode($result);
header ( 'X-JSON:('. $res .')' );

?>
