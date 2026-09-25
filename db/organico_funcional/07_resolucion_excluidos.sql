-- ==============================================================================
-- Quipux: Orgánico funcional — resolución de áreas excluidas (revisión manual)
-- ==============================================================================
-- Ejecutar DESPUÉS de 06. Resuelve las áreas que quedaron excluidas, aplicando la
-- regla correcta confirmada en revisión:
--
--   El padre es el PREFIJO MÁS LARGO (cortando por ' - ') que exista como área.
--   El resto es el nombre de la sub área, AUNQUE contenga guiones internos:
--     "FAC ARQUITECTURA Y URBANISMO - CENTRO ... URBANO - ARQUITECTÓNICO"
--        -> padre = FAC ARQUITECTURA Y URBANISMO
--           corto = "CENTRO ... URBANO - ARQUITECTÓNICO"   (NO se parte en 3)
--     "FAC ARQUITECTURA Y URBANISMO - CENTRO ... PRÁCTICAS PRE-PROFESIONALES"
--        -> padre = FAC ARQUITECTURA Y URBANISMO
--           corto = "CENTRO ... PRÁCTICAS PRE-PROFESIONALES"
--
-- Además crea el área "DIRECCIÓN ADMINISTRATIVA FINANCIERA" (distinta de
-- "DIRECCION ADMINISTRATIVA" 644 y de "DIRECCIÓN FINANCIERA" 11) para colgar sus
-- coordinaciones (276, 360, 361, 363, 367).
--
-- NO toca 239 y 376 (ya cuelgan de 238 INSTITUTO UNIVERSITARIO DE IDIOMAS): quedan
-- para decisión manual. 269 y 320 no tienen padre resoluble y también quedan.
--
-- Respaldo en organico_respaldo_areas_general. Idempotente.
-- ==============================================================================

SET client_encoding TO 'UTF8';

BEGIN;

-- 1) Crear el padre DIRECCIÓN ADMINISTRATIVA FINANCIERA si no existe
DO $$
DECLARE new_id integer;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM dependencia
                    WHERE inst_codi=3 AND norm_txt(depe_nomb)=norm_txt('DIRECCION ADMINISTRATIVA FINANCIERA')) THEN
        new_id := nextval('sec_dependencia');
        INSERT INTO dependencia
            (depe_codi, inst_codi, depe_nomb, dep_sigla, depe_estado, depe_pie1,
             depe_codi_padre, dep_central, depe_plantilla, inst_adscrita, estructura_organica)
        VALUES
            (new_id, 3, 'DIRECCIÓN ADMINISTRATIVA FINANCIERA', 'DAFIN', 1, '5',
             3, new_id, new_id, 3, true);
        RAISE NOTICE 'Creada DIRECCIÓN ADMINISTRATIVA FINANCIERA con depe_codi=%', new_id;
    END IF;
END $$;

-- 2) Respaldo de las áreas que se van a mover (todas las AREA excluidas salvo 239/376)
INSERT INTO organico_respaldo_areas_general (depe_codi, depe_nomb, depe_codi_padre)
SELECT d.depe_codi, d.depe_nomb, d.depe_codi_padre
  FROM organico_excluidos o JOIN dependencia d ON d.depe_codi=o.codigo
 WHERE o.tipo='AREA' AND o.codigo NOT IN (239,376);

-- 3) Resolución por prefijo más largo (excluye 239/376). El trigger recompone
--    usuario.depe_nomb de los usuarios afectados.
WITH ex AS (
    SELECT d.depe_codi, string_to_array(d.depe_nomb, ' - ') AS parts
      FROM organico_excluidos o JOIN dependencia d ON d.depe_codi=o.codigo
     WHERE o.tipo='AREA' AND o.codigo NOT IN (239,376)
),
cand AS (
    SELECT ex.depe_codi, k,
           array_to_string(ex.parts[1:k], ' - ')                              AS prefijo,
           array_to_string(ex.parts[k+1:array_length(ex.parts,1)], ' - ')     AS corto
      FROM ex, generate_series(1, array_length(ex.parts,1)-1) AS k
),
m AS (
    SELECT c.depe_codi, c.corto, p.depe_codi AS padre,
           row_number() OVER (PARTITION BY c.depe_codi ORDER BY c.k DESC) AS rn
      FROM cand c
      JOIN dependencia p
        ON p.inst_codi=3 AND p.depe_estado=1 AND p.depe_codi<>c.depe_codi
       AND norm_txt(p.depe_nomb)=norm_txt(c.prefijo)
)
UPDATE dependencia d
   SET depe_codi_padre = m.padre,
       depe_nomb       = m.corto
  FROM m
 WHERE m.rn=1 AND d.depe_codi=m.depe_codi
   AND m.padre NOT IN (SELECT depe_descendientes(d.depe_codi));   -- guarda de ciclo

-- 4) Quitar de la lista de exclusiones las que ya quedaron anidadas (padre no-raíz)
DELETE FROM organico_excluidos o
 WHERE o.tipo='AREA' AND o.codigo NOT IN (239,376)
   AND EXISTS (
        SELECT 1 FROM dependencia d JOIN dependencia p ON p.depe_codi=d.depe_codi_padre
         WHERE d.depe_codi=o.codigo
           AND coalesce(p.depe_codi_padre, p.depe_codi) <> p.depe_codi);   -- el padre no es raíz

-- 5) Reporte
\echo '== áreas de la familia DIRECCIÓN ADMINISTRATIVA FINANCIERA =='
SELECT d.depe_codi, d.depe_codi_padre AS padre, depe_ruta(d.depe_codi) AS ruta
  FROM dependencia d
 WHERE d.inst_codi=3 AND norm_txt(depe_ruta(d.depe_codi)) LIKE '%DIRECCION ADMINISTRATIVA FINANCIERA%'
 ORDER BY depe_ruta(d.depe_codi);

\echo '== áreas que siguen excluidas (para revisión) =='
SELECT o.codigo, o.motivo, depe_ruta(o.codigo) AS ruta
  FROM organico_excluidos o WHERE o.tipo='AREA' ORDER BY o.codigo;

\echo '== resumen de exclusiones restantes =='
SELECT tipo, motivo, count(*) FROM organico_excluidos GROUP BY 1,2 ORDER BY 1,2;

COMMIT;
