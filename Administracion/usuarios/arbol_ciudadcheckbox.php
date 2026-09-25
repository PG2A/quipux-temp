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
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php"); //para traer funciones p_get y p_post
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once("refrescarArbol.php");

?>

<table width="100%" border="1">
<tr>
             <td class="titulos2" width="15%">
		* Ciudad/País
                </td>
         
            <td class="listado2" colspan="3" width="85%">                
                <input type="text" size="30" value="<?php $ciud->dibujarCiudad($ciu_ciudad);?>" id="inputString" name="inputString" onkeypress="lookup(this);" autocomplete="off"/>
                <font size="1">Ingrese los primeros caracteres de la Ciudad o País y seleccione de la lista.</font>
                <div class="suggestionsBox" id="suggestions" style="display:none; width:300px; height:60px; overflow-x:hidden; autoflow-y:scroll;">
				
				<div class="suggestionList" id="autoSuggestionsList">
					&nbsp;
				</div>
			</div>                
          </td>
          
          
      </tr>
      
      
   
</table>
    