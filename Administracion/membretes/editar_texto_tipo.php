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
$tipo = membrete_texto_tipo($db, (int)($_GET["tipo"] ?? 0));
if (!$tipo) {
    echo html_error("El tipo de documento no existe o no est&aacute; activo.");
    die("");
}
$csrf = membrete_csrf_token();
$tokens = membrete_texto_tokens();
echo "<!DOCTYPE html>".html_head();
?>
<link rel="stylesheet" type="text/css" href="/estilos/membretes.css?v=11">
<script type="text/javascript" src="/js/ckeditor/ckeditor.js"></script>
<script>
function llamaCuerpo(parametros){
    if (window.bloquearPantalla) bloquearPantalla('Cargando...');
    window.location.href = parametros;
}
function editor(){
    return CKEDITOR.instances.texto;
}
function insertar_token(token){
    var ed = editor();
    if (ed) ed.insertHtml(token);
}
function enviar(destino, nueva){
    var f = document.getElementById('frm_texto');
    var ed = editor();
    if (ed) ed.updateElement();
    f.action = destino;
    f.target = nueva ? '_blank' : '_self';
    if (!nueva && window.bloquearPantalla) bloquearPantalla('Grabando...');
    f.submit();
}
function regresar(){
    var ed = editor();
    if (ed && ed.checkDirty()) {
        document.getElementById('memb-confirm').style.display = 'flex';
        return;
    }
    llamaCuerpo('textos_tipo.php');
}
function cerrar_confirmar(){
    document.getElementById('memb-confirm').style.display = 'none';
}
document.addEventListener('keydown', function(e){ if (e.key == 'Escape' && document.getElementById('memb-confirm').style.display == 'flex') cerrar_confirmar(); });
window.onload = function(){ CKEDITOR.replace('texto', { height: 320 }); };
</script>
<body>
<?php dibujar_loader_pantalla('Cargando...', '/imagenes/escudo_blanco.png'); ?>
<center>
<form id="frm_texto" method="post" action="grabar_texto_tipo.php">
<input type="hidden" name="csrf_token" value="<?=membrete_h($csrf)?>">
<input type="hidden" name="accion" value="grabar">
<input type="hidden" name="tipo" value="<?=$tipo['trad_codigo']?>">
<br>
<table width="90%" class="borde_tab">
  <tr>
    <td colspan="2" class="titulos4"><div align="center"><strong>Texto por defecto: <?=membrete_h($tipo['trad_descr'])?></strong></div></td>
  </tr>
  <tr>
    <td class="titulos2" width="25%">Tipo de documento</td>
    <td class="listado2"><b><?=membrete_h($tipo['trad_descr'])?></b><?php if ($tipo['trad_abreviatura'] !== '') { ?> (<?=membrete_h($tipo['trad_abreviatura'])?>)<?php } ?></td>
  </tr>
  <tr>
    <td class="titulos2">Campos autom&aacute;ticos</td>
    <td class="listado2">
      <div class="memb-tokens">
<?php foreach ($tokens as $token => $descr) { ?>
        <a href="javascript:void(0)" class="vinculos memb-token" onclick="insertar_token('<?=membrete_h($token)?>')" title="Insertar <?=membrete_h($token)?>"><?=$descr?></a>
<?php } ?>
      </div>
      <div class="memb-modal-ayuda">Haga clic para insertar el campo en la posici&oacute;n del cursor. Se reemplaza con el dato real cuando el usuario crea el documento.</div>
    </td>
  </tr>
  <tr>
    <td class="titulos2">Texto</td>
    <td class="listado2">
      <textarea id="texto" name="texto" rows="14" cols="100"><?=membrete_h($tipo['texto'])?></textarea>
    </td>
  </tr>
  <tr>
    <td colspan="2" class="listado2" align="center">
      <input type="button" class="botones" value="Grabar" onClick="enviar('grabar_texto_tipo.php', false)">
      <input type="button" class="botones" value="Vista previa PDF" onClick="enviar('probar_texto_tipo.php', true)">
      <input type="button" class="botones" value="Regresar" onClick="regresar()">
    </td>
  </tr>
</table>
</form>
</center>
<div id="memb-confirm" class="memb-modal" style="display:none">
  <div class="memb-modal-caja angosta">
    <table width="100%" class="borde_tab">
      <tr>
        <td class="titulos4"><strong>Cambios sin grabar</strong><a href="javascript:cerrar_confirmar()" class="memb-modal-cerrar" title="Cerrar">&times;</a></td>
      </tr>
      <tr>
        <td class="listado2"><div class="memb-confirm-texto">El texto tiene cambios que a&uacute;n no se han grabado. Si regresa ahora se perder&aacute;n.</div></td>
      </tr>
      <tr>
        <td class="listado2" align="center">
          <input type="button" class="botones" value="Salir sin grabar" onclick="llamaCuerpo('textos_tipo.php')">
          <input type="button" class="botones" value="Seguir editando" onclick="cerrar_confirmar()">
        </td>
      </tr>
    </table>
  </div>
</div>
</body>
</html>
