-- ==============================================================================
-- Quipux: Solicitud y aprobación de nuevos ciudadanos (RQT-7) — Esquema
-- ==============================================================================
-- Este script es IDEMPOTENTE: puede ejecutarse varias veces sin efectos
-- secundarios.
--
-- Al buscar destinatarios (De/Para) el funcionario puede registrar un ciudadano
-- nuevo, pero éste no queda operativo hasta que un aprobador lo autorice:
--
--   ciudadano.ciu_estado = 2   -> pendiente de aprobación (no inicia sesión,
--                                 no aparece en búsquedas de otros usuarios,
--                                 bloquea el envío de los documentos que lo llevan)
--   solicitud_ciudadano        -> quién lo pidió, para qué documento, quién lo
--                                 resolvió y con qué observación
--
-- Estados de solicitud_ciudadano.estado:
--   0 pendiente | 1 aprobada | 2 rechazada | 3 cancelada por el solicitante
--
-- radi_nume_radi se crea con el tipo real de radicado.radi_nume_radi (numeric(20,0)
-- en esta base), igual que hizo db/subrogacion.
-- ==============================================================================

-- Los literales de este archivo llevan acentos y estan guardados en UTF-8. En Windows
-- psql asume WIN1252 segun la consola, y sin esto falla al leerlos.
SET client_encoding TO 'UTF8';

BEGIN;

-- ------------------------------------------------------------------------------
-- 1. solicitud_ciudadano
-- ------------------------------------------------------------------------------
DO $$
DECLARE
    v_tipo text;
BEGIN
    SELECT format_type(a.atttypid, a.atttypmod)
      INTO v_tipo
      FROM pg_attribute a
      JOIN pg_class     c ON c.oid = a.attrelid
      JOIN pg_namespace n ON n.oid = c.relnamespace
     WHERE c.relname   = 'radicado'
       AND a.attname   = 'radi_nume_radi'
       AND a.attnum    > 0
       AND NOT a.attisdropped
       AND n.nspname   = ANY (current_schemas(false));

    IF v_tipo IS NULL THEN
        RAISE EXCEPTION 'No se encontró la columna radicado.radi_nume_radi; revise el esquema/search_path';
    END IF;

    EXECUTE format($f$
        CREATE TABLE IF NOT EXISTS solicitud_ciudadano (
            sol_codigo             serial       PRIMARY KEY,
            ciu_codigo             integer      NOT NULL,
            radi_nume_radi         %s,
            tipo_destinatario      smallint     NOT NULL DEFAULT 1,
            usua_codi_solicita     integer      NOT NULL,
            inst_codi_solicita     integer,
            fecha_solicitud        timestamp    NOT NULL DEFAULT now(),
            observacion_solicita   varchar(600),
            estado                 smallint     NOT NULL DEFAULT 0,
            usua_codi_resuelve     integer,
            fecha_resolucion       timestamp,
            observacion_resolucion varchar(600),
            CONSTRAINT ck_solicitud_ciudadano_estado CHECK (estado IN (0,1,2,3)),
            CONSTRAINT ck_solicitud_ciudadano_tipo   CHECK (tipo_destinatario IN (1,3))
        )$f$, v_tipo);
END $$;

COMMENT ON TABLE solicitud_ciudadano IS
    'Solicitudes de alta de ciudadanos hechas desde la búsqueda de destinatarios; el ciudadano queda en ciu_estado=2 hasta resolverse';
COMMENT ON COLUMN solicitud_ciudadano.tipo_destinatario IS
    '1 = Para (radi_usua_dest)  |  3 = Copia (radi_cca)';
COMMENT ON COLUMN solicitud_ciudadano.estado IS
    '0 pendiente | 1 aprobada | 2 rechazada | 3 cancelada por el solicitante';

CREATE INDEX IF NOT EXISTS ix_solicitud_ciudadano_estado
    ON solicitud_ciudadano (estado, fecha_solicitud);
CREATE INDEX IF NOT EXISTS ix_solicitud_ciudadano_ciu
    ON solicitud_ciudadano (ciu_codigo);
CREATE INDEX IF NOT EXISTS ix_solicitud_ciudadano_radi
    ON solicitud_ciudadano (radi_nume_radi);
CREATE INDEX IF NOT EXISTS ix_solicitud_ciudadano_solicita
    ON solicitud_ciudadano (usua_codi_solicita, estado);

-- ------------------------------------------------------------------------------
-- 2. Transacciones del histórico (hoja de ruta del documento)
-- ------------------------------------------------------------------------------
-- hist_eventos.sgd_ttr_codigo tiene FK a este catálogo; sin estas filas el
-- registro de la aprobación en la hoja de ruta fallaría.
INSERT INTO sgd_ttr_transaccion (sgd_ttr_codigo, sgd_ttr_descrip)
SELECT 89, 'Solicitud de ciudadano como destinatario'
 WHERE NOT EXISTS (SELECT 1 FROM sgd_ttr_transaccion WHERE sgd_ttr_codigo = 89);

INSERT INTO sgd_ttr_transaccion (sgd_ttr_codigo, sgd_ttr_descrip)
SELECT 90, 'Resolución de solicitud de ciudadano'
 WHERE NOT EXISTS (SELECT 1 FROM sgd_ttr_transaccion WHERE sgd_ttr_codigo = 90);

COMMIT;
