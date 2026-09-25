<?php
// This file is part of Quipux – Document Management System
//
// Quipux is free software and is currently under a process of technical
// modernization and functional improvement carried out by
// EXDUCERE ONLINE CIA. LTDA., as part of the development of a new version
// of the Quipux platform.
//
// Quipux is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Quipux is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Quipux. If not, see <http://www.gnu.org/licenses/>.

/**
 * Guarda el alta o la edición de un área desde areas_form.php. Mantiene la misma
 * lógica de la pantalla anterior: dep_central y depe_plantilla apuntan al área
 * misma si no se elige otra, se registra el área en el ámbito del administrador
 * (usuario_dependencia) y el Replace dispara el trigger que refresca la tabla
 * 'usuario' con la ruta jerárquica.
 *
 * @package    dependencias
 * @author     2026 Marco Terán <marcosdanny14@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/obtenerdatos.php');
include_once "../usuarios_dependencias/area_ajax_grabar.php";

if (($_SESSION["usua_admin_sistema"] ?? 0) != 1 and ($_SESSION["usua_codi"] ?? -1) != 0) {
    die( html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.") );
}

$inst_codi     = 0 + $_SESSION["inst_codi"];
$txt_depe_codi = 0 + trim(limpiar_numero($_POST["txt_depe_codi"] ?? 0));
$nombre        = trim(limpiar_sql($_POST["txt_nombre"] ?? ""));
$sigla         = trim(limpiar_sql(strtoupper($_POST["txt_sigla"] ?? "")));
$ciudad        = 0 + trim(limpiar_numero($_POST["txt_ciudad"] ?? 0));
$slc_padre     = 0 + trim(limpiar_numero($_POST["slc_padre"] ?? 0));
$estado        = (isset($_POST["txt_estado"]) and $_POST["txt_estado"] == 0) ? 0 : 1;
$estructura    = (isset($_POST["txt_estructura"]) and $_POST["txt_estructura"] == 1) ? "true" : "false";

$nombre = mb_substr($nombre, 0, 150);
$sigla  = mb_substr($sigla, 0, 20);

$mensaje = "";
$es_nueva = ($txt_depe_codi == 0);

if ($nombre == "" or $sigla == "") {
    $mensaje = "El nombre y la sigla son obligatorios.";
} elseif ($ciudad == 0) {
    $mensaje = "Debe seleccionar la ciudad del &aacute;rea.";
} else {

    // La edición sólo opera sobre áreas de la institución en curso.
    if (!$es_nueva) {
        $rs = $db->conn->Execute("select depe_codi from dependencia where depe_codi = $txt_depe_codi and inst_codi = $inst_codi");
        if (!$rs or $rs->EOF) {
            $mensaje = "El &aacute;rea que intenta modificar no existe en esta instituci&oacute;n.";
        }
    }

    // El padre elegido debe ser de la misma institución y no puede ser la propia
    // área ni una de sus descendientes (evita ciclos).
    if ($mensaje == "" and $slc_padre > 0) {
        $rs = $db->conn->Execute("select depe_codi from dependencia where depe_codi = $slc_padre and inst_codi = $inst_codi");
        if (!$rs or $rs->EOF) {
            $mensaje = "El &aacute;rea padre seleccionada no existe en esta instituci&oacute;n.";
        } elseif (!$es_nueva) {
            $desc = ",".substr(buscar_areas_dependientes_rec($txt_depe_codi), 1).",";
            if ($slc_padre == $txt_depe_codi or strpos($desc, ",$slc_padre,") !== false) {
                $mensaje = "El &aacute;rea padre no puede ser la propia &aacute;rea ni una de sus sub &aacute;reas.";
            }
        }
    }

    // No se admiten dos áreas con el mismo nombre corto ni la misma sigla en la
    // institución (comparando sin tildes ni mayúsculas).
    if ($mensaje == "") {
        $nombre_qs = $db->conn->qstr($nombre);
        $sigla_qs  = $db->conn->qstr($sigla);
        $sql = "select depe_codi,
                       translate(upper(depe_nomb),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                         = translate(upper($nombre_qs),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN') as dup_nom,
                       translate(upper(coalesce(dep_sigla,'')),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                         = translate(upper($sigla_qs),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN') as dup_sig
                  from dependencia
                 where inst_codi = $inst_codi and depe_estado = 1 and depe_codi <> $txt_depe_codi
                   and ( translate(upper(depe_nomb),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                           = translate(upper($nombre_qs),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                      or translate(upper(coalesce(dep_sigla,'')),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                           = translate(upper($sigla_qs),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN') )";
        $rs = $db->conn->Execute($sql);
        if ($rs and !$rs->EOF) {
            if ($rs->fields["DUP_NOM"] == 't' || $rs->fields["DUP_NOM"] === true || $rs->fields["DUP_NOM"] == 1)
                $mensaje = "Ya existe un &aacute;rea con el nombre <b>".htmlspecialchars($nombre)."</b> en esta instituci&oacute;n.";
            else
                $mensaje = "Ya existe un &aacute;rea con la sigla <b>".htmlspecialchars($sigla)."</b> en esta instituci&oacute;n.";
        }
    }

    if ($mensaje == "") {
        $db->conn->BeginTrans();

        $txtIdDep = $es_nueva ? $db->conn->nextId('sec_dependencia') : $txt_depe_codi;

        // Instituciones adscritas: se hereda la del padre; si no hay padre, la propia.
        $inst_adscrita = $inst_codi;
        $padre_real = ($slc_padre > 0) ? $slc_padre : $txtIdDep;
        if ($slc_padre > 0) {
            $rs_adsc = $db->query("select inst_adscrita from dependencia where depe_codi = ?", array($slc_padre));
            if ($rs_adsc && !$rs_adsc->EOF && trim((string)$rs_adsc->fields['INST_ADSCRITA']) != "")
                $inst_adscrita = $rs_adsc->fields['INST_ADSCRITA'];
        }

        // En un área nueva, dep_central y depe_plantilla apuntan a ella misma
        // (igual que el flujo anterior). En edición se conservan los actuales.
        $record = array();
        $record['inst_codi']       = $inst_codi;
        $record['depe_codi']       = $txtIdDep;
        $record['depe_nomb']       = $db->conn->qstr($nombre);
        $record['dep_sigla']       = $db->conn->qstr($sigla);
        $record['depe_estado']     = $estado;
        $record['depe_pie1']       = $db->conn->qstr($ciudad);   // la ciudad se guarda en depe_pie1
        $record['depe_codi_padre'] = $padre_real;
        $record['inst_adscrita']   = $inst_adscrita;

        // Estructura orgánica: sólo la controla el área de primer nivel (su padre es
        // una raíz de la institución). Una sub área la HEREDA de su padre; nunca se
        // toma del formulario. El valor final baja luego en cascada a los descendientes.
        $es_top = true;   // primer nivel salvo que su padre sea un área no raíz
        if ($padre_real != $txtIdDep) {
            $rsTop = $db->query("select coalesce(depe_codi_padre, depe_codi) = depe_codi as es_raiz
                                   from dependencia where depe_codi = ?", array($padre_real));
            $es_top = $rsTop && !$rsTop->EOF
                      && ($rsTop->fields['ES_RAIZ'] === 't' || $rsTop->fields['ES_RAIZ'] === true || $rsTop->fields['ES_RAIZ'] == 1);
        }
        if ($es_top) {
            $estructura_final = $estructura;                       // 'true' / 'false' del checkbox
        } else {
            $rsHer = $db->query("select estructura_organica from dependencia where depe_codi = ?", array($padre_real));
            $hereda = $rsHer && !$rsHer->EOF
                      && ($rsHer->fields['ESTRUCTURA_ORGANICA'] === 't' || $rsHer->fields['ESTRUCTURA_ORGANICA'] === true || $rsHer->fields['ESTRUCTURA_ORGANICA'] == 1);
            $estructura_final = $hereda ? "true" : "false";
        }
        $record['estructura_organica'] = $estructura_final;   // literal booleano (sin comillas)
        if ($es_nueva) {
            $record['dep_central']    = $txtIdDep;
            $record['depe_plantilla'] = $txtIdDep;
        }

        $ok = $db->conn->Replace("dependencia", $record, "depe_codi", false);
        if ($ok) {
            // Cascada: el valor de estructura orgánica baja a TODAS las descendientes
            // (y así en cadena), para que toda la rama quede igual al área de primer nivel.
            $db->conn->query(
                "update dependencia set estructura_organica = $estructura_final
                  where inst_codi = " . $inst_codi . "
                    and depe_codi <> " . $txtIdDep . "
                    and depe_codi in (select depe_descendientes(" . $txtIdDep . "))
                    and estructura_organica is distinct from $estructura_final");
            $db->conn->CommitTrans();
            $mensaje = $es_nueva
                ? "El &aacute;rea <b>".htmlspecialchars($nombre)."</b> fue creada correctamente."
                : "Los cambios en el &aacute;rea <b>".htmlspecialchars($nombre)."</b> se guardaron correctamente.";

            // Registra el área en el ámbito del administrador, como el flujo anterior.
            $depe_codi_admin = obtenerAreasAdmin($_SESSION["usua_codi"], $_SESSION["inst_codi"], $_SESSION["usua_admin_sistema"], $db);
            // Sólo si el administrador tiene ámbito RESTRINGIDO. Con acceso total
            // obtenerAreasAdmin() devuelve '' y en PHP 8 ('' != 0) es verdadero:
            // se le creaba una restricción al área recién guardada.
            if (!empty($depe_codi_admin)) {
                grabar_instancia($txtIdDep, $_SESSION['usua_codi'], $_SESSION['usua_codi'], $padre_real, $_SESSION['inst_codi'], $db, 1);
            }
        } else {
            $db->conn->RollbackTrans();
            $mensaje = "Error al guardar el &aacute;rea: ".$db->conn->ErrorMsg();
        }
    }
}

// "Aceptar" vuelve al listado donde vive el área guardada: el de su padre. Si el
// área quedó de primer nivel (padre = raíz o sin padre), al listado general.
$volver_listado = "areas.php";
$padre_guardado = 0 + ($_POST["slc_padre"] ?? 0);
if ($padre_guardado > 0) {
    $rsVR = $db->conn->Execute("select coalesce(depe_codi_padre, depe_codi) = depe_codi as es_raiz
                                  from dependencia where depe_codi = $padre_guardado and inst_codi = $inst_codi");
    $esRaiz = $rsVR && !$rsVR->EOF && ($rsVR->fields["ES_RAIZ"] === 't' || $rsVR->fields["ES_RAIZ"] === true || $rsVR->fields["ES_RAIZ"] == 1);
    if ($rsVR and !$rsVR->EOF and !$esRaiz) $volver_listado = "areas.php?padre=".$padre_guardado;
}

echo "<!DOCTYPE html>".html_head();
?>
<body>
<div class="sumillas-wrap">
    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr><td class="titulos4">Administraci&oacute;n de &Aacute;reas</td></tr>
        <tr><td class="listado2"><?php echo $mensaje; ?></td></tr>
    </table>
    <div class="sumillas-botonera">
        <input type="button" name="btn_aceptar" value="Aceptar" class="botones" onClick="window.location='<?php echo $volver_listado; ?>'">
    </div>
</div>
</body>
</html>
