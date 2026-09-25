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
 * Reglas de reasignación para documentos creados en un periodo JERÁRQUICO
 * (radicado.radi_jerarquico = true). Las reglas viven en la función SQL
 * jerarquia_destinos() (db/periodos/02_reglas_jerarquicas.sql); aquí sólo se
 * consultan, para que la pantalla de reasignar y el grabado usen la misma lógica.
 *
 * Si quien reasigna no tiene nivel en su puesto, las reglas no aplican y se
 * siguen las de siempre (orgánico funcional por área / jefe).
 *
 * @package    periodos
 * @author     2026 Marco Terán <marcosdanny14@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$GLOBALS['JERARQUIA_REGLAS'] = array(
    'MISMA_AREA'      => 'Misma área: un nivel arriba, mismo nivel o nivel inferior',
    'NIVEL1_A_NIVEL1' => 'Nivel 1 a nivel 1 de otra área',
    'OTRA_SUBAREA'    => 'Mismo nivel en otra sub área de su área',
    'NIVEL2_A_NIVEL1' => 'Nivel 2 a nivel 1 de otra área (con copia a su jefe)',
    'DOS_NIVELES'     => 'Dos niveles arriba (con copia al nivel intermedio)',
);

/** ¿Alguno de los documentos (lista "1,2,3") es de periodo jerárquico? */
function jerarquia_hay_documentos_jerarquicos($db, $lista_radicados) {
    $lista = trim(preg_replace('/[^0-9,]/', '', (string)$lista_radicados), ',');
    if ($lista == '') return false;
    $rs = $db->conn->Execute("select 1 from radicado where radi_nume_radi in ($lista) and radi_jerarquico limit 1");
    return ($rs && !$rs->EOF);
}

/** Documentos jerárquicos de un arreglo de radicados. */
function jerarquia_filtrar_jerarquicos($db, $radicados) {
    $lista = implode(',', array_filter(array_map(function ($r) { return preg_replace('/[^0-9]/', '', (string)$r); }, (array)$radicados)));
    $salida = array();
    if ($lista == '') return $salida;
    $rs = $db->conn->Execute("select radi_nume_radi from radicado where radi_nume_radi in ($lista) and radi_jerarquico");
    while ($rs && !$rs->EOF) {
        $salida[] = $rs->fields["RADI_NUME_RADI"];
        $rs->MoveNext();
    }
    return $salida;
}

/** Nivel del puesto del usuario, o null si no tiene. */
function jerarquia_nivel_usuario($db, $usua_codi) {
    $rs = $db->conn->Execute("select jerarquia_nivel(".(int)$usua_codi.") as nivel");
    if (!$rs || $rs->EOF) return null;
    $n = $rs->fields["NIVEL"];
    return ($n === null || $n === '') ? null : (int)$n;
}

/**
 * Destinos permitidos para un documento jerárquico:
 * usua_codi => array(depe_codi, nivel, regla, copias => array(usua_codi...), nombre)
 */
function jerarquia_destinos($db, $usua_codi) {
    $destinos = array();
    $rs = $db->conn->Execute(
        "select d.usua_codi, d.depe_codi, d.nivel, d.regla, array_to_string(d.copias, ',') as copias,
                u.usua_apellido || ' ' || u.usua_nomb as nombre
           from jerarquia_destinos(".(int)$usua_codi.") d join usuarios u on u.usua_codi = d.usua_codi
          order by 6");
    while ($rs && !$rs->EOF) {
        $f = $rs->fields;
        $destinos[(int)$f["USUA_CODI"]] = array(
            'depe_codi' => (int)$f["DEPE_CODI"],
            'nivel'     => ($f["NIVEL"] === null || $f["NIVEL"] === '') ? null : (int)$f["NIVEL"],
            'regla'     => $f["REGLA"],
            'copias'    => ($f["COPIAS"] == '') ? array() : array_map('intval', explode(',', $f["COPIAS"])),
            'nombre'    => $f["NOMBRE"],
        );
        $rs->MoveNext();
    }
    return $destinos;
}

/**
 * ¿El documento es (o será) de periodo jerárquico? Si ya existe se usa su sello;
 * si aún no se ha guardado, el modo del periodo vigente de la institución.
 */
function jerarquia_documento_es_jerarquico($db, $nurad) {
    $nurad = preg_replace('/\D/', '', (string)$nurad);
    if ($nurad != '') {
        $rs = $db->conn->Execute("select radi_jerarquico from radicado where radi_nume_radi = $nurad");
        if ($rs && !$rs->EOF) {
            $v = $rs->fields["RADI_JERARQUICO"];
            return ($v === 't' || $v === true || $v === 1 || $v === '1');
        }
    }
    $rs = $db->conn->Execute("select p.jerarquico from periodo p
                               where p.periodo_codi = periodo_vigente(".(int)$_SESSION["inst_codi"].")");
    if (!$rs || $rs->EOF) return false;
    $v = $rs->fields["JERARQUICO"];
    return ($v === 't' || $v === true || $v === 1 || $v === '1');
}

/**
 * Usuario desde el que se aplican las reglas al elegir destinatarios: el primer
 * remitente ("De") si es servidor de la institución, si no quien elabora.
 */
function jerarquia_origen_documento($db, $documento_us2) {
    foreach (explode('-', (string)$documento_us2) as $u) {
        $u = (int)$u;
        if ($u <= 0) continue;
        $rs = $db->conn->Execute("select 1 from usuario where usua_codi = $u and tipo_usuario = 1
                                     and inst_codi = ".(int)$_SESSION["inst_codi"]);
        if ($rs && !$rs->EOF) return $u;
        break;
    }
    return (int)$_SESSION["usua_codi"];
}

/**
 * Valida los "Para" (lista "-1--2-") de un documento jerárquico. Sólo se
 * restringen los servidores de la institución; ciudadanos y otras instituciones
 * no. Devuelve array('no_permitidos' => [...], 'copias' => [...]).
 */
function jerarquia_validar_destinatarios($db, $origen, $documento_us1) {
    $res = array('no_permitidos' => array(), 'copias' => array());
    $ids = array_filter(array_map('intval', explode('-', (string)$documento_us1)));
    if (empty($ids) || jerarquia_nivel_usuario($db, $origen) === null) return $res;
    $destinos = jerarquia_destinos($db, $origen);
    $rs = $db->conn->Execute("select usua_codi from usuario where usua_codi in (".implode(',', $ids).")
                                 and tipo_usuario = 1 and inst_codi = ".(int)$_SESSION["inst_codi"]);
    while ($rs && !$rs->EOF) {
        $u = (int)$rs->fields["USUA_CODI"];
        if ($u != $origen) {
            if (!isset($destinos[$u])) $res['no_permitidos'][] = $u;
            else foreach ($destinos[$u]['copias'] as $c) $res['copias'][$c] = $c;
        }
        $rs->MoveNext();
    }
    $res['copias'] = array_values(array_diff($res['copias'], $ids, array($origen)));
    return $res;
}

/** Nombres de una lista de usuarios, separados por coma. */
function jerarquia_nombres($db, $usuarios) {
    $ids = implode(',', array_map('intval', (array)$usuarios));
    if ($ids == '') return '';
    $rs = $db->conn->Execute("select string_agg(usua_apellido || ' ' || usua_nomb, ', ' order by 1) as n
                                from usuarios where usua_codi in ($ids)");
    return ($rs && !$rs->EOF) ? (string)$rs->fields["N"] : '';
}

/**
 * Combo de usuarios de reasignación restringido a los destinos permitidos de las
 * áreas indicadas ("1,2"). Mismo nombre/id que el combo original (usCodSelect).
 */
function jerarquia_combo_usuarios($destinos, $areas, $seleccionado = 0) {
    $areas = array_map('intval', explode(',', (string)$areas));
    $html = "<select name='usCodSelect' id='usCodSelect' class='select' onchange='jerarquia_mostrar_copia()'>"
          . "<option value='0'>&lt;&lt; Seleccione Usuario &gt;&gt;</option>";
    foreach ($destinos as $usua => $d) {
        if (!in_array($d['depe_codi'], $areas)) continue;
        $sel = ($usua == $seleccionado) ? " selected" : "";
        $etq = $d['nombre'] . ($d['nivel'] !== null ? " (nivel ".$d['nivel'].")" : "");
        $html .= "<option value='".(int)$usua."'$sel>".htmlspecialchars($etq, ENT_QUOTES, 'UTF-8')."</option>";
    }
    return $html."</select>";
}
