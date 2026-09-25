<?php
session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once(__DIR__.'/membretes_lib.php');
include_once(__DIR__.'/demo_puntos.php');
if (($_SESSION["usua_admin_sistema"] ?? 0) != 1) {
    echo html_error("No tiene permisos para administrar hojas membretadas.");
    die("");
}
$areas = membrete_areas($db);
$tipos = membrete_tipos_documento($db);
$depe = (int)($_GET["depe"] ?? ($_SESSION["depe_codi"] ?? 0));
$tipo = (int)($_GET["tipo"] ?? 0);
$inst = (int)($_SESSION["inst_codi"] ?? 0);
$area_nombre = membrete_area_en_alcance($db, $depe);
if ($area_nombre === null) { $depe = (int)($_SESSION["depe_codi"] ?? 0); $area_nombre = (string)membrete_area_en_alcance($db, $depe); }
$puntos = membrete_demo_puntos();
$origenes = array(
    'area'        => 'Asignada al &aacute;rea (m&oacute;dulo)',
    'tipo'        => 'Asignada al tipo de documento para todas las &aacute;reas (m&oacute;dulo)',
    'defecto'     => 'Hoja por defecto de la instituci&oacute;n (m&oacute;dulo)',
    'area_legado' => 'Archivo del &aacute;rea en Administraci&oacute;n de &Aacute;reas (anterior)',
    'ninguno'     => 'Sin hoja membretada',
);
$res_doc = ObtenerRutaMembrete($depe, $tipo, $inst, $db);
$res_rep = ObtenerRutaMembrete($depe, 0, $inst, $db);
$nombre_hoja = function ($r) use ($db) {
    if ($r['memb_codi'] > 0) { $m = membrete_obtener($db, $r['memb_codi']); return $m ? $m['memb_nombre'] : 'Hoja ' . $r['memb_codi']; }
    return $r['ruta'] != '' ? basename($r['ruta']) : '';
};
$resumen = array('modulo' => 0, 'sin' => 0, 'legado' => 0);
foreach ($puntos as $p) $resumen[$p['estado']]++;
echo "<!DOCTYPE html>".html_head();
?>
<link rel="stylesheet" type="text/css" href="/estilos/membretes.css?v=8">
<script>
function llamaCuerpo(parametros){
    if (window.bloquearPantalla) bloquearPantalla('Cargando...');
    window.location.href = parametros;
}
function recargar(){
    llamaCuerpo('demo_membretes.php?depe=' + document.getElementById('slc_depe').value + '&tipo=' + document.getElementById('slc_tipo').value);
}
</script>
<body>
<?php dibujar_loader_pantalla('Cargando...', '/imagenes/escudo_blanco.png'); ?>
<center>
<form name="formulario" id="formulario" method="get" action="" onsubmit="recargar(); return false;">
<br>
<table width="92%" class="borde_tab">
  <tr>
    <td colspan="2" class="titulos4"><div align="center"><strong>Demo: d&oacute;nde se aplica la hoja membretada</strong></div></td>
  </tr>
  <tr>
    <td colspan="2" class="listado2">
      <div class="memb-aviso info">Quipux genera PDF en <b><?=count($puntos)?> puntos</b>. <b><?=$resumen['modulo']?></b> ya toman la hoja desde este m&oacute;dulo, <b><?=$resumen['sin']?></b> nunca llevan hoja por dise&ntilde;o y <b><?=$resumen['legado']?></b> sigue leyendo el archivo antiguo del &aacute;rea. Elija un &aacute;rea y un tipo de documento para ver qu&eacute; hoja recibir&iacute;a cada PDF y genere un ejemplo real con el motor del sistema.</div>
    </td>
  </tr>
  <tr>
    <td class="titulos2" width="22%">Simular como &aacute;rea</td>
    <td class="listado2">
      <select name="depe" id="slc_depe" class="select" onchange="recargar()">
<?php foreach ($areas as $a) { ?>
        <option value="<?=$a['depe_codi']?>" <?=$a['depe_codi']==$depe?"selected":""?>><?=membrete_h($a['depe_nomb'])?></option>
<?php } ?>
      </select>
      <select name="tipo" id="slc_tipo" class="select" onchange="recargar()">
        <option value="0">Tipo de documento: cualquiera</option>
<?php foreach ($tipos as $t) { ?>
        <option value="<?=$t['trad_codigo']?>" <?=$t['trad_codigo']==$tipo?"selected":""?>><?=membrete_h($t['trad_descr'])?></option>
<?php } ?>
      </select>
    </td>
  </tr>
  <tr>
    <td class="titulos2">Hoja para documentos</td>
    <td class="listado2"><b><?=membrete_h($nombre_hoja($res_doc))?></b> &middot; <?=$origenes[$res_doc['origen']]?></td>
  </tr>
  <tr>
    <td class="titulos2">Hoja para reportes</td>
    <td class="listado2"><b><?=membrete_h($nombre_hoja($res_rep))?></b> &middot; <?=$origenes[$res_rep['origen']]?> <span class="memb-ayuda">(los reportes usan el &aacute;rea del usuario que los genera, sin tipo de documento)</span></td>
  </tr>
  <tr>
    <td colspan="2" class="listado2" align="center">
      <input type="button" class="botones" value="Hojas membretadas" onClick="llamaCuerpo('cuerpo_membretes.php')">
      <input type="button" class="botones" value="Regresar" onClick="llamaCuerpo('mnu_membretes.php')">
    </td>
  </tr>
</table>
<br>
<div class="memb-listado" style="width: 92%">
<table cols="6" border="1" class="memb-demo">
  <tr>
    <th>#</th><th>Acci&oacute;n del usuario</th><th>Archivo que genera</th><th>Formato</th><th>Estado</th><th>Hoja que recibir&iacute;a</th><th>Ejemplo</th>
  </tr>
<?php foreach ($puntos as $i => $p) {
    $r = $p['formato'] == 'V' ? $res_doc : $res_rep;
    if ($p['estado'] == 'sin') $hoja = 'Ninguna (por dise&ntilde;o)';
    elseif ($p['estado'] == 'legado') $hoja = 'Archivo del &aacute;rea (pantalla antigua)';
    else $hoja = membrete_h($nombre_hoja($r)) . ' <span class="memb-sub">' . $origenes[$r['origen']] . '</span>';
    $etq = array('modulo' => '<span class="memb-estado propia">Usa el m&oacute;dulo</span>', 'sin' => '<span class="memb-estado sin">Sin hoja</span>', 'legado' => '<span class="memb-estado heredada">Anterior</span>');
?>
  <tr>
    <td><?=$i + 1?></td>
    <td><b><?=$p['accion']?></b><div class="memb-sub"><?=$p['detalle']?></div></td>
    <td><code><?=$p['archivo']?></code></td>
    <td><?=$p['formato']?></td>
    <td><?=$etq[$p['estado']]?></td>
    <td><?=$hoja?></td>
    <td>
<?php if ($p['estado'] == 'modulo') { ?>
      <a class="vinculos" href="demo_pdf.php?punto=<?=$i?>&depe=<?=$depe?>&tipo=<?=$tipo?>" target="_blank">Ver ejemplo</a>
<?php } else { ?>
      &mdash;
<?php } ?>
    </td>
  </tr>
<?php } ?>
</table>
</div>
</form>
</center>
</body>
</html>
