-- ==============================================================================
-- Quipux Ucuenca: Restoration of NATIVE RDS Triggers for 'usuario' table
-- ==============================================================================

CREATE OR REPLACE FUNCTION public.func_actualizar_view_usuario_ciudad()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$ 
DECLARE 
BEGIN 
    BEGIN 
        UPDATE usuario SET usua_ciudad=NEW.nombre WHERE ciu_codi=NEW.id; 
    EXCEPTION WHEN OTHERS THEN 
        INSERT INTO log_view_usuario (fecha, tabla, accion, codigo, error) VALUES (now(), 'ciudad', TG_OP, NEW.id, SQLERRM); 
    END; 
    RETURN NULL; 
END; 
$function$
;

CREATE OR REPLACE FUNCTION public.func_actualizar_view_usuario_ciudadano()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
DECLARE
    var_usua_codi integer;
BEGIN
    BEGIN
        IF TG_OP = 'DELETE' THEN -- Cuando se pasa un ciudadano a la tabla funcionario (ciudadanos con firma electrónica)
            var_usua_codi := OLD.ciu_codigo;
	    DELETE FROM usuario WHERE usua_codi=OLD.ciu_codigo;
	END IF;
	
        IF TG_OP = 'UPDATE' THEN
            var_usua_codi := OLD.ciu_codigo;
	    UPDATE usuario 
	    SET   usua_cedula = NEW.ciu_cedula
	        , usua_nomb   = NEW.ciu_nombre
	        , usua_apellido = NEW.ciu_apellido
	        , usua_nombre = TRIM(COALESCE(NEW.ciu_nombre::text, ''::text) || ' '::text || COALESCE(NEW.ciu_apellido::text, ''::text))
	        , usua_nuevo  = NEW.ciu_nuevo 
	        , usua_login  = CASE WHEN NEW.ciu_estado=1 THEN 'U'::text || NEW.ciu_cedula::text ELSE 'l'::text || OLD.ciu_codigo::text END
	        , usua_pasw   = NEW.ciu_pasw 
	        , usua_esta   = NEW.ciu_estado
	        , usua_cargo  = NEW.ciu_cargo
	        , usua_cargo_cabecera = NEW.ciu_cargo
	        , usua_email  = NEW.ciu_email
	        , usua_titulo = NEW.ciu_titulo
	        , usua_abr_titulo = NEW.ciu_abr_titulo
	        , inst_nombre = NEW.ciu_empresa
	        , usua_direccion = NEW.ciu_direccion
	        , usua_telefono = NEW.ciu_telefono
	        , ciu_codi = COALESCE(NEW.ciudad_codi, 1)
	        , usua_ciudad = (SELECT c.nombre FROM ciudad c WHERE COALESCE(NEW.ciudad_codi, 1) = c.id)
	        , cargo_tipo  = 0
	        , depe_codi   = 0 
	        , depe_nomb   = ''
	        , dep_sigla = NULL
	        , inst_codi   = 0
	        , inst_sigla  = ''
	        , inst_estado = 1
	        , tipo_usuario = 2
	        , usua_tipo_certificado = 0
	        , usua_subrogado = 0
	        , visible_sub = 0
	        , usua_firma_path = ''
                , usua_datos = translate(UPPER(coalesce(NEW.ciu_cedula,'')||' '||coalesce(NEW.ciu_nombre,'')
                  ||' '||coalesce(NEW.ciu_apellido,'')||' '||coalesce(NEW.ciu_cargo,'')||' '||coalesce(NEW.ciu_email,'')
                  ||' '||coalesce(NEW.ciu_empresa,'')),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÂÊÎÔÛÃÕÑ','AEIOUAEIOUAEIOUAEIOUAON')
                , inst_adscrita = 0
                , inst_padre_nombre = 'Ciudadanos'
                , inst_padre_sigla = 'CIUDADANO'
	    WHERE usua_codi = OLD.ciu_codigo;
	END IF;

        IF TG_OP = 'INSERT' THEN
            var_usua_codi := NEW.ciu_codigo;
	    INSERT INTO usuario (
	        usua_codi, usua_cargo, usua_cargo_cabecera, usua_nuevo, usua_login, usua_pasw, usua_esta, usua_cedula, usua_nomb, usua_apellido, usua_nombre
	         , usua_email, usua_titulo, usua_abr_titulo, inst_nombre, usua_direccion, usua_telefono, ciu_codi, usua_ciudad
	         , cargo_tipo, depe_codi, inst_estado, inst_codi, depe_nomb, inst_sigla, tipo_usuario, usua_tipo_certificado
	         , usua_subrogado, visible_sub, dep_sigla, usua_firma_path, usua_datos, inst_adscrita, inst_padre_nombre, inst_padre_sigla
	    ) VALUES (
	        NEW.ciu_codigo, NEW.ciu_cargo, NEW.ciu_cargo, NEW.ciu_nuevo
	        , CASE WHEN NEW.ciu_estado=1 THEN 'U'::text || NEW.ciu_cedula::text ELSE 'l'::text || NEW.ciu_codigo::text END
	        , NEW.ciu_pasw, NEW.ciu_estado, NEW.ciu_cedula, NEW.ciu_nombre, NEW.ciu_apellido
	        , TRIM(COALESCE(NEW.ciu_nombre::text, ''::text) || ' '::text || COALESCE(NEW.ciu_apellido::text, ''::text))
	        , NEW.ciu_email, NEW.ciu_titulo, NEW.ciu_abr_titulo, NEW.ciu_empresa, NEW.ciu_direccion, NEW.ciu_telefono
	        , COALESCE(NEW.ciudad_codi, 1), (SELECT c.nombre FROM ciudad c WHERE COALESCE(NEW.ciudad_codi, 1) = c.id)
	        , 0, 0, 1, 0, '', '', 2, 0, 0, 0, NULL, ''
	        , translate(UPPER(coalesce(NEW.ciu_cedula,'')||' '||coalesce(NEW.ciu_nombre,'')
                    ||' '||coalesce(NEW.ciu_apellido,'')||' '||coalesce(NEW.ciu_cargo,'')||' '||coalesce(NEW.ciu_email,'')
                    ||' '||coalesce(NEW.ciu_empresa,'')),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÂÊÎÔÛÃÕÑ','AEIOUAEIOUAEIOUAEIOUAON')
                , 0, 'Ciudadanos', 'CIUDADANO'
	    );
	END IF;

    EXCEPTION WHEN OTHERS THEN
        INSERT INTO log_view_usuario (fecha, tabla, accion, codigo, error) VALUES (now(), 'ciudadano', TG_OP, var_usua_codi, SQLERRM);
    END;
    RETURN NULL;
END;
$function$
;

CREATE OR REPLACE FUNCTION public.func_actualizar_view_usuario_dependencia()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
DECLARE
    var_recordset record;
BEGIN
    BEGIN
        -- Consultamos los datos de la institucion adscrita
        SELECT inst_nombre, inst_sigla FROM institucion where inst_codi=NEW.inst_adscrita INTO var_recordset;

        UPDATE usuario 
        SET   depe_nomb=NEW.depe_nomb
            , dep_sigla=NEW.dep_sigla 
            , inst_adscrita=NEW.inst_adscrita
            , inst_nombre = var_recordset.inst_nombre
            , inst_sigla = var_recordset.inst_sigla
            , usua_datos = translate(UPPER(coalesce(usua_cedula,'')||' '||coalesce(usua_nombre,'')||' '||coalesce(usua_cargo,'')
                  ||' '||coalesce(usua_email,'')||' '||coalesce(NEW.depe_nomb,'')
                  ||' '||coalesce(var_recordset.inst_nombre,'')||' '||coalesce(var_recordset.inst_sigla,'')
              ),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÂÊÎÔÛÃÕÑ','AEIOUAEIOUAEIOUAEIOUAON')
        WHERE depe_codi = NEW.depe_codi;
    EXCEPTION WHEN OTHERS THEN
        INSERT INTO log_view_usuario (fecha, tabla, accion, codigo, error) VALUES (now(), 'dependencia', TG_OP, NEW.depe_codi, SQLERRM);
    END;
    RETURN NULL;
END;
$function$
;

CREATE OR REPLACE FUNCTION public.func_actualizar_view_usuario_institucion()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
DECLARE
BEGIN
    BEGIN
        UPDATE usuario 
        SET   
            -- Si modifico la institución padre
              inst_estado=case when inst_codi=NEW.inst_codi then NEW.inst_estado else inst_estado end
            , inst_padre_nombre=case when inst_codi=NEW.inst_codi then NEW.inst_nombre else inst_padre_nombre end
            , inst_padre_sigla=case when inst_codi=NEW.inst_codi then NEW.inst_sigla else inst_padre_sigla end
            -- Si modifico la institución adscrita
            , inst_nombre=case when inst_adscrita=NEW.inst_codi then NEW.inst_nombre else inst_nombre end
            , inst_sigla=case when inst_adscrita=NEW.inst_codi then NEW.inst_sigla else inst_sigla end
            , usua_datos = translate(UPPER(coalesce(usua_cedula,'')||' '||coalesce(usua_nombre,'')||' '||coalesce(usua_cargo,'')
                  ||' '||coalesce(usua_email,'')||' '||coalesce(depe_nomb,'')||' '||
                  case when inst_adscrita=NEW.inst_codi then coalesce(NEW.inst_nombre,'')||' '||coalesce(NEW.inst_sigla,'') 
                       else coalesce(inst_nombre,'')||' '||coalesce(inst_sigla,'') end
              ),'ÁÉÍÓÚÀÈÌÒÙÄËÏÖÜÂÊÎÔÛÃÕÑ','AEIOUAEIOUAEIOUAEIOUAON')
        WHERE inst_codi = NEW.inst_codi or inst_adscrita=NEW.inst_codi;
    EXCEPTION WHEN OTHERS THEN
        INSERT INTO log_view_usuario (fecha, tabla, accion, codigo, error) VALUES (now(), 'institucion', TG_OP, NEW.depe_codi, SQLERRM);
    END;
    RETURN NULL;
END;
$function$
;

CREATE OR REPLACE FUNCTION public.func_actualizar_view_usuario_usuarios()
 RETURNS trigger
 LANGUAGE plpgsql
AS $function$
DECLARE
    var_recordset record;
BEGIN
    BEGIN
        -- Consultamos los datos de la institucion, del área y de la ciudad para insertarlos luego
        SELECT u.usua_codi, d.depe_nomb, d.dep_sigla, i.inst_estado, ia.inst_sigla
            , CASE WHEN i.inst_codi = 1 THEN NEW.inst_nombre ELSE ia.inst_nombre END AS inst_nombre
            , d.inst_adscrita, i.inst_sigla as inst_padre_sigla, i.inst_nombre as inst_padre_nombre
            , COALESCE(NEW.ciu_codi, COALESCE(d.depe_pie1,'1')::integer) AS ciu_codi
	    , (SELECT c.nombre FROM ciudad c WHERE COALESCE(NEW.ciu_codi, COALESCE(d.depe_pie1,'1')::integer)=c.id) AS usua_ciudad
	FROM (SELECT NEW.usua_codi as usua_codi, coalesce(NEW.inst_codi,0) as inst_codi, coalesce(NEW.depe_codi,0) as depe_codi) as u
            LEFT JOIN dependencia d ON u.depe_codi = d.depe_codi
            LEFT JOIN institucion i ON u.inst_codi = i.inst_codi --institucion padre
            LEFT JOIN institucion ia ON d.inst_adscrita = ia.inst_codi --institucion adscrita
        INTO var_recordset;
        
    
        IF TG_OP = 'UPDATE' THEN
	    UPDATE usuario 
	    SET   usua_cedula = NEW.usua_cedula
	        , usua_nomb   = NEW.usua_nomb
	        , usua_apellido = NEW.usua_apellido
	        , usua_nombre = TRIM(COALESCE(NEW.usua_nomb::text, ''::text) || ' '::text || COALESCE(NEW.usua_apellido::text, ''::text))
	        , usua_nuevo  = NEW.usua_nuevo 
	        , usua_login  = NEW.usua_login
	        , usua_pasw   = NEW.usua_pasw 
	        , usua_cargo  = NEW.usua_cargo 
	        , usua_cargo_cabecera = NEW.usua_cargo_cabecera
	        , cargo_tipo  = NEW.cargo_tipo
	        , usua_esta   = NEW.usua_esta
	        , usua_email  = NEW.usua_email
	        , usua_titulo = NEW.usua_titulo
	        , usua_abr_titulo = NEW.usua_abr_titulo
	        , tipo_usuario = CASE WHEN NEW.inst_codi = 1 THEN 2 ELSE 1 END
	        , usua_tipo_certificado = NEW.usua_tipo_certificado
	        , usua_subrogado = NEW.usua_subrogado
	        , visible_sub = NEW.visible_sub
	        , usua_direccion = NEW.usua_direccion
	        , usua_telefono = NEW.usua_telefono
	        , usua_firma_path = NEW.usua_firma_path
	        , depe_codi   = NEW.depe_codi
	        , depe_nomb   = var_recordset.depe_nomb
	        , dep_sigla   = var_recordset.dep_sigla
	        , inst_codi   = NEW.inst_codi
	        , inst_nombre = var_recordset.inst_nombre
	        , inst_sigla  = var_recordset.inst_sigla
	        , inst_estado = var_recordset.inst_estado
	        , ciu_codi = var_recordset.ciu_codi
	        , usua_ciudad = var_recordset.usua_ciudad
                , tipo_identificacion = NEW.tipo_identificacion
                , usua_datos = translate(UPPER(coalesce(NEW.usua_cedula,'')||' '||coalesce(NEW.usua_nomb,'')
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
$function$
;

-- ==============================================================================
-- BINDING TRIGGERS TO TABLES NOW
-- ==============================================================================

DROP TRIGGER IF EXISTS trig_actualizar_view_usuario_ciudad ON ciudad;
CREATE TRIGGER trig_actualizar_view_usuario_ciudad AFTER INSERT OR UPDATE ON ciudad FOR EACH ROW EXECUTE FUNCTION func_actualizar_view_usuario_ciudad();

DROP TRIGGER IF EXISTS trig_actualizar_view_usuario_ciudadano ON ciudadano;
CREATE TRIGGER trig_actualizar_view_usuario_ciudadano AFTER INSERT OR UPDATE ON ciudadano FOR EACH ROW EXECUTE FUNCTION func_actualizar_view_usuario_ciudadano();

DROP TRIGGER IF EXISTS trig_actualizar_view_usuario_dependencia ON dependencia;
CREATE TRIGGER trig_actualizar_view_usuario_dependencia AFTER INSERT OR UPDATE ON dependencia FOR EACH ROW EXECUTE FUNCTION func_actualizar_view_usuario_dependencia();

DROP TRIGGER IF EXISTS trig_actualizar_view_usuario_institucion ON institucion;
CREATE TRIGGER trig_actualizar_view_usuario_institucion AFTER INSERT OR UPDATE ON institucion FOR EACH ROW EXECUTE FUNCTION func_actualizar_view_usuario_institucion();

DROP TRIGGER IF EXISTS trig_actualizar_view_usuario_usuarios ON usuarios;
CREATE TRIGGER trig_actualizar_view_usuario_usuarios AFTER INSERT OR UPDATE ON usuarios FOR EACH ROW EXECUTE FUNCTION func_actualizar_view_usuario_usuarios();
