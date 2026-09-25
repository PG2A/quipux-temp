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
 * Devuelve (JSON) los datos personales de un usuario interno existente a partir
 * de la cédula, para autocompletar el formulario de creación de otra cuenta
 * (mismo persona, otro puesto/área). Incluye las áreas donde la cédula ya tiene
 * cuenta activa, para avisar de duplicado si se elige la misma.
 *
 * @package    usuarios
 * @author     2026 Marco Terán <marcosdanny14@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

ob_start();
session_start();
if (($_SESSION["usua_admin_sistema"] ?? 0) != 1) { ob_end_clean(); echo '{"existe":false}'; exit; }
include_once(dirname(__DIR__, 2).'/rec_session.php');
require_once(dirname(__DIR__, 2).'/funciones.php');

$inst_codi = 0 + $_SESSION["inst_codi"];
$cedula    = trim(limpiar_sql($_GET["cedula"] ?? $_POST["cedula"] ?? ""));
$usr_codigo = 0 + ($_GET["usr_codigo"] ?? $_POST["usr_codigo"] ?? 0);

if (ob_get_length() !== false) ob_end_clean();
header("Content-Type: application/json; charset=UTF-8");

$out = array("existe" => false);

if ($cedula !== "") {
    $ced_qs = $db->conn->qstr($cedula);

    // Datos personales de una cuenta interna existente con esa cédula (todas las
    // cuentas de la misma persona comparten estos datos). Se toma la más reciente.
    $rs = $db->conn->Execute(
        "select usua_nomb, usua_apellido, usua_titulo, usua_abr_titulo, usua_email,
                usua_direccion, usua_telefono, ciu_codi, usua_sumilla, tipo_identificacion
           from usuarios
          where usua_cedula = $ced_qs
          order by usua_esta desc, usua_codi desc
          limit 1");

    if ($rs && !$rs->EOF) {
        $out["existe"]     = true;
        $out["nombre"]     = $rs->fields["USUA_NOMB"];
        $out["apellido"]   = $rs->fields["USUA_APELLIDO"];
        $out["titulo"]     = $rs->fields["USUA_TITULO"];
        $out["abr_titulo"] = $rs->fields["USUA_ABR_TITULO"];
        $out["email"]      = $rs->fields["USUA_EMAIL"];
        $out["direccion"]  = $rs->fields["USUA_DIRECCION"];
        $out["telefono"]   = $rs->fields["USUA_TELEFONO"];
        $out["ciu_codi"]   = (int)$rs->fields["CIU_CODI"];
        $out["sumilla"]    = $rs->fields["USUA_SUMILLA"];
        $out["tipo_id"]    = (int)$rs->fields["TIPO_IDENTIFICACION"];

        // Puestos (cargo_id) que la cédula YA ocupa con cuenta activa, excluyendo
        // la cuenta que se edita. Duplicado = misma cédula + mismo puesto.
        $cargos = array();
        $rsA = $db->conn->Execute(
            "select cargo_id from usuarios
              where usua_cedula = $ced_qs and usua_esta = 1 and inst_codi = $inst_codi
                and cargo_id is not null
                and usua_codi <> " . (int)$usr_codigo);
        while ($rsA && !$rsA->EOF) {
            $cargos[] = (int)$rsA->fields["CARGO_ID"];
            $rsA->MoveNext();
        }
        $out["cargos_usados"] = $cargos;

        // ¿Ya tiene un puesto ACTIVO en un área de la estructura orgánica? En ese
        // caso no se le puede crear otra cuenta (se valida también al grabar).
        $rsE = $db->conn->Execute(
            "select u.usua_cargo, depe_ruta(u.depe_codi) as area
               from usuarios u join dependencia d on d.depe_codi = u.depe_codi
              where u.usua_cedula = $ced_qs and u.usua_esta = 1
                and d.estructura_organica = true
                and u.usua_codi <> " . (int)$usr_codigo . "
              order by u.usua_codi limit 1");
        $out["puesto_estructura"] = ($rsE && !$rsE->EOF)
            ? trim($rsE->fields["USUA_CARGO"]) . " - " . trim($rsE->fields["AREA"])
            : "";
    }
}

echo json_encode($out);
