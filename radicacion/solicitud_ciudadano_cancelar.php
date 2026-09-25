<?php
// This file is part of Quipux – Document Management System
//
// Quipux is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Cancela, vía AJAX desde el popup de destinatarios, la solicitud de alta de un
 * ciudadano pendiente (RQT-7). Sólo quien la hizo (o un administrador).
 * Responde "OK" o el mensaje de error en texto plano.
 *
 * @package    radicacion
 */

session_start();
include_once(dirname(__DIR__).'/rec_session.php');
include_once(dirname(__DIR__).'/include/ciudadanos/SolicitudCiudadano.php');

header('Content-Type: text/plain; charset=UTF-8');

$ciu_codigo = (int)($_POST['ciu_codigo'] ?? 0);
if ($ciu_codigo <= 0) { echo "Solicitud no válida."; die(); }
if (!SolicitudCiudadano::disponible($db)) { echo "El módulo de solicitudes no está habilitado."; die(); }

$solicitudes = new SolicitudCiudadano($db);
$sol = $solicitudes->pendientePorCiudadano($ciu_codigo);
if ($sol === null) { echo "No hay una solicitud pendiente para este ciudadano."; die(); }

$error = $solicitudes->cancelar($sol['sol_codigo'], (int)$_SESSION['usua_codi']);
echo ($error === '') ? "OK" : $error;
