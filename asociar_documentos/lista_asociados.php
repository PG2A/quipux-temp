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
 * @package    anexos
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
if (isset ($replicacion) && $replicacion && $config_db_replica_info_lista_asociados!="") {
    $db = new ConnectionHandler(__DIR__, $config_db_replica_info_lista_asociados);
}


$radi_nume = limpiar_numero($_GET['verrad']);    //se incluyo por register_globals

include_once(dirname(__DIR__).'/funciones_interfaz.php');
echo "<!DOCTYPE html>".html_head();

?>
  <body >
    <center>
<?
    include(dirname(__DIR__)."/recursivas/funciones_recursivas.php");
    $lista = new FuncionesRecursivas($db);
    $lista->tabla = "radicado";
    $lista->id_tabla = "radi_nume_radi";
    $lista->id_padre = "radi_nume_asoc";
    $lista->condicion = "esta_codi in (0,1,2,3,4,5,6)";
//$lista->display["debug"] = true;
    $lista->buscar_padre($radi_nume,$lista->condicion);
//$lista->display["debug"] = false;
    $lista->add_campo("No. Documento", "30", "radi_nume_text");
    $lista->add_campo("Fecha", "20", "substr(radi_fech_ofic::text,1,19)||' GMT -5'", "L","popup_ver_documento","radi_nume_radi");
    $lista->add_campo("Asunto", "50", "radi_asunto");
    $lista->resaltar_fila($radi_nume);
    echo $lista->generar_tabla_recursiva();

    if($nivel_seguridad_documento>=4 or $_SESSION["ver_todos_docu"]==1) {
        echo "<br>
              <input type='button' name='btn_asociar' value='Asociar Documentos' class='botones_largo' onClick='AsociarDocumento();'>
              <br>&nbsp;";
    }
?>
    </center>
  </body>
</html>



