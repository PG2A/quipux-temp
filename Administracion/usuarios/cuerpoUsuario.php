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
require_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2)."/obtenerdatos.php");
include_once(dirname(__DIR__, 2)."/funciones_interfaz.php");
if(($_SESSION["usua_admin_sistema"] ?? 0)!=1) {
    die(html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina."));
}
include_once("mnuUsuariosH.php");

echo "<!DOCTYPE html>".html_head();

$accion = 0 + $_GET["accion"];

$paginador = new ADODB_Pager_Ajax(dirname(__DIR__, 2), "div_buscar_usuarios", "busqueda_paginador_usuarios.php",
                  "txt_nombre,txt_dependencia,txt_permiso,txt_estado,cmb_usr_perfil,txt_reporte");
if (!isset($descDependencia)) $descDependencia = "Dependencia";
?>

<script type="text/javascript">
    
   function realizar_busqueda() {
       document.getElementById("txt_reporte").value=0;
       paginador_reload_div('');
    }
    function seleccionar_usuario(codigo) {
        window.location='./adm_usuario.php?accion=<?=$accion?>&usr_codigo=' + codigo;
        return true;
    }
    function generar_reporte(){
        document.getElementById("txt_reporte").value=1;
        paginador_reload_div('');
    }
    function reportes_generar_guardar_como(tipo) {
        var div = document.getElementById('div_buscar_usuarios_reporte_guardar_como');
        if (div) div.innerHTML = '';
        if (window.bloquearPantalla) bloquearPantalla(tipo=='PDF' ? 'Generando PDF...' : 'Generando archivo...');
        nuevoAjax('div_buscar_usuarios_reporte_guardar_como', 'POST', 'busqueda_generar_usuario_guardar_como.php', 'tipo='+tipo);
        var intentos = 0;
        var timerGuardar = setInterval(function(){
            intentos++;
            var d = document.getElementById('div_buscar_usuarios_reporte_guardar_como');
            if ((d && d.innerHTML.length > 20) || intentos > 120) {
                clearInterval(timerGuardar);
                if (window.desbloquearPantalla) desbloquearPantalla();
            }
        }, 500);
    }
</script>
<?php $td1='20%'; $td2='60%'?>
<body>
  <?php dibujar_loader_pantalla('Generando archivo...', '/imagenes/escudo_blanco.png'); ?>
  <center>
    
        <?php 
                        graficarMenu(0,0,0,0);
        ?>
      <table border=0 width="100%" class="borde_tab" cellpadding="0" cellspacing="5">
          <tr><td>
            
                        
                 
            </td>
        </tr>
         
        <tr>
            <td width="20%" class="titulos5"><font class="tituloListado">Buscar usuarios por: </font></td>
            <td width="60%" class="listado5">
                <table width="100%" border="0">
                    <tr>
                        <td width="<?=$td1?>" class="listado5">Nombre / C.I. <br>Puesto / Correo</td>
                        <td width="<?=$td2?>"><input type=text id="txt_nombre" name="txt_nombre" value="" class="tex_area" onkeypress="if (event.keyCode==13) realizar_busqueda()"></td>
                    </tr>
                    <tr>
                        <td width="<?=$td1?>" class="listado5"><?=$descDependencia ?></td>
                        <td width="<?=$td2?>">
<?php
            $depe_codi_admin = obtenerAreasAdmin($_SESSION["usua_codi"],$_SESSION["inst_codi"],$_SESSION["usua_admin_sistema"],$db);
            //echo $depe_codi_admin;
           
            $sql="select depe_nomb, depe_codi from dependencia where depe_estado=1 
                and inst_codi=".$_SESSION["inst_codi"];
            if (!empty($depe_codi_admin))
            $sql.=" and depe_codi in ($depe_codi_admin)";            
            $sql.=" order by 1 asc";
            //echo $sql;
            //$sql="select depe_nomb, depe_codi from dependencia where depe_estado=1 and inst_codi=".$_SESSION["inst_codi"]." order by 1 asc";
            $rs=$db->conn->query($sql);
            if($rs) {
                print $rs->GetMenu2("txt_dependencia", "0", "0:&lt;&lt; Seleccione &gt;&gt;", false,"","class='select' id='txt_dependencia'  style='width: 300px;'");
            } else {
                $errorMsg = $db->conn ? $db->conn->ErrorMsg() : "No connection";
                if (empty($errorMsg)) $errorMsg = "Unknown error";
                echo "<select name='txt_dependencia' id='txt_dependencia' class='select' style='width: 300px;'><option value='0'>Error: " . htmlspecialchars($errorMsg) . "</option></select>";
            }
?>
                        </td>
                    </tr>
                    <tr>
                        <td width="<?=$td1?>" class="listado5">Permiso</td>
                        <td width="<?=$td2?>">
<?php
            $sql="select descripcion, id_permiso from permiso where estado=1 order by 1";
            $rs=$db->conn->query($sql);
            if($rs) {
                print $rs->GetMenu2("txt_permiso", "0", "0:&lt;&lt; Seleccione &gt;&gt;", false,"","class='select' id='txt_permiso' style='width: 300px;'" );
            } else {
                echo "<select name='txt_permiso' id='txt_permiso' class='select' style='width: 300px;'><option value='0'>Error al cargar</option></select>";
            }
?>
                        </td>
                    </tr>
                    <tr>
                        <td width="<?=$td1?>" class="listado5">Estado</td>
                        <td width="<?=$td2?>">
                            <select name="txt_estado" id="txt_estado" class="select">
                                <option value='1' selected>Activos</option>
                                <option value='0'>Inactivos</option>
                                <option value='2'>Todos</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="listado5" width="<?=$td1?>">Perfil</td>
                        <td class="listado2" width="<?=$td2?>">
                        <select name="cmb_usr_perfil" id="cmb_usr_perfil" class="select">
                            <option value='2'> Todos </option>
                            <option value='0'> Normal </option>
                            <option value='1'> Jefe </option>
                        </select>
                        </td>
                    </tr>
                </table>
            </td>
            <td width="20%" align="center" class="titulos5" >
                <table>
                    <tr>
                        <td>
                            <input type="button" name="btn_buscar" value="Buscar" class="botones" onClick="realizar_busqueda();">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="button" name="btn_buscar" value="Reporte" class="botones" onClick="generar_reporte();">
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
      <input type="hidden" name="txt_reporte" id="txt_reporte" value="0">
    <div id='div_buscar_usuarios' style="width: 99%"></div>
    <div id='div_buscar_usuarios_reporte_guardar_como' style="width: 99%"></div>
    <div id='div_reporte_guardar_como' style="width: 99%"></div>
  </center>
</body>
</html>