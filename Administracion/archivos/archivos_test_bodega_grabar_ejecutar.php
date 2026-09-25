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
 * @package    archivos
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

include_once(dirname(__DIR__, 2).'/config.php');
include_once(dirname(__DIR__, 2).'/include/db/ConnectionHandler.php');

error_reporting(7);
$db = new ConnectionHandler(dirname(__DIR__, 2),"busqueda");
$db->conn->SetFetchMode(ADODB_FETCH_ASSOC);

$db_bodega = new ConnectionHandler(dirname(__DIR__, 2), "bodega_test");
$db_bodega->conn->SetFetchMode(ADODB_FETCH_ASSOC);

$anio = 0 + $_POST["anio"];
$offset = 0 + $_POST["offset"];
$limit = 1;

$sql = "select anex_codigo, anex_path, anex_nombre from anexos where anex_codigo like '$anio%' order by anex_codigo limit $limit offset ".($limit*$offset);
$rs = $db->query($sql);

echo "$sql<br>";
if (!$rs or $rs->EOF) {
    //sleep(15);
    die ("Error - No se encuentran m&aacute;s archivos");
}

while (!$rs->EOF) {
    if (is_file(dirname(__DIR__, 2)."/bodega".$rs->fields["ANEX_PATH"])) {
        $archivo = base64_encode(file_get_contents(dirname(__DIR__, 2)."/bodega".$rs->fields["ANEX_PATH"]));
        $sql = "select func_grabar_archivo('".$rs->fields["ANEX_NOMBRE"]."', '$archivo') as arch_codi";

        list($useg, $seg) = explode(" ", microtime());
        $tiempo_inicio = 0 + $seg + $useg;

        $rs_bodega = $db_bodega->query($sql);

        list($useg, $seg) = explode(" ", microtime());
        $tiempo_fin = 0 + $seg + $useg;
    }
    echo ($tiempo_fin-$tiempo_inicio) . " - ". $rs_bodega->fields["ARCH_CODI"]."<br>";

    $sql = "insert into tmp_tiempo_insert (arch_codi, tiempo) values (".$rs_bodega->fields["ARCH_CODI"].", ".($tiempo_fin-$tiempo_inicio).")";
    $db_bodega->query($sql);

    $rs->MoveNext();
}
?>