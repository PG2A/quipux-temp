<?php
session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once(__DIR__.'/membretes_lib.php');
if (($_SESSION["usua_admin_sistema"] ?? 0) != 1) {
    echo html_error("No tiene permisos para administrar hojas membretadas.");
    die("");
}
$txt_buscar_area = trim((string)($_GET["txt_buscar_area"] ?? ""));
$slc_filtro = (string)($_GET["slc_filtro"] ?? "todas");
$slc_hoja = (int)($_GET["slc_hoja"] ?? 0);
$hojas = membrete_lista_activas($db);
$tipos = membrete_tipos_documento($db);
$defecto = membrete_defecto($db);
$csrf = membrete_csrf_token();
echo "<!DOCTYPE html>".html_head();
$paginador = new ADODB_Pager_Ajax(dirname(__DIR__, 2), "div_asignaciones", "busqueda_paginador_asignaciones.php", "txt_buscar_area,slc_filtro,slc_hoja");
?>
<link rel="stylesheet" type="text/css" href="/estilos/membretes.css?v=5">
<script>
var membDepe = 0;
var membCsrf = <?=json_encode($csrf)?>;
function llamaCuerpo(parametros){
    if (window.bloquearPantalla) bloquearPantalla('Cargando...');
    window.location.href = parametros;
}
function realizar_busqueda(){
    paginador_reload_div('');
}
function membAjax(metodo, url, datos, cb){
    var x = new XMLHttpRequest();
    x.open(metodo, url, true);
    if (metodo == 'POST') x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    x.onreadystatechange = function(){ if (x.readyState == 4) cb(x.responseText); };
    x.send(datos || null);
}
function abrir_modal(depe, nombre){
    membDepe = depe;
    document.getElementById('modal_area').textContent = nombre;
    document.getElementById('modal_tipo').value = '0';
    document.getElementById('modal_membrete').value = '0';
    document.getElementById('modal_resultado').innerHTML = '';
    document.getElementById('memb-modal').style.display = 'flex';
    cargar_actuales();
}
function cerrar_modal(){
    document.getElementById('memb-modal').style.display = 'none';
    paginador_reload_div('');
}
function cargar_actuales(){
    membAjax('GET', 'asignacion_ajax.php?depe=' + membDepe, null, function(html){
        document.getElementById('modal_actuales').innerHTML = html;
    });
}
function guardar_asignacion(){
    var tipo = document.getElementById('modal_tipo').value;
    var hoja = document.getElementById('modal_membrete').value;
    if (hoja == '0') { alert('Seleccione la hoja membretada.'); return; }
    var datos = 'accion=1&csrf_token=' + encodeURIComponent(membCsrf) + '&slc_area=' + membDepe + '&slc_tipo=' + tipo + '&slc_membrete=' + hoja;
    membAjax('POST', 'grabar_asignacion.php?ajax=1', datos, function(txt){
        mostrar_resultado(txt);
        cargar_actuales();
    });
}
function quitar_asignacion(masi){
    var datos = 'accion=2&csrf_token=' + encodeURIComponent(membCsrf) + '&id=' + masi;
    membAjax('POST', 'grabar_asignacion.php?ajax=1', datos, function(txt){
        mostrar_resultado(txt);
        cargar_actuales();
    });
}
function mostrar_resultado(txt){
    var ok = txt.indexOf('OK|') == 0;
    var msg = txt.replace(/^(OK|ERROR)\|/, '');
    var d = document.getElementById('modal_resultado');
    d.className = ok ? 'memb-aviso ok' : 'memb-aviso error';
    d.innerHTML = msg;
}
document.addEventListener('keydown', function(e){ if (e.key == 'Escape' && document.getElementById('memb-modal').style.display == 'flex') cerrar_modal(); });
</script>
<body onload="paginador_reload_div('')">
<?php dibujar_loader_pantalla('Cargando...', '/imagenes/escudo_blanco.png'); ?>
<center>
<form name="formulario" id="formulario" method="get" action="" onsubmit="realizar_busqueda(); return false;">
<br>
<table width="90%" class="borde_tab">
  <tr>
    <td colspan="2" class="titulos4"><div align="center"><strong>Asignaci&oacute;n de Hojas Membretadas</strong></div></td>
  </tr>
  <tr>
    <td class="titulos2" width="25%">Hoja por defecto</td>
    <td class="listado2">
<?php if ($defecto) { ?>
      <b><?=membrete_h($defecto['memb_nombre'])?></b>. La usan todas las &aacute;reas que no tengan una asignaci&oacute;n propia.
<?php } else { ?>
      <b>Ninguna.</b> Las &aacute;reas sin asignaci&oacute;n siguen usando el archivo cargado en Administraci&oacute;n de &Aacute;reas.
<?php } ?>
    </td>
  </tr>
  <tr>
    <td colspan="2" class="listado2">
<?php $por_tipo = membrete_asignaciones_por_tipo($db); if (count($por_tipo) > 0) { ?>
      <div class="memb-aviso info memb-por-tipo"><b>Por tipo de documento (todas las &aacute;reas):</b>
<?php foreach ($por_tipo as $i => $pt) { echo ($i > 0 ? " &middot; " : " ") . membrete_h($pt['trad_descr']) . " &rarr; <b>" . membrete_h($pt['memb_nombre']) . "</b>"; } ?>
      </div>
<?php } ?>
      <div class="memb-aviso info"><b>&iquest;Qu&eacute; muestra este listado?</b> Todas las &aacute;reas activas de la instituci&oacute;n (las mismas de Administraci&oacute;n &rarr; &Aacute;reas), una fila por &aacute;rea. No son registros creados aqu&iacute;: la columna "Hoja membretada" indica qu&eacute; hoja le corresponde hoy a cada &aacute;rea. "(por defecto)" significa que el &aacute;rea no tiene asignaci&oacute;n propia y usa la hoja por defecto; si un &aacute;rea tiene asignaciones, se listan por tipo de documento. Use "Asignar" o "Editar" para darle a un &aacute;rea una hoja distinta.</div>
    </td>
  </tr>
  <tr>
    <td class="titulos2">Buscar</td>
    <td class="listado2">
      <input type="text" name="txt_buscar_area" id="txt_buscar_area" class="tex_area" size="36" maxlength="100" value="<?=membrete_h($txt_buscar_area)?>" placeholder="Nombre o sigla del &aacute;rea">
      <select name="slc_filtro" id="slc_filtro" class="select" onchange="realizar_busqueda()">
        <option value="todas" <?=$slc_filtro=="todas"?"selected":""?>>Todas las &aacute;reas</option>
        <option value="asignadas" <?=$slc_filtro=="asignadas"?"selected":""?>>Con hoja asignada</option>
        <option value="defecto" <?=$slc_filtro=="defecto"?"selected":""?>>Usan la hoja por defecto</option>
      </select>
      <select name="slc_hoja" id="slc_hoja" class="select" onchange="realizar_busqueda()">
        <option value="0">Cualquier hoja</option>
<?php foreach ($hojas as $h) { ?>
        <option value="<?=$h['memb_codi']?>" <?=$h['memb_codi']==$slc_hoja?"selected":""?>><?=membrete_h($h['memb_nombre'])?></option>
<?php } ?>
      </select>
      <input type="button" name="btn_buscar" value="Buscar" class="botones" onClick="realizar_busqueda();">
    </td>
  </tr>
  <tr>
    <td colspan="2" class="listado2" align="center">
      <input type="button" class="botones" value="Crear hoja membretada" onClick="llamaCuerpo('adm_membrete.php?accion=1')">
      <input type="button" class="botones" value="Plantillas de hojas" onClick="llamaCuerpo('cuerpo_membretes.php')">
      <input type="button" class="botones" value="Regresar" onClick="llamaCuerpo('mnu_membretes.php')">
    </td>
  </tr>
</table>
<br>
<div id="div_asignaciones" class="memb-listado" style="width: 90%"></div>
</form>
</center>

<div id="memb-modal" class="memb-modal" style="display:none">
  <div class="memb-modal-caja">
    <table width="100%" class="borde_tab">
      <tr>
        <td colspan="2" class="titulos4"><strong>Asignar hoja membretada</strong><a href="javascript:cerrar_modal()" class="memb-modal-cerrar" title="Cerrar">&times;</a></td>
      </tr>
      <tr>
        <td class="titulos2" width="30%">&Aacute;rea</td>
        <td class="listado2"><b id="modal_area"></b></td>
      </tr>
      <tr>
        <td class="titulos2">Asignaciones actuales</td>
        <td class="listado2"><div id="modal_actuales">Cargando...</div></td>
      </tr>
      <tr>
        <td class="titulos2">Tipo de documento</td>
        <td class="listado2">
          <select id="modal_tipo" class="select">
            <option value="0">Todos los tipos</option>
<?php foreach ($tipos as $t) { ?>
            <option value="<?=$t['trad_codigo']?>"><?=membrete_h($t['trad_descr'])?></option>
<?php } ?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="titulos2">Hoja membretada</td>
        <td class="listado2">
          <select id="modal_membrete" class="select">
            <option value="0">&lt;&lt; seleccione &gt;&gt;</option>
<?php foreach ($hojas as $h) { ?>
            <option value="<?=$h['memb_codi']?>"><?=membrete_h($h['memb_nombre'])?><?=$h['memb_defecto'] ? " (por defecto)" : ""?></option>
<?php } ?>
          </select>
          <div class="memb-ayuda">&iquest;No est&aacute; la hoja que necesita? <a class="vinculos" href="javascript:llamaCuerpo('adm_membrete.php?accion=1')">Crear una hoja membretada nueva</a> y vuelva a esta pantalla para asignarla.</div>
        </td>
      </tr>
      <tr>
        <td colspan="2" class="listado2 memb-modal-ayuda">
          Al generar un documento, el sistema busca la hoja en este orden: la asignada al &aacute;rea para ese tipo de documento, la asignada al &aacute;rea para todos los tipos, la asignada a ese tipo de documento para todas las &aacute;reas, la hoja por defecto de la instituci&oacute;n y, si no hay ninguna, el archivo cargado en Administraci&oacute;n de &Aacute;reas.
        </td>
      </tr>
      <tr>
        <td colspan="2" class="listado2"><div id="modal_resultado"></div></td>
      </tr>
      <tr>
        <td colspan="2" class="listado2" align="center">
          <input type="button" class="botones" value="Guardar" onclick="guardar_asignacion()" <?=count($hojas)==0 ? "disabled" : ""?>>
          <input type="button" class="botones" value="Cerrar" onclick="cerrar_modal()">
        </td>
      </tr>
    </table>
  </div>
</div>
</body>
</html>
