-- ==============================================================================
-- Quipux: Orgánico funcional — jerarquización de áreas INACTIVAS
-- ==============================================================================
-- La migración general (06) sólo procesó áreas activas (depe_estado=1). Las
-- inactivas con nombre "PADRE - HIJA" quedaron planas (p. ej.
-- "DIRECCIÓN DE INFRAESTRUCTURA - MANTENIMIENTO"). Este script las anida con la
-- misma regla de prefijo más largo, admitiendo que el padre esté activo o inactivo.
--
-- Respaldo en organico_respaldo_areas_general. Idempotente.
-- ==============================================================================

SET client_encoding TO 'UTF8';

BEGIN;

-- Respaldo de las inactivas planas que se vayan a mover
INSERT INTO organico_respaldo_areas_general (depe_codi, depe_nomb, depe_codi_padre)
SELECT d.depe_codi, d.depe_nomb, d.depe_codi_padre
  FROM dependencia d
 WHERE d.inst_codi=3 AND d.depe_estado=0 AND d.depe_nomb LIKE '% - %';

-- Resolución por prefijo más largo (padre activo o inactivo). El trigger recompone
-- usuario.depe_nomb de los usuarios que hubiera en esas áreas.
WITH ex AS (
    SELECT d.depe_codi, string_to_array(d.depe_nomb, ' - ') AS parts
      FROM dependencia d
     WHERE d.inst_codi=3 AND d.depe_estado=0 AND d.depe_nomb LIKE '% - %'
),
cand AS (
    SELECT ex.depe_codi, k,
           array_to_string(ex.parts[1:k], ' - ')                          AS prefijo,
           array_to_string(ex.parts[k+1:array_length(ex.parts,1)], ' - ') AS corto
      FROM ex, generate_series(1, array_length(ex.parts,1)-1) AS k
),
m AS (
    SELECT c.depe_codi, c.corto, p.depe_codi AS padre,
           row_number() OVER (PARTITION BY c.depe_codi ORDER BY c.k DESC) AS rn
      FROM cand c
      JOIN dependencia p
        ON p.inst_codi=3 AND p.depe_codi<>c.depe_codi
       AND norm_txt(p.depe_nomb)=norm_txt(c.prefijo)
)
UPDATE dependencia d
   SET depe_codi_padre = m.padre,
       depe_nomb       = m.corto
  FROM m
 WHERE m.rn=1 AND d.depe_codi=m.depe_codi
   AND m.padre NOT IN (SELECT depe_descendientes(d.depe_codi));   -- guarda de ciclo

-- Reporte: inactivas que aún quedaran planas (sin padre resoluble)
\echo '== inactivas que siguen planas (para revisión, esperado 0) =='
SELECT depe_codi, depe_nomb FROM dependencia
 WHERE inst_codi=3 AND depe_estado=0 AND depe_nomb LIKE '% - %'
   AND depe_codi_padre IN (SELECT r.depe_codi FROM dependencia r WHERE coalesce(r.depe_codi_padre,r.depe_codi)=r.depe_codi)
 ORDER BY depe_codi;

COMMIT;
