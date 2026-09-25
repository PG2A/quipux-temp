<?php
session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once(__DIR__.'/membretes_lib.php');
$ajax = (int)($_GET["ajax"] ?? 0) == 1;
if (($_SESSION["usua_admin_sistema"] ?? 0) != 1) {
    if ($ajax) die("ERROR|No tiene permisos para administrar hojas membretadas.");
    echo html_error("No tiene permisos para administrar hojas membretadas.");
    die("");
}
membrete_requerir_post_csrf($ajax);
$accion = (int)($_POST["accion"] ?? 0);
$usuario = (int)($_SESSION["usua_codi"] ?? 0);
$inst = (int)($_SESSION["inst_codi"] ?? 0);
$destino = "asignar_membretes.php";
$db->conn->BeginTrans();
$ok = true;
$mensaje = "";

function membrete_ids_lista($texto) {
    $ids = array();
    foreach (explode(",", (string)$texto) as $v) {
        $v = (int)trim($v);
        if ($v > 0) $ids[$v] = $v;
    }
    return $ids;
}

try {
if ($accion == 1) {
    $depe = (int)($_POST["slc_area"] ?? 0);
    $memb = (int)($_POST["slc_membrete"] ?? 0);
    $tipo = (int)($_POST["slc_tipo"] ?? 0);
    $area_nombre = membrete_area_en_alcance($db, $depe);
    $hoja = membrete_obtener($db, $memb);
    $tipo_nombre = membrete_tipo_nombre($db, $tipo);
    if ($tipo_nombre === null) { $ok = false; $mensaje = "El tipo de documento seleccionado no es v&aacute;lido."; }
    elseif ($area_nombre === null) { $ok = false; $mensaje = "El &aacute;rea seleccionada no existe o no pertenece a su instituci&oacute;n."; }
    elseif (!$hoja || $hoja['memb_estado'] != 1) { $ok = false; $mensaje = "La hoja membretada seleccionada no est&aacute; activa."; }
    else {
        $cond_tipo = $tipo > 0 ? "trad_codigo=$tipo" : "trad_codigo is null";
        $val_tipo = $tipo > 0 ? $tipo : "null";
        membrete_ejecutar($db, "update membrete_asignacion set masi_estado=0 where depe_codi=$depe and masi_uso='documento' and $cond_tipo and masi_estado=1");
        membrete_ejecutar($db, "insert into membrete_asignacion (memb_codi, depe_codi, trad_codigo, masi_uso, masi_estado, usua_codi_crea) values ($memb, $depe, $val_tipo, 'documento', 1, $usuario)");
        membrete_log($db, $memb, "ASIGNA", "area $depe $area_nombre / $tipo_nombre");
        $mensaje = "\"" . membrete_h($area_nombre) . "\" usar&aacute; la hoja \"" . membrete_h($hoja['memb_nombre']) . "\" para " . membrete_h($tipo_nombre) . ".";
    }
} elseif ($accion == 2) {
    $masi = (int)($_POST["id"] ?? 0);
    $rs = membrete_ejecutar($db, "select a.masi_codi, a.depe_codi, a.memb_codi, a.trad_codigo, d.depe_nomb, coalesce(t.trad_descr,'todos los tipos') as tipo
                                  from membrete_asignacion a join membrete m on m.memb_codi=a.memb_codi left join dependencia d on d.depe_codi=a.depe_codi left join tiporad t on t.trad_codigo=a.trad_codigo
                                  where a.masi_codi=$masi and a.masi_estado=1 and m.inst_codi=$inst");
    if ($rs->EOF) { $ok = false; $mensaje = "La asignaci&oacute;n no existe."; }
    else {
        membrete_ejecutar($db, "update membrete_asignacion set masi_estado=0 where masi_codi=$masi");
        membrete_log($db, (int)$rs->fields['MEMB_CODI'], "DESASIGNA", ($rs->fields['DEPE_CODI'] ? "area " . $rs->fields['DEPE_CODI'] . " " . $rs->fields['DEPE_NOMB'] : "todas las areas") . " / " . $rs->fields['TIPO']);
        $mensaje = "Se quit&oacute; la asignaci&oacute;n para " . membrete_h($rs->fields['TIPO']) . ".";
    }
} elseif ($accion == 4) {
    $memb = (int)($_POST["memb"] ?? 0);
    $tipo = (int)($_POST["tipo"] ?? 0);
    $marcadas = membrete_ids_lista($_POST["marcadas"] ?? "");
    $visibles = membrete_ids_lista($_POST["visibles"] ?? "");
    $hoja = membrete_obtener($db, $memb);
    $tipo_nombre = membrete_tipo_nombre($db, $tipo);
    if ($tipo_nombre === null) { $ok = false; $mensaje = "El tipo de documento seleccionado no es v&aacute;lido."; }
    elseif (!$hoja || $hoja['memb_estado'] != 1) { $ok = false; $mensaje = "La hoja membretada no est&aacute; activa."; }
    elseif (count($visibles) == 0) { $ok = false; $mensaje = "No hay &aacute;reas en la lista."; }
    else {
        $cond_tipo = $tipo > 0 ? "trad_codigo=$tipo" : "trad_codigo is null";
        $val_tipo = $tipo > 0 ? $tipo : "null";
        $alcance = array();
        foreach (membrete_areas($db) as $a) $alcance[$a['depe_codi']] = true;
        $altas = 0;
        $bajas = 0;
        foreach ($visibles as $depe) {
            if (!isset($alcance[$depe])) continue;
            $actual = (int)$db->conn->GetOne("select memb_codi from membrete_asignacion where depe_codi=$depe and masi_uso='documento' and $cond_tipo and masi_estado=1 limit 1");
            if (isset($marcadas[$depe]) && $actual != $memb) {
                membrete_ejecutar($db, "update membrete_asignacion set masi_estado=0 where depe_codi=$depe and masi_uso='documento' and $cond_tipo and masi_estado=1");
                membrete_ejecutar($db, "insert into membrete_asignacion (memb_codi, depe_codi, trad_codigo, masi_uso, masi_estado, usua_codi_crea) values ($memb, $depe, $val_tipo, 'documento', 1, $usuario)");
                $altas++;
            } elseif (!isset($marcadas[$depe]) && $actual == $memb) {
                membrete_ejecutar($db, "update membrete_asignacion set masi_estado=0 where depe_codi=$depe and masi_uso='documento' and $cond_tipo and masi_estado=1 and memb_codi=$memb");
                $bajas++;
            }
        }
        membrete_log($db, $memb, "PERSONALIZA", "$tipo_nombre: $altas asignadas, $bajas quitadas de " . count($visibles) . " listadas");
        $mensaje = "Guardado para " . membrete_h($tipo_nombre) . ": $altas &aacute;rea(s) asignadas, $bajas quitadas.";
        if ($altas == 0 && $bajas == 0) $mensaje = "Sin cambios: las casillas ya reflejaban el estado actual.";
    }
} elseif ($accion == 5) {
    $memb = (int)($_POST["memb"] ?? 0);
    $marcadas = membrete_ids_lista($_POST["marcadas"] ?? "");
    $visibles = membrete_ids_lista($_POST["visibles"] ?? "");
    $hoja = membrete_obtener($db, $memb);
    if (!$hoja || $hoja['memb_estado'] != 1) { $ok = false; $mensaje = "La hoja membretada no est&aacute; activa."; }
    elseif (count($visibles) == 0) { $ok = false; $mensaje = "No hay tipos de documento en la lista."; }
    else {
        $alcance = array();
        foreach (membrete_tipos_documento($db) as $t) $alcance[$t['trad_codigo']] = $t['trad_descr'];
        $altas = 0;
        $bajas = 0;
        foreach ($visibles as $tipo) {
            if (!isset($alcance[$tipo])) continue;
            $actual = (int)$db->conn->GetOne("select memb_codi from membrete_asignacion where depe_codi is null and trad_codigo=$tipo and masi_uso='documento' and masi_estado=1 limit 1");
            if (isset($marcadas[$tipo]) && $actual != $memb) {
                membrete_ejecutar($db, "update membrete_asignacion set masi_estado=0 where depe_codi is null and trad_codigo=$tipo and masi_uso='documento' and masi_estado=1");
                membrete_ejecutar($db, "insert into membrete_asignacion (memb_codi, depe_codi, trad_codigo, masi_uso, masi_estado, usua_codi_crea) values ($memb, null, $tipo, 'documento', 1, $usuario)");
                $altas++;
            } elseif (!isset($marcadas[$tipo]) && $actual == $memb) {
                membrete_ejecutar($db, "update membrete_asignacion set masi_estado=0 where depe_codi is null and trad_codigo=$tipo and masi_uso='documento' and masi_estado=1 and memb_codi=$memb");
                $bajas++;
            }
        }
        membrete_log($db, $memb, "PERSONALIZA_T", "tipos para todas las areas: $altas asignados, $bajas quitados de " . count($visibles) . " listados");
        $mensaje = "Guardado: $altas tipo(s) de documento asignados, $bajas quitados.";
        if ($altas == 0 && $bajas == 0) $mensaje = "Sin cambios: las casillas ya reflejaban el estado actual.";
    }
} else {
    $ok = false;
    $mensaje = "Acci&oacute;n no reconocida.";
}
} catch (Throwable $e) {
    $ok = false;
    $mensaje = $e->getMessage();
}

if ($ok && !$db->conn->CommitTrans()) { $ok = false; $mensaje = "No se pudo confirmar la operaci&oacute;n."; $db->conn->RollbackTrans(); }
elseif (!$ok) $db->conn->RollbackTrans();
if ($ajax) die(($ok ? "OK|" : "ERROR|") . $mensaje);
membrete_pagina_resultado($mensaje, $destino);
