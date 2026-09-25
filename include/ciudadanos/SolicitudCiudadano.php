<?php
// This file is part of Quipux – Document Management System
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

/**
 * Solicitud y aprobación de nuevos ciudadanos (RQT-7).
 *
 * Al buscar destinatarios (De/Para) el funcionario registra un ciudadano que queda
 * en ciudadano.ciu_estado = 2 (pendiente). Mientras esté así:
 *   - no puede iniciar sesión (login exige usua_esta = 1);
 *   - sólo lo ve en las búsquedas quien lo solicitó;
 *   - los documentos que lo llevan como destinatario no se pueden firmar/enviar
 *     (seguridad_documentos.php).
 * Aprobar lo activa y le envía sus credenciales; rechazar lo desactiva y lo retira
 * de los documentos en elaboración donde estaba.
 *
 * @package    quipux.ciudadanos
 */

class SolicitudCiudadano
{
    const PENDIENTE = 0;
    const APROBADA  = 1;
    const RECHAZADA = 2;
    const CANCELADA = 3;

    const CIU_PENDIENTE = 2;   // ciudadano.ciu_estado mientras espera aprobación

    const TTR_SOLICITUD  = 89; // sgd_ttr_transaccion: solicitud registrada en el documento
    const TTR_RESOLUCION = 90; // sgd_ttr_transaccion: aprobación / rechazo / cancelación

    var $db;
    var $ruta_raiz;

    private static $disponible = null;

    function __construct($db)
    {
        $this->db = $db;
        $this->ruta_raiz = dirname(__DIR__, 2);
    }

    /**
     * La tabla solicitud_ciudadano existe (la migración db/ciudadanos_solicitud ya
     * corrió). Las pantallas compartidas (bandejas, listas de destinatarios) lo
     * consultan para no romperse en una base sin migrar.
     */
    static function disponible($db)
    {
        if (self::$disponible === null) {
            $rs = $db->conn->Execute("select to_regclass('solicitud_ciudadano') is not null as ok");
            self::$disponible = ($rs && !$rs->EOF && ($rs->fields['OK'] === 't' || $rs->fields['OK'] === true || $rs->fields['OK'] == 1));
        }
        return self::$disponible;
    }

    // -------------------------------------------------------------------------
    // Alta
    // -------------------------------------------------------------------------

    /**
     * Registra la solicitud de un ciudadano recién creado en estado pendiente.
     *
     * @return int sol_codigo
     */
    function crear($ciu_codigo, $radi_nume_radi, $tipo_destinatario, $observacion)
    {
        $ciu_codigo = (int)$ciu_codigo;
        $tipo_destinatario = ((int)$tipo_destinatario == 3) ? 3 : 1;
        $radi = $this->numeroRadicado($radi_nume_radi);

        $sql = "insert into solicitud_ciudadano
                    (ciu_codigo, radi_nume_radi, tipo_destinatario, usua_codi_solicita, inst_codi_solicita,
                     fecha_solicitud, observacion_solicita, estado)
                values ($ciu_codigo, " . ($radi === null ? 'null' : $radi) . ", $tipo_destinatario,
                        " . (int)$_SESSION['usua_codi'] . ", " . (int)($_SESSION['inst_codi'] ?? 0) . ",
                        " . $this->db->conn->qstr(date('Y-m-d H:i:s')) . ",
                        " . $this->db->conn->qstr(substr(trim((string)$observacion), 0, 600)) . ", " . self::PENDIENTE . ")
                returning sol_codigo";
        $rs = $this->db->conn->Execute($sql);
        $sol_codigo = ($rs && !$rs->EOF) ? (int)$rs->fields['SOL_CODIGO'] : 0;

        if ($sol_codigo && $radi !== null) {
            $ciu = $this->datosCiudadano($ciu_codigo);
            $this->historico($radi, (int)$_SESSION['usua_codi'], (int)$_SESSION['usua_codi'],
                "Solicitud de alta del ciudadano " . ($ciu['nombre'] ?? '') . " (CI " . ($ciu['cedula'] ?? '') . ") como "
                . ($tipo_destinatario == 3 ? "copia" : "destinatario") . ". Pendiente de aprobación.",
                self::TTR_SOLICITUD, "SOL-" . $sol_codigo);
        }
        return $sol_codigo;
    }

    /** Solicitud pendiente de un ciudadano, o null. */
    function pendientePorCiudadano($ciu_codigo)
    {
        $rs = $this->db->conn->Execute("select * from solicitud_ciudadano
                                          where ciu_codigo = " . (int)$ciu_codigo . " and estado = " . self::PENDIENTE . "
                                          order by sol_codigo desc limit 1");
        return ($rs && !$rs->EOF) ? $this->fila($rs->fields) : null;
    }

    /** true si el ciudadano pendiente fue solicitado por $usua_codi. */
    function esSolicitante($ciu_codigo, $usua_codi)
    {
        $sol = $this->pendientePorCiudadano($ciu_codigo);
        return $sol !== null && (int)$sol['usua_codi_solicita'] === (int)$usua_codi;
    }

    // -------------------------------------------------------------------------
    // Consultas
    // -------------------------------------------------------------------------

    /**
     * Destinatarios pendientes (ciu_estado = 2) entre una lista de códigos.
     * No depende de solicitud_ciudadano: sirve aunque la migración no haya corrido.
     *
     * @return array [usua_codi => nombre]
     */
    static function pendientesEntre($db, $codigos)
    {
        $lista = array();
        foreach ((array)$codigos as $c) { $c = (int)$c; if ($c > 0) $lista[] = $c; }
        if (!$lista) return array();
        $rs = $db->conn->Execute("select usua_codi, usua_nombre from usuario
                                    where usua_esta = " . self::CIU_PENDIENTE . " and usua_codi in (" . implode(',', array_unique($lista)) . ")");
        $out = array();
        while ($rs && !$rs->EOF) {
            $out[(int)$rs->fields['USUA_CODI']] = trim((string)$rs->fields['USUA_NOMBRE']);
            $rs->MoveNext();
        }
        return $out;
    }

    /** Códigos "-a--b-" de radi_usua_dest / radi_cca a array de enteros. */
    static function codigosDeCadena($cadena)
    {
        $out = array();
        foreach (explode('-', (string)$cadena) as $c) { if (trim($c) !== '' && (int)$c > 0) $out[] = (int)$c; }
        return $out;
    }

    /** Cantidad de solicitudes pendientes (para el contador del menú). */
    function contarPendientes()
    {
        $rs = $this->db->conn->Execute("select count(*) as n from solicitud_ciudadano where estado = " . self::PENDIENTE);
        return ($rs && !$rs->EOF) ? (int)$rs->fields['N'] : 0;
    }

    /**
     * Listado para la bandeja de aprobación / "mis solicitudes".
     *
     * @param array $f  estado (''|0|1|2|3), texto, usua_codi_solicita, limite
     */
    function listar($f = array())
    {
        $where = array('1=1');
        if (isset($f['estado']) && $f['estado'] !== '' && $f['estado'] !== null)
            $where[] = "s.estado = " . (int)$f['estado'];
        if (!empty($f['usua_codi_solicita']))
            $where[] = "s.usua_codi_solicita = " . (int)$f['usua_codi_solicita'];
        if (!empty($f['texto'])) {
            $t = $this->db->conn->qstr('%' . strtoupper(trim($f['texto'])) . '%');
            $where[] = "(upper(c.ciu_cedula) like $t or upper(c.ciu_nombre || ' ' || c.ciu_apellido) like $t
                         or upper(coalesce(c.ciu_empresa,'')) like $t or upper(coalesce(r.radi_nume_text,'')) like $t)";
        }
        $limite = (int)($f['limite'] ?? 300);

        $sql = "select s.sol_codigo, s.ciu_codigo, s.radi_nume_radi, s.tipo_destinatario, s.estado,
                       s.fecha_solicitud, s.fecha_resolucion, s.observacion_solicita, s.observacion_resolucion,
                       s.usua_codi_solicita, s.usua_codi_resuelve,
                       c.ciu_cedula, c.ciu_nombre || ' ' || c.ciu_apellido as ciu_nombre_completo,
                       c.ciu_empresa, c.ciu_email, c.ciu_estado,
                       us.usua_nombre as solicitante, us.inst_nombre as inst_solicitante,
                       ur.usua_nombre as aprobador,
                       r.radi_nume_text, r.radi_asunto, r.esta_codi
                  from solicitud_ciudadano s
                  join ciudadano c on c.ciu_codigo = s.ciu_codigo
                  left join usuario us on us.usua_codi = s.usua_codi_solicita
                  left join usuario ur on ur.usua_codi = s.usua_codi_resuelve
                  left join radicado r on r.radi_nume_radi = s.radi_nume_radi
                 where " . implode(' and ', $where) . "
                 order by s.estado asc, s.fecha_solicitud desc
                 limit $limite";
        $rs = $this->db->conn->Execute($sql);
        $out = array();
        while ($rs && !$rs->EOF) { $out[] = $this->fila($rs->fields); $rs->MoveNext(); }
        return $out;
    }

    /** Una solicitud con todos los datos del ciudadano, o null. */
    function obtener($sol_codigo)
    {
        $sql = "select s.*, c.*, c.ciu_nombre || ' ' || c.ciu_apellido as ciu_nombre_completo,
                       us.usua_nombre as solicitante, us.usua_email as email_solicitante, us.inst_nombre as inst_solicitante,
                       ur.usua_nombre as aprobador,
                       r.radi_nume_text, r.radi_asunto, r.esta_codi, r.radi_usua_actu,
                       ci.nombre as ciudad_nombre
                  from solicitud_ciudadano s
                  join ciudadano c on c.ciu_codigo = s.ciu_codigo
                  left join usuario us on us.usua_codi = s.usua_codi_solicita
                  left join usuario ur on ur.usua_codi = s.usua_codi_resuelve
                  left join radicado r on r.radi_nume_radi = s.radi_nume_radi
                  left join ciudad ci on ci.id = c.ciudad_codi
                 where s.sol_codigo = " . (int)$sol_codigo;
        $rs = $this->db->conn->Execute($sql);
        return ($rs && !$rs->EOF) ? $this->fila($rs->fields) : null;
    }

    // -------------------------------------------------------------------------
    // Resolución
    // -------------------------------------------------------------------------

    /**
     * Aprobar: activa al ciudadano con su clave inicial (cédula), cierra la
     * solicitud, deja rastro en la hoja de ruta y avisa por correo al ciudadano
     * (credenciales) y al solicitante.
     *
     * @return string '' si todo fue bien, o el mensaje de error
     */
    function aprobar($sol_codigo, $usua_codi_resuelve, $observacion = '')
    {
        $sol = $this->obtener($sol_codigo);
        if ($sol === null) return "La solicitud no existe.";
        if ((int)$sol['estado'] !== self::PENDIENTE) return "La solicitud ya fue resuelta.";

        // La cédula pudo registrarse por otra vía mientras la solicitud esperaba.
        include_once($this->ruta_raiz . '/Administracion/ciudadanos/util_ciudadano.php');
        $ciud = new Ciudadano($this->db);
        $otra = $ciud->cuentaExistentePorCedula($sol['ciu_cedula'], $sol['ciu_codigo']);
        if ($otra !== null) return $ciud->mensajeCuentaExistente($otra, $sol['ciu_cedula']);

        $clave = $ciud->claveInicial($sol['ciu_cedula'], $sol['ciu_documento'], $sol['ciu_cedula']);
        $conn = $this->db->conn;
        $conn->StartTrans();

        $conn->Execute("update ciudadano
                           set ciu_estado = 1, ciu_nuevo = 1, ciu_pasw = " . $conn->qstr(md5($clave)) . ",
                               usua_codi_actualiza = " . (int)$usua_codi_resuelve . ", ciu_fecha_actualiza = CURRENT_TIMESTAMP,
                               ciu_obs_actualiza = 'Aprobación de solicitud de alta'
                         where ciu_codigo = " . (int)$sol['ciu_codigo']);
        $this->cerrar($sol_codigo, self::APROBADA, $usua_codi_resuelve, $observacion);

        if ($sol['radi_nume_radi'] !== null && $sol['radi_nume_radi'] !== '') {
            $this->historico($sol['radi_nume_radi'], (int)$usua_codi_resuelve, (int)$sol['usua_codi_solicita'],
                "Aprobada la solicitud de alta del ciudadano " . $sol['ciu_nombre_completo'] . " (CI " . $sol['ciu_cedula'] . ")."
                . (trim($observacion) != '' ? " " . $observacion : ""),
                self::TTR_RESOLUCION, "SOL-" . (int)$sol_codigo);
        }
        $ok = $conn->CompleteTrans();
        if (!$ok) return "No se pudo grabar la aprobación: " . $conn->ErrorMsg();

        $this->correoCredenciales($sol, $clave);
        $this->correoSolicitante($sol, 'aprobada', $observacion);
        return '';
    }

    /**
     * Rechazar (o cancelar, si lo hace el solicitante): desactiva al ciudadano
     * como hace la baja normal (cédula sufijada con su código para liberarla) y
     * lo retira de los documentos en elaboración que lo llevaban.
     */
    function rechazar($sol_codigo, $usua_codi_resuelve, $observacion, $cancelacion = false)
    {
        $sol = $this->obtener($sol_codigo);
        if ($sol === null) return "La solicitud no existe.";
        if ((int)$sol['estado'] !== self::PENDIENTE) return "La solicitud ya fue resuelta.";
        if (!$cancelacion && trim($observacion) == '') return "Indique el motivo del rechazo.";

        $conn = $this->db->conn;
        $ciu_codigo = (int)$sol['ciu_codigo'];
        $conn->StartTrans();

        $cedula_baja = substr((string)$sol['ciu_cedula'], 0, 10) . "-" . $ciu_codigo;
        $conn->Execute("update ciudadano
                           set ciu_estado = 0, ciu_cedula = " . $conn->qstr($cedula_baja) . ",
                               usua_codi_actualiza = " . (int)$usua_codi_resuelve . ", ciu_fecha_actualiza = CURRENT_TIMESTAMP,
                               ciu_obs_actualiza = " . $conn->qstr($cancelacion ? 'Solicitud de alta cancelada' : 'Solicitud de alta rechazada') . "
                         where ciu_codigo = $ciu_codigo");

        $documentos = $this->retirarDeDocumentos($ciu_codigo);
        $this->cerrar($sol_codigo, $cancelacion ? self::CANCELADA : self::RECHAZADA, $usua_codi_resuelve, $observacion);

        foreach ($documentos as $radi) {
            $this->historico($radi, (int)$usua_codi_resuelve, (int)$sol['usua_codi_solicita'],
                ($cancelacion ? "Cancelada" : "Rechazada") . " la solicitud de alta del ciudadano " . $sol['ciu_nombre_completo']
                . " (CI " . $sol['ciu_cedula'] . "); se retiró de los destinatarios."
                . (trim($observacion) != '' ? " Motivo: " . $observacion : ""),
                self::TTR_RESOLUCION, "SOL-" . (int)$sol_codigo);
        }
        $ok = $conn->CompleteTrans();
        if (!$ok) return "No se pudo grabar la resolución: " . $conn->ErrorMsg();

        if (!$cancelacion) $this->correoSolicitante($sol, 'rechazada', $observacion);
        return '';
    }

    function cancelar($sol_codigo, $usua_codi)
    {
        $sol = $this->obtener($sol_codigo);
        if ($sol === null) return "La solicitud no existe.";
        if ((int)$sol['usua_codi_solicita'] !== (int)$usua_codi && ($_SESSION['usua_admin_sistema'] ?? 0) != 1)
            return "Sólo quien hizo la solicitud puede cancelarla.";
        return $this->rechazar($sol_codigo, $usua_codi, 'Cancelada por el solicitante', true);
    }

    // -------------------------------------------------------------------------
    // Internos
    // -------------------------------------------------------------------------

    private function cerrar($sol_codigo, $estado, $usua_codi_resuelve, $observacion)
    {
        $conn = $this->db->conn;
        $conn->Execute("update solicitud_ciudadano
                           set estado = " . (int)$estado . ", usua_codi_resuelve = " . (int)$usua_codi_resuelve . ",
                               fecha_resolucion = " . $conn->qstr(date('Y-m-d H:i:s')) . ",
                               observacion_resolucion = " . $conn->qstr(substr(trim((string)$observacion), 0, 600)) . "
                         where sol_codigo = " . (int)$sol_codigo);
    }

    /**
     * Quita "-cod-" de radi_usua_dest y radi_cca en los documentos en elaboración.
     *
     * @return array números de radicado afectados
     */
    private function retirarDeDocumentos($ciu_codigo)
    {
        $conn = $this->db->conn;
        $marca = $conn->qstr("-$ciu_codigo-");
        $rs = $conn->Execute("select radi_nume_radi from radicado
                               where esta_codi = 1
                                 and (position($marca in coalesce(radi_usua_dest,'')) > 0
                                   or position($marca in coalesce(radi_cca,'')) > 0)");
        $afectados = array();
        while ($rs && !$rs->EOF) {
            $radi = $rs->fields['RADI_NUME_RADI'];
            $conn->Execute("update radicado
                               set radi_usua_dest = replace(coalesce(radi_usua_dest,''), $marca, ''),
                                   radi_cca       = replace(coalesce(radi_cca,''), $marca, '')
                             where radi_nume_radi = $radi");
            $afectados[] = $radi;
            $rs->MoveNext();
        }
        return $afectados;
    }

    private function historico($radi, $usua_ori, $usua_dest, $observacion, $ttr, $referencia)
    {
        $conn = $this->db->conn;
        $radi = $this->numeroRadicado($radi);
        if ($radi === null) return;
        // Si el catálogo no tiene la transacción (migración a medias) se usa
        // "Comentar Documento" (21) para no perder el rastro.
        $ttr = (int)$ttr;
        $existe = $conn->GetOne("select 1 from sgd_ttr_transaccion where sgd_ttr_codigo = $ttr");
        if (!$existe) $ttr = 21;
        $conn->Execute("insert into hist_eventos (hist_fech, usua_codi_ori, radi_nume_radi, hist_obse, usua_codi_dest, sgd_ttr_codigo, hist_referencia)
                        values (" . $conn->qstr(date('Y-m-d H:i:s')) . ", " . (int)$usua_ori . ", $radi, "
                             . $conn->qstr(substr($observacion, 0, 600)) . ", " . (int)$usua_dest . ", $ttr, "
                             . $conn->qstr(substr($referencia, 0, 50)) . ")");
    }

    private function datosCiudadano($ciu_codigo)
    {
        $rs = $this->db->conn->Execute("select ciu_cedula, ciu_nombre || ' ' || ciu_apellido as nombre from ciudadano where ciu_codigo = " . (int)$ciu_codigo);
        return ($rs && !$rs->EOF) ? array('cedula' => $rs->fields['CIU_CEDULA'], 'nombre' => $rs->fields['NOMBRE']) : array();
    }

    /** Sólo dígitos: radi_nume_radi es numeric(20,0) y no cabe en int de PHP. */
    private function numeroRadicado($radi)
    {
        $radi = trim((string)$radi);
        return ($radi !== '' && ctype_digit($radi)) ? $radi : null;
    }

    private function fila($fields)
    {
        $out = array();
        foreach ($fields as $k => $v) $out[strtolower($k)] = $v;
        return $out;
    }

    // -------------------------------------------------------------------------
    // Correos
    // -------------------------------------------------------------------------

    /** Correos de los usuarios activos con el permiso de aprobar. */
    function correosAprobadores()
    {
        $rs = $this->db->conn->Execute("select distinct u.usua_email
                                          from permiso p
                                          join permiso_usuario pu on pu.id_permiso = p.id_permiso
                                          join usuario u on u.usua_codi = pu.usua_codi and u.usua_esta = 1
                                         where p.nombre = 'perm_aprobar_ciudadano'
                                           and coalesce(u.usua_email,'') <> ''");
        $out = array();
        while ($rs && !$rs->EOF) { $out[] = trim($rs->fields['USUA_EMAIL']); $rs->MoveNext(); }
        return $out;
    }

    private function cabeceraCorreo()
    {
        return "<!DOCTYPE html><title>Informaci&oacute;n Quipux</title>"
             . "<body><center><h1>QUIPUX</h1><br /><h2>Sistema de Gesti&oacute;n Documental</h2><br /><br /></center>";
    }

    private function pieCorreo()
    {
        global $CFG;
        $soporte = $CFG->cuenta_mail_soporte ?? '';
        return "<br /><br />Saludos cordiales,<br /><br />Soporte Quipux."
             . "<br /><br /><b>Nota: </b>Este mensaje fue enviado autom&aacute;ticamente por el sistema, por favor no lo responda."
             . ($soporte != '' ? "<br />Si tiene alguna inquietud respecto a este mensaje, comun&iacute;quese con <a href='mailto:$soporte'>$soporte</a>" : "")
             . "</body></html>";
    }

    private function servidor()
    {
        global $CFG;
        return $CFG->nombre_servidor ?? '';
    }

    /** Los ciudadanos inician sesión por el acceso externo, no por el login de funcionarios. */
    private function urlLoginCiudadano()
    {
        return rtrim($this->servidor(), '/') . '/login.php?tipo=externo';
    }

    private function enviar($mensaje, $asunto, $destinatarios, $nombre = '')
    {
        include_once($this->ruta_raiz . '/funciones.php');
        $destinatarios = is_array($destinatarios) ? implode(',', $destinatarios) : $destinatarios;
        if (trim($destinatarios) == '') return;
        enviarMail($mensaje, $asunto, $destinatarios, $nombre, $this->ruta_raiz);
    }

    /** Aviso a los aprobadores cuando se registra una solicitud. */
    function correoNuevaSolicitud($sol_codigo)
    {
        $sol = $this->obtener($sol_codigo);
        if ($sol === null) return;
        $m = $this->cabeceraCorreo();
        $m .= "Se ha registrado una <b>solicitud de alta de ciudadano</b> que requiere su aprobaci&oacute;n:<br /><br />";
        $m .= "<table border='0'>
                 <tr><td><b>Solicitante:</b></td><td>" . $sol['solicitante'] . " (" . $sol['inst_solicitante'] . ")</td></tr>
                 <tr><td><b>Ciudadano:</b></td><td>" . $sol['ciu_nombre_completo'] . "</td></tr>
                 <tr><td><b>C&eacute;dula:</b></td><td>" . $sol['ciu_cedula'] . "</td></tr>
                 <tr><td><b>Instituci&oacute;n:</b></td><td>" . $sol['ciu_empresa'] . "</td></tr>
                 <tr><td><b>Documento:</b></td><td>" . ($sol['radi_nume_text'] ?: 'sin documento vinculado') . "</td></tr>
                 <tr><td><b>Observaci&oacute;n:</b></td><td>" . nl2br((string)$sol['observacion_solicita']) . "</td></tr>
               </table>";
        $m .= "<br />Puede resolverla en Administraci&oacute;n &rarr; Solicitudes de ciudadanos, ingresando a "
            . "<a href='" . $this->servidor() . "' target='_blank'>" . $this->servidor() . "</a>";
        $m .= $this->pieCorreo();
        $this->enviar($m, "Quipux: Solicitud de alta de ciudadano pendiente de aprobación.", $this->correosAprobadores());
    }

    /** Credenciales al ciudadano aprobado (clave inicial = cédula, cambio obligatorio al entrar). */
    private function correoCredenciales($sol, $clave)
    {
        if (trim((string)$sol['ciu_email']) == '') return;
        $m = $this->cabeceraCorreo();
        $m .= "Estimado(a) " . $sol['ciu_nombre_completo'] . ".<br /><br />";
        $m .= "Se ha creado un usuario en el sistema QUIPUX como ciudadano con la siguiente informaci&oacute;n:<br /><br />";
        $m .= "<table border='0'>
                 <tr><td><b>C&eacute;dula:</b></td><td>" . $sol['ciu_cedula'] . "</td></tr>
                 <tr><td><b>Nombre:</b></td><td>" . $sol['ciu_nombre'] . "</td></tr>
                 <tr><td><b>Apellido:</b></td><td>" . $sol['ciu_apellido'] . "</td></tr>
                 <tr><td><b>Instituci&oacute;n:</b></td><td>" . $sol['ciu_empresa'] . "</td></tr>
                 <tr><td><b>Puesto:</b></td><td>" . $sol['ciu_cargo'] . "</td></tr>
                 <tr><td><b>E-mail:</b></td><td>" . $sol['ciu_email'] . "</td></tr>
               </table>";
        $m .= "<br /><br />Sus datos de acceso al sistema son:<br /><br />
               <table border='0'>
                 <tr><td><b>Usuario:</b></td><td>" . $sol['ciu_cedula'] . "</td></tr>
                 <tr><td><b>Contrase&ntilde;a:</b></td><td>$clave</td></tr>
               </table><br />
               Al ingresar por primera vez el sistema le solicitar&aacute; cambiar esta contrase&ntilde;a.";
        $m .= "<br /><br />Puede acceder ingresando a <a href='" . $this->urlLoginCiudadano() . "' target='_blank'>" . $this->urlLoginCiudadano() . "</a>";
        $m .= $this->pieCorreo();
        $this->enviar($m, "Quipux: Creación de Ciudadano.", $sol['ciu_email'], $sol['ciu_nombre_completo']);
    }

    /** Resultado al funcionario que pidió el alta. */
    private function correoSolicitante($sol, $resultado, $observacion)
    {
        if (trim((string)$sol['email_solicitante']) == '') return;
        $m = $this->cabeceraCorreo();
        $m .= "Estimado(a) " . $sol['solicitante'] . ".<br /><br />";
        if ($resultado == 'aprobada') {
            $m .= "Su solicitud de alta del ciudadano <b>" . $sol['ciu_nombre_completo'] . "</b> (CI " . $sol['ciu_cedula'] . ") fue <b>aprobada</b>"
                . " por " . ($_SESSION['usua_nomb'] ?? 'el aprobador') . ".";
            if ($sol['radi_nume_text'])
                $m .= "<br /><br />El documento <b>" . $sol['radi_nume_text'] . "</b> ya puede firmarse y enviarse.";
            $asunto = "Quipux: Solicitud de ciudadano aprobada.";
        } else {
            $m .= "Su solicitud de alta del ciudadano <b>" . $sol['ciu_nombre_completo'] . "</b> (CI " . $sol['ciu_cedula'] . ") fue <b>rechazada</b>"
                . " por " . ($_SESSION['usua_nomb'] ?? 'el aprobador') . ".";
            if ($sol['radi_nume_text'])
                $m .= "<br /><br />El ciudadano fue retirado de los destinatarios del documento <b>" . $sol['radi_nume_text'] . "</b>.";
            $asunto = "Quipux: Solicitud de ciudadano rechazada.";
        }
        if (trim((string)$observacion) != '')
            $m .= "<br /><br /><b>Observaci&oacute;n:</b> " . nl2br($observacion);
        $m .= $this->pieCorreo();
        $this->enviar($m, $asunto, $sol['email_solicitante'], $sol['solicitante']);
    }
}
