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
$txt_buscar = trim((string)($_GET["txt_buscar"] ?? ""));
$slc_estado = (string)($_GET["slc_estado"] ?? "1");
$defecto = membrete_defecto($db);
$tipos = membrete_tipos_documento($db);
$csrf = membrete_csrf_token();
echo "<!DOCTYPE html>".html_head();
$paginador = new ADODB_Pager_Ajax(dirname(__DIR__, 2), "div_membretes", "busqueda_paginador_membretes.php", "txt_buscar,slc_estado");
?>
<link rel="stylesheet" type="text/css" href="/estilos/membretes.css?v=10">
<script>
var persMemb = 0;
var persModo = 'areas';
var persSeq = 0;
var confCb = null;
var confCancelCb = null;
var membCsrf = <?=json_encode($csrf)?>;
function confirmar(titulo, html, boton, cb, cancelCb){
    document.getElementById('conf_titulo').innerHTML = titulo;
    document.getElementById('conf_texto').innerHTML = html;
    document.getElementById('conf_boton').value = boton;
    confCb = cb;
    confCancelCb = cancelCb || null;
    document.getElementById('memb-confirm').style.display = 'flex';
    document.getElementById('conf_boton').focus();
}
function cerrar_confirmar(){
    var cancel = confCancelCb;
    confCb = null;
    confCancelCb = null;
    document.getElementById('memb-confirm').style.display = 'none';
    if (cancel) cancel();
}
function confirmar_ok(){
    var cb = confCb;
    confCb = null;
    confCancelCb = null;
    document.getElementById('memb-confirm').style.display = 'none';
    if (cb) cb();
}
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
function editar_membrete(id){
    llamaCuerpo('adm_membrete.php?accion=2&id=' + id);
}
function accion_membrete(accion, id){
    var f = document.createElement('form');
    f.method = 'post';
    f.action = 'grabar_membrete.php';
    var datos = {accion: accion, id: id, csrf_token: membCsrf};
    for (var nombre in datos) {
        var campo = document.createElement('input');
        campo.type = 'hidden';
        campo.name = nombre;
        campo.value = datos[nombre];
        f.appendChild(campo);
    }
    document.body.appendChild(f);
    if (window.bloquearPantalla) bloquearPantalla('Guardando...');
    f.submit();
}
function cambiar_defecto(id, marcado){
    if (marcado) {
        accion_membrete(4, id);
    } else {
        confirmar('Quitar hoja por defecto',
            'Si desmarca la hoja por defecto, las &aacute;reas y tipos de documento sin asignaci&oacute;n propia volver&aacute;n a usar el archivo cargado en Administraci&oacute;n de &Aacute;reas.<br><br>&iquest;Desea continuar?',
            'Quitar', function(){ accion_membrete(6, id); }, function(){ paginador_reload_div(''); });
    }
}
function usar_en_todas(id, nombre){
    confirmar('Aplicar a todas las &aacute;reas',
        '<b>' + nombre + '</b> pasar&aacute; a ser la <b>hoja por defecto</b> de la instituci&oacute;n.<br><br>' +
        'Se quitar&aacute;n <b>todas</b> las asignaciones existentes, tanto por &aacute;rea como por tipo de documento, de modo que todas las &aacute;reas y todos los tipos usen esta hoja sin excepciones.<br><br>' +
        'Si solo quiere que la usen algunas &aacute;reas o algunos tipos de documento, use <b>Personalizar</b>.',
        'Aplicar a todas', function(){ accion_membrete(7, id); });
}
function eliminar_membrete(id, esDefecto){
    var texto = 'Las &aacute;reas y tipos de documento que la ten&iacute;an asignada volver&aacute;n a usar la hoja por defecto.';
    if (esDefecto == 1) texto = 'Esta es la <b>hoja por defecto</b>. Si la elimina, la instituci&oacute;n queda sin hoja por defecto y las &aacute;reas volver&aacute;n a usar el archivo cargado en Administraci&oacute;n de &Aacute;reas.';
    confirmar('Eliminar hoja membretada', texto + '<br><br>La hoja queda en la lista de eliminadas y puede restaurarse despu&eacute;s.', 'Eliminar', function(){ accion_membrete(3, id); });
}
function restaurar_membrete(id){
    accion_membrete(5, id);
}
function personalizar(id, nombre){
    persMemb = id;
    document.getElementById('pers_nombre').textContent = nombre;
    document.getElementById('pers_modo').value = 'areas';
    cambiar_modo(false);
    document.getElementById('pers_tipo').value = '0';
    document.getElementById('pers_txt').value = '';
    document.getElementById('pers_solo').value = 'todas';
    document.getElementById('pers_resultado').innerHTML = '';
    document.getElementById('pers_resultado').className = '';
    document.getElementById('memb-pers').style.display = 'flex';
    cargar_areas();
}
function cerrar_personalizar(){
    document.getElementById('memb-pers').style.display = 'none';
    paginador_reload_div('');
}
function cambiar_modo(recargar){
    persModo = document.getElementById('pers_modo').value;
    var esTipos = persModo == 'tipos';
    document.getElementById('pers_fila_tipo').style.display = esTipos ? 'none' : '';
    document.getElementById('pers_txt').style.display = esTipos ? 'none' : '';
    document.getElementById('pers_btn_buscar').style.display = esTipos ? 'none' : '';
    document.getElementById('pers_ayuda_areas').style.display = esTipos ? 'none' : '';
    document.getElementById('pers_ayuda_tipos').style.display = esTipos ? '' : 'none';
    document.getElementById('pers_que').textContent = esTipos ? 'tipos de documento que usan' : '\u00e1reas que usan';
    var opciones = document.getElementById('pers_solo').options;
    opciones[0].text = 'Todos';
    opciones[1].text = esTipos ? 'Solo los que usan esta hoja' : 'Solo las que usan esta hoja';
    opciones[2].text = esTipos ? 'Solo los que no la usan' : 'Solo las que no la usan';
    document.getElementById('pers_resultado').innerHTML = '';
    document.getElementById('pers_resultado').className = '';
    if (recargar !== false) cargar_areas();
}
function cargar_areas(){
    var seq = ++persSeq;
    var qs = 'id=' + persMemb + '&modo=' + persModo + '&tipo=' + document.getElementById('pers_tipo').value + '&txt=' + encodeURIComponent(document.getElementById('pers_txt').value) + '&solo=' + document.getElementById('pers_solo').value;
    document.getElementById('pers_lista').innerHTML = 'Cargando...';
    membAjax('GET', 'personalizar_ajax.php?' + qs, null, function(html){ if (seq != persSeq) return; document.getElementById('pers_lista').innerHTML = html; });
}
function marcar_visibles(valor){
    var c = document.querySelectorAll('#pers_lista .memb-chk');
    for (var i = 0; i < c.length; i++) { c[i].checked = valor; c[i].parentNode.className = valor ? 'memb-fila marcada' : 'memb-fila'; }
}
function guardar_personalizar(){
    var c = document.querySelectorAll('#pers_lista .memb-chk');
    var d = document.getElementById('pers_resultado');
    if (c.length == 0) { d.className = 'memb-aviso error'; d.innerHTML = persModo == 'tipos' ? 'No hay tipos de documento en la lista.' : 'No hay &aacute;reas en la lista.'; return; }
    var marcadas = [], visibles = [];
    for (var i = 0; i < c.length; i++) { visibles.push(c[i].value); if (c[i].checked) marcadas.push(c[i].value); }
    var accion = persModo == 'tipos' ? 5 : 4;
    var datos = 'accion=' + accion + '&csrf_token=' + encodeURIComponent(membCsrf) + '&memb=' + persMemb + '&tipo=' + document.getElementById('pers_tipo').value + '&marcadas=' + marcadas.join(',') + '&visibles=' + visibles.join(',');
    membAjax('POST', 'grabar_asignacion.php?ajax=1', datos, function(txt){
        var ok = txt.indexOf('OK|') == 0;
        var d = document.getElementById('pers_resultado');
        d.className = ok ? 'memb-aviso ok' : 'memb-aviso error';
        d.innerHTML = txt.replace(/^(OK|ERROR)\|/, '');
        cargar_areas();
    });
}
document.addEventListener('keydown', function(e){
    if (e.key != 'Escape') return;
    if (document.getElementById('memb-confirm').style.display == 'flex') { cerrar_confirmar(); return; }
    if (document.getElementById('memb-pers').style.display == 'flex') cerrar_personalizar();
});
document.addEventListener('change', function(e){ if (e.target && e.target.className == 'memb-chk') e.target.parentNode.className = e.target.checked ? 'memb-fila marcada' : 'memb-fila'; });
</script>
<body onload="paginador_reload_div('')">
<?php dibujar_loader_pantalla('Cargando...', '/imagenes/escudo_blanco.png'); ?>
<center>
<form name="formulario" id="formulario" method="get" action="" onsubmit="realizar_busqueda(); return false;">
<br>
<table width="90%" class="borde_tab">
  <tr>
    <td colspan="2" class="titulos4"><div align="center"><strong>Hojas Membretadas de <?=membrete_h($_SESSION['inst_nombre'] ?? '')?></strong></div></td>
  </tr>
  <tr>
    <td class="titulos2" width="25%">Hoja por defecto</td>
    <td class="listado2">
<?php if ($defecto) { ?>
      <b><?=membrete_h($defecto['memb_nombre'])?></b>. La usan todas las &aacute;reas y tipos de documento que no tengan una asignaci&oacute;n propia.
<?php } else { ?>
      <b>Ninguna.</b> Mientras no exista, cada &aacute;rea sigue usando el archivo cargado en Administraci&oacute;n de &Aacute;reas.
<?php } ?>
    </td>
  </tr>
<?php $incompatibles = membrete_incompatibles($db); if ($incompatibles) { ?>
  <tr>
    <td colspan="2" class="listado2">
      <div class="memb-aviso error"><b>Hojas incompatibles con el generador de documentos.</b> Los documentos que usen estas hojas fallar&aacute;n al generar el PDF. Edite cada una y vuelva a cargar el archivo (se convertir&aacute; autom&aacute;ticamente):
        <ul class="memb-incompatibles">
<?php foreach ($incompatibles as $inc) { ?>
          <li><a href="javascript:editar_membrete(<?=$inc['memb_codi']?>)" class="vinculos"><?=membrete_h($inc['memb_nombre'])?></a> &mdash; <?=$inc['motivo']?></li>
<?php } ?>
        </ul>
      </div>
    </td>
  </tr>
<?php } ?>
  <tr>
    <td colspan="2" class="listado2">
      <div class="memb-aviso info"><b>C&oacute;mo funciona.</b> Cada hoja puede <b>aplicarse a todas</b> las &aacute;reas (pasa a ser la hoja por defecto y se quitan las excepciones) o <b>personalizarse</b> de dos formas: <b>por &aacute;reas</b>, marcando con casillas las &aacute;reas que la usar&aacute;n (para todos los tipos de documento o solo para uno), o <b>por tipos de documento</b>, marcando los tipos (oficio, memorando, etc.) que la usar&aacute;n en todas las &aacute;reas. Orden al generar un documento: hoja del &aacute;rea para ese tipo &rarr; hoja del &aacute;rea para todos los tipos &rarr; hoja del tipo para todas las &aacute;reas &rarr; hoja por defecto.</div>
    </td>
  </tr>
  <tr>
    <td class="titulos2">Buscar</td>
    <td class="listado2">
      <input type="text" name="txt_buscar" id="txt_buscar" class="tex_area" size="40" maxlength="100" value="<?=membrete_h($txt_buscar)?>" placeholder="Nombre o descripci&oacute;n">
      <select name="slc_estado" id="slc_estado" class="select" onchange="realizar_busqueda()">
        <option value="1" <?=$slc_estado=="1"?"selected":""?>>Activas</option>
        <option value="0" <?=$slc_estado=="0"?"selected":""?>>Eliminadas</option>
      </select>
      <input type="button" name="btn_buscar" value="Buscar" class="botones" onClick="realizar_busqueda();">
    </td>
  </tr>
  <tr>
    <td colspan="2" class="listado2" align="center">
      <input type="button" class="botones" value="Crear hoja membretada" onClick="llamaCuerpo('adm_membrete.php?accion=1')">
      <input type="button" class="botones" value="Consulta por &aacute;rea" onClick="llamaCuerpo('asignar_membretes.php')">
      <input type="button" class="botones" value="Demo: d&oacute;nde se aplica" onClick="llamaCuerpo('demo_membretes.php')">
      <input type="button" class="botones" value="Regresar" onClick="llamaCuerpo('mnu_membretes.php')">
    </td>
  </tr>
</table>
<br>
<div id="div_membretes" class="memb-listado" style="width: 90%"></div>
</form>
</center>

<div id="memb-pers" class="memb-modal" style="display:none">
  <div class="memb-modal-caja ancha">
    <table width="100%" class="borde_tab">
      <tr>
        <td colspan="2" class="titulos4"><strong>Personalizar: <span id="pers_que">&aacute;reas que usan</span> "<span id="pers_nombre"></span>"</strong><a href="javascript:cerrar_personalizar()" class="memb-modal-cerrar" title="Cerrar">&times;</a></td>
      </tr>
      <tr>
        <td class="titulos2" width="22%">Alcance</td>
        <td class="listado2">
          <select id="pers_modo" class="select" onchange="cambiar_modo()">
            <option value="areas">Por &aacute;reas</option>
            <option value="tipos">Por tipos de documento (todas las &aacute;reas)</option>
          </select>
        </td>
      </tr>
      <tr id="pers_fila_tipo">
        <td class="titulos2">Tipo de documento</td>
        <td class="listado2">
          <select id="pers_tipo" class="select" onchange="cargar_areas()">
            <option value="0">Todos los tipos</option>
<?php foreach ($tipos as $t) { ?>
            <option value="<?=$t['trad_codigo']?>"><?=membrete_h($t['trad_descr'])?></option>
<?php } ?>
          </select>
          <span class="memb-ayuda">Las casillas muestran qu&eacute; &aacute;reas tienen esta hoja asignada para el tipo elegido.</span>
        </td>
      </tr>
      <tr>
        <td class="titulos2">Filtrar</td>
        <td class="listado2">
          <input type="text" id="pers_txt" class="tex_area" size="34" maxlength="100" placeholder="Nombre o sigla del &aacute;rea" onkeypress="if(event.keyCode==13){cargar_areas();return false;}">
          <select id="pers_solo" class="select" onchange="cargar_areas()">
            <option value="todas">Todas</option>
            <option value="marcadas">Solo las que usan esta hoja</option>
            <option value="sin">Solo las que no la usan</option>
          </select>
          <input type="button" class="botones" id="pers_btn_buscar" value="Buscar" onclick="cargar_areas()">
          <input type="button" class="botones" value="Marcar visibles" onclick="marcar_visibles(true)">
          <input type="button" class="botones" value="Desmarcar visibles" onclick="marcar_visibles(false)">
        </td>
      </tr>
      <tr>
        <td colspan="2" class="listado2"><div id="pers_lista"></div></td>
      </tr>
      <tr>
        <td colspan="2" class="listado2 memb-modal-ayuda"><span id="pers_ayuda_areas">Al guardar, las &aacute;reas marcadas quedan con esta hoja para el tipo elegido y a las desmarcadas de la lista se les quita (si la ten&iacute;an). Las &aacute;reas que no aparecen en la lista no se tocan. Una asignaci&oacute;n de tipo espec&iacute;fico tiene prioridad sobre la de "Todos los tipos", &eacute;sta sobre la asignaci&oacute;n por tipo de documento para todas las &aacute;reas, y &eacute;sta sobre la hoja por defecto.</span><span id="pers_ayuda_tipos" style="display:none">Al guardar, los tipos de documento marcados usar&aacute;n esta hoja en <b>todas las &aacute;reas</b> que no tengan una asignaci&oacute;n propia por &aacute;rea; a los desmarcados de la lista se les quita (si la ten&iacute;an). Un tipo solo puede tener una hoja: si ya ten&iacute;a otra, se reemplaza.</span></td>
      </tr>
      <tr>
        <td colspan="2" class="listado2"><div id="pers_resultado"></div></td>
      </tr>
      <tr>
        <td colspan="2" class="listado2" align="center">
          <input type="button" class="botones" value="Guardar" onclick="guardar_personalizar()">
          <input type="button" class="botones" value="Cerrar" onclick="cerrar_personalizar()">
        </td>
      </tr>
    </table>
  </div>
</div>

<div id="memb-confirm" class="memb-modal" style="display:none">
  <div class="memb-modal-caja angosta">
    <table width="100%" class="borde_tab">
      <tr>
        <td class="titulos4"><strong id="conf_titulo"></strong><a href="javascript:cerrar_confirmar()" class="memb-modal-cerrar" title="Cerrar">&times;</a></td>
      </tr>
      <tr>
        <td class="listado2"><div id="conf_texto" class="memb-confirm-texto"></div></td>
      </tr>
      <tr>
        <td class="listado2" align="center">
          <input type="button" class="botones" id="conf_boton" value="Confirmar" onclick="confirmar_ok()">
          <input type="button" class="botones" value="Cancelar" onclick="cerrar_confirmar()">
        </td>
      </tr>
    </table>
  </div>
</div>
</body>
</html>
