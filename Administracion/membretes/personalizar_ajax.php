<?php
session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once(__DIR__.'/membretes_lib.php');
if (($_SESSION["usua_admin_sistema"] ?? 0) != 1) die("");
$memb = (int)($_GET["id"] ?? 0);
$tipo = (int)($_GET["tipo"] ?? 0);
$txt = (string)($_GET["txt"] ?? "");
$solo = (string)($_GET["solo"] ?? "todas");
$modo = (string)($_GET["modo"] ?? "areas");
$hoja = membrete_obtener($db, $memb);
if (!$hoja) die("Hoja membretada no v&aacute;lida.");
if ($modo == "tipos") {
    $tipos = membrete_tipos_uso($db, $memb);
    $filas = array();
    foreach ($tipos as $t) {
        if ($solo == "marcadas" && !$t['marcada']) continue;
        if ($solo == "sin" && $t['marcada']) continue;
        $filas[] = $t;
    }
    $marcadas = 0;
    foreach ($tipos as $t) if ($t['marcada']) $marcadas++;
    echo "<div class='memb-lista-cab'><b>" . count($tipos) . "</b> tipo(s) de documento &middot; <b>$marcadas</b> de ellos usan esta hoja en todas las &aacute;reas" . ($solo != "todas" ? " &middot; mostrando " . count($filas) : "") . "</div>";
    if (count($filas) == 0) {
        echo "<div class='memb-lista-vacia'>Ning&uacute;n tipo de documento coincide con el filtro.</div>";
        die("");
    }
    echo "<div class='memb-lista'>";
    foreach ($filas as $t) {
        $chk = $t['marcada'] ? "checked" : "";
        $cls = $t['marcada'] ? "memb-fila marcada" : "memb-fila";
        echo "<label class='$cls'><input type='checkbox' class='memb-chk' value='" . $t['trad_codigo'] . "' $chk>";
        echo "<span class='memb-fila-nombre'>" . membrete_h($t['trad_descr']) . "</span>";
        echo "<span class='memb-fila-usa'>usa: " . membrete_h($t['usa']) . "</span></label>";
    }
    echo "</div>";
    die("");
}
$areas = membrete_areas_uso($db, $memb, $tipo, $txt);
$filas = array();
foreach ($areas as $a) {
    if ($solo == "marcadas" && !$a['marcada']) continue;
    if ($solo == "sin" && $a['marcada']) continue;
    $filas[] = $a;
}
$marcadas = 0;
foreach ($areas as $a) if ($a['marcada']) $marcadas++;
echo "<div class='memb-lista-cab'><b>" . count($areas) . "</b> &aacute;rea(s) coinciden con la b&uacute;squeda &middot; <b>$marcadas</b> de ellas usan esta hoja" . ($tipo > 0 ? " para este tipo de documento" : " para todos los tipos") . ($solo != "todas" ? " &middot; mostrando " . count($filas) : "") . "</div>";
if (count($filas) == 0) {
    echo "<div class='memb-lista-vacia'>Ninguna &aacute;rea coincide con la b&uacute;squeda.</div>";
    die("");
}
echo "<div class='memb-lista'>";
foreach ($filas as $a) {
    $chk = $a['marcada'] ? "checked" : "";
    $cls = $a['marcada'] ? "memb-fila marcada" : "memb-fila";
    echo "<label class='$cls'><input type='checkbox' class='memb-chk' value='" . $a['depe_codi'] . "' $chk>";
    echo "<span class='memb-fila-nombre'>" . membrete_h($a['depe_nomb']) . ($a['sigla'] != '' ? " <small>(" . membrete_h($a['sigla']) . ")</small>" : "") . "</span>";
    echo "<span class='memb-fila-usa'>usa: " . membrete_h($a['usa']) . "</span></label>";
}
echo "</div>";
