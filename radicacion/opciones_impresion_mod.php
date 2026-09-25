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

include_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__).'/obtenerdatos.php');
include_once(dirname(__DIR__).'/funciones_interfaz.php');
//Permite grabar el area en la base de datos con todos sus atributos


echo "<!DOCTYPE html>".html_head();

?>
<body>
    <form name="frmConfirmaCreacion" action="../mnu_dependencias.php" method="post">
    <center>
      <table width="100%">
      <?php 
      $radiNumeRadi = 0+limpiar_numero($_GET['num_radicado']);
       
      $OpcImpr = ObtenerDatosOpcImpresion($radiNumeRadi, $db);
     
      $opcImpresion= array();
      $opcImpresion['OPC_IMP_DESTINO_DESTINATARIO'] = $db->conn->qstr(limpiar_sql(trim($_GET['txtSaludo'])));
      $opcImpresion['RADI_NUME_RADI'] = 0+limpiar_numero($_GET['num_radicado']);
      //echo $OpcImpr['OPC_IMP_CODI'];
      if($OpcImpr['OPC_IMP_CODI']){  
           $opcImpresion['OPC_IMP_CODI'] = $OpcImpr['OPC_IMP_CODI'];
           $ok1 = $db->conn->Replace("OPCIONES_IMPRESION", $opcImpresion, "OPC_IMP_CODI", false,false,false,false);            
      }
      else{
         
            $ok1 = $db->conn->Replace("OPCIONES_IMPRESION", $opcImpresion, "", false,false,false,false);
      }
      ?>
      
      </table>	
    </center>
    </form>
</body>
</html>