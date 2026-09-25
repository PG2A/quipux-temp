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

include_once(__DIR__.'/config.php');
include_once(__DIR__.'/include/db/ConnectionHandler.php');
include_once(__DIR__.'/funciones_interfaz.php');

if (isset($_GET['rsw']))
    $rsw=base64_decode($_GET['rsw']);
else
    $rsw = 0;

if ($rsw!=1){
    session_start();    
    include_once(__DIR__.'/rec_session.php');
} else {
    if (!isset ($config_db_replica_contenido_index)) $config_db_replica_contenido_index = "";
    $db = new ConnectionHandler(__DIR__,$config_db_replica_contenido_index);
    $db->conn->SetFetchMode(ADODB_FETCH_ASSOC);
}

$cuenta_mail = "";
$texto = "";


//Se consulta contenido de catalogos de la pagina Index
$sql = "select * from contenido c
left outer join contenido_tipo  ct
on c.cont_tipo_codi = ct.cont_tipo_codi
where ct.funcionalidad = 'Ayuda'";
$rs = $db->conn->query($sql);
if($rs && !$rs->EOF){
    $texto = $rs->fields['TEXTO'];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Soporte · Quipux UCUENCA</title>
    <link rel="shortcut icon" href="/imagenes/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="estilos/soporte.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-in">
        <a class="brand" href="index.php">Quipux UCUENCA</a>
        <span class="topbar-sub">Soporte a Usuarios</span>
        <a class="topbar-home" href="index.php" title="Regresar al inicio"><svg viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg><span>Inicio</span></a>
    </div>
</header>
<main class="soporte-main">
    <div class="glass soporte-card">
<?php

    //Si no hay el texto en la base de datos se obitne el texto extático
    if(trim($texto ?? '')=="")
        $texto = obtener_texto_estatico();
    
    //Se obtiene correo electrónico de la Institución Actual
    if ($rsw!=1){
        $sql = "select inst_email from institucion where inst_codi = ".$_SESSION["inst_codi"];
        $rs = $db->conn->query($sql);
        $cuenta_mail = $rs->fields['INST_EMAIL']; 
    }

    if (trim($cuenta_mail ?? '')=='')
        $cuenta_mail = $cuenta_mail_soporte;

    $correo_comodin = "**cuenta_correo**";    

    //Se reemplaza el correo según corresponda en el texto de ayuda.
    $texto = str_replace($correo_comodin, $cuenta_mail, $texto);    
    echo $texto;
?>
    </div>
</main>
<footer class="soporte-foot">Dirección de Tecnologías de la Información y Comunicación · DTIC</footer>
</body>
</html>

<?php

function obtener_texto_estatico(){
    
    $texto_estatico = "<table width='80%' border='0' align='center' bgcolor='#a8bac6'>
	<tr>
	<td>
		<center>
		<p><B><span><font size='2' face='Verdana,Arial,Helvetica,sans-serif'>SOPORTE A USUARIOS DEL SISTEMA</br>
                            Subsecretar&iacute;a de Gobierno Electr&oacute;nico
                            </font></span></B> </p>
	</td>
	</tr>
        </table>
        <table width='80%' border='0' align='center' bgcolor='#e3e8ec'>
	
        <TR>
		<TD>
                    <font size='2' face='Verdana,Arial,Helvetica,sans-serif' color='#086478'><b>Manuales de Usuario:</b></font></br></br>
        <a href='http://www.informatica.gov.ec/descargas/ManualBandejaSalidaQuipuxV2_20110406.pdf' target='Manual Bandeja de Salida'><font size='2' face='Verdana,Arial,Helvetica,sans-serif'>Descargar Manual Bandeja de Salida </font></a>
        </TD>
	</TR>
        <TR>
		<TD>
        <a href='http://www.informatica.gov.ec/descargas/ManualBandejaEntradaQuipux_20110405.pdf' target='Manual Bandeja de Entrada'><font size='2' face='Verdana,Arial,Helvetica,sans-serif'>Descargar Manual Bandeja de Entrada </font></a>
        </TD>
	</TR>
        <TR><TD>&nbsp;</TD></TR>
	<TR>
		<TD><font size='2' face='Verdana,Arial,Helvetica,sans-serif'>Para problemas o incidentes del sistema, por favor comunicarse al siguiente correo electrónico:</font></TD>
	</TR>
	<TR><TD>&nbsp;</TD></TR>
	<TR>            
            <TD><center><a href='mailto:**cuenta_correo**'>**cuenta_correo**</a></center></TD>          
	</TR>
	<TR><TD>&nbsp;</TD></TR>
	<TR>
		<TD><font size='2' face='Verdana,Arial,Helvetica,sans-serif' color='#086478'><b>Con los siguentes datos:</b></font></TD>
	</TR>
	<TR>
		<TD><font size='2' face='Verdana,Arial,Helvetica,sans-serif'>- Nombre de la Institución</font></TD>
	</TR>
	<TR>
		<TD><font size='2' face='Verdana,Arial,Helvetica,sans-serif'>- Nombre Completo</font></TD>
	</TR>
	<TR>
		<TD><font size='2' face='Verdana,Arial,Helvetica,sans-serif'>- Cargo</font></TD>
	</TR>
	<TR>
		<TD><font size='2' face='Verdana,Arial,Helvetica,sans-serif'>- Una descripción concisa y precisa sobre el problema (Si es posible enviar como adjunto pantallas que muestren el error)</font></TD>
	</TR>
        <tr>
        <td><br><br>
        <font size='2' face='Verdana,Arial,Helvetica,sans-serif' color='#086478'><b>Requerimientos del Sistema:</b></font>
        <br><br>
        <font size='2' face='Verdana,Arial,Helvetica,sans-serif' >
            Hardware
        <ul>

        <li> Procesador: 2000MHz de velocidad por CPU mínimo   </li>
        <li>  Espacio en disco: 600MB libre mínimo, recomendado 1GB   </li>
        <li>  Memoria física (RAM): 1GB mínimo, 2GB recomendado </li>
        <li> Adaptador de video: 256 colores mínimo   </li>
        <li>  Dispositivo apuntador o ratón  </li>
        <li> Enlace de acceso a la red Internet de 64kbps mínimo </li>
        <li> Dispositivo Token USB de firma digital (solo para funcionarios autorizados)  </li>
        <li>Scaner de alta velocidad A4 (para digitalización documentos entrada)</li>

    </ul>
    Software
    <ul>

        <li> Instalación programa navegador Mozilla Firefox vesión 3 o superior.  </li>
        </ul>

        Para Firma Digital:
        <ul>
        <li>  Instalación del programa manejador (driver) del token USB para Microsoft Windows XP o superiormendado </li>
        <li> Sistema operativo: Microsoft Windows XP o superior   </li>
        <li>  Instalación y funcionamiento apropiado del programa Maquina Virtual de Java (JVM) versión 1.5 </li>

    </ul>
        </font>
        </td>
        </tr>
        <tr>
            <td><font size='2' face='Verdana,Arial,Helvetica,sans-serif' color='#086478'><b>Información General:</b></font>
                <font size='2' face='Verdana,Arial,Helvetica,sans-serif' >
                Sistema de Gestión Documental se desarrolla y mantiene con el personal de la Subsecretaría de Tecnologías de la Información. 
                Inicialmente en el 2007 se basó en el sistema Orfeo, en su primera versión se adaptó las necesidades de las instituciones.
                En el 2008 se inició un desarrollo nuevo para cubrir con las necesidades de los usuarios en relación al ámbito de Gestión de Documentos. 
                Hasta la fecha se han generado 15 revisiones del sistema y se está en continuo cambio.</font>
            </td>        
        </tr>
    </table>";
    
    return $texto_estatico;
}
?>