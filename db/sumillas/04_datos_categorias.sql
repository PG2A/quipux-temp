-- ==============================================================================
-- Quipux: Administración de Sumillas — Catálogo de motivos
-- ==============================================================================
-- Este script es IDEMPOTENTE: puede ejecutarse varias veces sin duplicar nada.
--
-- Crea los motivos por los que se agrupan las sumillas en el árbol de
-- reasignación y clasifica bajo ellos el catálogo base.
--
-- IMPORTANTE: esta clasificación es una propuesta de arranque, hecha para que el
-- árbol no nazca vacío. No sale de ninguna norma: revísela con el área que define
-- los motivos y ajústela desde Administración -> Administración de Sumillas, que
-- permite crear motivos y mover sumillas sin tocar la base.
--
-- Sólo clasifica sumillas que aún no tienen motivo (cate_codi IS NULL), así que
-- nunca pisa una reclasificación hecha a mano.
--
-- Requiere 01_esquema_sumillas.sql y 03_categorias_sumillas.sql.
--
-- Uso:
--   psql ... -f 04_datos_categorias.sql                       -- todas las instituciones activas
--   psql ... -v instituciones=3 -f 04_datos_categorias.sql    -- sólo la institución 3
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
CREATE TEMP TABLE tmp_inst_categorias ON COMMIT DROP AS
SELECT inst_codi
  FROM institucion
 WHERE inst_estado = 1
   AND ( ( :'instituciones' = 'all' AND inst_codi > 0 )
         OR inst_codi::text = ANY(string_to_array(:'instituciones', ',')) );

DO $$
DECLARE
    -- Motivos, en el orden en que se quieren ver en el árbol.
    v_motivos text[] := ARRAY[
        'Trámite y gestión',
        'Análisis y criterio',
        'Decisión y resolución',
        'Control y seguimiento',
        'Registro y archivo'
    ];
    -- {motivo, sumilla que se clasifica bajo él}
    v_mapa text[][] := ARRAY[
        ['Trámite y gestión',      'Atender lo Solicitado'],
        ['Trámite y gestión',      'Preparar Respuesta'],
        ['Trámite y gestión',      'Trámite Legal'],
        ['Trámite y gestión',      'Trámite Reglamentario'],
        ['Trámite y gestión',      'Coordinar'],
        ['Trámite y gestión',      'Comunicar'],
        ['Trámite y gestión',      'Convocar a Sesión'],

        ['Análisis y criterio',    'Analizar'],
        ['Análisis y criterio',    'Estudio'],
        ['Análisis y criterio',    'Evaluar'],
        ['Análisis y criterio',    'Investigar'],
        ['Análisis y criterio',    'Inf. Escritorio con Crit. y Recom.'],
        ['Análisis y criterio',    'Informe Verbal'],
        ['Análisis y criterio',    'Verificar'],

        ['Decisión y resolución',  'Autorizado'],
        ['Decisión y resolución',  'Negativo'],
        ['Decisión y resolución',  'Cancelar'],
        ['Decisión y resolución',  'Cumplimiento'],
        ['Decisión y resolución',  'Devolver el Asunto'],

        ['Control y seguimiento',  'Controlar'],
        ['Control y seguimiento',  'Urgente'],
        ['Control y seguimiento',  'Dar Lectura'],

        ['Registro y archivo',     'Acusar Recibo'],
        ['Registro y archivo',     'Registrar'],
        ['Registro y archivo',     'Archivo'],
        ['Registro y archivo',     'Conocimiento']
    ];
    v_inst       record;
    i            integer;
    v_motivo     text;
    v_sumilla    text;
    v_codi_cate  integer;
    v_creadas    integer := 0;
    v_asignadas  integer := 0;
BEGIN
    FOR v_inst IN SELECT inst_codi FROM tmp_inst_categorias ORDER BY inst_codi LOOP

        -- Motivos
        FOR i IN 1 .. array_length(v_motivos, 1) LOOP
            v_motivo := v_motivos[i];

            IF NOT EXISTS (SELECT 1 FROM accion_categoria
                            WHERE inst_codi = v_inst.inst_codi
                              AND translate(upper(cate_nombre),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                                = translate(upper(v_motivo),  'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN'))
            THEN
                INSERT INTO accion_categoria (cate_codi, cate_nombre, cate_activo, cate_orden, inst_codi)
                VALUES (nextval('sec_accion_categoria'), v_motivo, 1, i, v_inst.inst_codi);
                v_creadas := v_creadas + 1;
            ELSE
                -- Un motivo que ya existía sin orden asignado (0) toma el de esta
                -- propuesta; si alguien ya lo ordenó a mano, se respeta.
                UPDATE accion_categoria
                   SET cate_orden = i
                 WHERE inst_codi = v_inst.inst_codi
                   AND cate_orden = 0
                   AND translate(upper(cate_nombre),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                     = translate(upper(v_motivo),  'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN');
            END IF;
        END LOOP;

        -- Clasificación
        FOR i IN 1 .. array_length(v_mapa, 1) LOOP
            v_motivo  := v_mapa[i][1];
            v_sumilla := v_mapa[i][2];

            SELECT cate_codi INTO v_codi_cate
              FROM accion_categoria
             WHERE inst_codi = v_inst.inst_codi
               AND translate(upper(cate_nombre),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                 = translate(upper(v_motivo),  'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
             LIMIT 1;

            CONTINUE WHEN v_codi_cate IS NULL;

            -- Sólo las que aún no tienen motivo: no se pisa lo decidido a mano.
            UPDATE accion
               SET cate_codi = v_codi_cate
             WHERE inst_codi = v_inst.inst_codi
               AND cate_codi IS NULL
               AND translate(upper(accion_nombre),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN')
                 = translate(upper(v_sumilla),    'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÑ','AEIOUAEIOUAEIOUN');

            IF FOUND THEN v_asignadas := v_asignadas + 1; END IF;
        END LOOP;

    END LOOP;

    RAISE NOTICE 'Motivos creados: %  |  Sumillas clasificadas: %', v_creadas, v_asignadas;
END $$;

COMMIT;

-- Verificación: el árbol tal como se verá al reasignar.
SELECT a.inst_codi,
       coalesce(c.cate_nombre, '(sin categoría)') AS motivo,
       a.accion_nombre                            AS sumilla,
       CASE WHEN a.accion_activo = 1 THEN 'Activo' ELSE 'Inactivo' END AS estado
  FROM accion a
  LEFT JOIN accion_categoria c ON c.cate_codi = a.cate_codi
 ORDER BY a.inst_codi, coalesce(c.cate_orden, 999), motivo, sumilla;
