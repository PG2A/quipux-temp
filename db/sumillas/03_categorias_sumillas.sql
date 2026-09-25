-- ==============================================================================
-- Quipux: Administración de Sumillas — Categorías (motivos)
-- ==============================================================================
-- Este script es IDEMPOTENTE: puede ejecutarse varias veces sin efectos
-- secundarios.
--
-- La sumilla se selecciona desde un árbol que la clasifica por el motivo por el
-- que se emite. Los motivos viven en su propia tabla, 'accion_categoria', y no
-- como filas de 'accion': así la tabla de acciones contiene sólo sumillas
-- reales, y no una mezcla de sumillas con rótulos que nadie puede elegir.
--
--   accion_categoria  -> los motivos (ramas del árbol)
--   accion.cate_codi  -> a qué motivo pertenece cada sumilla (hoja del árbol)
--
-- Una sumilla sin cate_codi sigue siendo válida: aparece en el árbol al primer
-- nivel, sin clasificar.
--
-- Si la base viene de la versión intermedia que modelaba la jerarquía dentro de
-- 'accion' (columna accion_codi_padre), este script la convierte: pasa esas
-- filas a accion_categoria, repunta las sumillas y elimina la columna.
--
-- Requiere 01_esquema_sumillas.sql.
-- ==============================================================================

-- Los literales de este archivo llevan acentos y estan guardados en UTF-8. En Windows
-- psql asume WIN1252 segun la consola, y sin esto falla al leerlos.
SET client_encoding TO 'UTF8';

BEGIN;

-- ------------------------------------------------------------------------------
-- 1. Tabla de categorías
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS accion_categoria (
    cate_codi   integer               NOT NULL,
    cate_nombre character varying(50),
    cate_activo smallint              NOT NULL DEFAULT 1,
    -- Permite decidir en qué orden se listan los motivos en el árbol; el
    -- alfabético rara vez es el orden en que la gente los piensa.
    cate_orden  integer               NOT NULL DEFAULT 0,
    inst_codi   bigint,
    CONSTRAINT pk_accion_categoria PRIMARY KEY (cate_codi)
);

-- A diferencia de 'accion', que arrastra códigos asignados a mano, la tabla
-- nueva sí usa secuencia.
CREATE SEQUENCE IF NOT EXISTS sec_accion_categoria;

CREATE INDEX IF NOT EXISTS idx_accion_categoria_inst ON accion_categoria (inst_codi, cate_activo);

-- ------------------------------------------------------------------------------
-- 2. Enlace desde la sumilla
-- ------------------------------------------------------------------------------
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                    WHERE table_name = 'accion' AND column_name = 'cate_codi') THEN

        ALTER TABLE accion ADD COLUMN cate_codi integer;

        -- Sin ON DELETE: borrar una categoría con sumillas debe fallar en vez de
        -- dejarlas sin clasificar en silencio. La pantalla de administración
        -- comprueba y explica el caso antes de intentarlo.
        ALTER TABLE accion
            ADD CONSTRAINT fk_accion_categoria
            FOREIGN KEY (cate_codi) REFERENCES accion_categoria (cate_codi);
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_accion_cate ON accion (cate_codi);

-- ------------------------------------------------------------------------------
-- 3. Conversión desde el modelo anterior (accion_codi_padre)
-- ------------------------------------------------------------------------------
DO $$
DECLARE
    v_categorias integer := 0;
    v_sumillas   integer := 0;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns
                    WHERE table_name = 'accion' AND column_name = 'accion_codi_padre') THEN
        RETURN;
    END IF;

    -- Cada fila de 'accion' de la que colgaban otras era, en realidad, un motivo.
    INSERT INTO accion_categoria (cate_codi, cate_nombre, cate_activo, cate_orden, inst_codi)
    SELECT nextval('sec_accion_categoria'), p.accion_nombre, p.accion_activo, 0, p.inst_codi
      FROM accion p
     WHERE EXISTS (SELECT 1 FROM accion h WHERE h.accion_codi_padre = p.accion_codi)
       AND NOT EXISTS (SELECT 1 FROM accion_categoria c
                        WHERE c.inst_codi = p.inst_codi
                          AND translate(upper(c.cate_nombre),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                            = translate(upper(p.accion_nombre),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN'));
    GET DIAGNOSTICS v_categorias = ROW_COUNT;

    UPDATE accion a
       SET cate_codi = c.cate_codi
      FROM accion p
      JOIN accion_categoria c
        ON c.inst_codi = p.inst_codi
       AND translate(upper(c.cate_nombre),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
         = translate(upper(p.accion_nombre),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
     WHERE a.accion_codi_padre = p.accion_codi
       AND a.cate_codi IS NULL;
    GET DIAGNOSTICS v_sumillas = ROW_COUNT;

    -- La restricción se quita antes del DELETE: las sumillas todavía apuntan a
    -- su antiguo padre y borrarlo con la llave puesta fallaría.
    ALTER TABLE accion DROP CONSTRAINT IF EXISTS fk_accion_padre;

    DELETE FROM accion p
     WHERE EXISTS (SELECT 1 FROM accion h WHERE h.accion_codi_padre = p.accion_codi);

    ALTER TABLE accion DROP COLUMN accion_codi_padre;

    RAISE NOTICE 'Convertidas desde el modelo anterior: % categorías, % sumillas', v_categorias, v_sumillas;
END $$;

DROP INDEX IF EXISTS idx_accion_padre;

COMMIT;

-- Verificación
SELECT a.inst_codi,
       count(*)                                        AS sumillas,
       count(*) FILTER (WHERE a.cate_codi IS NULL)     AS sin_categoria,
       (SELECT count(*) FROM accion_categoria c
         WHERE c.inst_codi = a.inst_codi)              AS categorias
  FROM accion a
 GROUP BY a.inst_codi
 ORDER BY a.inst_codi;
