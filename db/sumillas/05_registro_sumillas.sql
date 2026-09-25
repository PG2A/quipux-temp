-- ==============================================================================
-- Quipux: Administración de Sumillas — Registro por documento
-- ==============================================================================
-- Este script es IDEMPOTENTE: puede ejecutarse varias veces sin efectos
-- secundarios.
--
-- Hasta ahora la sumilla elegida sólo quedaba embebida como texto dentro del
-- comentario de la transacción ("*Trámite Legal ..."), de donde no se puede
-- consultar, contar ni auditar. 'radicado_sumilla' la registra como dato, ligada
-- al documento y al evento concreto de la hoja de ruta en que se aplicó.
--
--   radi_nume_radi -> el documento
--   hist_codi      -> el evento de hist_eventos, para ubicarla en la hoja de ruta
--   accion_codi    -> la sumilla del catálogo
--   rasu_nombre    -> copia del texto en el momento de aplicarla
--   rasu_categoria -> copia del motivo en el momento de aplicarla
--
-- Las dos copias existen a propósito. Una sumilla puede renombrarse, cambiar de
-- categoría o eliminarse, y la hoja de ruta debe seguir diciendo qué se sumilló
-- aquel día, no lo que ese código signifique hoy. Por eso accion_codi es
-- ON DELETE SET NULL: el catálogo puede depurarse sin falsear el histórico.
--
-- Requiere 01_esquema_sumillas.sql y 03_categorias_sumillas.sql.
-- ==============================================================================

-- Los literales de este archivo llevan acentos y estan guardados en UTF-8. En Windows
-- psql asume WIN1252 segun la consola, y sin esto falla al leerlos.
SET client_encoding TO 'UTF8';

BEGIN;

CREATE TABLE IF NOT EXISTS radicado_sumilla (
    rasu_codi      bigint                   NOT NULL,
    radi_nume_radi numeric(20,0)            NOT NULL,
    hist_codi      bigint,
    accion_codi    integer,
    rasu_nombre    character varying(50)    NOT NULL,
    rasu_categoria character varying(50),
    usua_codi      integer,
    rasu_fecha     timestamp with time zone NOT NULL DEFAULT now(),
    CONSTRAINT pk_radicado_sumilla PRIMARY KEY (rasu_codi)
);

CREATE SEQUENCE IF NOT EXISTS sec_radicado_sumilla;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_radicado_sumilla_radicado') THEN
        ALTER TABLE radicado_sumilla
            ADD CONSTRAINT fk_radicado_sumilla_radicado
            FOREIGN KEY (radi_nume_radi) REFERENCES radicado (radi_nume_radi);
    END IF;

    -- El evento puede borrarse al depurar el recorrido de un documento; la
    -- sumilla sobrevive ligada al documento aunque pierda su fila de la ruta.
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_radicado_sumilla_hist') THEN
        ALTER TABLE radicado_sumilla
            ADD CONSTRAINT fk_radicado_sumilla_hist
            FOREIGN KEY (hist_codi) REFERENCES hist_eventos (hist_codi) ON DELETE SET NULL;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_radicado_sumilla_accion') THEN
        ALTER TABLE radicado_sumilla
            ADD CONSTRAINT fk_radicado_sumilla_accion
            FOREIGN KEY (accion_codi) REFERENCES accion (accion_codi) ON DELETE SET NULL;
    END IF;
END $$;

-- La hoja de ruta pide las sumillas de cada evento; el documento las pide todas.
CREATE INDEX IF NOT EXISTS idx_radicado_sumilla_hist ON radicado_sumilla (hist_codi);
CREATE INDEX IF NOT EXISTS idx_radicado_sumilla_radi ON radicado_sumilla (radi_nume_radi);
CREATE INDEX IF NOT EXISTS idx_radicado_sumilla_accion ON radicado_sumilla (accion_codi);

COMMIT;

-- Verificación
SELECT count(*) AS sumillas_registradas,
       count(DISTINCT radi_nume_radi) AS documentos
  FROM radicado_sumilla;
