<?php
/*------------------------------------------------------------------------------
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU Affero General Public License as
*    published by the Free Software Foundation, either version 3 of the
*    License, or (at your option) any later version.
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU Affero General Public License for more details.
*
*    You should have received a copy of the GNU Affero General Public License
*    along with this program.  If not, see http://www.gnu.org/licenses.
*------------------------------------------------------------------------------
*   Autor: David Gamboa 
**/
//BORRAR LA OPCIONES DE IMPRESION SIEMPRE SI CAMBIA EL DESTINATARIO
require_once(__DIR__."/funciones.php"); //para traer funciones p_get y p_post
p_register_globals(array());

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
include_once(__DIR__.'/obtenerdatos.php');
include_once(__DIR__.'/funciones_interfaz.php');
if (isset($_GET[codigo_opc])){
$codigo_opc_imp = 0+limpiar_sql($_GET["codigo_opc"]);
$deleteOpc="delete from opciones_impresion where opc_imp_codi = ".$codigo_opc_imp;
$db->conn->Execute($deleteOpc);
}
?>