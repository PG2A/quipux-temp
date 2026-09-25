-- ==============================================================================
-- Quipux: Migración de las subrogaciones basadas en cuenta clonada
-- ==============================================================================
-- Fase 2 de 3. Ejecutar DESPUÉS de 01_esquema_subrogacion.sql y ANTES de poner
-- en producción el código nuevo.
--
-- Contexto
-- --------
-- El modelo anterior creaba, por cada subrogación, una fila adicional en
-- 'usuarios' que duplicaba login, cédula y contraseña de la persona real pero
-- con el área, el puesto y los permisos del jefe. usuarios_subrogacion
-- .usua_subrogante apuntaba a ESA cuenta clonada, no a la persona.
--
-- El modelo nuevo no admite clones: usua_subrogante debe apuntar siempre a la
-- cuenta real. Este script reasigna las subrogaciones vigentes a la cuenta real,
-- traslada lo que el clon tenía en su bandeja y desactiva el clon.
--
-- IMPORTANTE: tomar respaldo antes de ejecutar. El bloque de diagnóstico de más
-- abajo permite revisar qué se va a migrar sin modificar nada.
-- ==============================================================================


-- ------------------------------------------------------------------------------
-- DIAGNÓSTICO (no modifica nada) — ejecutar primero y revisar el resultado
-- ------------------------------------------------------------------------------
-- Un clon se reconoce porque usuarios.usua_subrogado apunta al puesto que
-- estaba cubriendo; las cuentas normales no tienen ese campo.
--
--   select s.usua_subrogacion_codi
--        , clon.usua_codi   as clon_codi,  clon.usua_login  as clon_login
--        , real.usua_codi   as real_codi,  real.usua_login  as real_login
--        , s.usua_subrogado as titular_codi
--        , (select count(1) from radicado where radi_usua_actu = clon.usua_codi
--                                          and esta_codi in (1,2)) as docs_pendientes
--     from usuarios_subrogacion s
--     join usuarios clon on clon.usua_codi = s.usua_subrogante
--                       and coalesce(clon.usua_subrogado,0) > 0
--     left join usuarios real on real.usua_cedula = clon.usua_cedula
--                            and real.usua_codi  <> clon.usua_codi
--                            and coalesce(real.usua_subrogado,0) = 0
--                            and real.usua_esta = 1
--    where s.estado = 1;
--
-- Si alguna fila sale con real_codi NULL, esa subrogación NO se puede migrar
-- automáticamente (no se halló la cuenta real por cédula) y debe resolverse a
-- mano antes de continuar.
-- ------------------------------------------------------------------------------


-- ------------------------------------------------------------------------------
-- Resolución manual de casos ambiguos
-- ------------------------------------------------------------------------------
-- Una misma persona puede tener legítimamente varias cuentas reales (distinto
-- puesto y área), que es justo el caso para el que existe el combo "Usuario:".
-- Cuando eso ocurre, la cédula no basta para saber a cuál cuenta debe quedar
-- atada la subrogación, y el script se detiene.
--
-- Para resolverlo, registre aquí la cuenta correcta ANTES de ejecutar:
--
--   INSERT INTO subrogacion_migracion_override (usua_subrogacion_codi, real_codi)
--   VALUES (70, 33002)
--   ON CONFLICT (usua_subrogacion_codi) DO UPDATE SET real_codi = EXCLUDED.real_codi;

CREATE TABLE IF NOT EXISTS subrogacion_migracion_override (
    usua_subrogacion_codi integer PRIMARY KEY,
    real_codi             integer NOT NULL
);


-- Los literales de este archivo llevan acentos y estan guardados en UTF-8. En Windows
-- psql asume WIN1252 segun la consola, y sin esto falla al leerlos.
SET client_encoding TO 'UTF8';

BEGIN;

-- Tabla temporal con el mapeo clon -> persona real, para no repetir el JOIN.
-- El override manda sobre la resolución automática por cédula.
CREATE TEMP TABLE tmp_migracion_subrogacion ON COMMIT DROP AS
SELECT s.usua_subrogacion_codi
     , s.usua_subrogado                        AS titular_codi
     , clon.usua_codi                          AS clon_codi
     , COALESCE(ov.real_codi, realu.usua_codi) AS real_codi
  FROM usuarios_subrogacion s
  JOIN usuarios clon  ON clon.usua_codi = s.usua_subrogante
                     AND COALESCE(clon.usua_subrogado, 0) > 0
  LEFT JOIN subrogacion_migracion_override ov
                     ON ov.usua_subrogacion_codi = s.usua_subrogacion_codi
  LEFT JOIN usuarios realu ON ov.real_codi IS NULL
                     AND realu.usua_cedula = clon.usua_cedula
                     AND realu.usua_codi  <> clon.usua_codi
                     AND COALESCE(realu.usua_subrogado, 0) = 0
                     AND realu.usua_esta = 1
 WHERE s.estado = 1
   AND COALESCE(ov.real_codi, realu.usua_codi) IS NOT NULL;

-- Aborta si una misma cédula resolviera a más de una cuenta real: en ese caso el
-- mapeo sería ambiguo y hay que decidirlo manualmente.
DO $$
DECLARE
    v_ambiguos integer;
BEGIN
    SELECT count(*) INTO v_ambiguos
      FROM (SELECT usua_subrogacion_codi
              FROM tmp_migracion_subrogacion
             GROUP BY usua_subrogacion_codi
            HAVING count(*) > 1) x;

    IF v_ambiguos > 0 THEN
        RAISE EXCEPTION 'Hay % subrogación(es) con más de una cuenta real candidata. Regístrelas en subrogacion_migracion_override y vuelva a ejecutar.', v_ambiguos;
    END IF;
END $$;


-- 1. Sellar lo que el clon tiene en su bandeja como documentos de la subrogación.
--    Sin este sello, la finalización no sabría cuáles devolver al titular.
INSERT INTO radicado_subrogacion (radi_nume_radi, usua_subrogacion_codi, tipo)
SELECT r.radi_nume_radi, m.usua_subrogacion_codi, 'T'
  FROM tmp_migracion_subrogacion m
  JOIN radicado r ON r.radi_usua_actu = m.clon_codi
 WHERE NOT EXISTS (SELECT 1 FROM radicado_subrogacion rs
                    WHERE rs.radi_nume_radi = r.radi_nume_radi
                      AND rs.usua_subrogacion_codi = m.usua_subrogacion_codi
                      AND rs.tipo = 'T');

-- 2. Trasladar los documentos del clon a la cuenta real.
UPDATE radicado r
   SET radi_usua_actu = m.real_codi
  FROM tmp_migracion_subrogacion m
 WHERE r.radi_usua_actu = m.clon_codi;

-- 3. Trasladar las tareas del clon a la cuenta real.
UPDATE tarea t
   SET usua_codi_dest = m.real_codi
  FROM tmp_migracion_subrogacion m
 WHERE t.usua_codi_dest = m.clon_codi;

UPDATE tarea t
   SET usua_codi_ori = m.real_codi
  FROM tmp_migracion_subrogacion m
 WHERE t.usua_codi_ori = m.clon_codi;

-- 4. Reapuntar la subrogación a la persona real.
UPDATE usuarios_subrogacion s
   SET usua_subrogante = m.real_codi
  FROM tmp_migracion_subrogacion m
 WHERE s.usua_subrogacion_codi = m.usua_subrogacion_codi;

-- 5. Devolver la visibilidad al titular. El modelo anterior lo ocultaba
--    (visible_sub = 0) porque el clon ocupaba su lugar; ahora el titular sigue
--    siendo un usuario normal y debe poder recibir documentos.
UPDATE usuarios u
   SET visible_sub = 1
     , cargo_tipo  = 1
  FROM tmp_migracion_subrogacion m
 WHERE u.usua_codi = m.titular_codi;

-- 6. Retirar del clon lo que le daba acceso.
DELETE FROM bandeja_compartida bc
 USING tmp_migracion_subrogacion m
 WHERE bc.usua_codi = m.clon_codi;

DELETE FROM permiso_usuario pu
 USING tmp_migracion_subrogacion m
 WHERE pu.usua_codi = m.clon_codi;

DELETE FROM usuarios_sesion us
 USING tmp_migracion_subrogacion m
 WHERE us.usua_codi = m.clon_codi;

-- 7. Desactivar el clon definitivamente, liberando login y cédula para que no
--    colisionen con la cuenta real (misma operación que hacía la desactivación
--    del modelo anterior).
UPDATE usuarios u
   SET usua_esta   = 0
     , visible_sub = 0
     , cargo_tipo  = 0
     , usua_login  = 'l' || u.usua_codi
     , usua_cedula = u.usua_cedula || '-' || u.usua_codi
     , usua_obs_actualiza = 'Cuenta clon retirada por la migración del módulo de subrogación'
     , usua_fecha_actualiza = now()
  FROM tmp_migracion_subrogacion m
 WHERE u.usua_codi = m.clon_codi;

-- 8. Forzar que el subrogante vuelva a autenticarse, para que el puesto aparezca
--    en el combo "Usuario:" bajo el modelo nuevo.
DELETE FROM usuarios_sesion us
 USING tmp_migracion_subrogacion m
 WHERE us.usua_codi = m.real_codi;

COMMIT;


-- ------------------------------------------------------------------------------
-- VERIFICACIÓN posterior
-- ------------------------------------------------------------------------------
-- No debe quedar ninguna subrogación activa apuntando a una cuenta clon:
--
--   select count(1) as clones_restantes
--     from usuarios_subrogacion s
--     join usuarios u on u.usua_codi = s.usua_subrogante
--    where s.estado = 1 and coalesce(u.usua_subrogado,0) > 0;
--   -- esperado: 0
-- ------------------------------------------------------------------------------
