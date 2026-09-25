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
 * @package    tx
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Defensive defaults to avoid undefined variable notices when this file is included directly
$descZonaHoraria = $descZonaHoraria ?? "";
$descRadicado = $descRadicado ?? "Documento";
$whereFiltro = $whereFiltro ?? '0';
$ruta_raiz = $ruta_raiz ?? '';
$orderNo = $orderNo ?? 4;
$orderTipo = $orderTipo ?? "desc";
$fecha_documento = $fecha_documento ?? "(case when radi_nume_temp::text like '%0' then radi_fech_ofic else radi_fech_radi end)";

switch($db->driver)
{
    case 'postgres':
    {
        $descZonaHoraria = $descZonaHoraria ?? "";
        $descRadicado = $descRadicado ?? "Documento";
        $orderNo = 4;
        $orderTipo = "desc";
        $fecha_documento = "(case when radi_nume_temp::text like '%0' then radi_fech_ofic else radi_fech_radi end)";
        $isql = "select CAST(b.RADI_NUME_RADI AS varchar) AS \"CHK_CHKANULAR\"
                ,b.RADI_ASUNTO  as \"Asunto\"
                ,ver_usuarios(radi_usua_rem,',<br>') AS \"De\"
                ,ver_usuarios(radi_usua_dest,',<br>') AS \"Para\"
                ,substr($fecha_documento::text, 1,19) || '$descZonaHoraria' as \"DAT_Fecha $descRadicado\"
                ,b.RADI_NUME_RADI as \"HID_RADI_NUME_RADI\"
                ,b.RADI_NUME_TEXT as \"Número Documento\"
                ,e.esta_desc as \"Estado\"
                from radicado b left outer join estado e on b.esta_codi=e.esta_codi
                where b.radi_nume_radi in ($whereFiltro)
                order by ".($orderNo+1)." $orderTipo";
        break;
    }
}
?>