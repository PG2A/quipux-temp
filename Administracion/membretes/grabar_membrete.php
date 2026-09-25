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
membrete_requerir_post_csrf(false);
$accion = (int)($_POST["accion"] ?? 0);
$id = (int)($_POST["id"] ?? 0);
$inst = (int)($_SESSION["inst_codi"] ?? 0);
$usuario = (int)($_SESSION["usua_codi"] ?? 0);
$destino = "cuerpo_membretes.php";
$dir = membrete_dir();
if (!is_dir($dir)) @mkdir($dir, 0775, true);
$historial = $dir . '/historial';
if (!is_dir($historial)) @mkdir($historial, 0775, true);

function membrete_archivo_subido($campo) {
    if (empty($_FILES[$campo]) || ($_FILES[$campo]['error'] ?? 4) == 4 || trim((string)$_FILES[$campo]['tmp_name']) == '') return null;
    if ($_FILES[$campo]['error'] != 0) return "Error al subir el archivo (c&oacute;digo " . (int)$_FILES[$campo]['error'] . ").";
    if ((int)($_FILES[$campo]['size'] ?? 0) > 10 * 1024 * 1024) return "El archivo PDF no puede superar 10 MB.";
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = (string)$finfo->file($_FILES[$campo]['tmp_name']);
        if (!in_array($mime, array('application/pdf', 'application/x-pdf'))) return "El archivo cargado no tiene contenido PDF.";
    }
    if (!membrete_es_pdf($_FILES[$campo]['tmp_name'])) return "El archivo no es un PDF v&aacute;lido.";
    return $_FILES[$campo]['tmp_name'];
}

$membrete = ($accion >= 2) ? membrete_obtener($db, $id) : null;
if ($accion >= 2 && !$membrete) membrete_pagina_resultado("La hoja membretada no existe o no pertenece a su instituci&oacute;n.", $destino);

$db->conn->BeginTrans();
$ok = true;
$mensaje = "";
$ruta_escrita = null;
$respaldo_reversion = null;
$archivo_existia = false;

try {
switch ($accion) {
    case 1:
    case 2:
        $txt_nombre = trim(limpiar_sql($_POST["txt_nombre"] ?? ""));
        $txt_descripcion = trim(limpiar_sql($_POST["txt_descripcion"] ?? ""));
        $chk_defecto = isset($_POST["chk_defecto"]) ? 1 : 0;
        $tmp = membrete_archivo_subido("arch_membrete");
        if ($txt_nombre == "") { $ok = false; $mensaje = "Ingrese el nombre de la hoja membretada."; break; }
        if ($accion == 1 && $tmp === null) { $ok = false; $mensaje = "No se recibi&oacute; el archivo PDF. Si pesa m&aacute;s de " . ini_get('upload_max_filesize') . " el servidor lo rechaza antes de llegar aqu&iacute;."; break; }
        if ($tmp !== null && !is_file($tmp)) { $ok = false; $mensaje = $tmp; break; }
        $preparado = null;
        if ($tmp !== null) {
            $preparado = membrete_pdf_preparar($tmp);
            if ($preparado['error'] !== null) { $ok = false; $mensaje = $preparado['error']; break; }
        }
        $nombre_sql = $db->conn->qstr($txt_nombre);
        $desc_sql = $db->conn->qstr($txt_descripcion);
        if ($accion == 1) {
            membrete_ejecutar($db, "insert into membrete (inst_codi, memb_nombre, memb_descripcion, memb_archivo, memb_defecto, memb_estado, usua_codi_crea) values ($inst, $nombre_sql, $desc_sql, 'pendiente', 0, 1, $usuario)");
            $id = (int)$db->conn->GetOne("select currval(pg_get_serial_sequence('membrete','memb_codi'))");
            if ($id <= 0) { $ok = false; $mensaje = "No se pudo registrar la hoja membretada."; break; }
            $archivo = $id . ".pdf";
            membrete_ejecutar($db, "update membrete set memb_archivo='$archivo' where memb_codi=$id and inst_codi=$inst");
        } else {
            $archivo = $membrete['memb_archivo'];
            membrete_ejecutar($db, "update membrete set memb_nombre=$nombre_sql, memb_descripcion=$desc_sql, usua_codi_modi=$usuario, memb_fecha_modi=now() where memb_codi=$id and inst_codi=$inst");
        }
        if ($tmp !== null) {
            $ruta = membrete_ruta($archivo);
            $archivo_existia = is_file($ruta);
            if ($archivo_existia) {
                $respaldo_reversion = $historial . '/' . $id . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.pdf';
                if (!copy($ruta, $respaldo_reversion)) throw new RuntimeException("No se pudo respaldar el archivo anterior.");
            }
            $guardado = $preparado['normalizado'] ? (@rename($preparado['ruta'], $ruta) || (copy($preparado['ruta'], $ruta) && @unlink($preparado['ruta']))) : move_uploaded_file($tmp, $ruta);
            if (!$guardado) { $ok = false; $mensaje = "No se pudo guardar el archivo en el servidor. Revise permisos de escritura en bodega/plantillas/membretes."; break; }
            $ruta_escrita = $ruta;
            @chmod($ruta, 0664);
        }
        if ($chk_defecto == 1) {
            membrete_ejecutar($db, "update membrete set memb_defecto=0 where inst_codi=$inst and memb_defecto=1 and memb_codi<>$id");
            membrete_ejecutar($db, "update membrete set memb_defecto=1 where memb_codi=$id and inst_codi=$inst and memb_estado=1");
        } elseif ($accion == 2 && $membrete['memb_defecto'] == 1) {
            membrete_ejecutar($db, "update membrete set memb_defecto=0 where memb_codi=$id and inst_codi=$inst");
        }
        $normalizado = $preparado !== null && $preparado['normalizado'];
        membrete_log($db, $id, $accion == 1 ? "ALTA" : "EDICION", $txt_nombre . ($tmp !== null ? " (archivo reemplazado)" : "") . ($normalizado ? " (convertido a PDF 1.4)" : "") . ($chk_defecto ? " (por defecto)" : ""));
        $mensaje = "Hoja membretada \"" . membrete_h($txt_nombre) . "\" guardada correctamente.";
        if ($normalizado) $mensaje .= "<br><br>El archivo se convirti&oacute; autom&aacute;ticamente a <b>PDF 1.4</b> para que el generador de documentos pueda usarlo. El contenido visual no cambia.";
        break;

    case 3:
        membrete_ejecutar($db, "update membrete set memb_estado=0, memb_defecto=0, usua_codi_modi=$usuario, memb_fecha_modi=now() where memb_codi=$id and inst_codi=$inst");
        membrete_ejecutar($db, "update membrete_asignacion set masi_estado=0 where memb_codi=$id and masi_estado=1");
        membrete_log($db, $id, "BAJA", $membrete['memb_nombre'] . " (" . $membrete['n_asignadas'] . " asignaciones liberadas)");
        $mensaje = "Hoja membretada \"" . membrete_h($membrete['memb_nombre']) . "\" eliminada. " . ($membrete['n_asignadas'] > 0 ? $membrete['n_asignadas'] . " asignaci&oacute;n(es) liberadas. " : "") . ($membrete['memb_defecto'] == 1 ? "La instituci&oacute;n queda sin hoja por defecto." : "");
        break;

    case 6:
        membrete_ejecutar($db, "update membrete set memb_defecto=0, usua_codi_modi=$usuario, memb_fecha_modi=now() where memb_codi=$id and inst_codi=$inst");
        membrete_log($db, $id, "SIN_DEFECTO", $membrete['memb_nombre']);
        $mensaje = "\"" . membrete_h($membrete['memb_nombre']) . "\" ya no es la hoja por defecto. Las &aacute;reas sin asignaci&oacute;n vuelven al archivo de Administraci&oacute;n de &Aacute;reas.";
        break;

    case 7:
        $liberadas = (int)$db->conn->GetOne("select count(*) from membrete_asignacion a join membrete m on m.memb_codi=a.memb_codi where m.inst_codi=$inst and a.masi_estado=1");
        membrete_ejecutar($db, "update membrete_asignacion set masi_estado=0 where masi_estado=1 and memb_codi in (select memb_codi from membrete where inst_codi=$inst)");
        membrete_ejecutar($db, "update membrete set memb_defecto=0 where inst_codi=$inst and memb_defecto=1 and memb_codi<>$id");
        membrete_ejecutar($db, "update membrete set memb_defecto=1, usua_codi_modi=$usuario, memb_fecha_modi=now() where memb_codi=$id and inst_codi=$inst and memb_estado=1");
        membrete_log($db, $id, "USAR_TODAS", $membrete['memb_nombre'] . " ($liberadas asignaciones liberadas)");
        $mensaje = "\"" . membrete_h($membrete['memb_nombre']) . "\" es ahora la hoja de todas las &aacute;reas y tipos de documento de la instituci&oacute;n." . ($liberadas > 0 ? " Se quitaron $liberadas asignaci&oacute;n(es) por &aacute;rea." : "");
        break;

    case 4:
        membrete_ejecutar($db, "update membrete set memb_defecto=0 where inst_codi=$inst and memb_defecto=1 and memb_codi<>$id");
        membrete_ejecutar($db, "update membrete set memb_defecto=1, usua_codi_modi=$usuario, memb_fecha_modi=now() where memb_codi=$id and inst_codi=$inst and memb_estado=1");
        membrete_log($db, $id, "DEFECTO", $membrete['memb_nombre']);
        $mensaje = "\"" . membrete_h($membrete['memb_nombre']) . "\" es ahora la hoja membretada por defecto de la instituci&oacute;n.";
        break;

    case 5:
        membrete_ejecutar($db, "update membrete set memb_estado=1, usua_codi_modi=$usuario, memb_fecha_modi=now() where memb_codi=$id and inst_codi=$inst");
        membrete_log($db, $id, "RESTAURA", $membrete['memb_nombre']);
        $mensaje = "Hoja membretada \"" . membrete_h($membrete['memb_nombre']) . "\" restaurada.";
        $destino = "cuerpo_membretes.php?slc_estado=0";
        break;

    default:
        $ok = false;
        $mensaje = "Acci&oacute;n no reconocida.";
}
} catch (Throwable $e) {
    $ok = false;
    $mensaje = $e->getMessage();
}

if (!empty($preparado['normalizado']) && is_file($preparado['ruta'])) @unlink($preparado['ruta']);
if ($ok && !$db->conn->CommitTrans()) { $ok = false; $mensaje = "No se pudo confirmar la operaci&oacute;n."; $db->conn->RollbackTrans(); }
elseif (!$ok) $db->conn->RollbackTrans();
if (!$ok && $ruta_escrita !== null) {
    $archivo_revertido = true;
    if ($archivo_existia && $respaldo_reversion && is_file($respaldo_reversion)) {
        $archivo_revertido = copy($respaldo_reversion, $ruta_escrita);
    } elseif (!$archivo_existia && is_file($ruta_escrita)) {
        $archivo_revertido = unlink($ruta_escrita);
    }
    if (!$archivo_revertido) {
        $mensaje .= " No se pudo restaurar autom&aacute;ticamente el archivo PDF; revise el respaldo en bodega/plantillas/membretes/historial.";
    }
}
if (!$ok && in_array($accion, array(1, 2))) $destino = "adm_membrete.php?accion=$accion&id=$id";
membrete_pagina_resultado($mensaje, $destino);
