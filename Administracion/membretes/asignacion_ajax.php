<?php
session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once(__DIR__.'/membretes_lib.php');
if (($_SESSION["usua_admin_sistema"] ?? 0) != 1) die("");
$depe = (int)($_GET["depe"] ?? 0);
$inst = (int)($_SESSION["inst_codi"] ?? 0);
if (membrete_area_en_alcance($db, $depe) === null) die("&Aacute;rea no v&aacute;lida.");
$rs = $db->conn->Execute("select a.masi_codi, coalesce(t.trad_descr,'Todos los tipos') as tipo, m.memb_nombre, to_char(a.masi_fecha_crea,'YYYY-MM-DD') as fecha
                          from membrete_asignacion a
                          join membrete m on m.memb_codi=a.memb_codi and m.memb_estado=1 and m.inst_codi=$inst
                          left join tiporad t on t.trad_codigo=a.trad_codigo
                          where a.masi_estado=1 and a.masi_uso='documento' and a.depe_codi=$depe
                          order by a.trad_codigo nulls first, t.trad_descr");
$defecto = membrete_defecto($db);
if (!$rs || $rs->EOF) {
    echo "Sin asignaciones propias. Usa " . ($defecto ? "la hoja por defecto <b>" . membrete_h($defecto['memb_nombre']) . "</b>." : "el archivo cargado en Administraci&oacute;n de &Aacute;reas.");
    die("");
}
echo "<table class='memb-actuales'>";
while (!$rs->EOF) {
    $f = $rs->fields;
    echo "<tr><td>" . membrete_h($f['TIPO']) . "</td><td><b>" . membrete_h($f['MEMB_NOMBRE']) . "</b></td><td>" . $f['FECHA'] . "</td>";
    echo "<td><a class='vinculos' href='javascript:quitar_asignacion(" . (int)$f['MASI_CODI'] . ")'>Quitar</a></td></tr>";
    $rs->MoveNext();
}
echo "</table>";
