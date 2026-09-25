-- ==============================================================================
-- Quipux: Administración de Sumillas — Catálogo base
-- ==============================================================================
-- Este script es IDEMPOTENTE: puede ejecutarse varias veces sin duplicar nada.
--
-- Carga el juego de sumillas que hasta ahora venía fijo en la base (las 26
-- operaciones que ofrece la pantalla de reasignación) para las instituciones
-- que todavía no las tienen. Una institución nueva arrancaba con la tabla
-- 'accion' vacía y, como el combo sólo se dibuja cuando hay más de una sumilla,
-- se quedaba sin operaciones al reasignar.
--
-- La comparación se hace sin tildes ni mayúsculas, de modo que una sumilla ya
-- cargada a mano ("TRAMITE LEGAL") no se duplica como "Trámite Legal".
--
-- Requiere 01_esquema_sumillas.sql (columna accion_activo).
--
-- Uso:
--   psql ... -f 02_datos_sumillas.sql                          -- todas las instituciones activas
--   psql ... -v instituciones=3 -f 02_datos_sumillas.sql       -- sólo la institución 3
--   psql ... -v instituciones=3,5 -f 02_datos_sumillas.sql     -- varias
-- ==============================================================================

-- Los literales de este archivo llevan acentos y estan guardados en UTF-8. En Windows
-- psql asume WIN1252 segun la consola, y sin esto falla al leerlos.
SET client_encoding TO 'UTF8';

\if :{?instituciones}
\else
    \set instituciones 'all'
\endif

BEGIN;

-- El filtro se resuelve fuera del bloque DO porque psql no sustituye variables
-- dentro de una cadena con comillas de dólar.
CREATE TEMP TABLE tmp_inst_sumillas ON COMMIT DROP AS
SELECT inst_codi
  FROM institucion
 WHERE inst_estado = 1
   -- inst_codi = 0 es la fila marcador del super-administrador: no tiene usuarios
   -- ni bandejas, así que se excluye de la carga masiva. Nombrándola de forma
   -- explícita (-v instituciones=0) sí se le cargan las sumillas.
   AND ( ( :'instituciones' = 'all' AND inst_codi > 0 )
         OR inst_codi::text = ANY(string_to_array(:'instituciones', ',')) );

DO $$
DECLARE
    -- Mismo orden y misma redacción con que ya estaban cargadas.
    v_sumillas text[] := ARRAY[
        'Acusar Recibo',
        'Analizar',
        'Archivo',
        'Atender lo Solicitado',
        'Autorizado',
        'Cancelar',
        'Comunicar',
        'Conocimiento',
        'Controlar',
        'Coordinar',
        'Convocar a Sesión',
        'Cumplimiento',
        'Dar Lectura',
        'Estudio',
        'Evaluar',
        'Inf. Escritorio con Crit. y Recom.',
        'Informe Verbal',
        'Investigar',
        'Negativo',
        'Preparar Respuesta',
        'Registrar',
        'Trámite Legal',
        'Trámite Reglamentario',
        'Urgente',
        'Verificar',
        'Devolver el Asunto'
    ];
    v_inst   record;
    v_nombre text;
    v_codi   integer;
    v_nuevas integer := 0;
BEGIN
    FOR v_inst IN SELECT inst_codi FROM tmp_inst_sumillas ORDER BY inst_codi LOOP
        FOREACH v_nombre IN ARRAY v_sumillas LOOP

            IF NOT EXISTS (
                SELECT 1
                  FROM accion
                 WHERE inst_codi = v_inst.inst_codi
                   AND translate(upper(accion_nombre),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                     = translate(upper(v_nombre),     'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
            ) THEN
                -- 'accion' no tiene secuencia y su llave primaria es única entre
                -- todas las instituciones, así que el código sale del máximo global.
                SELECT coalesce(max(accion_codi), 0) + 1 INTO v_codi FROM accion;

                INSERT INTO accion (accion_codi, accion_nombre, inst_codi, accion_activo)
                VALUES (v_codi, v_nombre, v_inst.inst_codi, 1);

                v_nuevas := v_nuevas + 1;
            END IF;

        END LOOP;
    END LOOP;

    RAISE NOTICE 'Sumillas insertadas: %', v_nuevas;
END $$;

COMMIT;

-- Verificación
SELECT i.inst_codi,
       left(i.inst_nombre, 40)                     AS institucion,
       count(a.accion_codi)                        AS total,
       count(*) FILTER (WHERE a.accion_activo = 1) AS activas
  FROM institucion i
  LEFT JOIN accion a ON a.inst_codi = i.inst_codi
 WHERE i.inst_estado = 1
 GROUP BY i.inst_codi, i.inst_nombre
 ORDER BY i.inst_codi;
