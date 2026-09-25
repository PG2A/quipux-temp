-- ==============================================================================
-- Quipux: Administración de Sumillas — Esquema
-- ==============================================================================
-- Este script es IDEMPOTENTE: puede ejecutarse varias veces sin efectos
-- secundarios.
--
-- Las "sumillas" son las operaciones que el usuario escoge al reasignar un
-- documento (tx/formEnvio.php, combo "Operaciones") y se guardan en la tabla
-- 'accion'. Hasta ahora sólo se podían crear o borrar directamente en la base:
-- no había forma de retirar una sumilla de circulación sin perder el histórico
-- de los comentarios que ya la citan.
--
-- 'accion_activo' resuelve eso: 0 deja la sumilla fuera del combo pero conserva
-- la fila. Todas las sumillas existentes se marcan activas.
-- ==============================================================================

-- Los literales de este archivo llevan acentos y estan guardados en UTF-8. En Windows
-- psql asume WIN1252 segun la consola, y sin esto falla al leerlos.
SET client_encoding TO 'UTF8';

BEGIN;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                    WHERE table_name = 'accion' AND column_name = 'accion_activo') THEN

        ALTER TABLE accion ADD COLUMN accion_activo smallint;

        -- Lo que ya existía estaba en uso, así que entra como activo.
        UPDATE accion SET accion_activo = 1 WHERE accion_activo IS NULL;

        ALTER TABLE accion ALTER COLUMN accion_activo SET DEFAULT 1;
        ALTER TABLE accion ALTER COLUMN accion_activo SET NOT NULL;
    END IF;
END $$;

-- El combo de reasignación filtra siempre por institución + activo.
CREATE INDEX IF NOT EXISTS idx_accion_inst_activo ON accion (inst_codi, accion_activo);

COMMIT;

-- Verificación
SELECT inst_codi,
       count(*)                                  AS total,
       count(*) FILTER (WHERE accion_activo = 1) AS activas
  FROM accion
 GROUP BY inst_codi
 ORDER BY inst_codi;
