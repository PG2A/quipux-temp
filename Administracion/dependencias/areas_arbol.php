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
 * Vista árbol (sólo lectura) de la administración de Áreas y Puestos. Muestra la
 * jerarquía de áreas y, dentro de cada una, sus puestos como hojas. No edita nada;
 * se carga por AJAX en el div 'div_arbol' de areas.php.
 *
 * @package    dependencias
 * @author     2026 Marco Terán <marcosdanny14@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
if (($_SESSION["usua_admin_sistema"] ?? 0) != 1 and ($_SESSION["usua_codi"] ?? -1) != 0) {
    die("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.");
}
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');

$inst_codi = 0 + $_SESSION["inst_codi"];
$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

// Áreas de la institución
$rsA = $db->conn->Execute(
    "select depe_codi, depe_codi_padre, depe_nomb, dep_sigla, depe_estado, estructura_organica
       from dependencia where inst_codi = $inst_codi order by depe_nomb");
$areas    = array();   // codi => datos
$hijos    = array();   // padre => [codi,...]
$es_raiz  = array();   // codi => bool
while ($rsA && !$rsA->EOF) {
    $c = $rsA->fields;
    $id  = (int)$c['DEPE_CODI'];
    $pad = (int)$c['DEPE_CODI_PADRE'];
    $areas[$id] = $c;
    $es_raiz[$id] = ($pad == 0 || $pad == $id);
    if (!$es_raiz[$id]) $hijos[$pad][] = $id;
    $rsA->MoveNext();
}

// Puestos por área (catálogo cargo)
$rsC = $db->conn->Execute(
    "select depe_codi, cargo_nombre, cargo_tipo, cargo_estado
       from cargo where inst_codi = $inst_codi
      order by cargo_estado desc, cargo_tipo desc, lower(cargo_nombre)");
$puestos = array();
while ($rsC && !$rsC->EOF) {
    $puestos[(int)$rsC->fields['DEPE_CODI']][] = $rsC->fields;
    $rsC->MoveNext();
}

/**
 * Dibuja recursivamente un área, sus sub áreas y sus puestos (hojas).
 */
function pintar_area($id, $areas, $hijos, $puestos, $h) {
    if (!isset($areas[$id])) return '';
    $a = $areas[$id];
    $tiene_hijos = !empty($hijos[$id]);
    $tiene_puestos = !empty($puestos[$id]);
    $activa = ((int)$a['DEPE_ESTADO'] == 1);
    $estruct = ($a['ESTRUCTURA_ORGANICA'] === 't' || $a['ESTRUCTURA_ORGANICA'] === true || $a['ESTRUCTURA_ORGANICA'] == 1);

    $etiqueta = $h($a['DEPE_NOMB'])
              . ' <span style="color:#8a94a8">('.$h($a['DEP_SIGLA']).')</span>'
              . (!$activa ? ' <i style="color:#b33">inactiva</i>' : '')
              . ($estruct ? ' <span class="badge-inline">estructura</span>' : '');

    // Los nodos con contenido arrancan plegados (el árbol es grande); se abren al clic.
    $html = ($tiene_hijos || $tiene_puestos) ? '<li class="cerrada">' : '<li>';
    if ($tiene_hijos || $tiene_puestos) {
        $html .= '<span class="sumillas-toggle">'.$etiqueta.'</span>';
        $html .= '<ul class="sumillas-nivel">';
        // Sub áreas primero
        if ($tiene_hijos) {
            $orden = $hijos[$id];
            usort($orden, function($x, $y) use ($areas) {
                return strcasecmp($areas[$x]['DEPE_NOMB'], $areas[$y]['DEPE_NOMB']);
            });
            foreach ($orden as $hijo) {
                $html .= pintar_area($hijo, $areas, $hijos, $puestos, $h);
            }
        }
        // Puestos como hojas
        if ($tiene_puestos) {
            foreach ($puestos[$id] as $p) {
                $jefe = ((int)$p['CARGO_TIPO'] == 1) ? ' <b>(Jefe)</b>' : '';
                $inact = ((int)$p['CARGO_ESTADO'] == 1) ? '' : ' <i style="color:#b33">inactivo</i>';
                $html .= '<li><span class="sumillas-item" style="cursor:default">&#128100; '
                       . $h($p['CARGO_NOMBRE']).$jefe.$inact.'</span></li>';
            }
        }
        $html .= '</ul>';
    } else {
        $html .= '<span class="sumillas-item" style="cursor:default">'.$etiqueta.'</span>';
    }
    $html .= '</li>';
    return $html;
}

// Raíces de primer nivel: áreas cuyo padre es una raíz (la institución)
$primer_nivel = array();
foreach ($areas as $id => $a) {
    $pad = (int)$a['DEPE_CODI_PADRE'];
    if (!$es_raiz[$id] && isset($es_raiz[$pad]) && $es_raiz[$pad]) $primer_nivel[] = $id;
}
usort($primer_nivel, function($x, $y) use ($areas) {
    return strcasecmp($areas[$x]['DEPE_NOMB'], $areas[$y]['DEPE_NOMB']);
});

echo '<div class="sumillas-arbol" style="max-height:520px">';
echo '<div class="listado2" style="margin:0 0 6px"><b>Vista de solo lectura.</b> '
   . '&#128193; áreas y sub áreas &nbsp; &#128100; puestos. Haga clic en un área para plegar o desplegar.</div>';
if (empty($primer_nivel)) {
    echo '<i>No hay áreas registradas.</i>';
} else {
    echo '<ul class="sumillas-nivel">';
    foreach ($primer_nivel as $id) {
        echo pintar_area($id, $areas, $hijos, $puestos, $h);
    }
    echo '</ul>';
}
echo '</div>';
