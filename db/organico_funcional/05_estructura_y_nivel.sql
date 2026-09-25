-- ==============================================================================
-- Quipux: Orgánico funcional — campo "estructura orgánica" en áreas y "nivel" en
--         puestos
-- ==============================================================================
-- Añade:
--   dependencia.estructura_organica  boolean NOT NULL DEFAULT false
--       Marca si el área forma parte de la estructura orgánica de la institución.
--   cargo.cargo_nivel                integer (nullable)
--       Nivel jerárquico del puesto. Se alinea con usuarios.nivel_jerarquico.
--
-- Idempotente: puede ejecutarse más de una vez.
-- ==============================================================================

SET client_encoding TO 'UTF8';

BEGIN;

ALTER TABLE dependencia ADD COLUMN IF NOT EXISTS estructura_organica boolean NOT NULL DEFAULT false;
COMMENT ON COLUMN dependencia.estructura_organica IS 'El área forma parte de la estructura orgánica de la institución (por defecto false).';

ALTER TABLE cargo ADD COLUMN IF NOT EXISTS cargo_nivel integer;
COMMENT ON COLUMN cargo.cargo_nivel IS 'Nivel jerárquico del puesto (se corresponde con usuarios.nivel_jerarquico).';

COMMIT;
