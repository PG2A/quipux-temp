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
 * @package    quipux
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__."/funciones.php"); //para traer funciones p_get y p_post
p_register_globals(array());

//session_start();
//	if($_SESSION["usua_admin_sistema"]!=1) die("");
//include_once(__DIR__.'/rec_session.php');

include_once(__DIR__.'/funciones_interfaz.php');
echo "<!DOCTYPE html>".html_head();

?>
<script type="text/javascript">
function mostrarVentana()
{
    var ventana = document.getElementById('miVentana'); // Accedemos al contenedor
    ventana.style.marginTop = "100px"; // Definimos su posición vertical. La ponemos fija para simplificar el código
    ventana.style.marginLeft = ((document.body.clientWidth-350) / 2) +  "px"; // Definimos su posición horizontal
    ventana.style.display = 'block'; // Y lo hacemos visible
}

function ocultarVentana()
{
    var ventana = document.getElementById('miVentana'); // Accedemos al contenedor
    ventana.style.display = 'none'; // Y lo hacemos invisible
}
</script>
<body>
<?php
$_GET['tipo_mensaje'];
//$_GET['tipo_mensaje'];//lee el tipo de mensaje
include(__DIR__."/Administracion/adm_mensajes_txt.php");

if ($dia==1 or $dia==0){

  if ($bloqueaSistema==0){     
     // echo $mensaje;
?>
    
<div id="miVentana" style="position: fixed; width: 700px; height: 60px; top: 15; right: 200; font-family:Verdana, Arial, Helvetica, sans-serif; font-size: 12px; font-weight: normal; border: #333333 2px solid; background-color: #F2F2F2; color: #000000;"> 
    <div style="text-align: right; padding: 5px; background-color:#F2F2F2"><font size="1">Cerrar<a href="javascript:ocultarVentana();"><font color="black">[x]</font></a></font> </div>
      <?php
             graficaAreaTexto('text_alerta',$mensaje);
      ?>  
</div>
            
          
<?php }
}?>
</body>
</html>

<?php
function graficaAreaTexto($nombreCajaTexto,$valorpost){//caja texto
?>
        <textarea name="<?php echo $nombreCajaTexto;?>" id="<?php echo $nombreCajaTexto;?>" rows="1" cols="60" readonly class="transpa"><?php echo $valorpost;?></textarea>

      <?php
}//cajatexto
?>
