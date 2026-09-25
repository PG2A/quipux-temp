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
 * @package    listas
 * @author      2025 Casen Xu<casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');

    unset($rsArea);
    if($_GET["codDepe"]!='')
    {
        $sqlArea = "select
                            depe_nomb
                         from
                            dependencia
                         where
                            depe_estado=1 and depe_codi = ".$_GET["codDepe"];
        $rsArea=$db->conn->query($sqlArea);
        while(!$rsArea->EOF)
        {
            $inputArea = $rsArea->fields["DEPE_NOMB"];
            $rsArea->MoveNext();
        }
    }
    else
        $inputArea = '';
    echo '&nbsp;&nbsp;'.$inputArea;
    //echo htmlspecialchars($menu_depeHijo);
?>