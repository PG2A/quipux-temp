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
$tipos = membrete_textos_tipos($db);
$csrf = membrete_csrf_token();
$con_texto = 0;
foreach ($tipos as $t) if ($t['texto'] !== '') $con_texto++;
echo "<!DOCTYPE html>".html_head();
?>
<link rel="stylesheet" type="text/css" href="/estilos/membretes.css?v=11">
<script>
var confCb = null;
function llamaCuerpo(parametros){
    if (window.bloquearPantalla) bloquearPantalla('Cargando...');
    window.location.href = parametros;
}
function confirmar(titulo, html, boton, cb){
    document.getElementById('conf_titulo').innerHTML = titulo;
    document.getElementById('conf_texto').innerHTML = html;
    document.getElementById('conf_boton').value = boton;
    confCb = cb;
    document.getElementById('memb-confirm').style.display = 'flex';
    document.getElementById('conf_boton').focus();
}
function cerrar_confirmar(){
    confCb = null;
    document.getElementById('memb-confirm').style.display = 'none';
}
function confirmar_ok(){
    var cb = confCb;
    confCb = null;
    document.getElementById('memb-confirm').style.display = 'none';
    if (cb) cb();
}
function vaciar_texto(tipo, nombre){
    confirmar('Vaciar texto por defecto',
        'El tipo <b>' + nombre + '</b> dejar&aacute; de precargar texto al crear un documento nuevo. Los documentos ya creados no cambian.',
        'Vaciar', function(){
            document.getElementById('frm_tipo').value = tipo;
            if (window.bloquearPantalla) bloquearPantalla('Grabando...');
            document.getElementById('frm_vaciar').submit();
        });
}
document.addEventListener('keydown', function(e){ if (e.key == 'Escape' && document.getElementById('memb-confirm').style.display == 'flex') cerrar_confirmar(); });
</script>
<body>
<?php dibujar_loader_pantalla('Cargando...', '/imagenes/escudo_blanco.png'); ?>
<center>
<br>
<table width="90%" class="borde_tab">
  <tr>
    <td colspan="2" class="titulos4"><div align="center"><strong>Textos por defecto por tipo de documento</strong></div></td>
  </tr>
  <tr>
    <td class="titulos2" width="25%">Resumen</td>
    <td class="listado2"><b><?=$con_texto?></b> de <?=count($tipos)?> tipos de documento tienen texto por defecto.</td>
  </tr>
  <tr>
    <td colspan="2" class="listado2">
      <div class="memb-aviso info"><b>&iquest;Qu&eacute; es esto?</b> Al crear un documento nuevo y elegir el tipo (Oficio, Acuerdo, Nota, etc.), el editor se precarga con el texto configurado aqu&iacute;. El usuario puede modificarlo libremente antes de firmar. Los campos entre asteriscos (instituci&oacute;n, ciudad, fecha) se reemplazan autom&aacute;ticamente al abrir el documento.</div>
    </td>
  </tr>
  <tr>
    <td colspan="2" class="listado2" align="center">
      <input type="button" class="botones" value="Regresar" onClick="llamaCuerpo('mnu_membretes.php')">
    </td>
  </tr>
</table>
<br>
<div class="memb-listado" style="width: 90%">
<table width="100%" class="borde_tab memb-textos">
  <tr>
    <td class="titulos2">Tipo de documento</td>
    <td class="titulos2">Estado</td>
    <td class="titulos2">Inicio del texto</td>
    <td class="titulos2">Acciones</td>
  </tr>
<?php foreach ($tipos as $i => $t) { $cls = ($i % 2 == 0) ? 'listado1' : 'listado2'; $tiene = $t['texto'] !== ''; ?>
  <tr>
    <td class="<?=$cls?>"><b><?=membrete_h($t['trad_descr'])?></b><?php if ($t['trad_abreviatura'] !== '') { ?> <small>(<?=membrete_h($t['trad_abreviatura'])?>)</small><?php } ?></td>
    <td class="<?=$cls?>"><span class="memb-estado <?=$tiene ? 'propia' : 'sin'?>"><?=$tiene ? 'Con texto' : 'Sin texto'?></span></td>
    <td class="<?=$cls?> memb-texto-previa"><?=$tiene ? membrete_texto_plano(membrete_texto_tokens_amigables($t['texto'])) : '<i>El editor se abre vac&iacute;o.</i>'?></td>
    <td class="<?=$cls?>">
      <div class="memb-alcance-acciones">
        <a href="javascript:void(0)" class="vinculos" onclick="llamaCuerpo('editar_texto_tipo.php?tipo=<?=$t['trad_codigo']?>')">Editar</a>
<?php if ($tiene) { ?>
        <a href="probar_texto_tipo.php?tipo=<?=$t['trad_codigo']?>" target="_blank" class="vinculos">Ver PDF</a>
        <a href="javascript:void(0)" class="vinculos" onclick="vaciar_texto(<?=$t['trad_codigo']?>, '<?=membrete_h(addslashes($t['trad_descr']))?>')">Vaciar</a>
<?php } ?>
      </div>
    </td>
  </tr>
<?php } ?>
</table>
</div>
</center>
<form id="frm_vaciar" method="post" action="grabar_texto_tipo.php" style="display:none">
  <input type="hidden" name="csrf_token" value="<?=membrete_h($csrf)?>">
  <input type="hidden" name="accion" value="vaciar">
  <input type="hidden" name="tipo" id="frm_tipo" value="0">
</form>
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
