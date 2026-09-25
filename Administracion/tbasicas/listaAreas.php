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
 * @package    tbasicas
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function obtenerAreas($codInst, $db)
{
    // Fetch ALL active dependencies for this institution in ONE query
    // This avoids the N+1 query problem and recursion timeouts
    $sql = "SELECT depe_codi, depe_nomb, depe_codi_padre 
            FROM dependencia 
            WHERE depe_estado=1 AND inst_codi = $codInst 
            ORDER BY depe_nomb";
            
    $rs = $db->conn->query($sql);
    
    $deps = [];
    while(!$rs->EOF) {
        $row = $rs->fields;
        $id = $row['DEPE_CODI'];
        $nom = $row['DEPE_NOMB'];
        $pId = $row['DEPE_CODI_PADRE'];
        
        // Handle root nodes (where parent is same as id, or null/0)
        // In this system, root usually means depe_codi = depe_codi_padre
        if ($pId == $id || empty($pId)) {
            $pId = 'ROOT';
        }
        
        $deps[$pId][] = ['id' => $id, 'nom' => $nom];
        $rs->MoveNext();
    }
    
    // Start rendering from ROOT
    return renderTree($deps, 'ROOT');
}

// Internal recursive helper to render the tree from memory
function renderTree($deps, $parentId) {
    if (empty($deps[$parentId])) return "";
    
    $html = "";
    foreach ($deps[$parentId] as $child) {
        $childId = $child['id'];
        $hasChildren = isset($deps[$childId]);
        
        $html .= '<li><a href="javascript:;" onclick="datosArea('.$childId.');">';
        $html .= $child['nom'];
        $html .= "</a>";
        
        if ($hasChildren) {
             $html .= "<ul>" . renderTree($deps, $childId) . "</ul>";
        }
        
        $html .= "</li>";
    }
    return $html;
}

// Deprecated/Legacy shim if needed, or just unused now
function obtenerDependencia($depe_codi, $db, $nivel = 0){
    return ""; // No longer used by obtenerAreas
}
?>