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
 * @package    tipo_documental
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
require_once(dirname(__DIR__).'/funciones.php');
if (isset ($replicacion) && $replicacion && $config_db_replica_tx_formenvio_ajax!="") {
    $db = new ConnectionHandler(dirname(__DIR__), $config_db_replica_tx_formenvio_ajax);
}

    $area = limpiar_sql($_GET["area"]);
    if (trim($area,",0123456789 ")!="") $area = 0 + $area;

    $where = "";
    if (($_GET["codTx"]==9)  and $_SESSION["depe_codi"] != $area)
        $where = " and (cargo_tipo=1 or usua_codi in (select usua_codi from permiso_usuario where id_permiso=29)) ";

    $sql = "select (usua_apellido || ' ' || usua_nomb)
                || ' ' || case when usua_codi in (select usua_subrogado from usuarios_subrogacion where usua_visible=1) = true then '(Subrogado)' else '' end
                || ' ' || case when usua_codi in (select usua_subrogante from usuarios_subrogacion where usua_visible=1) = true then '(Subrogante)' else '' end as usua_nombre
                , usua_codi
            from usuarios
            where usua_codi>0 and usua_esta=1 and visible_sub=1 and usua_login not like 'UADM%'
                and depe_codi in ($area) $where
            order by 1";
    //echo $sql;
    $rs_usr = $db->conn->Execute($sql);

    if ($_GET["codTx"]==8  ){
        $menu_usr  = $rs_usr->GetMenu2("usCodSelect[]", 0, false, true, 8," id='usCodSelect' class='select'" );
       
    }
    if ($_GET["codTx"]==9 or $_GET["codTx"]==69)//
        $menu_usr  = $rs_usr->GetMenu2("usCodSelect", 0, "0:&lt;&lt; Seleccione Usuario &gt;&gt;", false,""," id='usCodSelect' class='select'" );
         
    echo $menu_usr

    ?>
