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
$accion = (int)($_GET["accion"] ?? 1);
if ($accion != 2) $accion = 1;
$id = (int)($_GET["id"] ?? 0);
$membrete = null;
if ($accion == 2) {
    $membrete = membrete_obtener($db, $id);
    if (!$membrete) {
        echo html_error("La hoja membretada no existe o no pertenece a su instituci&oacute;n.");
        die("");
    }
}
$txt_nombre = $membrete ? $membrete['memb_nombre'] : '';
$txt_descripcion = $membrete ? $membrete['memb_descripcion'] : '';
$chk_defecto = $membrete ? $membrete['memb_defecto'] : 0;
$hay_defecto = membrete_defecto($db);
if (!$hay_defecto && $accion == 1) $chk_defecto = 1;
$max_subida = ini_get('upload_max_filesize');
$csrf = membrete_csrf_token();
echo "<!DOCTYPE html>".html_head();
?>
<link rel="stylesheet" type="text/css" href="/estilos/membretes.css?v=3">
<script>
function llamaCuerpo(parametros){
    if (window.bloquearPantalla) bloquearPantalla('Cargando...');
    window.location.href = parametros;
}
function validarPdf(){
    var v = document.getElementById('arch_membrete').value;
    if (v != '' && v.substr(-4).toLowerCase() != '.pdf') {
        alert('Solo se permite subir archivos con extensión pdf.');
        document.getElementById('arch_membrete').value = '';
    }
}
function ValidarInformacion(){
    var msg = '';
    if (trim(document.getElementById('txt_nombre').value) == '') msg += '- Ingrese el nombre de la hoja membretada.\n';
    if (<?=$accion?> == 1 && document.getElementById('arch_membrete').value == '') msg += '- Seleccione el archivo PDF de la hoja membretada.\n';
    if (msg != '') { alert(msg); return false; }
    document.forms[0].action = 'grabar_membrete.php';
    if (window.bloquearPantalla) bloquearPantalla('Guardando...');
    return true;
}
function trim(s){ return s.replace(/^\s+|\s+$/g, ''); }
</script>
<body>
<?php dibujar_loader_pantalla('Guardando...', '/imagenes/escudo_blanco.png'); ?>
<center>
<form name="formulario" id="formulario" method="post" action="" enctype="multipart/form-data" onsubmit="return ValidarInformacion();">
<input type="hidden" name="accion" value="<?=$accion?>">
<input type="hidden" name="id" value="<?=$id?>">
<input type="hidden" name="csrf_token" value="<?=membrete_h($csrf)?>">
<br>
<table width="80%" class="borde_tab">
  <tr>
    <td colspan="2" class="titulos4"><div align="center"><strong><?=$accion==1 ? "Crear" : "Editar"?> Hoja Membretada</strong></div></td>
  </tr>
  <tr>
    <td class="titulos2" width="25%">Instituci&oacute;n</td>
    <td class="listado2"><?=membrete_h($_SESSION['inst_nombre'] ?? '')?></td>
  </tr>
  <tr>
    <td class="titulos2">* Nombre</td>
    <td class="listado2"><input type="text" name="txt_nombre" id="txt_nombre" class="tex_area" size="50" maxlength="100" value="<?=membrete_h($txt_nombre)?>"></td>
  </tr>
  <tr>
    <td class="titulos2">Descripci&oacute;n</td>
    <td class="listado2"><input type="text" name="txt_descripcion" id="txt_descripcion" class="tex_area" size="70" maxlength="250" value="<?=membrete_h($txt_descripcion)?>"></td>
  </tr>
<?php if ($membrete) { ?>
  <tr>
    <td class="titulos2">Archivo actual</td>
    <td class="listado2">
<?php if ($membrete['existe']) { ?>
      <b><?=membrete_h($membrete['memb_archivo'])?></b> &middot; <?=membrete_kb($membrete['tamanio'])?> &middot; actualizado el <?=$membrete['fecha']?>
      &nbsp; <a class="vinculos" href="ver_membrete.php?id=<?=$id?>" target="_blank">Ver</a>
      &nbsp; <a class="vinculos" href="probar_membrete.php?id=<?=$id?>" target="_blank">Probar con un oficio de ejemplo</a>
      <div class="memb-preview"><iframe src="ver_membrete.php?id=<?=$id?>#toolbar=0&navpanes=0&view=Fit" title="Hoja membretada actual"></iframe></div>
<?php } else { ?>
      <b>El archivo <?=membrete_h($membrete['memb_archivo'])?> no se encuentra en el servidor.</b> Suba uno nuevo.
<?php } ?>
<?php if ($membrete['n_asignadas'] > 0) { ?>
      <div class="memb-alerta"><?=$membrete['n_asignadas']?> &aacute;rea(s) tienen asignada esta hoja. Al reemplazar el archivo, cambia para todas.</div>
<?php } ?>
    </td>
  </tr>
<?php } ?>
  <tr>
    <td class="titulos2"><?=$accion==1 ? "* Archivo PDF" : "Reemplazar archivo"?></td>
    <td class="listado2">
      <input type="file" name="arch_membrete" id="arch_membrete" class="tex_area" size="60" accept="application/pdf,.pdf" onChange="validarPdf();">
      <br><b>Una p&aacute;gina tama&ntilde;o A4 en formato PDF. Se superpone completa a cada hoja del documento. L&iacute;mite del servidor: <?=membrete_h($max_subida)?>.</b>
    </td>
  </tr>
  <tr>
    <td class="titulos2">Hoja por defecto</td>
    <td class="listado2">
      <input type="checkbox" name="chk_defecto" id="chk_defecto" value="1" <?=$chk_defecto ? "checked" : ""?>>
      Usar en todas las &aacute;reas de la instituci&oacute;n que no tengan una hoja asignada.
<?php if ($hay_defecto && (!$membrete || $hay_defecto['memb_codi'] != $membrete['memb_codi'])) { ?>
      <br><b>Actualmente la hoja por defecto es "<?=membrete_h($hay_defecto['memb_nombre'])?>"; si marca esta casilla, la reemplaza.</b>
<?php } ?>
    </td>
  </tr>
  <tr>
    <td colspan="2" class="listado2" align="center">
      <input name="btn_aceptar" id="btn_aceptar" type="submit" class="botones" title="Almacena los cambios realizados" value="Aceptar">
      <input name="btn_accion" type="button" class="botones" title="Regresa sin guardar los cambios" value="Regresar" onclick="llamaCuerpo('cuerpo_membretes.php')">
    </td>
  </tr>
</table>
</form>
</center>
</body>
</html>
