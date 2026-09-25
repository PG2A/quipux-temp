<?php
/**  Programa para el manejo de gestion documental, oficios, memorandos, circulares, acuerdos
*    Desarrollado y en otros Modificado por la SubSecretaría de Informática del Ecuador
*    Quipux    www.gestiondocumental.gov.ec
*------------------------------------------------------------------------------
*    This program is free software: you can redistribute it and/or modify
*    it under the terms of the GNU Affero General Public License as
*    published by the Free Software Foundation, either version 3 of the
*    License, or (at your option) any later version.
*    This program is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*    GNU Affero General Public License for more details.
*
*    You should have received a copy of the GNU Affero General Public License
*    along with this program.  If not, see http://www.gnu.org/licenses.
*------------------------------------------------------------------------------
**/

/*  FUNCION PARA CREAR EL CALENDARIO */

/**
 * Combo de hora en pasos de 30 minutos, para acompañar a dibujar_calendario()
 * cuando el dato que se captura es un instante y no solo un día.
 *
 * @param string $objeto      id/name del <select>
 * @param string $hora        hora actual en formato "HH:MM"
 * @param string $accion      JS a ejecutar en el onchange
 * @param string $por_defecto hora a preseleccionar si $hora viene vacía
 */
function dibujar_combo_hora($objeto, $hora = "", $accion = "", $por_defecto = "17:00") {
    $hora = substr(trim((string)$hora), 0, 5);
    if (!preg_match('/^\d{2}:\d{2}$/', $hora)) $hora = $por_defecto;

    $opciones = array();
    for ($h = 0; $h < 24; ++$h)
        foreach (array("00", "30") as $m) $opciones[] = sprintf("%02d", $h) . ":" . $m;

    // Una tarea guardada con una hora que no cae en el paso de 30 minutos debe
    // seguir viéndose tal cual, no redondearse en silencio al abrir la pantalla.
    if (!in_array($hora, $opciones)) {
        $opciones[] = $hora;
        sort($opciones);
    }

    $onchange = ($accion != "") ? " onchange=\"$accion\"" : "";
    $html = "<select name='$objeto' id='$objeto' class='calphp_combos calphp_hora'$onchange>";
    foreach ($opciones as $v)
        $html .= "<option value='$v'" . ($v == $hora ? " selected" : "") . ">$v</option>";
    return $html . "</select>";
}

function dibujar_calendario($objeto, $fecha, $ruta_raiz=".", $accion = "") {
    $anio_desde = "2008";
    $anio_hasta = date('Y')+1;

    // Genero el combo de los años
    $combo_anios="<select id='calphp_combo_anio_$objeto' class='calphp_combos' onchange='calphp_generar_calendario(\"$objeto\")'>";
    $anio = substr($fecha, 0, 4);
    for ($i=$anio_desde ; $i<=$anio_hasta ; ++$i) {
        $combo_anios .= "<option value='$i'";
        if ((0+$anio) == $i) $combo_anios .= " selected";
        $combo_anios .= ">$i</option>";
    }
    $combo_anios .= "</select>";

    // Genero el combo de los meses
    $combo_meses = "<select id='calphp_combo_mes_$objeto' class='calphp_combos' onchange='calphp_generar_calendario(\"$objeto\")'>";
    $meses = array ('','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic');
    $mes = substr($fecha, 5, 2);
    for ($i=1 ; $i<=12 ; ++$i) {
    $combo_meses .= "<option value='$i'";
    if ((0+$mes) == $i) $combo_meses .= " selected";
        $combo_meses .= ">".$meses[$i]."</option>";
    }
    $combo_meses .= "</select>";

    $calendario =
        "<span style='border: none; height: 22px; vertical-align: middle; position: absolute;'>
            <input type='text' name='$objeto' id='$objeto' value='$fecha' size='10' maxlength='10' class='calphp_fecha' readonly>
            <span id='calphp_accion_$objeto' style='display: none;'>$accion</span>
            <img src='/js/calendario_php/btn_date1_up.gif'   id='img_calphp_mostrar_$objeto' alt='Mostrar' title='Mostrar calendario' style='vertical-align: middle' onclick='calphp_mostrar_calendario(\"$objeto\")'>
            <img src='/js/calendario_php/btn_date1_down.gif' id='img_calphp_ocultar_$objeto' alt='Ocultar' title='Ocultar calendario' style='display: none;vertical-align: bottom' onclick='calphp_ocultar_calendario(\"$objeto\")'>
            <div id='div_calphp_calendario_$objeto' class='calphp_div_calendario' style='display: none;'>
                <table width='100%' border='0' cellpadding='0' cellspacing='0'>
                    <tr>
                        <td width='100%' align='center' height='22px' valign='middle'>$combo_meses&nbsp;$combo_anios</td>
                    </tr>
                    <tr>
                        <td><div id='div_calphp_calendario_dias_$objeto'></div></td>
                    </tr>
                </table>
            </div>
        </span>";
    return $calendario;
}
?>
