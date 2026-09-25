-- ==============================================================================
-- Quipux: DATOS DE PRUEBA - niveles de puestos para practicar las reglas de
-- reasignación de periodo jerárquico (02_reglas_jerarquicas.sql)
-- ==============================================================================
-- Sólo para la base local. Idempotente. Para deshacer: ver el bloque REVERTIR al final.
--
-- Escenario (Facultad de Ciencias Médicas, depe 112; 318 y 177 son sub áreas hermanas):
--
--   Unidad                         Nivel  Puesto (cargo_id)                         Usuario
--   318 INTERNADO                    1    Directora del Programa de Internado (132) 37645
--                                    2    Analista de Gestión (129)                 34777
--                                    3    Asistente de Gestión de Facultad (131)    36918
--                                    4    Asistente de Gestión (130)                38917
--   177 DIR. ESCUELA DE MEDICINA     1    Director de la Carrera de Medicina (49)   38773
--                                    2    Analista de Gestión (47)                  34774, 22220
--                                    3    Asistente Ejecutivo 1 ... (48)            38426
--   112 FACULTAD (área)              1    Decana (13)                               31700
--                                    2    Analista de Facultad (4)                  25927
--   309 COMISIÓN DE PUBLICACIONES    1    Director de Publicaciones (127)           32644
--   (el resto de puestos sigue sin nivel)
-- ==============================================================================

SET client_encoding TO 'UTF8';

BEGIN;

UPDATE cargo c SET cargo_nivel = v.nivel
  FROM (VALUES (132, 1), (129, 2), (131, 3), (130, 4),
               (49, 1),  (47, 2),  (48, 3),
               (13, 1),  (4, 2),
               (127, 1)) AS v(cargo_id, nivel)
 WHERE c.cargo_id = v.cargo_id;

-- Igual que grabar_usuario.php: el nivel del puesto se copia al usuario.
UPDATE usuarios u SET nivel_jerarquico = c.cargo_nivel
  FROM cargo c
 WHERE c.cargo_id = u.cargo_id AND c.cargo_id IN (132, 129, 131, 130, 49, 47, 48, 13, 4, 127);

COMMIT;

-- ------------------------------------------------------------------------------
-- REVERTIR (ejecutar a mano):
--   UPDATE cargo    SET cargo_nivel = NULL      WHERE cargo_id IN (132,129,131,130,49,47,48,13,4,127);
--   UPDATE usuarios SET nivel_jerarquico = NULL WHERE cargo_id IN (132,129,131,130,49,47,48,13,4,127);
--
-- Contraseñas de prueba: a estas cuentas se les puso md5('Quipux123') (2026-09-24),
-- con respaldo previo en la tabla prueba_respaldo_pasw. Para restaurarlas:
--   UPDATE usuarios u SET usua_pasw = r.usua_pasw FROM prueba_respaldo_pasw r WHERE r.usua_codi = u.usua_codi;
--   DROP TABLE prueba_respaldo_pasw;
-- ------------------------------------------------------------------------------

-- ==============================================================================
-- Segundo escenario: DIRECCIÓN DE GESTIÓN DEL TALENTO HUMANO (depe 12) y sus
-- sub áreas 313 SEGURIDAD Y SALUD, 371 REMUNERACIONES, 372 SELECCIÓN.
--
--   Unidad                    Nivel  Puesto (cargo_id)                               Usuario de prueba
--   12  DIRECCIÓN (área)        1    Directora de Gestión del Talento Humano (287)   31173
--                               2    Asesora Jurídica (283)                          31373
--   313 SEGURIDAD Y SALUD       1    Coordinador de Seguridad y Salud Ocup. (868)    31631
--                               2    Médica Ocupacional (870)                        20072
--                               3    Asistente ejecutiva 1 (867)                     38307
--                               4    Ingeniero Industrial (869)                      38306
--   371 REMUNERACIONES          1    Especialista de Nómina (958)                    20085
--                               2    Analista de Nómina (957, 5 titulares)           36031
--   372 SELECCIÓN               1    Especialista de Talento Humano (961)            35606
--                               2    Analista de Talento Humano (959, 3 titulares)   32943
--                               3    Asistente Ejecutiva 1 (960)                     35821
-- ==============================================================================
BEGIN;
UPDATE cargo c SET cargo_nivel = v.nivel
  FROM (VALUES (287, 1), (283, 2),
               (868, 1), (870, 2), (867, 3), (869, 4),
               (958, 1), (957, 2),
               (961, 1), (959, 2), (960, 3)) AS v(cargo_id, nivel)
 WHERE c.cargo_id = v.cargo_id;
UPDATE usuarios u SET nivel_jerarquico = c.cargo_nivel
  FROM cargo c
 WHERE c.cargo_id = u.cargo_id AND c.cargo_id IN (287, 283, 868, 870, 867, 869, 958, 957, 961, 959, 960);
COMMIT;
-- REVERTIR segundo escenario:
--   UPDATE cargo    SET cargo_nivel = NULL      WHERE cargo_id IN (287,283,868,870,867,869,958,957,961,959,960);
--   UPDATE usuarios SET nivel_jerarquico = NULL WHERE cargo_id IN (287,283,868,870,867,869,958,957,961,959,960);
