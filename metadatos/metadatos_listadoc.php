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
 * @package    metadatos
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
include_once(dirname(__DIR__).'/rec_session.php');
if (isset ($replicacion) && $replicacion && $config_db_replica_trd_lista_expediente!="") {
    $db = new ConnectionHandler(__DIR__, $config_db_replica_trd_lista_expediente);
}
require_once(dirname(__DIR__).'/tipo_documental/obtener_datos_trd.php');
include_once(dirname(__DIR__).'/funciones_interfaz.php');

echo "<!DOCTYPE html>".html_head();
$verrad = $_GET['verrad'] ?? $_POST['verrad'] ?? '';
$nivel_seguridad_documento = $nivel_seguridad_documento ?? 0;
?>
<script type="text/javascript">
    var tot_exp=0;
    function regresar(){
        window.location.reload();
    }
    function incluir_documento()
    {
        window.open("../expediente/IncluirEnExpediente.php?numrad=<?=$verrad?>","MflujoExp<?=$verrad?>","height=450,width=750,scrollbars=yes");
    }
   
</script>

<body bgcolor="#FFFFFF" topmargin="0">
    <center>

<?php
    $boton = "";
        if ($nivel_seguridad_documento > 3)
            $boton = "<input type='button' onClick='DefinirMetadato();' name='Submit1' value='Definir Metadatos' ".
                     "title='Se define el metadato para el documento' class='botones_largo'>";
        
        $rs = ConsultarMetadatosRadiDoc($db, $verrad);
?>
        <table border="0" width="100%" class="borde_tab titulos2" align="center">
            <tr>
                <th><?=$descDependencia?></th><th>Metadato</th>
            </tr>
<?php
        $met_codi = 0;
        $filas_metadato = 0;
        while ($rs && !$rs->EOF) {
            $nombre_trd = $rs->fields["METADATO_TEXTO"];
            echo '<tr><td class="listado1">'.$rs->fields["DEPE_NOMB"].'</td><td class="listado1">'.$nombre_trd.'</td></tr>';
            if ($rs->fields["DEPE_CODI"] == ($_SESSION["depe_codi"] ?? null)) {
                $met_codi = $rs->fields["MET_CODI"];
            }
            $filas_metadato++;
            $rs->MoveNext();
        }
        if ($filas_metadato == 0) {
            $area_actual = $usr_actual["dependencia"] ?? ($_SESSION["depe_nomb"] ?? '');
            echo '<tr><td class="listado1">'.$area_actual.'</td><td class="listado1">Este documento no tiene metadato definido.</td></tr>';
        }

?>
        </table>

<?php
    echo "<br><center>$boton</center><br>";
?>
</body>
</html>