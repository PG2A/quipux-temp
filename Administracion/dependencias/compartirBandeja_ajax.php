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
if($_SESSION["usua_admin_sistema"]!=1) {
    die("");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2)."/funciones.php"); //para traer funciones p_get y p_post
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once("../usuarios_dependencias/refrescarArbol.php");

p_register_globals(array());

$txt_depe_codi = isset($_GET['dependencia']) ? (int)$_GET['dependencia'] : 0;
$slc_padre = isset($_GET['padre']) ? (int)$_GET['padre'] : 0;

if($txt_depe_codi!=0)
{
    $tituloJefe = 'Datos del Jefe de &Aacute;rea';
    $depeCodi = $txt_depe_codi;
}
else
{
    $tituloJefe = 'Datos del Jefe del &Aacute;rea Padre';
    $depeCodi = $slc_padre;
}
//Obtener datos del Jefe de Área
$datosJefe = ObtenerJefeArea($_SESSION["inst_codi"], $depeCodi, '1', $db);

if(isset($datosJefe['ciudad']) && $datosJefe['ciudad'])
{
    $codigoCiu = $datosJefe['ciudad'];
    $ciudad = ObtenerCiudadUsua(' ciudad ', ' id = '. $codigoCiu, $db);
}
?>
<br>
<?php //Comentado por nueva funcinalidad de administrar_jefe_ajax.php 
?>
 
<br>
<?php if(isset($datosJefe['usua_codi']) && trim($datosJefe['usua_codi'])!='' and $_GET['accion'] == '2') { ?>
<table width="100%" class="borde_tab">
    <tr>
        <td align="center" class="titulos4" colspan="3"><font size="2">Compartir Bandeja de Documentos Recibidos</font></td>
    </tr>
    <tr>
        <td width="25%" align="center" class="titulos2">Compartir con</td>
        
        <td width="60%" align="center" class="titulos2">Lista de Usuarios </td>
    </tr>
    <tr>
        <td align="center" class="listado2">
        <?php
            $sql = "select
                         usua_nomb || ' ' || usua_apellido || ' ' ||
              case when usua_codi in 
              (select usua_subrogado from usuarios_subrogacion where usua_visible=1) = true then
              '(Subrogado)' else '' end || ' ' ||
              --Subrogante
              case when usua_codi in 
              (select usua_subrogante from usuarios_subrogacion where usua_visible=1) = true then
              '(Subrogante)' else '' end as usua_nombre,
                        usua_codi
                    from
                        usuario
                    where
                        usua_esta=1
                        and depe_codi=$depeCodi
                        and cargo_tipo <> 1 and usua_codi not in (select usua_codi from bandeja_compartida where usua_codi_jefe = " . (isset($datosJefe['usua_codi']) ? $datosJefe['usua_codi'] : 0) . ")
                    order by 1 asc";
            //echo $sql;
            $rs = $db->conn->Execute($sql);
            
             $slMultiple = $rs->GetMenu2('usuarioSel[]', 0, false, true, 8, " id='usuario' class='select' ");             
             echo $slMultiple;
        ?>
        </td>
        <td valign="top" class="listado2">
            <table width="100%" class="borde_tab">
                <tr>
                    <td width="60%" align="center" class="titulos2">Nombre</td>
                    <td width="45%" align="center" class="titulos2">Puesto</td>
                    <td width="15%" align="center" class="titulos2">Acci&oacute;n</td>
                </tr>
                <?php

    $editar_area = obtenerCodigos($_SESSION['usua_codi'],$depeCodi,$db);
                $jefeCodi = isset($datosJefe['usua_codi']) ? $datosJefe['usua_codi'] : 0;
                $sql = 'select * from bandeja_compartida where usua_codi_jefe = '.$jefeCodi;
                //echo $sql;
                $rs = $db->conn->query($sql);
                while (!$rs->EOF) {
                    $datosUsua = ObtenerDatosUsuario($rs->fields["USUA_CODI"], $db);
                    echo '<tr>';
                        echo '<td align="left" class="listado2_ver">'.$datosUsua['nombre'].'</td>';
                        echo '<td align="left" class="listado2_ver">'.$datosUsua['cargo'].'</td>';
                          if (trim($editar_area)==1 || $_SESSION['usua_codi']==0 || $_SESSION['perm_admin_institucional']==1) 
                        echo '<td align="left" class="listado2"><input type="button" value="eliminar" onclick="compartir_bandeja('.$rs->fields["BAN_COM_CODI"].','.$depeCodi.',2);" class="botones"></td>';
                    echo '</tr>';
                    $rs->MoveNext();
                }
                ?>
            </table>
        </td>
    </tr>
    <tr>
        <td width="15%" align="center" class="listado2">
            <?php
            $fnjava= "compartir_bandeja('".$datosJefe['usua_codi']."','".$depeCodi."',1);";
              if (trim($editar_area)==1 || $_SESSION['usua_codi']==0 || $_SESSION['perm_admin_institucional']==1) 
            echo '<input type="button" class="botones" value="Aceptar" onclick="'.$fnjava.'">';
            ?>
        </td>
        <td colspan="2" class="listado2"></td>
    </tr>
</table>
<?php } ?>