-- ==============================================================================
-- Quipux: Orgánico funcional — piloto Facultad de Ciencias Médicas (puestos)
-- ==============================================================================
-- Fase 3 de 3. Ejecutar DESPUÉS de 02_fcm_jerarquia.sql.
--
-- Qué hace
-- --------
--  A. Mueve a la hoja correcta a los usuarios cuyo puesto lo dice sin ambigüedad
--     pero que estaban registrados en el padre (Facultad o Centro de Posgrados).
--     Lista explícita y revisable más abajo. Quien no está en la lista se queda
--     donde está: el catálogo no obliga a mover a nadie.
--  B. Numeración: un área que nunca ha numerado documentos (sin filas en
--     formato_numeracion) y que recibe usuarios, se configura para seguir
--     numerando con el área de la que vienen (depe_numeracion). Las áreas que
--     ya tienen configuración propia no se tocan: el usuario que llega adopta la
--     numeración del área, como cualquier usuario asignado allí.
--  C. Carga el catálogo 'cargo' con los puestos distintos de cada área de la
--     FCM (un puesto por área, comparando sin mayúsculas ni espacios dobles) y
--     enlaza usuarios.cargo_id. El texto de usuarios.usua_cargo NO se modifica:
--     es lo que sale en el pie de firma de los documentos.
--
-- Respaldo: las filas originales de usuarios quedan en organico_respaldo_usuarios.
-- ==============================================================================

SET client_encoding TO 'UTF8';

BEGIN;

CREATE TABLE IF NOT EXISTS organico_respaldo_usuarios (
    LIKE usuarios,
    respaldo_fecha timestamptz NOT NULL DEFAULT now(),
    respaldo_lote  varchar(50)
);

-- Áreas de la familia FCM (padre + descendientes), tras la fase 2
CREATE TEMP TABLE tmp_fcm_depes ON COMMIT DROP AS
SELECT depe_descendientes(112) AS depe_codi;

INSERT INTO organico_respaldo_usuarios
SELECT u.*, now(), 'fcm_cargos'
  FROM usuarios u
 WHERE u.usua_esta = 1 AND u.depe_codi IN (SELECT depe_codi FROM tmp_fcm_depes)
   AND NOT EXISTS (SELECT 1 FROM organico_respaldo_usuarios r WHERE r.usua_codi = u.usua_codi AND r.respaldo_lote = 'fcm_cargos');

-- ------------------------------------------------------------------------------
-- A. Usuarios que pasan del padre a la hoja que nombra su puesto
-- ------------------------------------------------------------------------------
CREATE TEMP TABLE tmp_mov (usua_codi integer PRIMARY KEY, depe_destino integer NOT NULL, motivo varchar(120)) ON COMMIT DROP;

INSERT INTO tmp_mov (usua_codi, depe_destino, motivo) VALUES
  -- desde 112 FACULTAD DE CIENCIAS MÉDICAS
  (35801, 635, 'Docente -> PERSONAL DOCENTE'),
  (37811, 635, 'Docente -> PERSONAL DOCENTE'),
  (36126, 635, 'Docente Investigadora -> PERSONAL DOCENTE'),
  (33865, 635, 'Docente Ocasional TC -> PERSONAL DOCENTE'),
  (26367, 628, 'Presidenta Comisión de Trabajos de Titulación -> COMISIÓN DE TRABAJOS DE TITULACIÓN'),
  (38426, 177, 'Asistente Ejecutivo 1 de la carrera de Medicina -> DIRECCIÓN DE ESCUELA DE MEDICINA'),
  -- desde 176 CENTRO DE POSGRADOS
  (34192, 416, 'Director Especialización Cirugía General -> DIRECCION PROGRAMA DE POSGRADO DE CIRUGIA'),
  (33044, 402, 'Directora Especialización Imagenología -> POSGRADO DE IMAGENOLOGÍA'),
  (31250, 348, 'Directora Especialización Anestesiología -> POSGRADO DE ANESTESIOLOGÍA'),
  (28033, 349, 'Directora Especialización Ginecología y Obstetricia -> POSGRADO DE GINECOLOGÍA Y OBSTETRICIA'),
  (31943, 174, 'Directora Especialización Medicina Interna -> ESPECIALIDAD DE MEDICINA INTERNA'),
  (33038, 179, 'Directora Maestría Investigación en Ciencias de la Salud -> MAESTRÍA EN INVESTIGACION DE LA SALUD');

-- Guardas
DO $$
DECLARE
    n integer;
BEGIN
    -- Todos existen, están activos y su área actual es de la familia FCM
    SELECT count(*) INTO n FROM tmp_mov m LEFT JOIN usuarios u ON u.usua_codi = m.usua_codi
     WHERE u.usua_codi IS NULL OR u.usua_esta <> 1 OR u.depe_codi NOT IN (SELECT depe_codi FROM tmp_fcm_depes);
    IF n > 0 THEN RAISE EXCEPTION '% usuarios del movimiento no existen, no están activos o no son de la FCM', n; END IF;

    -- El destino es de la familia FCM
    SELECT count(*) INTO n FROM tmp_mov m WHERE m.depe_destino NOT IN (SELECT depe_codi FROM tmp_fcm_depes);
    IF n > 0 THEN RAISE EXCEPTION '% destinos no pertenecen a la FCM', n; END IF;

    -- Nadie con perfil Jefe se mueve a un área que ya tiene Jefe
    SELECT count(*) INTO n
      FROM tmp_mov m JOIN usuarios u ON u.usua_codi = m.usua_codi
     WHERE u.cargo_tipo = 1
       AND EXISTS (SELECT 1 FROM usuarios j WHERE j.depe_codi = m.depe_destino AND j.cargo_tipo = 1 AND j.usua_esta = 1 AND j.usua_codi <> u.usua_codi);
    IF n > 0 THEN RAISE EXCEPTION '% movimientos dejarían dos Jefes en la misma área', n; END IF;

    -- Idempotencia: quitar los que ya están en destino
    DELETE FROM tmp_mov m USING usuarios u WHERE u.usua_codi = m.usua_codi AND u.depe_codi = m.depe_destino;
END $$;

-- ------------------------------------------------------------------------------
-- B. Numeración para las áreas que reciben usuarios y nunca han numerado
-- ------------------------------------------------------------------------------
-- Se toma la configuración del área de origen (tipos, formato, abreviatura) y se
-- apunta depe_numeracion al origen, de modo que el documento sale con la sigla y
-- el contador de siempre. Solo si el destino no tiene NINGUNA fila.
INSERT INTO formato_numeracion (fn_abr_texto, fn_formato, depe_codi, fn_caracter, fn_num_consec, fn_num_anio, depe_numeracion, fn_contador, fn_tiporad)
SELECT DISTINCT f.fn_abr_texto, f.fn_formato, m.depe_destino, f.fn_caracter, f.fn_num_consec, f.fn_num_anio
     , coalesce(f.depe_numeracion, u.depe_codi), 0, f.fn_tiporad
  FROM tmp_mov m
  JOIN usuarios u ON u.usua_codi = m.usua_codi
  JOIN formato_numeracion f ON f.depe_codi = u.depe_codi
 WHERE NOT EXISTS (SELECT 1 FROM formato_numeracion x WHERE x.depe_codi = m.depe_destino)
ON CONFLICT (depe_codi, fn_tiporad) DO NOTHING;

-- ------------------------------------------------------------------------------
-- A (ejecución). El trigger de usuarios refresca la tabla 'usuario'.
-- ------------------------------------------------------------------------------
UPDATE usuarios u
   SET depe_codi            = m.depe_destino
     , usua_codi_actualiza  = 0
     , usua_fecha_actualiza = now()
     , usua_obs_actualiza   = 'Orgánico funcional FCM: ' || m.motivo
  FROM tmp_mov m
 WHERE u.usua_codi = m.usua_codi;

-- ------------------------------------------------------------------------------
-- C. Catálogo de puestos de la FCM y enlace usuarios.cargo_id
-- ------------------------------------------------------------------------------
-- Un puesto por (área, nombre normalizado). Como nombre visible se toma la
-- variante con acentos si la hay (mayor longitud en bytes) y, a igualdad, la
-- primera alfabéticamente. cargo_tipo = 1 si algún titular del puesto es Jefe.
CREATE TEMP TABLE tmp_cargos ON COMMIT DROP AS
SELECT DISTINCT ON (u.depe_codi, lower(regexp_replace(trim(u.usua_cargo), '\s+', ' ', 'g')))
       u.depe_codi
     , u.inst_codi
     , regexp_replace(trim(u.usua_cargo), '\s+', ' ', 'g')                        AS cargo_nombre
     , nullif(regexp_replace(trim(u.usua_cargo_cabecera), '\s+', ' ', 'g'), '')   AS cargo_cabecera
     , max(u.cargo_tipo) OVER (PARTITION BY u.depe_codi, lower(regexp_replace(trim(u.usua_cargo), '\s+', ' ', 'g'))) AS cargo_tipo
  FROM usuarios u
 WHERE u.usua_esta = 1
   AND u.inst_codi = 3
   AND u.depe_codi IN (SELECT depe_codi FROM tmp_fcm_depes)
   AND trim(coalesce(u.usua_cargo, '')) <> ''
 ORDER BY u.depe_codi, lower(regexp_replace(trim(u.usua_cargo), '\s+', ' ', 'g'))
        , octet_length(u.usua_cargo) DESC, u.usua_cargo;

INSERT INTO cargo (depe_codi, inst_codi, cargo_nombre, cargo_cabecera, cargo_tipo, usua_codi_actualiza, cargo_obs)
SELECT t.depe_codi, t.inst_codi, t.cargo_nombre, t.cargo_cabecera, t.cargo_tipo, 0, 'Carga inicial piloto FCM'
  FROM tmp_cargos t
 WHERE NOT EXISTS (
        SELECT 1 FROM cargo c
         WHERE c.depe_codi = t.depe_codi
           AND lower(regexp_replace(trim(c.cargo_nombre), '\s+', ' ', 'g')) = lower(t.cargo_nombre));

UPDATE usuarios u
   SET cargo_id = c.cargo_id
  FROM cargo c
 WHERE u.usua_esta = 1
   AND u.depe_codi IN (SELECT depe_codi FROM tmp_fcm_depes)
   AND c.depe_codi = u.depe_codi
   AND lower(regexp_replace(trim(c.cargo_nombre), '\s+', ' ', 'g')) = lower(regexp_replace(trim(u.usua_cargo), '\s+', ' ', 'g'))
   AND u.cargo_id IS DISTINCT FROM c.cargo_id;

-- ------------------------------------------------------------------------------
-- Reporte
-- ------------------------------------------------------------------------------
SELECT 'usuarios movidos' AS concepto, count(*) FROM usuarios u WHERE u.usua_obs_actualiza LIKE 'Orgánico funcional FCM:%'
UNION ALL SELECT 'puestos en catálogo (FCM)', count(*) FROM cargo WHERE depe_codi IN (SELECT depe_codi FROM tmp_fcm_depes)
UNION ALL SELECT 'usuarios FCM activos con cargo_id', count(*) FROM usuarios WHERE usua_esta = 1 AND cargo_id IS NOT NULL AND depe_codi IN (SELECT depe_codi FROM tmp_fcm_depes)
UNION ALL SELECT 'usuarios FCM activos SIN cargo_id', count(*) FROM usuarios WHERE usua_esta = 1 AND cargo_id IS NULL AND depe_codi IN (SELECT depe_codi FROM tmp_fcm_depes)
UNION ALL SELECT 'filas de numeración añadidas', count(*) FROM formato_numeracion f WHERE f.depe_codi IN (SELECT depe_destino FROM tmp_mov) AND f.depe_numeracion <> f.depe_codi;

COMMIT;
