-- ==============================================================================
-- Quipux: Orgánico funcional — catálogo de puestos y ruta jerárquica de áreas
-- ==============================================================================
-- Fase 1 de 3. Solo esquema: no mueve usuarios ni renombra áreas.
--
-- Qué crea
-- --------
--  1. Tabla 'cargo': catálogo de puestos. Cada puesto pertenece a UNA sola
--     área (la hoja del orgánico). Reutiliza la secuencia cargo_cargo_id_seq,
--     única superviviente de un intento anterior de catálogo.
--  2. FK usuarios.cargo_id -> cargo. Los valores que hoy apuntan a la tabla
--     borrada (1.815 en esta base) se ponen en NULL: no referencian nada.
--  3. depe_ruta(depe_codi): nombre completo del área recorriendo depe_codi_padre
--     hasta la raíz de la institución, unido con ' - '. Es el formato que hoy se
--     escribe a mano en dependencia.depe_nomb ('PADRE - HIJA').
--  4. depe_descendientes(depe_codi): el área y todas las que cuelgan de ella.
--  5. Los triggers que mantienen la tabla 'usuario' pasan a guardar en
--     usuario.depe_nomb la ruta completa en lugar del nombre plano. Así, cuando
--     dependencia.depe_nomb se acorta a solo el nombre de la hija, las pantallas
--     que leen usuario.depe_nomb siguen mostrando el contexto completo.
--
-- Idempotente: puede ejecutarse más de una vez.
-- ==============================================================================

SET client_encoding TO 'UTF8';

BEGIN;

-- ------------------------------------------------------------------------------
-- 1. Catálogo de puestos
-- ------------------------------------------------------------------------------
CREATE SEQUENCE IF NOT EXISTS cargo_cargo_id_seq;

CREATE TABLE IF NOT EXISTS cargo (
    cargo_id             integer      PRIMARY KEY DEFAULT nextval('cargo_cargo_id_seq'),
    depe_codi            integer      NOT NULL REFERENCES dependencia (depe_codi),
    inst_codi            integer      NOT NULL REFERENCES institucion (inst_codi),
    cargo_nombre         varchar(200) NOT NULL,   -- texto del pie de firma   (-> usuarios.usua_cargo)
    cargo_cabecera       varchar(200),            -- texto de la cabecera doc (-> usuarios.usua_cargo_cabecera)
    cargo_tipo           smallint     NOT NULL DEFAULT 0,  -- 0 normal, 1 jefe de área (-> usuarios.cargo_tipo)
    cargo_estado         smallint     NOT NULL DEFAULT 1,  -- 1 activo, 0 inactivo
    cargo_fecha_crea     timestamptz  NOT NULL DEFAULT now(),
    usua_codi_actualiza  integer,
    cargo_obs            text,
    CONSTRAINT ck_cargo_tipo   CHECK (cargo_tipo IN (0, 1, 2)),
    CONSTRAINT ck_cargo_estado CHECK (cargo_estado IN (0, 1))
);

ALTER SEQUENCE cargo_cargo_id_seq OWNED BY cargo.cargo_id;

-- Un mismo puesto no se repite dentro de un área (comparación sin mayúsculas
-- ni espacios dobles; los acentos sí distinguen para no fusionar a ciegas).
CREATE UNIQUE INDEX IF NOT EXISTS ux_cargo_depe_nombre
    ON cargo (depe_codi, lower(regexp_replace(trim(cargo_nombre), '\s+', ' ', 'g')));

CREATE INDEX IF NOT EXISTS idx_cargo_depe_codi ON cargo (depe_codi);

COMMENT ON TABLE  cargo              IS 'Catálogo de puestos. Cada puesto pertenece a una sola área (hoja del orgánico funcional).';
COMMENT ON COLUMN cargo.depe_codi    IS 'Área hoja a la que pertenece el puesto.';
COMMENT ON COLUMN cargo.cargo_nombre IS 'Nombre del puesto tal como va en el pie de firma (usuarios.usua_cargo).';
COMMENT ON COLUMN cargo.cargo_tipo   IS '0 = normal, 1 = jefe de área, 2 = asistente (no usado en la UI).';

-- ------------------------------------------------------------------------------
-- 2. FK desde usuarios. Primero se limpian los ids huérfanos del catálogo viejo.
-- ------------------------------------------------------------------------------
DO $$
DECLARE
    n integer;
BEGIN
    UPDATE usuarios u SET cargo_id = NULL
     WHERE u.cargo_id IS NOT NULL
       AND NOT EXISTS (SELECT 1 FROM cargo c WHERE c.cargo_id = u.cargo_id);
    GET DIAGNOSTICS n = ROW_COUNT;
    RAISE NOTICE 'usuarios.cargo_id huérfanos puestos en NULL: %', n;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_usuarios_cargo') THEN
        ALTER TABLE usuarios
            ADD CONSTRAINT fk_usuarios_cargo FOREIGN KEY (cargo_id) REFERENCES cargo (cargo_id);
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_usuarios_cargo_id ON usuarios (cargo_id) WHERE cargo_id IS NOT NULL;

-- ------------------------------------------------------------------------------
-- 3. Ruta jerárquica de un área
-- ------------------------------------------------------------------------------
-- Un área es raíz cuando depe_codi_padre es NULL o apunta a sí misma (así está
-- modelada la institución: UNIVERSIDAD DE CUENCA = 3, padre 3). La raíz no
-- forma parte de la ruta, salvo que se pida la ruta de la propia raíz.
--
--   depe_ruta(177) -> 'FACULTAD DE CIENCIAS MÉDICAS - DIRECCIÓN DE ESCUELA DE MEDICINA'
--   depe_ruta(112) -> 'FACULTAD DE CIENCIAS MÉDICAS'
--   depe_ruta(3)   -> 'UNIVERSIDAD DE CUENCA'
CREATE OR REPLACE FUNCTION depe_ruta(p_depe integer)
RETURNS varchar
LANGUAGE sql STABLE
AS $$
    WITH RECURSIVE r AS (
        SELECT d.depe_codi, d.depe_codi_padre, d.depe_nomb, 1 AS nivel
          FROM dependencia d
         WHERE d.depe_codi = p_depe
        UNION ALL
        SELECT p.depe_codi, p.depe_codi_padre, p.depe_nomb, r.nivel + 1
          FROM dependencia p
          JOIN r ON p.depe_codi = r.depe_codi_padre
         WHERE r.depe_codi_padre IS NOT NULL
           AND r.depe_codi_padre <> r.depe_codi
           AND p.depe_codi_padre IS NOT NULL
           AND p.depe_codi_padre <> p.depe_codi      -- el padre raíz no entra en la ruta
           AND r.nivel < 10                          -- corta ciclos accidentales
    )
    SELECT string_agg(trim(depe_nomb), ' - ' ORDER BY nivel DESC) FROM r;
$$;

-- ------------------------------------------------------------------------------
-- 4. El área y todas sus descendientes
-- ------------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION depe_descendientes(p_depe integer)
RETURNS SETOF integer
LANGUAGE sql STABLE
AS $$
    WITH RECURSIVE r AS (
        SELECT depe_codi FROM dependencia WHERE depe_codi = p_depe
        UNION                                           -- UNION (no ALL) corta ciclos
        SELECT d.depe_codi
          FROM dependencia d
          JOIN r ON d.depe_codi_padre = r.depe_codi
         WHERE d.depe_codi <> d.depe_codi_padre
    )
    SELECT depe_codi FROM r;
$$;

-- ------------------------------------------------------------------------------
-- 5. Triggers de la tabla 'usuario': depe_nomb pasa a ser la ruta completa
-- ------------------------------------------------------------------------------
-- 5a. Cambios en dependencia: refresca a los usuarios del área y, porque la ruta
--     de las hijas incluye el nombre del padre, también a los de las descendientes.
CREATE OR REPLACE FUNCTION public.func_actualizar_view_usuario_dependencia()
RETURNS trigger
LANGUAGE plpgsql
AS $function$
DECLARE
    var_recordset record;
BEGIN
    BEGIN
        SELECT inst_nombre, inst_sigla FROM institucion WHERE inst_codi = NEW.inst_adscrita INTO var_recordset;

        -- Usuarios directamente en el área: todos los campos, como siempre
        UPDATE usuario
           SET depe_nomb     = depe_ruta(NEW.depe_codi)
             , dep_sigla     = NEW.dep_sigla
             , inst_adscrita = NEW.inst_adscrita
             , inst_nombre   = var_recordset.inst_nombre
             , inst_sigla    = var_recordset.inst_sigla
             , usua_datos    = translate(UPPER(coalesce(usua_cedula,'')||' '||coalesce(usua_nombre,'')||' '||coalesce(usua_cargo,'')
                                  ||' '||coalesce(usua_email,'')||' '||coalesce(depe_ruta(NEW.depe_codi),'')
                                  ||' '||coalesce(var_recordset.inst_nombre,'')||' '||coalesce(var_recordset.inst_sigla,'')
                               ),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÂÊÎÔÛÃÕÑ','AEIOUAEIOUAEIOUAEIOUAON')
         WHERE depe_codi = NEW.depe_codi;

        -- Usuarios de las áreas descendientes: solo la ruta (y el índice de búsqueda)
        IF NEW.depe_nomb IS DISTINCT FROM OLD.depe_nomb OR NEW.depe_codi_padre IS DISTINCT FROM OLD.depe_codi_padre THEN
            UPDATE usuario u
               SET depe_nomb  = depe_ruta(u.depe_codi)
                 , usua_datos = translate(UPPER(coalesce(u.usua_cedula,'')||' '||coalesce(u.usua_nombre,'')||' '||coalesce(u.usua_cargo,'')
                                   ||' '||coalesce(u.usua_email,'')||' '||coalesce(depe_ruta(u.depe_codi),'')
                                   ||' '||coalesce(u.inst_nombre,'')||' '||coalesce(u.inst_sigla,'')
                                ),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÂÊÎÔÛÃÕÑ','AEIOUAEIOUAEIOUAEIOUAON')
             WHERE u.depe_codi <> NEW.depe_codi
               AND u.depe_codi IN (SELECT depe_descendientes(NEW.depe_codi));
        END IF;
    EXCEPTION WHEN OTHERS THEN
        INSERT INTO log_view_usuario (fecha, tabla, accion, codigo, error) VALUES (now(), 'dependencia', TG_OP, NEW.depe_codi, SQLERRM);
    END;
    RETURN NULL;
END;
$function$;

-- 5b. Cambios en usuarios: idéntico al original salvo que d.depe_nomb -> depe_ruta().
CREATE OR REPLACE FUNCTION public.func_actualizar_view_usuario_usuarios()
RETURNS trigger
LANGUAGE plpgsql
AS $function$
DECLARE
    var_recordset record;
BEGIN
    BEGIN
        SELECT u.usua_codi, depe_ruta(d.depe_codi) AS depe_nomb, d.dep_sigla, i.inst_estado, ia.inst_sigla
             , CASE WHEN i.inst_codi = 1 THEN NEW.inst_nombre ELSE ia.inst_nombre END AS inst_nombre
             , d.inst_adscrita, i.inst_sigla AS inst_padre_sigla, i.inst_nombre AS inst_padre_nombre
             , COALESCE(NEW.ciu_codi, COALESCE(d.depe_pie1,'1')::integer) AS ciu_codi
             , (SELECT c.nombre FROM ciudad c WHERE COALESCE(NEW.ciu_codi, COALESCE(d.depe_pie1,'1')::integer) = c.id) AS usua_ciudad
          FROM (SELECT NEW.usua_codi AS usua_codi, coalesce(NEW.inst_codi,0) AS inst_codi, coalesce(NEW.depe_codi,0) AS depe_codi) AS u
          LEFT JOIN dependencia d  ON u.depe_codi = d.depe_codi
          LEFT JOIN institucion i  ON u.inst_codi = i.inst_codi      -- institución padre
          LEFT JOIN institucion ia ON d.inst_adscrita = ia.inst_codi -- institución adscrita
          INTO var_recordset;

        IF TG_OP = 'UPDATE' THEN
            UPDATE usuario
               SET usua_cedula   = NEW.usua_cedula
                 , usua_nomb     = NEW.usua_nomb
                 , usua_apellido = NEW.usua_apellido
                 , usua_nombre   = TRIM(COALESCE(NEW.usua_nomb::text, ''::text) || ' '::text || COALESCE(NEW.usua_apellido::text, ''::text))
                 , usua_nuevo    = NEW.usua_nuevo
                 , usua_login    = NEW.usua_login
                 , usua_pasw     = NEW.usua_pasw
                 , usua_cargo    = NEW.usua_cargo
                 , usua_cargo_cabecera = NEW.usua_cargo_cabecera
                 , cargo_tipo    = NEW.cargo_tipo
                 , usua_esta     = NEW.usua_esta
                 , usua_email    = NEW.usua_email
                 , usua_titulo   = NEW.usua_titulo
                 , usua_abr_titulo = NEW.usua_abr_titulo
                 , tipo_usuario  = CASE WHEN NEW.inst_codi = 1 THEN 2 ELSE 1 END
                 , usua_tipo_certificado = NEW.usua_tipo_certificado
                 , usua_subrogado = NEW.usua_subrogado
                 , visible_sub   = NEW.visible_sub
                 , usua_direccion = NEW.usua_direccion
                 , usua_telefono = NEW.usua_telefono
                 , usua_firma_path = NEW.usua_firma_path
                 , depe_codi     = NEW.depe_codi
                 , depe_nomb     = var_recordset.depe_nomb
                 , dep_sigla     = var_recordset.dep_sigla
                 , inst_codi     = NEW.inst_codi
                 , inst_nombre   = var_recordset.inst_nombre
                 , inst_sigla    = var_recordset.inst_sigla
                 , inst_estado   = var_recordset.inst_estado
                 , ciu_codi      = var_recordset.ciu_codi
                 , usua_ciudad   = var_recordset.usua_ciudad
                 , tipo_identificacion = NEW.tipo_identificacion
                 , usua_datos    = translate(UPPER(coalesce(NEW.usua_cedula,'')||' '||coalesce(NEW.usua_nomb,'')
                                      ||' '||coalesce(NEW.usua_apellido,'')||' '||coalesce(NEW.usua_cargo,'')||' '||coalesce(NEW.usua_email,'')
                                      ||' '||coalesce(var_recordset.depe_nomb,'')||' '||coalesce(var_recordset.inst_nombre,'')
                                      ||' '||coalesce(var_recordset.inst_sigla,'')),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÂÊÎÔÛÃÕÑ','AEIOUAEIOUAEIOUAEIOUAON')
                 , inst_padre_nombre = var_recordset.inst_padre_nombre
                 , inst_padre_sigla  = var_recordset.inst_padre_sigla
                 , inst_adscrita = var_recordset.inst_adscrita
             WHERE usua_codi = NEW.usua_codi;
        END IF;

        IF TG_OP = 'INSERT' THEN
            INSERT INTO usuario (
                usua_codi, usua_cedula, usua_nomb, usua_apellido, usua_nombre, usua_nuevo, usua_login
                , usua_pasw, usua_cargo, usua_cargo_cabecera, cargo_tipo, usua_esta, usua_email
                , usua_titulo, usua_abr_titulo, tipo_usuario, usua_tipo_certificado, usua_subrogado
                , visible_sub, usua_direccion, usua_telefono, usua_firma_path, depe_codi, depe_nomb
                , dep_sigla, inst_codi, inst_nombre, inst_sigla, inst_estado, ciu_codi, usua_ciudad
                , tipo_identificacion, usua_datos, inst_padre_nombre, inst_padre_sigla, inst_adscrita
            ) VALUES (
                NEW.usua_codi, NEW.usua_cedula, NEW.usua_nomb, NEW.usua_apellido
                , TRIM(COALESCE(NEW.usua_nomb::text, ''::text) || ' '::text || COALESCE(NEW.usua_apellido::text, ''::text))
                , NEW.usua_nuevo, NEW.usua_login, NEW.usua_pasw, NEW.usua_cargo, NEW.usua_cargo_cabecera
                , NEW.cargo_tipo, NEW.usua_esta, NEW.usua_email, NEW.usua_titulo, NEW.usua_abr_titulo
                , CASE WHEN NEW.inst_codi = 1 THEN 2 ELSE 1 END
                , NEW.usua_tipo_certificado, NEW.usua_subrogado, NEW.visible_sub, NEW.usua_direccion
                , NEW.usua_telefono, NEW.usua_firma_path, NEW.depe_codi, var_recordset.depe_nomb
                , var_recordset.dep_sigla, NEW.inst_codi, var_recordset.inst_nombre, var_recordset.inst_sigla
                , var_recordset.inst_estado, var_recordset.ciu_codi, var_recordset.usua_ciudad, NEW.tipo_identificacion
                , translate(UPPER(coalesce(NEW.usua_cedula,'')||' '||coalesce(NEW.usua_nomb,'')||' '||coalesce(NEW.usua_apellido,'')
                      ||' '||coalesce(NEW.usua_cargo,'')||' '||coalesce(NEW.usua_email,'')||' '||coalesce(var_recordset.depe_nomb,'')
                      ||' '||coalesce(var_recordset.inst_nombre,'')||' '||coalesce(var_recordset.inst_sigla,'')
                  ),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÂÊÎÔÛÃÕÑ','AEIOUAEIOUAEIOUAEIOUAON')
                , var_recordset.inst_padre_nombre, var_recordset.inst_padre_sigla, var_recordset.inst_adscrita
            );
        END IF;

    EXCEPTION WHEN OTHERS THEN
        INSERT INTO log_view_usuario (fecha, tabla, accion, codigo, error) VALUES (now(), 'usuarios', TG_OP, NEW.usua_codi, SQLERRM);
    END;
    RETURN NULL;
END;
$function$;

COMMIT;
