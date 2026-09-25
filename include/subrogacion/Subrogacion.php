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
 * Lógica compartida del módulo de Subrogación de Puestos.
 *
 * Concentra en un solo lugar las reglas que antes estaban duplicadas (y
 * divergentes) entre grabar_usuario_subrogante.php, desactivar_usuario_subrogante.php
 * y el resto del sistema:
 *
 *   - qué subrogaciones están vigentes en este instante
 *   - activación y finalización (usadas tanto por el cron como por el administrador)
 *   - sellado de los documentos que pertenecen a una subrogación
 *   - auditoría de quién actúa bajo qué cargo
 *
 * A diferencia del modelo anterior, aquí NUNCA se crea un usuario: usua_subrogante
 * apunta siempre a la cuenta real de la persona.
 *
 * @package    subrogacion
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class Subrogacion
{
    /** Aún no vigente: el cron la activará al llegar usua_fecha_inicio */
    const PROGRAMADA  = 0;
    /** Vigente */
    const ACTIVA      = 1;
    /** Terminada por vencimiento de fecha o por el administrador */
    const FINALIZADA  = 2;
    /** Anulada antes de entrar en vigencia */
    const CANCELADA   = 3;

    /** Copia tramitable, en la bandeja del subrogante */
    const DOC_TRAMITABLE = 'T';
    /** Copia de lectura, en Informados del titular */
    const DOC_LECTURA    = 'L';

    var $db;

    function __construct($db)
    {
        $this->db = $db;
    }

    // -------------------------------------------------------------------------
    // Consultas de vigencia
    // -------------------------------------------------------------------------

    /**
     * Subrogaciones vigentes en las que $usua_codi actúa COMO SUBROGANTE.
     * Alimenta el combo "Usuario:" del encabezado.
     *
     * @return array filas con los datos del cargo subrogado
     */
    function contextosVigentes($usua_codi)
    {
        $usua_codi = (int)$usua_codi;
        if ($usua_codi <= 0) return array();

        $sql = "select s.usua_subrogacion_codi, s.usua_subrogado, s.usua_subrogante
                     , s.usua_fecha_inicio, s.usua_fecha_fin
                     , u.usua_nombre, u.usua_cargo, u.depe_nomb, u.inst_nombre
                  from usuarios_subrogacion s
                  join usuario u on u.usua_codi = s.usua_subrogado
                 where s.usua_subrogante = $usua_codi
                   and s.estado = " . self::ACTIVA . "
                   and now() between s.usua_fecha_inicio and s.usua_fecha_fin
                 order by u.usua_nombre";

        $contextos = array();
        $rs = $this->db->conn->query($sql);
        while ($rs && !$rs->EOF) {
            $contextos[] = $rs->fields;
            $rs->MoveNext();
        }
        return $contextos;
    }

    /**
     * Subrogación vigente de un TITULAR, si la tiene.
     * La usan los puntos de entrega de documentos para decidir el desvío.
     *
     * @return array|null
     */
    function vigenteParaSubrogado($usua_codi)
    {
        $usua_codi = (int)$usua_codi;
        if ($usua_codi <= 0) return null;

        $sql = "select usua_subrogacion_codi, usua_subrogado, usua_subrogante
                  from usuarios_subrogacion
                 where usua_subrogado = $usua_codi
                   and estado = " . self::ACTIVA . "
                   and now() between usua_fecha_inicio and usua_fecha_fin
                 order by usua_fecha_inicio desc
                 limit 1";

        $rs = $this->db->conn->query($sql);
        return ($rs && !$rs->EOF) ? $rs->fields : null;
    }

    /**
     * Verifica que $usua_codi_real pueda actuar bajo la identidad de
     * $usua_codi_destino. Es la autorización del cambio de contexto: sustituye a
     * la comparación de cédulas que reiniciar_session.php usa para cuentas
     * propias, porque aquí ya no hay cuenta clon que compartir cédula.
     *
     * @return array|null la subrogación que lo autoriza, o null
     */
    function contextoAutorizado($usua_codi_real, $usua_codi_destino)
    {
        $usua_codi_real     = (int)$usua_codi_real;
        $usua_codi_destino  = (int)$usua_codi_destino;
        if ($usua_codi_real <= 0 || $usua_codi_destino <= 0) return null;

        $sql = "select usua_subrogacion_codi, usua_subrogado, usua_subrogante
                  from usuarios_subrogacion
                 where usua_subrogante = $usua_codi_real
                   and usua_subrogado  = $usua_codi_destino
                   and estado = " . self::ACTIVA . "
                   and now() between usua_fecha_inicio and usua_fecha_fin
                 limit 1";

        $rs = $this->db->conn->query($sql);
        return ($rs && !$rs->EOF) ? $rs->fields : null;
    }

    function obtener($subrogacion_codi)
    {
        $subrogacion_codi = (int)$subrogacion_codi;
        $rs = $this->db->conn->query(
            "select * from usuarios_subrogacion where usua_subrogacion_codi = $subrogacion_codi");
        return ($rs && !$rs->EOF) ? $rs->fields : null;
    }

    // -------------------------------------------------------------------------
    // Reglas de elegibilidad
    // -------------------------------------------------------------------------

    /**
     * ¿El cargo admite ser subrogado? (requisito del Manual de Puestos)
     *
     * Mientras la institución no cargue el Manual de Puestos real, la tabla se
     * expresa sobre cargo_tipo, que es el único indicador de nivel disponible
     * hoy en la base (0 Normal / 1 Jefe / 2 Asistente).
     */
    function cargoPermiteSubrogacion($cargo_tipo, $depe_codi = null)
    {
        $cargo_tipo = (int)$cargo_tipo;
        $sql = "select permite
                  from subrogacion_cargo_permitido
                 where cargo_tipo = $cargo_tipo
                   and (depe_codi is null";
        if ($depe_codi !== null) $sql .= " or depe_codi = " . (int)$depe_codi;
        $sql .= ")
                 order by depe_codi nulls last
                 limit 1";

        $rs = $this->db->conn->query($sql);
        return ($rs && !$rs->EOF) ? ((int)$rs->fields['PERMITE'] === 1) : false;
    }

    // -------------------------------------------------------------------------
    // Ciclo de vida
    // -------------------------------------------------------------------------

    /**
     * Pone en vigencia una subrogación programada.
     * La invoca el cron cuando se alcanza usua_fecha_inicio.
     */
    function activar($subrogacion_codi)
    {
        $subrogacion_codi = (int)$subrogacion_codi;
        $subr = $this->obtener($subrogacion_codi);
        if (!$subr) return "No existe la subrogación $subrogacion_codi";
        if ((int)$subr['ESTADO'] === self::ACTIVA) return "";

        $this->db->conn->BeginTrans();

        $ok = $this->db->conn->Execute(
            "update usuarios_subrogacion
                set estado = " . self::ACTIVA . "
                  , usua_visible = 1
                  , fecha_activacion = " . $this->db->conn->sysTimeStamp . "
              where usua_subrogacion_codi = $subrogacion_codi");

        if (!$ok) {
            $error = $this->db->conn->ErrorMsg();
            $this->db->conn->RollbackTrans();
            return "Error al activar la subrogación $subrogacion_codi: $error";
        }

        $this->auditar($subrogacion_codi, 0, (int)$subr['USUA_SUBROGADO'], 'ACTIVACION');

        // El contexto nuevo sólo aparece en el combo tras releer la sesión.
        $this->invalidarSesion((int)$subr['USUA_SUBROGANTE']);

        $this->db->conn->CommitTrans();
        return "";
    }

    /**
     * Finaliza una subrogación: devuelve al titular lo que quedó pendiente y
     * revoca el contexto.
     *
     * La usan por igual el cron (por vencimiento) y el administrador
     * (finalización anticipada), de modo que ambos caminos no puedan divergir.
     *
     * @param  int    $usua_codi_ejecuta 0 = sistema/cron
     * @return string cadena vacía si todo fue bien; el error en caso contrario
     */
    function finalizar($subrogacion_codi, $observa = '', $usua_codi_ejecuta = 0)
    {
        $subrogacion_codi = (int)$subrogacion_codi;
        $subr = $this->obtener($subrogacion_codi);
        if (!$subr) return "No existe la subrogación $subrogacion_codi";
        if ((int)$subr['ESTADO'] === self::FINALIZADA) return "";

        $subrogante = (int)$subr['USUA_SUBROGANTE'];
        $subrogado  = (int)$subr['USUA_SUBROGADO'];
        if ($observa === '') $observa = 'Reasignado por finalización de la subrogación de puesto';

        // 1. Devolver SÓLO los documentos sellados como recibidos por esta
        //    subrogación y aún sin despachar. Los documentos propios del
        //    subrogante no llevan sello y por tanto no se tocan: ese era
        //    justamente el defecto del flujo anterior, que reasignaba todo lo
        //    que tuviera radi_usua_actu = subrogante.
        $pendientes = $this->documentosPendientes($subrogacion_codi);

        $this->db->conn->BeginTrans();

        if (count($pendientes) > 0) {
            include_once(dirname(__DIR__) . '/tx/Tx.php');
            $tx = new Tx($this->db);
            $tx->reasignar($pendientes, $subrogante, $subrogado, $observa, "", true);
            $tx->cambiarPropietarioTareasSubrogacion($pendientes, $subrogado, $subrogante, 0);
        }

        // 2. Cerrar el registro.
        $ok = $this->db->conn->Execute(
            "update usuarios_subrogacion
                set estado = " . self::FINALIZADA . "
                  , usua_visible = 0
                  , fecha_finalizacion = " . $this->db->conn->sysTimeStamp . "
              where usua_subrogacion_codi = $subrogacion_codi");

        if (!$ok) {
            $error = $this->db->conn->ErrorMsg();
            $this->db->conn->RollbackTrans();
            return "Error al finalizar la subrogación $subrogacion_codi: $error";
        }

        // 3. Revocar el contexto: al releer la sesión, el cargo ya no aparece en
        //    el combo "Usuario:". Esto es lo que en el modelo anterior se hacía
        //    desactivando la cuenta clon.
        $this->invalidarSesion($subrogante);

        $this->auditar($subrogacion_codi, $usua_codi_ejecuta, $subrogado, 'FINALIZACION');

        $this->db->conn->CommitTrans();
        return "";
    }

    /**
     * Documentos de esta subrogación que quedaron en poder del subrogante y
     * siguen sin despacharse (esta_codi 1 = en trámite, 2 = recibido).
     *
     * Con el modelo actual los documentos permanecen en la bandeja del puesto,
     * así que normalmente no habrá ninguno. Sigue haciendo falta para dos casos:
     * las subrogaciones migradas del modelo de cuenta clon, y los documentos que
     * el subrogante se haya reasignado a sí mismo durante el período.
     */
    function documentosPendientes($subrogacion_codi)
    {
        $subrogacion_codi = (int)$subrogacion_codi;
        $sql = "select r.radi_nume_radi
                  from radicado_subrogacion rs
                  join radicado r on r.radi_nume_radi = rs.radi_nume_radi
                  join usuarios_subrogacion s on s.usua_subrogacion_codi = rs.usua_subrogacion_codi
                 where rs.usua_subrogacion_codi = $subrogacion_codi
                   and rs.tipo = '" . self::DOC_TRAMITABLE . "'
                   and r.esta_codi in (1,2)
                   and r.radi_usua_actu = s.usua_subrogante";

        $radicados = array();
        $rs = $this->db->conn->query($sql);
        while ($rs && !$rs->EOF) {
            $radicados[] = $rs->fields['RADI_NUME_RADI'];
            $rs->MoveNext();
        }
        return $radicados;
    }

    // -------------------------------------------------------------------------
    // Sellado y auditoría
    // -------------------------------------------------------------------------

    /**
     * Marca un documento como perteneciente a una subrogación.
     * El sello es lo que permite después devolver sólo lo heredado y construir
     * las bandejas de cierre de ambas partes.
     */
    function sellarDocumento($radi_nume_radi, $subrogacion_codi, $tipo)
    {
        $radi_nume_radi   = $this->numeroRadicado($radi_nume_radi);
        $subrogacion_codi = (int)$subrogacion_codi;
        if ($radi_nume_radi === null) return false;
        if ($tipo !== self::DOC_TRAMITABLE && $tipo !== self::DOC_LECTURA) return false;

        // Un mismo documento puede sellarse una sola vez por subrogación y tipo.
        $existe = $this->db->conn->query(
            "select 1 from radicado_subrogacion
              where radi_nume_radi = $radi_nume_radi
                and usua_subrogacion_codi = $subrogacion_codi
                and tipo = '$tipo'");
        if ($existe && !$existe->EOF) return true;

        return (bool)$this->db->conn->Execute(
            "insert into radicado_subrogacion (radi_nume_radi, usua_subrogacion_codi, tipo)
             values ($radi_nume_radi, $subrogacion_codi, '$tipo')");
    }

    /**
     * Deja constancia de que una persona actuó bajo un cargo subrogado.
     * El histórico normal (hist_eventos) registra al cargo; esta tabla registra
     * a la persona real.
     */
    function auditar($subrogacion_codi, $usua_codi_real, $usua_codi_actuando, $accion, $radi_nume_radi = null)
    {
        $subrogacion_codi   = (int)$subrogacion_codi;
        $usua_codi_real     = (int)$usua_codi_real;
        $usua_codi_actuando = (int)$usua_codi_actuando;

        $ip = ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '') . ' - ' . ($_SERVER['REMOTE_ADDR'] ?? 'cron');
        $session_id = (session_status() === PHP_SESSION_ACTIVE) ? session_id() : 'cron';

        // La marca de tiempo se toma del reloj de PHP, igual que
        // Historico::insertarHistorico(). Usar el now() del servidor dejaría las
        // dos tablas desfasadas cuando PHP y PostgreSQL tienen zonas horarias
        // distintas, y el cruce entre auditoría e histórico no cuadraría.
        $campos = "usua_subrogacion_codi, usua_codi_real, usua_codi_actuando, accion, fecha_hora, ip, session_id";
        $valores = "$subrogacion_codi, $usua_codi_real, $usua_codi_actuando"
                 . ", " . $this->db->conn->qstr(substr($accion, 0, 50))
                 . ", " . $this->db->conn->qstr(date("Y-m-d H:i:s"))
                 . ", " . $this->db->conn->qstr(substr($ip, 0, 150))
                 . ", " . $this->db->conn->qstr(substr($session_id, 0, 100));

        $radi = $this->numeroRadicado($radi_nume_radi);
        if ($radi !== null) {
            $campos  .= ", radi_nume_radi";
            $valores .= ", " . $radi;
        }

        return (bool)$this->db->conn->Execute(
            "insert into subrogacion_auditoria ($campos) values ($valores)");
    }

    /**
     * Valida y normaliza un número de radicado.
     *
     * radicado.radi_nume_radi es numeric(20,0) y sus valores reales llegan a 20
     * dígitos, por encima de PHP_INT_MAX (19 dígitos). Castearlo a int lo
     * truncaría a 9223372036854775807, con lo que todos los documentos recientes
     * quedarían sellados bajo el mismo número. Por eso se maneja como cadena de
     * dígitos, validada aquí para que sea seguro interpolarla en el SQL.
     *
     * @return string|null la cadena de dígitos, o null si no es válida
     */
    function numeroRadicado($valor)
    {
        $valor = trim((string)$valor);
        return preg_match('/^[0-9]{1,20}$/', $valor) ? $valor : null;
    }

    // -------------------------------------------------------------------------

    /**
     * Fuerza a que el usuario vuelva a autenticarse, para que su lista de
     * contextos y sus permisos se recalculen.
     */
    function invalidarSesion($usua_codi)
    {
        $usua_codi = (int)$usua_codi;
        if ($usua_codi <= 0) return;
        $this->db->conn->Execute("delete from usuarios_sesion where usua_codi = $usua_codi");
    }
}
