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
 * @package    radicacion
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
require_once(dirname(__DIR__)."/funciones.php"); //para traer funciones p_get y p_post
include_once(dirname(__DIR__).'/funciones_interfaz.php');
include_once(dirname(__DIR__).'/obtenerdatos.php');

p_register_globals(array());

if ($_GET['destinatario']){
    $codiDestinatario = $_GET['destinatario'];
    $tipo_busqueda = $_GET['tipo_busqueda'];
    $usuadest = ObtenerDatosUsuario(str_replace("-","",$codiDestinatario),$db);
    
    
        $usuadest = ObtenerDatosUsuario(str_replace("-","",$codiDestinatario),$db);        
        $cargo=$usuadest['cargo'];        
        $titulo = $usuadest['titulo'];
        $institucion = $usuadest["institucion"];
        $tipoUser = $usuadest['tipo_usuario'];        
  if ($tipo_busqueda==6 || $tipo_busqueda==1){      
    if ( trim($cargo)== '' || trim($titulo) == '' || trim($institucion)=='')
        echo "<font color='red'>El destinatario no tiene completos los datos, Favor cambie el Tipo de Impresión</font>";
  }elseif($tipo_busqueda==2){
       if (trim($cargo)== '' || trim($institucion)=='')
           echo "<font color='red'>El destinatario no tiene completos los datos, Favor cambie el Tipo de Impresión</font>";
  }
  elseif($tipo_busqueda==5){
       if (trim($titulo)=='' || trim($institucion)==''){           
           echo "<font color='red'>El destinatario no tiene completos los datos, Favor cambie el Tipo de Impresión</font>";
       }
  }
  elseif($tipo_busqueda==4){
       if (trim($titulo)=='' || trim($cargo)=='')
           echo "<font color='red'>El destinatario no tiene completos los datos, Favor cambie el Tipo de Impresión</font>";
  }
    }
     
?>