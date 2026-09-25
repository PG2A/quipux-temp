-- ==============================================================================
-- Quipux: Orgánico funcional — piloto Facultad de Ciencias Médicas (áreas)
-- ==============================================================================
-- Fase 2 de 3. Ejecutar DESPUÉS de 01_esquema_cargo.sql.
--
-- Contexto
-- --------
-- Las 35 áreas de la Facultad de Ciencias Médicas cuelgan casi todas de la raíz
-- (depe_codi_padre = 3) y llevan la jerarquía escrita en el nombre:
--     'FACULTAD DE CIENCIAS MÉDICAS - DIRECCIÓN DE ESCUELA DE MEDICINA'
-- Este script la convierte en jerarquía real:
--     depe_codi_padre = 112 (FACULTAD DE CIENCIAS MÉDICAS)
--     depe_nomb       = 'DIRECCIÓN DE ESCUELA DE MEDICINA'
--
-- El nombre completo no se pierde: usuario.depe_nomb lo recompone con
-- depe_ruta() gracias a los triggers de la fase 1.
--
-- Reglas del mapeo (revisado a mano, no es un split automático)
-- -----------------------------------------------------------
--  * Nombre corto = último segmento separado por ' - ', sin espacios extremos.
--    Se conserva la grafía actual (acentos incluidos) para no alterar nada más.
--  * Padre por defecto = 112. Las tres áreas que ya colgaban de 176 (CENTRO DE
--    POSGRADOS) se mantienen ahí.
--  * ÚNICA DECISIÓN PROPIA: los programas de posgrado que colgaban de la raíz
--    (174, 178, 179, 348, 349) pasan bajo 176, igual que 402 POSGRADO DE
--    IMAGENOLOGÍA, que ya estaba ahí. Si no se quiere, cambiar 176 por 112 en
--    esas cinco filas antes de ejecutar.
--
-- Respaldo: las filas originales quedan en organico_respaldo_dependencia.
-- ==============================================================================

SET client_encoding TO 'UTF8';

BEGIN;

CREATE TABLE IF NOT EXISTS organico_respaldo_dependencia (
    LIKE dependencia,
    respaldo_fecha timestamptz NOT NULL DEFAULT now(),
    respaldo_lote  varchar(50)
);

CREATE TEMP TABLE tmp_fcm_areas (depe_codi integer PRIMARY KEY, nuevo_padre integer NOT NULL, nombre_corto varchar(150) NOT NULL) ON COMMIT DROP;

INSERT INTO tmp_fcm_areas (depe_codi, nuevo_padre, nombre_corto) VALUES
  (140, 112, 'VICEDECANATO'),
  (175, 112, 'UNIDAD JURÍDICA'),
  (176, 112, 'CENTRO DE POSGRADOS'),
  (177, 112, 'DIRECCIÓN DE ESCUELA DE MEDICINA'),
  (180, 112, 'PROGRAMA DE INTERNADO'),
  (181, 112, 'CENTRO DE DIAGNÓSTICO'),
  (182, 112, 'DIRECCIÓN DE ESCUELA DE ENFERMERÍA'),
  (183, 112, 'DIRECCIÓN DE ESCUELA DE TECNOLOGÍA MÉDICA'),
  (184, 112, 'PROYECTO CERCA'),
  (185, 112, 'INTERNADO DE LA ESCUELA DE ENFERMERÍA'),
  (186, 112, 'COMISIÓN DE ASESORIA EN TRABAJOS DE INVESTIGACION DE MEDICINA'),
  (187, 112, 'COORDINACIÓN DE REACT'),
  (188, 112, 'CEDIUC'),
  (189, 112, 'LABORATORIO DE CÓMPUTO DE MEDICINA'),
  (190, 112, 'VINCULACIÓN CON LA COLECTIVIDAD'),
  (309, 112, 'COMISIÓN DE PUBLICACIONES'),
  (318, 112, 'INTERNADO'),
  (379, 112, 'DOCENTE FISCAL'),
  (418, 112, 'CURSO DE AUXILIARES DE ENFERMERÍA'),
  (602, 112, 'DIRECCION DE CARRERA DE FISIOTERAPIA'),
  (603, 112, 'DIRECCION DE CARRERA DE FONOAUDIOLOGIA'),
  (604, 112, 'DIRECCION DE CARRERA DE IMAGENOLOGIA'),
  (605, 112, 'DIRECCION DE CARRERA DE LABORATORIO CLINICO'),
  (606, 112, 'DIRECCIÓN DE CARRERA DE ESTIMULACIÓN TEMPRANA EN SALUD'),
  (607, 112, 'DIRECCION DE CARRERA DE NUTRICIÓN Y DIETÉTICA'),
  (628, 112, 'COMISIÓN DE TRABAJOS DE TITULACIÓN'),
  (635, 112, 'PERSONAL DOCENTE'),
  -- Ya colgaban de 176 CENTRO DE POSGRADOS: solo se acorta el nombre
  (314, 176, 'PROGRAMA ESPECIAL PARA TITULACIÓN DE EGRESADOS'),
  (402, 176, 'POSGRADO DE IMAGENOLOGÍA'),
  (416, 176, 'DIRECCION PROGRAMA DE POSGRADO DE CIRUGIA'),
  -- Decisión propia: programas de posgrado bajo CENTRO DE POSGRADOS (ver cabecera)
  (174, 176, 'ESPECIALIDAD DE MEDICINA INTERNA'),
  (178, 176, 'ESPECIALIDAD DE IMAGENOLOGÍA'),
  (179, 176, 'MAESTRÍA EN INVESTIGACION DE LA SALUD'),
  (348, 176, 'POSGRADO DE ANESTESIOLOGÍA'),
  (349, 176, 'POSGRADO DE GINECOLOGÍA Y OBSTETRICIA');

-- Guardas: todas las áreas del mapeo existen, son de la UC, y el padre existe
DO $$
DECLARE
    n integer;
BEGIN
    SELECT count(*) INTO n FROM tmp_fcm_areas t LEFT JOIN dependencia d ON d.depe_codi = t.depe_codi
     WHERE d.depe_codi IS NULL OR d.inst_codi <> 3;
    IF n > 0 THEN RAISE EXCEPTION 'Hay % áreas del mapeo que no existen o no son de la UC', n; END IF;

    SELECT count(*) INTO n FROM tmp_fcm_areas t LEFT JOIN dependencia p ON p.depe_codi = t.nuevo_padre
     WHERE p.depe_codi IS NULL;
    IF n > 0 THEN RAISE EXCEPTION 'Hay % padres del mapeo que no existen', n; END IF;

    -- Idempotencia: si ya se aplicó (ningún nombre lleva el prefijo), no hacer nada
    SELECT count(*) INTO n FROM tmp_fcm_areas t JOIN dependencia d ON d.depe_codi = t.depe_codi
     WHERE d.depe_nomb LIKE '% - %';
    IF n = 0 THEN
        RAISE NOTICE 'La jerarquía de la FCM ya estaba aplicada; no se modifica nada.';
        DELETE FROM tmp_fcm_areas;
    END IF;
END $$;

INSERT INTO organico_respaldo_dependencia
SELECT d.*, now(), 'fcm_jerarquia'
  FROM dependencia d JOIN tmp_fcm_areas t ON t.depe_codi = d.depe_codi;

-- El trigger AFTER UPDATE de dependencia recompone usuario.depe_nomb con depe_ruta()
-- para los usuarios de cada área y de sus descendientes.
UPDATE dependencia d
   SET depe_codi_padre = t.nuevo_padre
     , depe_nomb       = t.nombre_corto
  FROM tmp_fcm_areas t
 WHERE d.depe_codi = t.depe_codi;

-- Reporte
SELECT d.depe_codi, d.depe_codi_padre AS padre, d.depe_nomb AS nombre_corto, depe_ruta(d.depe_codi) AS ruta
  FROM dependencia d
 WHERE d.depe_codi IN (SELECT depe_descendientes(112))
 ORDER BY depe_ruta(d.depe_codi);

COMMIT;
