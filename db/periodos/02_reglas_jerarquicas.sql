-- ==============================================================================
-- Quipux: Reglas de reasignación para documentos de periodo JERÁRQUICO
-- ==============================================================================
-- IDEMPOTENTE. Requiere 01_esquema_periodos.sql y el catálogo 'cargo'
-- (db/organico_funcional/).
--
-- Nivel de un usuario = cargo.cargo_nivel de su puesto (o usuarios.nivel_jerarquico
-- si el puesto no lo tiene). 1 es el nivel más alto (jefe). El nivel es relativo a
-- su área/sub área: cada unidad tiene su propio nivel 1.
--
-- jerarquia_destinos(usua) devuelve a quién puede reasignar ese usuario un
-- documento jerárquico, qué regla lo permite y a quién debe informarse (copia):
--
--   MISMA_AREA        Cualquier nivel: misma área/sub área, un nivel arriba,
--                     mismo nivel o cualquier nivel abajo. Un destinatario sin
--                     nivel sólo es alcanzable por esta regla.
--   NIVEL1_A_NIVEL1   Nivel 1 -> nivel 1 de otra área o sub área.
--   OTRA_SUBAREA      Nivel 2 o menor -> mismo nivel en otra sub área de la misma
--                     área (hermanas bajo el mismo padre, que no sea la raíz).
--   NIVEL2_A_NIVEL1   Nivel 2 -> nivel 1 de otra área o sub área, con copia al
--                     nivel 1 (jefe) de su propia unidad.
--   DOS_NIVELES       Nivel 3 o menor -> dos niveles arriba en su misma unidad,
--                     con copia al nivel intermedio.
--
-- Si un destinatario cumple varias reglas se toma la primera sin copia. Las
-- reglas que exigen copia no se ofrecen si no hay a quién copiar. Un usuario sin
-- nivel no obtiene filas: la aplicación usa entonces las reglas anteriores.
-- ==============================================================================

SET client_encoding TO 'UTF8';

BEGIN;

-- Nivel efectivo de un usuario (NULL si su puesto no tiene nivel).
CREATE OR REPLACE FUNCTION jerarquia_nivel(p_usua integer) RETURNS integer AS $$
    SELECT coalesce(c.cargo_nivel, u.nivel_jerarquico)
      FROM usuarios u LEFT JOIN cargo c ON c.cargo_id = u.cargo_id
     WHERE u.usua_codi = p_usua;
$$ LANGUAGE sql STABLE;

CREATE OR REPLACE FUNCTION jerarquia_destinos(p_usua integer)
RETURNS TABLE (usua_codi integer, depe_codi integer, nivel integer, regla varchar, copias integer[]) AS $$
WITH candidatos AS (
    -- Usuarios que pueden recibir documentos, con su nivel y el padre real de su
    -- unidad (NULL si la unidad es raíz o cuelga directamente de la raíz).
    SELECT u.usua_codi, u.depe_codi, u.inst_codi,
           coalesce(c.cargo_nivel, u.nivel_jerarquico) AS niv,
           CASE WHEN d.depe_codi_padre IS NULL OR d.depe_codi_padre = d.depe_codi THEN NULL
                WHEN p.depe_codi_padre IS NULL OR p.depe_codi_padre = p.depe_codi THEN NULL
                ELSE d.depe_codi_padre END AS area_padre
      FROM usuarios u
      LEFT JOIN cargo c       ON c.cargo_id = u.cargo_id
      JOIN dependencia d      ON d.depe_codi = u.depe_codi
      LEFT JOIN dependencia p ON p.depe_codi = d.depe_codi_padre
     WHERE u.usua_codi > 0 AND u.usua_esta = 1 AND u.visible_sub = 1
       AND u.usua_login NOT LIKE 'UADM%'
       AND d.depe_estado = 1
),
o AS (
    SELECT c.* FROM candidatos c WHERE c.usua_codi = p_usua AND c.niv IS NOT NULL
),
jefes AS (   -- nivel 1 de la unidad de quien reasigna
    SELECT array_agg(c.usua_codi ORDER BY c.usua_codi) AS ids
      FROM candidatos c, o
     WHERE c.depe_codi = o.depe_codi AND c.niv = 1 AND c.usua_codi <> o.usua_codi
),
intermedios AS (   -- nivel inmediatamente superior en su unidad
    SELECT array_agg(c.usua_codi ORDER BY c.usua_codi) AS ids
      FROM candidatos c, o
     WHERE c.depe_codi = o.depe_codi AND c.niv = o.niv - 1
),
evaluados AS (
    SELECT t.usua_codi, t.depe_codi, t.niv,
           CASE
             WHEN t.depe_codi = o.depe_codi AND (t.niv IS NULL OR t.niv >= o.niv - 1)
                  THEN 'MISMA_AREA'
             WHEN o.niv = 1 AND t.niv = 1 AND t.depe_codi <> o.depe_codi
                  THEN 'NIVEL1_A_NIVEL1'
             WHEN o.niv >= 2 AND t.niv = o.niv AND t.depe_codi <> o.depe_codi
                  AND o.area_padre IS NOT NULL AND t.area_padre = o.area_padre
                  THEN 'OTRA_SUBAREA'
             WHEN o.niv = 2 AND t.niv = 1 AND t.depe_codi <> o.depe_codi
                  AND (SELECT ids FROM jefes) IS NOT NULL
                  THEN 'NIVEL2_A_NIVEL1'
             WHEN o.niv >= 3 AND t.depe_codi = o.depe_codi AND t.niv = o.niv - 2
                  AND (SELECT ids FROM intermedios) IS NOT NULL
                  THEN 'DOS_NIVELES'
           END AS regla
      FROM candidatos t, o
     WHERE t.inst_codi = o.inst_codi AND t.usua_codi <> o.usua_codi
)
SELECT e.usua_codi, e.depe_codi, e.niv, e.regla::varchar,
       CASE e.regla
         WHEN 'NIVEL2_A_NIVEL1' THEN (SELECT ids FROM jefes)
         WHEN 'DOS_NIVELES'     THEN (SELECT ids FROM intermedios)
         ELSE '{}'::integer[]
       END
  FROM evaluados e
 WHERE e.regla IS NOT NULL;
$$ LANGUAGE sql STABLE;

COMMIT;
