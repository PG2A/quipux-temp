-- ==============================================================================
-- Quipux: Periodos de trabajo (modo jerárquico / lineal) - Esquema
-- ==============================================================================
-- IDEMPOTENTE: puede ejecutarse varias veces sin efectos secundarios.
--
--   1. periodo            -> rango de fechas por institución, modo
--                            (jerarquico = true: jerárquico; false: lineal) y
--                            marca 'vigente' (sólo un periodo vigente sella documentos).
--   2. periodo_historial  -> una fila por cada alta o cambio de un periodo
--                            (modo, fechas, quién y cuándo). La llena un trigger:
--                            nada se sobrescribe sin dejar rastro.
--   3. radicado.periodo_codi / radicado.radi_jerarquico
--                          -> con qué periodo y en qué modo se creó cada
--                            documento. Lo sella un trigger BEFORE INSERT, así
--                            que cubre todas las rutas de creación sin tocar PHP.
--
-- Los documentos anteriores a este script quedan con periodo NULL ("sin periodo").
--
-- Uso:  psql -v ON_ERROR_STOP=1 -f db/periodos/01_esquema_periodos.sql
-- ==============================================================================

SET client_encoding TO 'UTF8';

BEGIN;

-- ------------------------------------------------------------------------------
-- 1. periodo
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS periodo (
    periodo_codi        serial PRIMARY KEY,
    inst_codi           integer      NOT NULL REFERENCES institucion(inst_codi),
    periodo_nombre      varchar(100) NOT NULL,
    fecha_inicio        date         NOT NULL,
    fecha_fin           date         NOT NULL,
    jerarquico          boolean      NOT NULL DEFAULT true,
    observacion         varchar(500),
    usua_codi_actualiza integer,
    fecha_actualiza     timestamp    NOT NULL DEFAULT now(),
    CONSTRAINT ck_periodo_rango CHECK (fecha_fin >= fecha_inicio)
);

ALTER TABLE periodo ADD COLUMN IF NOT EXISTS vigente boolean NOT NULL DEFAULT true;

CREATE INDEX IF NOT EXISTS idx_periodo_inst_rango ON periodo (inst_codi, fecha_inicio, fecha_fin);

-- ------------------------------------------------------------------------------
-- 2. periodo_historial
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS periodo_historial (
    periodo_hist_codi serial PRIMARY KEY,
    periodo_codi      integer      NOT NULL REFERENCES periodo(periodo_codi),
    accion            varchar(20)  NOT NULL,   -- CREACION | CAMBIO_MODO | ACTIVACION | DESACTIVACION | CAMBIO_FECHAS | EDICION
    jerarquico        boolean      NOT NULL,
    fecha_inicio      date         NOT NULL,
    fecha_fin         date         NOT NULL,
    periodo_nombre    varchar(100) NOT NULL,
    observacion       varchar(500),
    usua_codi         integer,
    fecha             timestamp    NOT NULL DEFAULT now()
);

ALTER TABLE periodo_historial ADD COLUMN IF NOT EXISTS vigente boolean;

CREATE INDEX IF NOT EXISTS idx_periodo_historial_periodo ON periodo_historial (periodo_codi, fecha);

-- ------------------------------------------------------------------------------
-- Validación: los periodos de una institución no se solapan, estén vigentes o
-- no: un rango ya registrado no se puede volver a usar.
-- ------------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION periodo_validar() RETURNS trigger AS $$
BEGIN
    IF EXISTS (SELECT 1 FROM periodo p
                WHERE p.inst_codi = NEW.inst_codi
                  AND p.periodo_codi <> NEW.periodo_codi
                  AND p.fecha_inicio <= NEW.fecha_fin
                  AND p.fecha_fin    >= NEW.fecha_inicio) THEN
        RAISE EXCEPTION 'El rango % - % se solapa con otro periodo de la institución',
                        NEW.fecha_inicio, NEW.fecha_fin;
    END IF;
    NEW.fecha_actualiza := now();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_periodo_validar ON periodo;
CREATE TRIGGER trg_periodo_validar
    BEFORE INSERT OR UPDATE ON periodo
    FOR EACH ROW EXECUTE FUNCTION periodo_validar();

-- ------------------------------------------------------------------------------
-- Historial: cada alta o cambio deja una fila con el estado resultante
-- ------------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION periodo_registrar_historial() RETURNS trigger AS $$
DECLARE
    v_accion varchar(20);
BEGIN
    IF TG_OP = 'INSERT' THEN
        v_accion := 'CREACION';
    ELSIF NEW.jerarquico IS DISTINCT FROM OLD.jerarquico THEN
        v_accion := 'CAMBIO_MODO';
    ELSIF NEW.vigente IS DISTINCT FROM OLD.vigente THEN
        v_accion := CASE WHEN NEW.vigente THEN 'ACTIVACION' ELSE 'DESACTIVACION' END;
    ELSIF NEW.fecha_inicio IS DISTINCT FROM OLD.fecha_inicio
       OR NEW.fecha_fin    IS DISTINCT FROM OLD.fecha_fin THEN
        v_accion := 'CAMBIO_FECHAS';
    ELSIF NEW.periodo_nombre IS DISTINCT FROM OLD.periodo_nombre
       OR NEW.observacion    IS DISTINCT FROM OLD.observacion THEN
        v_accion := 'EDICION';
    ELSE
        RETURN NEW;   -- nada relevante cambió
    END IF;

    INSERT INTO periodo_historial (periodo_codi, accion, jerarquico, vigente, fecha_inicio, fecha_fin,
                                   periodo_nombre, observacion, usua_codi)
    VALUES (NEW.periodo_codi, v_accion, NEW.jerarquico, NEW.vigente, NEW.fecha_inicio, NEW.fecha_fin,
            NEW.periodo_nombre, NEW.observacion, NEW.usua_codi_actualiza);
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_periodo_historial ON periodo;
CREATE TRIGGER trg_periodo_historial
    AFTER INSERT OR UPDATE ON periodo
    FOR EACH ROW EXECUTE FUNCTION periodo_registrar_historial();

-- ------------------------------------------------------------------------------
-- Periodo vigente de una institución en una fecha (NULL si no hay)
-- ------------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION periodo_vigente(p_inst integer, p_fecha date DEFAULT current_date)
RETURNS integer AS $$
    SELECT periodo_codi FROM periodo
     WHERE inst_codi = p_inst AND vigente AND p_fecha BETWEEN fecha_inicio AND fecha_fin
     LIMIT 1;
$$ LANGUAGE sql STABLE;

-- ------------------------------------------------------------------------------
-- 3. radicado: periodo y modo con que se creó el documento
-- ------------------------------------------------------------------------------
ALTER TABLE radicado ADD COLUMN IF NOT EXISTS periodo_codi    integer;
ALTER TABLE radicado ADD COLUMN IF NOT EXISTS radi_jerarquico boolean;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_radicado_periodo') THEN
        ALTER TABLE radicado ADD CONSTRAINT fk_radicado_periodo
            FOREIGN KEY (periodo_codi) REFERENCES periodo(periodo_codi);
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_radicado_periodo_codi ON radicado (periodo_codi);

-- Sella el documento con el periodo vigente (marcado y dentro de rango) en su
-- fecha de creación y con el
-- modo que ese periodo tenía EN ESE MOMENTO. Un cambio posterior de modo no
-- altera los documentos ya creados. Si quien inserta ya trae el periodo, se respeta.
CREATE OR REPLACE FUNCTION radicado_sellar_periodo() RETURNS trigger AS $$
BEGIN
    IF NEW.periodo_codi IS NULL THEN
        SELECT p.periodo_codi, p.jerarquico
          INTO NEW.periodo_codi, NEW.radi_jerarquico
          FROM periodo p
         WHERE p.inst_codi = NEW.radi_inst_actu
           AND p.vigente
           AND coalesce(NEW.radi_fech_radi, now())::date BETWEEN p.fecha_inicio AND p.fecha_fin
         LIMIT 1;
    ELSIF NEW.radi_jerarquico IS NULL THEN
        SELECT p.jerarquico INTO NEW.radi_jerarquico FROM periodo p WHERE p.periodo_codi = NEW.periodo_codi;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_radicado_sellar_periodo ON radicado;
CREATE TRIGGER trg_radicado_sellar_periodo
    BEFORE INSERT ON radicado
    FOR EACH ROW EXECUTE FUNCTION radicado_sellar_periodo();

COMMIT;
