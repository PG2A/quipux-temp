-- ==============================================================================
-- Quipux: Orgánico funcional — migración general (resto de áreas y puestos)
-- ==============================================================================
-- Ejecutar DESPUÉS del piloto FCM (01–05). Generaliza a toda la institución:
--
--  A. Áreas con nombre "PADRE - HIJA" → jerarquía real (padre + nombre corto),
--     SÓLO cuando el padre se resuelve sin ambigüedad. Todo lo dudoso NO se toca
--     y queda listado en organico_excluidos para revisión manual.
--  B. Catálogo de puestos (cargo) para todas las áreas con usuarios activos, y
--     enlace usuarios.cargo_id. Los usuarios activos sin puesto se listan como
--     excluidos (no se puede catalogar un puesto vacío).
--
-- Regla de resolución de áreas (conservadora):
--   * corto  = último segmento tras el último ' - '
--   * prefijo = lo anterior
--   * el padre es el área (activa, misma institución) cuyo nombre normalizado
--     (sin acentos, mayúsculas, espacios colapsados) es igual al prefijo.
--   * Si el prefijo no coincide con ninguna área  -> PADRE_NO_ENCONTRADO
--     Si coincide con más de una                  -> PADRE_AMBIGUO
--     Si no hay segmento corto (guion interno)     -> SIN_CORTO
--     Si el padre resulta descendiente (ciclo)     -> CICLO
--   Esto excluye por diseño los guiones legítimos ("... URBANO - ARQUITECTÓNICO",
--   "PROYECTO NOVA VICE - ACADEMICO", "2021-2025") y los padres inexistentes
--   ("DIRECCIÓN ADMINISTRATIVA FINANCIERA - ...").
--
-- Respaldo: organico_respaldo_areas_general (valores previos de las áreas movidas).
-- Idempotente: puede ejecutarse más de una vez.
-- ==============================================================================

SET client_encoding TO 'UTF8';

BEGIN;

-- Normalizador: mayúsculas, sin acentos, espacios colapsados
CREATE OR REPLACE FUNCTION norm_txt(t text) RETURNS text LANGUAGE sql IMMUTABLE AS $$
  SELECT translate(upper(regexp_replace(trim(coalesce(t,'')),'\s+',' ','g')),
                   'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÂÊÎÔÛÃÕÑ','AEIOUAEIOUAEIOUAEIOUAON');
$$;

CREATE TABLE IF NOT EXISTS organico_respaldo_areas_general (
    depe_codi        integer,
    depe_nomb        varchar,
    depe_codi_padre  integer,
    respaldo_fecha   timestamptz NOT NULL DEFAULT now()
);

-- Tabla de casos para revisión manual (se regenera en cada corrida)
CREATE TABLE IF NOT EXISTS organico_excluidos (
    tipo     varchar(20),   -- AREA | PUESTO
    codigo   integer,       -- depe_codi (AREA) o usua_codi (PUESTO)
    nombre   varchar,       -- nombre del área o del usuario
    motivo   varchar(40),
    depe_codi integer,      -- área asociada
    detalle  varchar,       -- ruta/prefijo/observación
    fecha    timestamptz NOT NULL DEFAULT now()
);
DELETE FROM organico_excluidos;

-- ------------------------------------------------------------------------------
-- A. Resolución de áreas (snapshot, contra los nombres ORIGINALES)
-- ------------------------------------------------------------------------------
CREATE TEMP TABLE tmp_res ON COMMIT DROP AS
WITH cand AS (
  SELECT d.depe_codi, d.depe_nomb,
         trim(regexp_replace(d.depe_nomb, ' - [^-]*$', ''))       AS prefijo,
         trim((regexp_match(d.depe_nomb, ' - ([^-]*)$'))[1])      AS corto
  FROM dependencia d
  WHERE d.inst_codi = 3 AND d.depe_estado = 1 AND d.depe_nomb LIKE '% - %'
    AND d.depe_codi NOT IN (SELECT depe_descendientes(112))       -- FCM ya migrada
)
SELECT c.depe_codi, c.depe_nomb, c.prefijo, c.corto,
       (SELECT array_agg(p.depe_codi) FROM dependencia p
         WHERE p.inst_codi = 3 AND p.depe_estado = 1 AND p.depe_codi <> c.depe_codi
           AND norm_txt(p.depe_nomb) = norm_txt(c.prefijo))       AS padres
FROM cand c;

-- Estado de cada candidata
ALTER TABLE tmp_res ADD COLUMN estado varchar(30);
ALTER TABLE tmp_res ADD COLUMN padre  integer;

UPDATE tmp_res SET
  padre  = CASE WHEN padres IS NOT NULL AND array_length(padres,1) = 1 THEN padres[1] END,
  estado = CASE
             WHEN corto IS NULL OR corto = ''            THEN 'SIN_CORTO'
             WHEN padres IS NULL                          THEN 'PADRE_NO_ENCONTRADO'
             WHEN array_length(padres,1) > 1              THEN 'PADRE_AMBIGUO'
             ELSE 'OK'
           END;

-- Guarda de ciclo: si el padre resuelto es descendiente de la candidata, se excluye
UPDATE tmp_res t SET estado = 'CICLO'
 WHERE t.estado = 'OK'
   AND t.padre IN (SELECT depe_descendientes(t.depe_codi));

-- Respaldo de las que sí se van a mover
INSERT INTO organico_respaldo_areas_general (depe_codi, depe_nomb, depe_codi_padre)
SELECT d.depe_codi, d.depe_nomb, d.depe_codi_padre
  FROM dependencia d JOIN tmp_res t ON t.depe_codi = d.depe_codi
 WHERE t.estado = 'OK';

-- Aplica las resueltas: padre real + nombre corto (el trigger recompone usuario.depe_nomb)
UPDATE dependencia d
   SET depe_codi_padre = t.padre,
       depe_nomb       = t.corto
  FROM tmp_res t
 WHERE d.depe_codi = t.depe_codi AND t.estado = 'OK';

-- Registra las excluidas para revisión
INSERT INTO organico_excluidos (tipo, codigo, nombre, motivo, depe_codi, detalle)
SELECT 'AREA', t.depe_codi, t.depe_nomb, t.estado, t.depe_codi,
       CASE WHEN t.estado = 'PADRE_AMBIGUO'
            THEN 'prefijo="'||t.prefijo||'" coincide con áreas: '||array_to_string(t.padres, ',')
            ELSE 'prefijo="'||t.prefijo||'"' END
  FROM tmp_res t
 WHERE t.estado <> 'OK';

-- ------------------------------------------------------------------------------
-- B. Catálogo de puestos para todas las áreas con usuarios activos (idempotente)
-- ------------------------------------------------------------------------------
-- Un puesto por (área, nombre normalizado). Nombre visible = variante con más
-- bytes (suele ser la acentuada); cargo_tipo = 1 si algún titular es Jefe.
INSERT INTO cargo (depe_codi, inst_codi, cargo_nombre, cargo_cabecera, cargo_tipo, usua_codi_actualiza, cargo_obs)
SELECT t.depe_codi, t.inst_codi, t.cargo_nombre, t.cargo_cabecera, t.cargo_tipo, 0, 'Carga general orgánico funcional'
FROM (
    SELECT DISTINCT ON (u.depe_codi, lower(regexp_replace(trim(u.usua_cargo), '\s+', ' ', 'g')))
           u.depe_codi, u.inst_codi,
           regexp_replace(trim(u.usua_cargo), '\s+', ' ', 'g')                      AS cargo_nombre,
           nullif(regexp_replace(trim(u.usua_cargo_cabecera), '\s+', ' ', 'g'), '') AS cargo_cabecera,
           max(u.cargo_tipo) OVER (PARTITION BY u.depe_codi, lower(regexp_replace(trim(u.usua_cargo), '\s+', ' ', 'g'))) AS cargo_tipo
    FROM usuarios u
    WHERE u.usua_esta = 1 AND u.inst_codi = 3 AND trim(coalesce(u.usua_cargo, '')) <> ''
    ORDER BY u.depe_codi, lower(regexp_replace(trim(u.usua_cargo), '\s+', ' ', 'g')),
             octet_length(u.usua_cargo) DESC, u.usua_cargo
) t
WHERE NOT EXISTS (
    SELECT 1 FROM cargo c
     WHERE c.depe_codi = t.depe_codi
       AND lower(regexp_replace(trim(c.cargo_nombre), '\s+', ' ', 'g')) = lower(t.cargo_nombre));

-- Enlace usuarios.cargo_id por coincidencia exacta normalizada
UPDATE usuarios u
   SET cargo_id = c.cargo_id
  FROM cargo c
 WHERE u.usua_esta = 1 AND u.inst_codi = 3
   AND c.depe_codi = u.depe_codi
   AND lower(regexp_replace(trim(c.cargo_nombre), '\s+', ' ', 'g')) = lower(regexp_replace(trim(u.usua_cargo), '\s+', ' ', 'g'))
   AND u.cargo_id IS DISTINCT FROM c.cargo_id;

-- Usuarios activos sin puesto: no se pueden catalogar → revisión manual
INSERT INTO organico_excluidos (tipo, codigo, nombre, motivo, depe_codi, detalle)
SELECT 'PUESTO', u.usua_codi, trim(coalesce(u.usua_nomb,'')||' '||coalesce(u.usua_apellido,'')),
       'PUESTO_VACIO', u.depe_codi, 'usuario activo sin texto de puesto (usua_cargo vacío)'
  FROM usuarios u
 WHERE u.usua_esta = 1 AND u.inst_codi = 3 AND trim(coalesce(u.usua_cargo, '')) = '';

-- ------------------------------------------------------------------------------
-- Reporte
-- ------------------------------------------------------------------------------
SELECT 'áreas migradas'            AS concepto, count(*) FROM tmp_res WHERE estado = 'OK'
UNION ALL SELECT 'áreas excluidas', count(*) FROM organico_excluidos WHERE tipo = 'AREA'
UNION ALL SELECT 'puestos en catálogo (total)', count(*) FROM cargo
UNION ALL SELECT 'usuarios UC activos con cargo_id', count(*) FROM usuarios WHERE usua_esta=1 AND inst_codi=3 AND cargo_id IS NOT NULL
UNION ALL SELECT 'usuarios UC activos SIN cargo_id', count(*) FROM usuarios WHERE usua_esta=1 AND inst_codi=3 AND cargo_id IS NULL
UNION ALL SELECT 'puestos excluidos (vacíos)', count(*) FROM organico_excluidos WHERE tipo = 'PUESTO';

SELECT motivo, count(*) FROM organico_excluidos GROUP BY motivo ORDER BY 2 DESC;

COMMIT;
