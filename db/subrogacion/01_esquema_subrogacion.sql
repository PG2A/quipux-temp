-- ==============================================================================
-- Quipux: Rediseño del módulo de Subrogación de Puestos - Esquema
-- ==============================================================================
-- Fase 1 de 3. Este script es IDEMPOTENTE: puede ejecutarse varias veces sin
-- efectos secundarios.
--
-- Introduce el modelo que permite tratar la subrogación como un "contexto de
-- actuación con vigencia" en lugar de clonar la cuenta del subrogante:
--
--   1. usuarios_subrogacion  -> estados reales y marcas de tiempo de vigencia
--   2. subrogacion_auditoria -> quién actúa como subrogante en cada momento
--   3. radicado_subrogacion  -> qué documentos pertenecen a qué subrogación
--   4. subrogacion_cargo_permitido -> qué cargos admiten subrogación
--
-- Los tipos de las columnas que referencian radicado.radi_nume_radi se resuelven
-- dinámicamente contra el esquema real, porque este despliegue no expone el DDL
-- de 'radicado'.
-- ==============================================================================

-- Los literales de este archivo llevan acentos y estan guardados en UTF-8. En Windows
-- psql asume WIN1252 segun la consola, y sin esto falla al leerlos.
SET client_encoding TO 'UTF8';

BEGIN;

-- ------------------------------------------------------------------------------
-- 1. usuarios_subrogacion: ciclo de vida explícito
-- ------------------------------------------------------------------------------
-- Hasta ahora la vigencia se representaba sólo con usua_visible (1/0), y las
-- fechas usua_fecha_inicio / usua_fecha_fin se guardaban pero nada las aplicaba.
-- 'estado' distingue una subrogación PROGRAMADA (aún no vigente) de una ACTIVA,
-- que es lo que permite al cron activarla en su fecha.

DO $$
DECLARE
    v_creada boolean := false;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                    WHERE table_name = 'usuarios_subrogacion' AND column_name = 'estado') THEN
        ALTER TABLE usuarios_subrogacion ADD COLUMN estado smallint;
        v_creada := true;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                    WHERE table_name = 'usuarios_subrogacion' AND column_name = 'fecha_activacion') THEN
        ALTER TABLE usuarios_subrogacion ADD COLUMN fecha_activacion timestamp;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                    WHERE table_name = 'usuarios_subrogacion' AND column_name = 'fecha_finalizacion') THEN
        ALTER TABLE usuarios_subrogacion ADD COLUMN fecha_finalizacion timestamp;
    END IF;

    -- El backfill corre UNA sola vez, al crear la columna, para no pisar estados
    -- que el sistema haya asignado después (p. ej. una subrogación PROGRAMADA).
    IF v_creada THEN
        UPDATE usuarios_subrogacion
           SET estado = CASE WHEN usua_visible = 1 THEN 1 ELSE 2 END;

        -- Las que ya estaban vigentes se consideran activadas desde su fecha de inicio.
        UPDATE usuarios_subrogacion
           SET fecha_activacion = usua_fecha_inicio
         WHERE estado = 1;

        ALTER TABLE usuarios_subrogacion ALTER COLUMN estado SET NOT NULL;
        ALTER TABLE usuarios_subrogacion ALTER COLUMN estado SET DEFAULT 0;
    END IF;
END $$;

COMMENT ON COLUMN usuarios_subrogacion.estado IS
    '0 Programada  1 Activa  2 Finalizada  3 Cancelada';
COMMENT ON COLUMN usuarios_subrogacion.fecha_activacion IS
    'Momento real de activación (lo fija cron/procesar_subrogaciones.php)';
COMMENT ON COLUMN usuarios_subrogacion.fecha_finalizacion IS
    'Momento real de finalización (cron o finalización anticipada por el administrador)';

-- El cron busca por vigencia; el combo "Usuario:" busca por subrogante.
CREATE INDEX IF NOT EXISTS ix_subrogacion_vigencia
    ON usuarios_subrogacion (estado, usua_fecha_inicio, usua_fecha_fin);
CREATE INDEX IF NOT EXISTS ix_subrogacion_subrogante
    ON usuarios_subrogacion (usua_subrogante, estado);
CREATE INDEX IF NOT EXISTS ix_subrogacion_subrogado
    ON usuarios_subrogacion (usua_subrogado, estado);


-- ------------------------------------------------------------------------------
-- 2. subrogacion_auditoria: quién actúa como subrogante en cada momento
-- ------------------------------------------------------------------------------
-- Se alimenta desde el hook en Tx::insertarHistorico(): cuando la sesión está
-- actuando bajo un contexto de subrogación, el histórico normal registra al
-- cargo (el jefe) y esta tabla registra a la persona real detrás de la acción.

CREATE TABLE IF NOT EXISTS subrogacion_auditoria (
    audi_codi             bigserial    PRIMARY KEY,
    usua_subrogacion_codi integer      NOT NULL,
    usua_codi_real        integer      NOT NULL,
    usua_codi_actuando    integer      NOT NULL,
    fecha_hora            timestamp    NOT NULL DEFAULT now(),
    accion                varchar(50)  NOT NULL,
    ip                    varchar(150),
    session_id            varchar(100)
);

COMMENT ON TABLE subrogacion_auditoria IS
    'Registro de qué persona actuó bajo qué cargo subrogado y cuándo';
COMMENT ON COLUMN subrogacion_auditoria.usua_codi_real IS
    'Persona física que ejecutó la acción';
COMMENT ON COLUMN subrogacion_auditoria.usua_codi_actuando IS
    'Cargo (usuario titular) bajo cuya identidad se ejecutó';
COMMENT ON COLUMN subrogacion_auditoria.accion IS
    'CAMBIO_CONTEXTO, ACTIVACION, FINALIZACION, o el código de transacción ejecutado';

CREATE INDEX IF NOT EXISTS ix_subrogacion_audi_subrogacion
    ON subrogacion_auditoria (usua_subrogacion_codi, fecha_hora);
CREATE INDEX IF NOT EXISTS ix_subrogacion_audi_real
    ON subrogacion_auditoria (usua_codi_real, fecha_hora);


-- ------------------------------------------------------------------------------
-- 3. radicado_subrogacion: sello de qué documentos pertenecen a la subrogación
-- ------------------------------------------------------------------------------
-- Réplica del patrón ya probado de 'informados' (tabla companion + JOIN), en vez
-- de un ALTER sobre 'radicado', que es la tabla más grande del sistema.
--
-- Es la pieza que permite:
--   - devolver al titular SÓLO los documentos heredados y no los propios del
--     subrogante (el bug actual de desactivar_usuario_subrogante.php)
--   - construir las dos bandejas de cierre de sólo lectura
--
-- radi_nume_radi y las columnas de auditoría que lo referencian se crean con el
-- tipo real de radicado.radi_nume_radi.

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
        CREATE TABLE IF NOT EXISTS radicado_subrogacion (
            radi_nume_radi        %s        NOT NULL,
            usua_subrogacion_codi integer   NOT NULL,
            tipo                  char(1)   NOT NULL,
            fecha                 timestamp NOT NULL DEFAULT now(),
            CONSTRAINT pk_radicado_subrogacion
                PRIMARY KEY (radi_nume_radi, usua_subrogacion_codi, tipo),
            CONSTRAINT ck_radicado_subrogacion_tipo
                CHECK (tipo IN ('T','L'))
        )$f$, v_tipo);

    -- La auditoría puede referirse a un documento concreto.
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                    WHERE table_name = 'subrogacion_auditoria' AND column_name = 'radi_nume_radi') THEN
        EXECUTE format('ALTER TABLE subrogacion_auditoria ADD COLUMN radi_nume_radi %s', v_tipo);
    END IF;
END $$;

COMMENT ON TABLE radicado_subrogacion IS
    'Marca qué documentos se entregaron o tramitaron bajo una subrogación';
COMMENT ON COLUMN radicado_subrogacion.tipo IS
    'T = copia tramitable (bandeja del subrogante)  |  L = copia de lectura (titular)';

CREATE INDEX IF NOT EXISTS ix_radicado_subrogacion_subr
    ON radicado_subrogacion (usua_subrogacion_codi, tipo);


-- ------------------------------------------------------------------------------
-- 4. subrogacion_cargo_permitido: qué cargos admiten subrogación
-- ------------------------------------------------------------------------------
-- El requisito pide restringir la subrogación al nivel jerárquico del Manual de
-- Puestos, pero ESE MODELO NO EXISTE en la base: usuarios.cargo_id está marcado
-- 'Campo en desuso' y se rellena con 99 arbitrariamente. Lo único parecido a un
-- nivel es cargo_tipo (0 Normal / 1 Jefe / 2 Asistente).
--
-- Se entrega la estructura para expresar la regla; poblarla con el Manual de
-- Puestos real es una tarea de datos de la institución.

CREATE TABLE IF NOT EXISTS subrogacion_cargo_permitido (
    scp_codi    serial   PRIMARY KEY,
    cargo_tipo  smallint NOT NULL,
    depe_codi   integer,
    permite     smallint NOT NULL DEFAULT 1,
    observacion varchar(200)
);

COMMENT ON TABLE subrogacion_cargo_permitido IS
    'Cargos habilitados para ser subrogados. depe_codi NULL = aplica a toda la institución';

CREATE UNIQUE INDEX IF NOT EXISTS ux_subrogacion_cargo_permitido
    ON subrogacion_cargo_permitido (cargo_tipo, COALESCE(depe_codi, -1));

-- Semilla: se preserva la regla vigente hasta hoy (sólo Jefe de Área).
INSERT INTO subrogacion_cargo_permitido (cargo_tipo, depe_codi, permite, observacion)
SELECT 1, NULL, 1, 'Jefe de Área - regla vigente antes del rediseño'
 WHERE NOT EXISTS (SELECT 1 FROM subrogacion_cargo_permitido
                    WHERE cargo_tipo = 1 AND depe_codi IS NULL);

-- Columna reservada para cuando se cargue el Manual de Puestos real.
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                    WHERE table_name = 'usuarios' AND column_name = 'nivel_jerarquico') THEN
        ALTER TABLE usuarios ADD COLUMN nivel_jerarquico integer;
    END IF;
END $$;

COMMENT ON COLUMN usuarios.nivel_jerarquico IS
    'Nivel del Manual de Puestos. Reservado: pendiente de carga por la institución';


-- ------------------------------------------------------------------------------
-- 5. Bandejas de consulta de subrogación (sólo lectura)
-- ------------------------------------------------------------------------------
-- Sirven durante el período y también después de finalizado:
--   17 -> el titular consulta qué se gestionó en su puesto mientras lo subrogaban
--   18 -> el subrogante consulta qué gestionó él cubriendo puestos ajenos
-- correspondencia.php sólo ofrece cada una a quien tiene documentos en ella.

INSERT INTO carpeta (carp_codi, carp_nombre, carp_descripcion, carp_orden)
SELECT 17, 'Trámites de mi Puesto Subrogado',
          'Documentos gestionados en su puesto mientras estuvo subrogado (sólo lectura)',
          (SELECT COALESCE(MAX(carp_orden),0) + 1 FROM carpeta)
 WHERE NOT EXISTS (SELECT 1 FROM carpeta WHERE carp_codi = 17);

INSERT INTO carpeta (carp_codi, carp_nombre, carp_descripcion, carp_orden)
SELECT 18, 'Trámites como Subrogante',
          'Documentos que usted gestionó actuando como subrogante (sólo lectura)',
          (SELECT COALESCE(MAX(carp_orden),0) + 1 FROM carpeta)
 WHERE NOT EXISTS (SELECT 1 FROM carpeta WHERE carp_codi = 18);

COMMIT;
