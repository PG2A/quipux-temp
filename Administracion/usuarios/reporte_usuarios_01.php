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
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
if(($_SESSION["usua_admin_sistema"] ?? 0)!=1) {
    echo html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
    die("");
}

echo "<!DOCTYPE html>".html_head(); /*Imprime el head definido para el sistema*/
require_once("../../js/ajax.js");

?>
<script>
    function consultar_usuarios() {
        area = document.getElementById('cmb_dependencia').value;
        estado = document.getElementById('cmb_estado').value;
        nuevoAjax('div_tabla_usuarios', 'POST', 'reporte_usuarios_01_cargar.php', 'area='+area+'&estado='+estado);
        return;
    }
</script>

<body>
  <form name="formulario" action="" method="post">
    <table border=0 width="100%" class="borde_tab" cellpadding="0" cellspacing="5">
	  <tr>
	    <td width="30%" class="titulos5"><font class="tituloListado">Buscar usuarios por: </font></td>
	    <td class="listado5" valign="middle">
          <table>
            <tr>
              <td><span class="listado5"><?= isset($descDependencia) ? $descDependencia : "Dependencia" ?></span></td>
              <td>
<?php              $depe_codi_admin = obtenerAreasAdmin($_SESSION["usua_codi"],$_SESSION["inst_codi"],$_SESSION["usua_admin_sistema"],$db);
                $sql="select depe_nomb, depe_codi from dependencia where inst_codi=".$_SESSION["inst_codi"];
                
                // Fix: PHP 8 comparison check to avoid empty IN ()
                if (!empty($depe_codi_admin) && $depe_codi_admin != "0")
                    $sql.=" and depe_codi in ($depe_codi_admin)";
                    
                $sql.=" order by 1 asc";
                
                $rs=$db->conn->query($sql);
                if($rs) {
                    print $rs->GetMenu2("cmb_dependencia", 0, "0:&lt;&lt; Seleccione &gt;&gt;", false,0,"class='select' id='cmb_dependencia'" );
                } else {
                    echo "Error en consulta de dependencias"; 
                    // Render fallback to prevent JS error
                    echo "<select id='cmb_dependencia' class='select'><option value='0'>Error</option></select>";
                }
?>
              </td>
            </tr>
            <tr>
              <td><span class="listado5">Estado</span></td>
              <td>
                  <select name="cmb_estado" id="cmb_estado" class='select'>
                      <option value='1' selected>Activos</option>
                      <option value='0'>Inactivos</option>
                      <option value='2'>Todos</option>
                  </select>
              </td>
            </tr>
          </table>
        </td>
        <td width="20%" align="center" class="titulos5" >
            <input type="button" name="btn_buscar" value="Buscar" class="botones" onClick="consultar_usuarios();">
        </td>
      </tr>
    </table>
    <br />
    <div id="div_tabla_usuarios" name="div_tabla_usuarios"></div>
    <br/>
    <center>
<!--        <input  name="btn_accion" type="button" class="botones" value="Imprimir" onClick="window.print();"/>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input  name="btn_accion" type="button" class="botones" value="Regresar" onClick="location='./mnuUsuarios.php'"/>-->
    </center>

</form>
</body>
</html>
