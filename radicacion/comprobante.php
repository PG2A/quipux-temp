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
 * @package    radicacion
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__DIR__)."/funciones.php"); //para traer funciones p_get y p_post
p_register_globals(array());
include_once(dirname(__DIR__)."/funciones_interfaz.php");

$krd     = $_GET['krd']     ?? $_POST['krd']     ?? '';
$verrad  = $_GET['verrad']  ?? $_POST['verrad']  ?? '';
$textrad = $_GET['textrad'] ?? $_POST['textrad'] ?? '';

$accion = "../plantillas/CodigoBarras.php?krd=" . rawurlencode($krd) . "&nuevo=si&verrad=" . rawurlencode($verrad);

echo "<!DOCTYPE html>" . html_head();
?>
<body>
<div class="comprobante-wrap">
<form action="<?=htmlspecialchars($accion, ENT_QUOTES, 'UTF-8')?>" name="formulario" id="formulario" method="POST">
    <input type="hidden" name="tipo_comp" id="tipo_comp" value="">
    <table class="admin-card comprobante-card">
        <tr>
            <td class="titulos4"><strong>Imprimir comprobantes del registro</strong></td>
        </tr>
        <tr>
            <td class="listado2">
                <div class="comprobante-body">
                    <span class="comprobante-num"><?=htmlspecialchars((string)$textrad, ENT_QUOTES, 'UTF-8')?></span>
                    <p class="comprobante-hint">Elija el formato que desea imprimir. El comprobante se abrir&aacute; en una pesta&ntilde;a nueva.</p>
                    <div class="comprobante-acciones">
                        <input type="button" value="Imprimir C&oacute;digo de Barras" class="botones_largo" onclick='document.formulario.tipo_comp.value="1"; document.formulario.submit();'>
                        <input type="button" value="Imprimir Comprobante" class="botones_largo" onclick='document.formulario.tipo_comp.value="2"; document.formulario.submit();'>
                        <input type="button" value="Imprimir Comprobante en Ticket" class="botones_largo" onclick='document.formulario.tipo_comp.value="3"; document.formulario.submit();'>
                    </div>
                    <div class="comprobante-pie">
                        <input type="button" value="Cerrar" class="botones_largo" onclick="window.close();">
                    </div>
                </div>
            </td>
        </tr>
    </table>
</form>
</div>
</body>
</html>

