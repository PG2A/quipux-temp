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
 * Comprobación de destinatarios: elegido un remitente ("De"), muestra a qué
 * servidores de la institución podría enviar o reasignar un documento de
 * periodo JERÁRQUICO y a cuáles no, con la regla y la copia obligatoria. Sólo
 * consulta: usa las mismas reglas que el buscador de destinatarios y Reasignar
 * (jerarquia_destinos, include/periodos/Jerarquia.php).
 *
 * @package    dependencias
 * @author     2026 Marco Terán <marcosdanny14@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

session_start();
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');
include_once(dirname(__DIR__, 2).'/funciones_interfaz.php');
include_once(dirname(__DIR__, 2).'/include/periodos/Jerarquia.php');

if (($_SESSION["usua_admin_sistema"] ?? 0) != 1 and ($_SESSION["usua_codi"] ?? -1) != 0) {
    die( html_error("Lo sentimos, usted no tiene permisos suficientes para acceder a esta p&aacute;gina.") );
}

$inst_codi  = 0 + $_SESSION["inst_codi"];
$de         = 0 + trim(limpiar_numero($_GET["de"] ?? 0));
$buscar_de  = trim(limpiar_sql($_GET["buscar_de"] ?? ""));
$buscar_para= trim(limpiar_sql($_GET["buscar_para"] ?? ""));
$area_para  = 0 + trim(limpiar_numero($_GET["area_para"] ?? 0));
$estado     = in_array($_GET["estado"] ?? "", array("todos", "si", "no")) ? $_GET["estado"] : "todos";
$ver_para   = (($_GET["ver_para"] ?? "") == "1");

$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
$MAX = 300;

// Servidores activos de la institución (misma base que los combos de Quipux).
$sql_usuarios = "select u.usua_codi, u.usua_login, trim(u.usua_nomb || ' ' || u.usua_apellido) as nombre,
                        u.depe_codi, depe_ruta(u.depe_codi) as ruta, u.usua_cargo,
                        coalesce(c.cargo_nivel, u.nivel_jerarquico) as nivel
                   from usuarios u
                   join usuario v on v.usua_codi = u.usua_codi and v.tipo_usuario = 1
                   left join cargo c on c.cargo_id = u.cargo_id
                  where u.usua_codi > 0 and u.usua_esta = 1 and u.visible_sub = 1
                    and u.usua_login not like 'UADM%' and u.inst_codi = $inst_codi";

// Filtro por nombre, apellido, cédula o login: todas las palabras deben aparecer.
function filtro_texto($db, $texto) {
    $w = "";
    foreach (preg_split('/\s+/', trim($texto)) as $p) {
        if ($p == '') continue;
        $q = $db->conn->qstr('%'.$p.'%');
        $w .= " and (u.usua_nomb || ' ' || u.usua_apellido ilike $q or u.usua_cedula ilike $q or u.usua_login ilike $q)";
    }
    return $w;
}

// ---------------------------------------------------------------- Remitente
$de_datos = null;
if ($de > 0) {
    $rs = $db->conn->Execute("$sql_usuarios and u.usua_codi = $de");
    if ($rs && !$rs->EOF) $de_datos = $rs->fields;
    else $de = 0;
}
$candidatos_de = array();
if ($de == 0 && $buscar_de != "") {
    $rs = $db->conn->Execute("$sql_usuarios ".filtro_texto($db, $buscar_de)." order by 3 limit 50");
    while ($rs && !$rs->EOF) { $candidatos_de[] = $rs->fields; $rs->MoveNext(); }
}

// ---------------------------------------------------------------- Destinatarios
$filas = array(); $total = 0; $n_si = 0; $n_no = 0; $de_nivel = null; $destinos = array();
if ($de_datos) {
    $de_nivel = ($de_datos["NIVEL"] === null || $de_datos["NIVEL"] === "") ? null : (int)$de_datos["NIVEL"];
    if ($de_nivel !== null) $destinos = jerarquia_destinos($db, $de);

    if ($ver_para) {
        $w = filtro_texto($db, $buscar_para);
        if ($area_para > 0) $w .= " and u.depe_codi = $area_para";
        $rs = $db->conn->Execute("$sql_usuarios $w and u.usua_codi <> $de order by 5, 7 nulls last, 3");
        while ($rs && !$rs->EOF) {
            $f = $rs->fields;
            $u = (int)$f["USUA_CODI"];
            $f["PERMITIDO"] = ($de_nivel === null) || isset($destinos[$u]);
            $f["REGLA"] = isset($destinos[$u]) ? ($GLOBALS['JERARQUIA_REGLAS'][$destinos[$u]['regla']] ?? '') : '';
            $f["COPIAS"] = isset($destinos[$u]) ? jerarquia_nombres($db, $destinos[$u]['copias']) : '';
            $f["PERMITIDO"] ? $n_si++ : $n_no++;
            if ($estado == "todos" || ($estado == "si") == $f["PERMITIDO"]) $filas[] = $f;
            $rs->MoveNext();
        }
        $total = count($filas);
    }
}

// Áreas con servidores, para filtrar los destinatarios.
$rsAreas = $db->conn->Execute(
    "select depe_ruta(d.depe_codi) as ruta, d.depe_codi from dependencia d
      where d.inst_codi = $inst_codi and d.depe_estado = 1
        and exists (select 1 from usuarios u where u.depe_codi = d.depe_codi and u.usua_esta = 1)
      order by 1");

// Periodo vigente hoy, sólo informativo.
$rsPer = $db->conn->Execute("select periodo_nombre, jerarquico, to_char(fecha_fin, 'YYYY-MM-DD') as hasta
                               from periodo where periodo_codi = periodo_vigente($inst_codi)");
$txt_periodo = "Hoy no hay periodo vigente: los documentos nuevos se crean \"sin periodo\" y usan las reglas de siempre.";
if ($rsPer && !$rsPer->EOF) {
    $jer = ($rsPer->fields["JERARQUICO"] === 't' || $rsPer->fields["JERARQUICO"] === true);
    $txt_periodo = "Periodo vigente: <b>".$h($rsPer->fields["PERIODO_NOMBRE"])."</b> (hasta ".$h($rsPer->fields["HASTA"])."), modo <b>"
                 .($jer ? "Jer&aacute;rquico" : "Lineal")."</b>.".($jer ? "" : " Esta comprobaci&oacute;n muestra c&oacute;mo ser&iacute;a en un periodo jer&aacute;rquico.");
}

function url_de($de) { return "areas_comprobar_destinatarios.php?de=".(int)$de; }

echo "<!DOCTYPE html>".html_head();
?>
<body>
<div class="sumillas-wrap">

    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr><td class="titulos4">Comprobaci&oacute;n de Destinatarios</td></tr>
        <tr><td class="listado2">
            Elija un remitente (<b>De</b>) y consulte a qu&eacute; servidores (<b>Para</b>) podr&iacute;a enviar o reasignar un
            documento de periodo <b>jer&aacute;rquico</b> seg&uacute;n el nivel de los puestos. S&oacute;lo consulta; no modifica nada.
        </td></tr>
        <tr><td class="listado2"><?php echo $txt_periodo; ?></td></tr>
    </table>

<?php if (!$de_datos) { ?>
    <!-- Paso 1: elegir el remitente -->
    <form method="get" action="areas_comprobar_destinatarios.php">
    <table class="borde_tab sumillas-form" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td class="titulos2">De (remitente)</td>
            <td class="listado2">
                <input type="text" name="buscar_de" size="40" class="tex_area" value="<?php echo $h($buscar_de); ?>"
                       placeholder="Nombre, apellido, c&eacute;dula o usuario">
                <input type="submit" class="botones" value="Buscar">
            </td>
        </tr>
    </table>
    </form>
<?php   if ($buscar_de != "") { ?>
    <table width="100%" class="borde_tab" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td width="28%" align="center" class="titulos2">Nombre</td>
            <td width="12%" align="center" class="titulos2">Usuario</td>
            <td width="32%" align="center" class="titulos2">&Aacute;rea</td>
            <td width="18%" align="center" class="titulos2">Puesto</td>
            <td width="5%"  align="center" class="titulos2">Nivel</td>
            <td width="5%"  align="center" class="titulos2">&nbsp;</td>
        </tr>
<?php       if (empty($candidatos_de)) echo '<tr><td colspan="6" class="listado2" align="center">No se encontraron servidores con ese criterio.</td></tr>';
            foreach ($candidatos_de as $c) { ?>
        <tr>
            <td class="listado2"><?php echo $h($c["NOMBRE"]); ?></td>
            <td class="listado2"><?php echo $h($c["USUA_LOGIN"]); ?></td>
            <td class="listado2"><?php echo $h($c["RUTA"]); ?></td>
            <td class="listado2"><?php echo $h($c["USUA_CARGO"]); ?></td>
            <td class="listado2" align="center"><?php echo ($c["NIVEL"] === null || $c["NIVEL"] === "") ? "&mdash;" : (int)$c["NIVEL"]; ?></td>
            <td class="listado2" align="center">
                <input type="button" class="botones" value="De" onclick="window.location='<?php echo url_de($c["USUA_CODI"]); ?>'">
            </td>
        </tr>
<?php       } ?>
    </table>
<?php   }
    } else { ?>
    <!-- Remitente elegido -->
    <table width="100%" class="borde_tab" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td width="12%" class="titulos2">De</td>
            <td class="listado2_ver">
                <b><?php echo $h($de_datos["NOMBRE"]); ?></b> (<?php echo $h($de_datos["USUA_LOGIN"]); ?>) &mdash;
                <?php echo $h($de_datos["RUTA"]); ?> &mdash; <?php echo $h($de_datos["USUA_CARGO"]); ?> &mdash;
                <b><?php echo ($de_nivel === null) ? "sin nivel" : "nivel $de_nivel"; ?></b>
                &nbsp;<input type="button" class="botones" value="Cambiar remitente" onclick="window.location='areas_comprobar_destinatarios.php'">
            </td>
        </tr>
<?php   if ($de_nivel === null) { ?>
        <tr><td class="titulos2">&nbsp;</td><td class="listado2">
            <span style="color:#b00000">El puesto de este usuario no tiene nivel:</span> en documentos jer&aacute;rquicos se le aplican
            las reglas de siempre (sin restricci&oacute;n por nivel). Asigne el nivel en <b>Puestos</b> del &aacute;rea para aplicar las reglas.
        </td></tr>
<?php   } else { ?>
        <tr><td class="titulos2">&nbsp;</td><td class="listado2">
            Puede enviar/reasignar a <b><?php echo count($destinos); ?></b> servidor(es) de la instituci&oacute;n.
        </td></tr>
<?php   } ?>
    </table>

    <!-- Paso 2: buscar destinatarios -->
    <form method="get" action="areas_comprobar_destinatarios.php">
    <input type="hidden" name="de" value="<?php echo $de; ?>">
    <input type="hidden" name="ver_para" value="1">
    <table class="borde_tab sumillas-form" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td class="titulos2">Para</td>
            <td class="listado2">
                <input type="text" name="buscar_para" size="40" class="tex_area" value="<?php echo $h($buscar_para); ?>"
                       placeholder="Nombre, apellido, c&eacute;dula o usuario (vac&iacute;o = todos)">
            </td>
        </tr>
        <tr>
            <td class="titulos2">&Aacute;rea</td>
            <td class="listado2">
<?php       echo $rsAreas->GetMenu2("area_para", $area_para, "0:Todas las áreas", false, 0, " class='select'"); ?>
            </td>
        </tr>
        <tr>
            <td class="titulos2">Mostrar</td>
            <td class="listado2">
                <label><input type="radio" name="estado" value="todos" <?php if ($estado == "todos") echo "checked"; ?>> Todos</label>&nbsp;&nbsp;
                <label><input type="radio" name="estado" value="si" <?php if ($estado == "si") echo "checked"; ?>> S&oacute;lo disponibles</label>&nbsp;&nbsp;
                <label><input type="radio" name="estado" value="no" <?php if ($estado == "no") echo "checked"; ?>> S&oacute;lo no disponibles</label>
            </td>
        </tr>
    </table>
    <div class="sumillas-botonera">
        <input type="submit" class="botones" value="Comprobar">
    </div>
    </form>

<?php   if ($ver_para) { ?>
    <table width="100%" class="borde_tab barra_busqueda_top" border="0" cellpadding="0" cellspacing="3">
        <tr><td class="listado2">
            Encontrados: <b><?php echo $n_si + $n_no; ?></b> &mdash;
            <span style="color:#1e7b34"><b><?php echo $n_si; ?></b> disponibles</span> &mdash;
            <span style="color:#b00000"><b><?php echo $n_no; ?></b> no disponibles</span>
            <?php if ($total > $MAX) echo " &mdash; se muestran los primeros $MAX; afine la b&uacute;squeda."; ?>
        </td></tr>
    </table>
    <table width="100%" class="borde_tab" border="0" cellpadding="0" cellspacing="3">
        <tr>
            <td width="20%" align="center" class="titulos2">Para</td>
            <td width="10%" align="center" class="titulos2">Usuario</td>
            <td width="24%" align="center" class="titulos2">&Aacute;rea</td>
            <td width="5%"  align="center" class="titulos2">Nivel</td>
            <td width="10%" align="center" class="titulos2">Disponible</td>
            <td width="18%" align="center" class="titulos2">Regla</td>
            <td width="13%" align="center" class="titulos2">Copia obligatoria</td>
        </tr>
<?php       if (empty($filas)) echo '<tr><td colspan="7" class="listado2" align="center">No hay servidores con ese criterio.</td></tr>';
            foreach (array_slice($filas, 0, $MAX) as $f) {
                $ok = $f["PERMITIDO"]; ?>
        <tr>
            <td class="listado2"><?php echo $h($f["NOMBRE"]); ?></td>
            <td class="listado2"><?php echo $h($f["USUA_LOGIN"]); ?></td>
            <td class="listado2"><?php echo $h($f["RUTA"]); ?></td>
            <td class="listado2" align="center"><?php echo ($f["NIVEL"] === null || $f["NIVEL"] === "") ? "&mdash;" : (int)$f["NIVEL"]; ?></td>
            <td class="listado2" align="center" style="background:<?php echo $ok ? '#e2efda' : '#fce4d6'; ?>">
                <b><?php echo $ok ? "S&iacute;" : "No"; ?></b></td>
            <td class="listado2"><?php echo ($de_nivel === null) ? "Reglas de siempre" : $h($f["REGLA"]); ?></td>
            <td class="listado2"><?php echo $h($f["COPIAS"]); ?></td>
        </tr>
<?php       } ?>
    </table>
<?php   }
    } ?>

    <div class="sumillas-botonera">
        <input type="button" class="botones" value="Regresar" onclick="window.location='areas.php'">
    </div>
</div>
</body>
</html>
