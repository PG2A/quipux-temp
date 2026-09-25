<?php
session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
if (($_SESSION["usua_admin_sistema"] ?? 0) != 1) {
    echo html_error("No tiene permisos para administrar hojas membretadas.");
    die("");
}
echo "<!DOCTYPE html>".html_head();
?>
<script>
function llamaCuerpo(parametros){
    if (window.bloquearPantalla) bloquearPantalla('Cargando...');
    window.location.href = parametros;
}
</script>
<body>
<?php dibujar_loader_pantalla('Cargando...', '/imagenes/escudo_blanco.png'); ?>
<form name='frmMnuMembretes' action='../formAdministracion.php' method="post">
<br>
<br>
<center>
  <table width="32%" border="0" cellpadding="0" cellspacing="5" class="borde_tab admin-card">
  <tr>
    <td colspan="2" class="titulos4"><div align="center"><strong>Hojas Membretadas</strong></div></td>
  </tr>
  <tr>
    <td class="listado2" width="98%">
      <a onclick="llamaCuerpo('cuerpo_membretes.php');" href='javascript:void(0);' class="vinculos" title="Crear, editar, eliminar, aplicar a todas o personalizar por &aacute;reas y tipos">&nbsp;1. Hojas membretadas</a>
    </td>
  </tr>
  <tr>
    <td class="listado2" width="98%">
      <a onclick="llamaCuerpo('asignar_membretes.php');" href='javascript:void(0);' class="vinculos" title="Ver qu&eacute; hoja usa cada &aacute;rea y ajustarla una por una">&nbsp;2. Consulta por &aacute;rea</a>
    </td>
  </tr>
  <tr>
    <td class="listado2" width="98%">
      <a onclick="llamaCuerpo('demo_membretes.php');" href='javascript:void(0);' class="vinculos" title="Ver en qu&eacute; PDF del sistema se aplica la hoja y generar ejemplos">&nbsp;3. Demo: d&oacute;nde se aplica</a>
    </td>
  </tr>
  <tr>
    <td class="listado2" width="98%">
      <a onclick="llamaCuerpo('textos_tipo.php');" href='javascript:void(0);' class="vinculos" title="Texto que se precarga en el editor al crear un documento de cada tipo (Oficio, Acuerdo, Nota, etc.)">&nbsp;4. Textos por defecto por tipo de documento</a>
    </td>
  </tr>
  <tr>
    <td align="center" class="listado2">
      <center><input align="middle" class="botones" type="submit" name="Submit" value="Regresar"></center>
    </td>
  </tr>
</table>
</center>
</form>
</body>
</html>
