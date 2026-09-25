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
 * @package    ciudadanos
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
require_once(dirname(__DIR__, 2)."/funciones_interfaz.php");

$ciu_codigo = 0+limpiar_sql($_POST["ciu_codigo"]);

if(isset ($_GET["codigo"]))
    $ciu_codigo = 0+limpiar_sql($_GET["codigo"]);

$apellidosnombres = limpiar_sql($_GET["nombre"]);

$sql = "select * from solicitud_firma_ciudadano where ciu_codigo=$ciu_codigo";
//echo $sql;
$rs = $db->conn->query($sql);

if ($rs->EOF) {
    echo html_error("No se encontr&oacute; el usuario en el sistema.");
    die("");
}
$ciu_cedula1 = $rs->fields["CIU_CEDULA"];
if(strlen($ciu_cedula1)== 10){

        if (trim(substr($ciu_cedula1,0,2))!= "99") {
            include_once dirname(__DIR__, 2)."/interconexion/validar_datos_ciudadano.php";
            $datos_rc = ws_validar_datos_ciudadano($ciu_cedula1);            
            }
}
else
    {
    echo "<!DOCTYPE html>".html_head();
    echo "<center>
        <br />
        <table width='40%' border='2' align='center' class='t_bordeGris'>
            <tr>
            <td width='100%' height='30' class='listado2'>
                <span class='listado5'><center><B>Solo valido para números de cédula</B></center></span>
            </td>
            </tr>
            <tr>
            <td height='30' class='listado2'>
                <center><input class='botones' type='button' value='Cerrar' onClick='window.close();'></center>
            </td>
            </tr>
        </table>
    </center>";
    die();
    }
?>
<?php echo "<!DOCTYPE html>".html_head(); ?>
<title>Datos del Ciudadano</title>
<link href="../../estilos/light_slate.css" rel="stylesheet" type="text/css">
<link href="../../estilos/splitmenu.css" rel="stylesheet" type="text/css">
<link href="../../estilos/template_css.css" rel="stylesheet" type="text/css">
</head>
<body>

<form method="post" action="adm_solicitud_validar.php">
<table border=0 width=100% align="center" class="borde_tab" cellspacing="0">

    <?php if($apellidosnombres != $datos_rc['nombre']) {?>

    <tr align="center" 	class="titulos2">
        <td class="titulos2"><font color="Maroon">Existen inconsistencias en los datos(Apellido, Nombre)</font></td>
    </tr>

    <?php }?>

    <tr align="center" class="titulos2">
	<td class="titulos2">DATOS DEL CIUDADANO</td>
    </tr>
</table>
    
<table width="100%" border="0" cellspacing="1" cellpadding="0" align="center" class="borde_tab">
    <tr >
         <td class="listado5" width="30%">Cédula:</td>
	 <td class="listado1" width="70%"><?=$datos_rc["cedula"]?></td>
    </tr>
    <tr >
         <td class="listado5" width="30%">Nombres:</td>
	 <td class="listado1" width="70%"><?=$datos_rc["nombre"]?></td>
    </tr>
 
     <tr>
                <td class="listado5">Estado Civil:</td>
                <td class="listado1"><?=$datos_rc["estado_civil"]?></td>
     </tr>
     <tr>
                <td class="listado5">Domicilio:</td>
                <td class="listado1"><?=$datos_rc["domicilio"]?></td>
     </tr>
         <tr>
                <td class="listado5">Intrucción:</td>
                <td class="listado1"><?=$datos_rc["instruccion"]?></td>
     </tr>
         <tr>
                <td class="listado5">Profesión:</td>
                <td class="listado1"><?=$datos_rc["profesion"]?></td>
     </tr>
   
</table>

    <table width="100%" border="0" cellspacing="1" cellpadding="0" align="center" class="borde_tab">
     <tr>
 	<td class=listado2  align="center">
	    <center><input name="Cerrar" type="button" class="botones" id="envia22" onClick="window.close();"value="Cerrar"></center>
	</td>
    </tr>
    </table>

<br/>
</form>

</body>
</html>
