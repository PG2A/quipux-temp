--
-- PostgreSQL database dump
--

\restrict ySaoBfyWLeeuCapQV0IbPs3ZhJQVPGd6FzdkR2SrRj1GdNk3VSlBbG1VoWsVNbH

-- Dumped from database version 16.8
-- Dumped by pg_dump version 17.7 (Homebrew)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: ciudad; Type: TABLE; Schema: public; Owner: quipux_ucuenca
--

CREATE TABLE public.ciudad (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    id_padre integer
);


ALTER TABLE public.ciudad OWNER TO quipux_ucuenca;

--
-- Name: TABLE ciudad; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON TABLE public.ciudad IS 'Catálogo de ciudades, tabla recursiva';


--
-- Name: COLUMN ciudad.id; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudad.id IS 'Id de la ciudad';


--
-- Name: COLUMN ciudad.nombre; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudad.nombre IS 'Nombre de la ciudad';


--
-- Name: COLUMN ciudad.id_padre; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudad.id_padre IS 'Código del país o de la provincia a la que pertenece la ciudad';


--
-- Name: ciudadano; Type: TABLE; Schema: public; Owner: quipux_ucuenca
--

CREATE TABLE public.ciudadano (
    ciu_nombre character varying(200),
    ciu_direccion character varying(150),
    ciu_empresa character varying(200),
    ciu_cargo character varying(150),
    ciu_telefono character varying(50),
    ciu_email character varying(500),
    ciu_titulo character varying(100),
    ciu_abr_titulo character varying(30),
    ciu_codigo integer DEFAULT nextval(('public.usuarios_usua_codi_seq'::text)::regclass) NOT NULL,
    ciu_apellido character varying(200),
    ciu_cedula character varying(50),
    inst_codi integer,
    ciu_estado integer DEFAULT 1,
    ciu_documento character varying(50),
    ciu_pasw character varying(35),
    ciu_nuevo smallint DEFAULT 0,
    usua_codi_actualiza integer,
    ciu_fecha_actualiza timestamp with time zone,
    ciudad_codi integer,
    ciu_obs_actualiza character varying,
    ciu_referencia character varying
);


ALTER TABLE public.ciudadano OWNER TO quipux_ucuenca;

--
-- Name: TABLE ciudadano; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON TABLE public.ciudadano IS 'Contiene los usuarios externos (que no pertenecen a una institución pública) y que solo pueden conectarse al sistema para consultar los documentos que dejaron en alguna institución y las respuestas recibidas';


--
-- Name: COLUMN ciudadano.ciu_nombre; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_nombre IS 'Nombre de la persona';


--
-- Name: COLUMN ciudadano.ciu_direccion; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_direccion IS 'Dirección Domiciliaria';


--
-- Name: COLUMN ciudadano.ciu_empresa; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_empresa IS 'Nombre de la empresa a la que pertenece';


--
-- Name: COLUMN ciudadano.ciu_cargo; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_cargo IS 'Cargo que desempeña en su empresa';


--
-- Name: COLUMN ciudadano.ciu_telefono; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_telefono IS 'Número telefónico';


--
-- Name: COLUMN ciudadano.ciu_email; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_email IS 'email; pueden ser varios separados por comas';


--
-- Name: COLUMN ciudadano.ciu_titulo; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_titulo IS 'Título o tratamiento (Señor, Ingeniero, etc.)';


--
-- Name: COLUMN ciudadano.ciu_abr_titulo; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_abr_titulo IS 'Abreviación del título o tratamiento (Sr., Ing., etc.)';


--
-- Name: COLUMN ciudadano.ciu_codigo; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_codigo IS 'Id del usuario ciudadano';


--
-- Name: COLUMN ciudadano.ciu_apellido; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_apellido IS 'Apellido de la persona';


--
-- Name: COLUMN ciudadano.ciu_cedula; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_cedula IS 'Número de cédula de ciudadanía';


--
-- Name: COLUMN ciudadano.inst_codi; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.inst_codi IS 'Institución en la que se creo el ciudadano (log)';


--
-- Name: COLUMN ciudadano.ciu_estado; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_estado IS 'Estado del ciudadano
0 - Inactivo
1 - Activo';


--
-- Name: COLUMN ciudadano.ciu_documento; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_documento IS 'Número de identificación adicional (RUC, pasaporte, etc.)';


--
-- Name: COLUMN ciudadano.ciu_pasw; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_pasw IS 'Contraseña';


--
-- Name: COLUMN ciudadano.ciu_nuevo; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_nuevo IS 'Indica si es un usuario nuevo y si se le debe enviar la contraseña a su correo electrónico';


--
-- Name: COLUMN ciudadano.usua_codi_actualiza; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.usua_codi_actualiza IS 'Usuario que realizó la última modificación a los datos del ciudadano';


--
-- Name: COLUMN ciudadano.ciu_fecha_actualiza; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_fecha_actualiza IS 'Fecha en que se modificó por última vez al ciudadano';


--
-- Name: COLUMN ciudadano.ciudad_codi; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciudad_codi IS 'Id de la ciudad en la que se encuentra la persona';


--
-- Name: COLUMN ciudadano.ciu_obs_actualiza; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_obs_actualiza IS 'Descripción de los últimos cambios realizados en la información del usuario';


--
-- Name: COLUMN ciudadano.ciu_referencia; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.ciudadano.ciu_referencia IS 'Datos de referencia de la dirección del domicilio';


--
-- Name: dependencia; Type: TABLE; Schema: public; Owner: quipux_ucuenca
--

CREATE TABLE public.dependencia (
    depe_codi integer NOT NULL,
    depe_nomb character varying(150) NOT NULL,
    depe_codi_padre integer,
    dep_sigla character varying(100),
    dep_central integer,
    dep_direccion character varying(100),
    depe_estado smallint,
    inst_codi integer,
    depe_plantilla integer,
    depe_pie1 character varying(150),
    depe_pie2 character varying(150),
    depe_pie3 character varying(150),
    inst_adscrita integer
);


ALTER TABLE public.dependencia OWNER TO quipux_ucuenca;

--
-- Name: TABLE dependencia; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON TABLE public.dependencia IS 'Áreas funcionales de las instituciones, estructura orgánica funcional
Tabla recursiva';


--
-- Name: COLUMN dependencia.depe_codi; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.dependencia.depe_codi IS 'Id del área';


--
-- Name: COLUMN dependencia.depe_nomb; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.dependencia.depe_nomb IS 'Nombre del área';


--
-- Name: COLUMN dependencia.depe_codi_padre; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.dependencia.depe_codi_padre IS 'Id del área padre; código del área superior en el orgánico funcional';


--
-- Name: COLUMN dependencia.dep_sigla; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.dependencia.dep_sigla IS 'Siglas del área';


--
-- Name: COLUMN dependencia.dep_central; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.dependencia.dep_central IS 'Indica en qué area se encuentra el archivo físico donde se guarda la documentación impresa del área';


--
-- Name: COLUMN dependencia.dep_direccion; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.dependencia.dep_direccion IS 'Campo en desuso';


--
-- Name: COLUMN dependencia.depe_estado; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.dependencia.depe_estado IS 'Estado del área';


--
-- Name: COLUMN dependencia.inst_codi; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.dependencia.inst_codi IS 'Codigo de la institución a la que pertenece';


--
-- Name: COLUMN dependencia.depe_plantilla; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.dependencia.depe_plantilla IS 'Dependencia de la que se copiará la plantilla con que se generan los documentos ';


--
-- Name: COLUMN dependencia.depe_pie1; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.dependencia.depe_pie1 IS 'Ciudad a la que pertenece el área y que se pondrá por defecto a los usuarios del área';


--
-- Name: COLUMN dependencia.depe_pie2; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.dependencia.depe_pie2 IS 'Campo en desuso';


--
-- Name: COLUMN dependencia.depe_pie3; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.dependencia.depe_pie3 IS 'Campo en desuso';


--
-- Name: COLUMN dependencia.inst_adscrita; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.dependencia.inst_adscrita IS 'Código de la institución adscrita';


--
-- Name: institucion; Type: TABLE; Schema: public; Owner: quipux_ucuenca
--

CREATE TABLE public.institucion (
    inst_ruc character varying(14),
    inst_nombre character varying(200),
    inst_logo character varying(100),
    inst_sigla character varying(10),
    inst_pie1 character varying(150),
    inst_pie2 character varying(150),
    inst_pie3 character varying(150),
    inst_codi integer NOT NULL,
    inst_estado integer,
    inst_coordinador smallint DEFAULT 0,
    inst_telefono character varying(30),
    inst_despedida_ofi character varying,
    inst_email character varying(50),
    inst_ws_wsdl character varying(500),
    inst_ws_usuario character varying(100),
    inst_ws_contrasena character varying(100)
);


ALTER TABLE public.institucion OWNER TO quipux_ucuenca;

--
-- Name: TABLE institucion; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON TABLE public.institucion IS 'Instituciones registradas';


--
-- Name: COLUMN institucion.inst_ruc; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.institucion.inst_ruc IS 'RUC de la Institución';


--
-- Name: COLUMN institucion.inst_nombre; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.institucion.inst_nombre IS 'Nombre de la institución';


--
-- Name: COLUMN institucion.inst_logo; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.institucion.inst_logo IS 'Path donde se encuentra la imágen con el logo institucional';


--
-- Name: COLUMN institucion.inst_sigla; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.institucion.inst_sigla IS 'Siglas de la institución';


--
-- Name: COLUMN institucion.inst_pie1; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.institucion.inst_pie1 IS 'Campo en desuso';


--
-- Name: COLUMN institucion.inst_pie2; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.institucion.inst_pie2 IS 'Campo en desuso';


--
-- Name: COLUMN institucion.inst_pie3; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.institucion.inst_pie3 IS 'Campo en desuso';


--
-- Name: COLUMN institucion.inst_codi; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.institucion.inst_codi IS 'Id';


--
-- Name: COLUMN institucion.inst_estado; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.institucion.inst_estado IS 'Estado, activa o inactiva';


--
-- Name: COLUMN institucion.inst_coordinador; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.institucion.inst_coordinador IS 'Id del ministerio coordinador';


--
-- Name: COLUMN institucion.inst_telefono; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.institucion.inst_telefono IS 'Número telefónico';


--
-- Name: COLUMN institucion.inst_despedida_ofi; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.institucion.inst_despedida_ofi IS 'Frase de despedida por defecto que saldrá en los documentos (Ejm: Dios, Patria y Libertad)';


--
-- Name: COLUMN institucion.inst_email; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.institucion.inst_email IS 'email para soporte institucional';


--
-- Name: usuarios; Type: TABLE; Schema: public; Owner: quipux_ucuenca
--

CREATE TABLE public.usuarios (
    usua_login character varying(50),
    usua_pasw character varying(35),
    usua_nomb character varying(200),
    usua_cedula character varying(50),
    usua_email character varying(500),
    usua_titulo character varying(100),
    usua_abr_titulo character varying(30),
    usua_esta smallint DEFAULT 1,
    usua_codi integer DEFAULT nextval('public.usuarios_usua_codi_seq'::regclass) NOT NULL,
    cargo_tipo smallint DEFAULT 0,
    depe_codi integer,
    usua_nuevo smallint DEFAULT 1,
    usua_tipo smallint DEFAULT 2,
    usua_cargo character varying(200),
    inst_codi integer,
    usua_apellido character varying(200),
    cargo_id integer,
    usua_obs text,
    ciu_codi integer,
    usua_genero character(1),
    usua_firma_path character varying,
    usua_direccion character varying,
    usua_telefono character varying,
    usua_codi_actualiza integer,
    usua_fecha_actualiza timestamp with time zone,
    usua_obs_actualiza character varying,
    usua_cargo_cabecera character varying(200),
    usua_sumilla character varying(50),
    usua_responsable_area integer DEFAULT 0,
    inst_nombre character varying(200),
    usua_tipo_certificado smallint DEFAULT 0,
    visible_sub integer DEFAULT 1,
    usua_subrogado integer,
    usua_celular character varying,
    tipo_identificacion integer DEFAULT 0
);


ALTER TABLE public.usuarios OWNER TO quipux_ucuenca;

--
-- Name: TABLE usuarios; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON TABLE public.usuarios IS 'Datos de los usuarios del sistema ';


--
-- Name: COLUMN usuarios.usua_login; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_login IS 'Login del usuario (deben comenzar con "U"); existen usuarios especiales que comienzan con ''UUSR'' y ''UADM''';


--
-- Name: COLUMN usuarios.usua_pasw; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_pasw IS 'Contraseña del usuario en md5';


--
-- Name: COLUMN usuarios.usua_nomb; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_nomb IS 'Nombre del usuario';


--
-- Name: COLUMN usuarios.usua_cedula; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_cedula IS 'Número de cédula';


--
-- Name: COLUMN usuarios.usua_email; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_email IS 'Email, pueden ser varios separados por comas';


--
-- Name: COLUMN usuarios.usua_titulo; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_titulo IS 'Tratamiento o título académico';


--
-- Name: COLUMN usuarios.usua_abr_titulo; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_abr_titulo IS 'Abreviacion del titulo';


--
-- Name: COLUMN usuarios.usua_esta; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_esta IS 'Estado del usuario, activo o inactivo';


--
-- Name: COLUMN usuarios.usua_codi; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_codi IS 'Id del usuario';


--
-- Name: COLUMN usuarios.cargo_tipo; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.cargo_tipo IS '0 normal  1 jefe     2  asistente';


--
-- Name: COLUMN usuarios.depe_codi; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.depe_codi IS 'Área a la que pertenece el usuario';


--
-- Name: COLUMN usuarios.usua_nuevo; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_nuevo IS 'Determina si el usuario ya cambió su clave del sistema o si se debe enviar el email para cambio de clave';


--
-- Name: COLUMN usuarios.usua_tipo; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_tipo IS 'si el usuario es interno o externo';


--
-- Name: COLUMN usuarios.usua_cargo; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_cargo IS 'Cargo del usuario';


--
-- Name: COLUMN usuarios.inst_codi; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.inst_codi IS 'Institución a la que pertenece el usuario';


--
-- Name: COLUMN usuarios.usua_apellido; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_apellido IS 'Apellido del usuario';


--
-- Name: COLUMN usuarios.cargo_id; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.cargo_id IS 'Campo en desuso';


--
-- Name: COLUMN usuarios.usua_obs; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_obs IS 'Observaciones sobre el usuario';


--
-- Name: COLUMN usuarios.ciu_codi; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.ciu_codi IS 'Id de la ciudad a la que pertenece el usuario';


--
-- Name: COLUMN usuarios.usua_firma_path; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_firma_path IS 'Path en el que se encuentra la imágen escaneada de la firma';


--
-- Name: COLUMN usuarios.usua_direccion; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_direccion IS 'Dirección domiciliaria';


--
-- Name: COLUMN usuarios.usua_telefono; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_telefono IS 'Número telefónico';


--
-- Name: COLUMN usuarios.usua_codi_actualiza; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_codi_actualiza IS 'Id del usuario que realizó la ultima modificación de los datos';


--
-- Name: COLUMN usuarios.usua_fecha_actualiza; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_fecha_actualiza IS 'Fecha en la que se realizó la última modificación de los datos';


--
-- Name: COLUMN usuarios.usua_obs_actualiza; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_obs_actualiza IS 'Cambios realizados durante la última modificación del usuario';


--
-- Name: COLUMN usuarios.usua_cargo_cabecera; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_cargo_cabecera IS 'Cargo que se muestra cuando se selecciona al usuario como destinatario';


--
-- Name: COLUMN usuarios.usua_sumilla; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_sumilla IS 'Iniciales del usuario utilizadas cuando este tiene responsabilidad en la elaboración de un documento';


--
-- Name: COLUMN usuarios.usua_responsable_area; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_responsable_area IS 'Indica que el usuario es responsable del area, razón por la cual la inicial de sus sumilla se
visualizará en todos los documentos generados en el área y con mayúsculas';


--
-- Name: COLUMN usuarios.inst_nombre; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.inst_nombre IS 'Nombre de la institución a la que pertenece el usuario';


--
-- Name: COLUMN usuarios.usua_tipo_certificado; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_tipo_certificado IS 'Id del tipo de certificado digital que posee';


--
-- Name: COLUMN usuarios.visible_sub; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.visible_sub IS 'Indica si el usuario ha sido subrogado';


--
-- Name: COLUMN usuarios.usua_subrogado; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_subrogado IS 'Id del usuario subrogado';


--
-- Name: COLUMN usuarios.usua_celular; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.usua_celular IS 'No. del teléfono celular';


--
-- Name: COLUMN usuarios.tipo_identificacion; Type: COMMENT; Schema: public; Owner: quipux_ucuenca
--

COMMENT ON COLUMN public.usuarios.tipo_identificacion IS '0 cedula  1 pasaporte';


--
-- Name: ciudadano pk_ciu_codigo; Type: CONSTRAINT; Schema: public; Owner: quipux_ucuenca
--

ALTER TABLE ONLY public.ciudadano
    ADD CONSTRAINT pk_ciu_codigo PRIMARY KEY (ciu_codigo);


--
-- Name: ciudad pk_ciudad; Type: CONSTRAINT; Schema: public; Owner: quipux_ucuenca
--

ALTER TABLE ONLY public.ciudad
    ADD CONSTRAINT pk_ciudad PRIMARY KEY (id);


--
-- Name: dependencia pk_dependencia; Type: CONSTRAINT; Schema: public; Owner: quipux_ucuenca
--

ALTER TABLE ONLY public.dependencia
    ADD CONSTRAINT pk_dependencia PRIMARY KEY (depe_codi);


--
-- Name: institucion pk_institucion; Type: CONSTRAINT; Schema: public; Owner: quipux_ucuenca
--

ALTER TABLE ONLY public.institucion
    ADD CONSTRAINT pk_institucion PRIMARY KEY (inst_codi);


--
-- Name: usuarios pk_usua_codi; Type: CONSTRAINT; Schema: public; Owner: quipux_ucuenca
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT pk_usua_codi PRIMARY KEY (usua_codi);


--
-- Name: fki_depe_codi; Type: INDEX; Schema: public; Owner: quipux_ucuenca
--

CREATE INDEX fki_depe_codi ON public.usuarios USING btree (depe_codi) WITH (fillfactor='70');


--
-- Name: idx_ciudadano_ciu_cedula; Type: INDEX; Schema: public; Owner: quipux_ucuenca
--

CREATE INDEX idx_ciudadano_ciu_cedula ON public.ciudadano USING btree (ciu_cedula);


--
-- Name: idx_dependencia_depe_nombre; Type: INDEX; Schema: public; Owner: quipux_ucuenca
--

CREATE INDEX idx_dependencia_depe_nombre ON public.dependencia USING btree (depe_nomb);


--
-- Name: idx_inst_codi; Type: INDEX; Schema: public; Owner: quipux_ucuenca
--

CREATE UNIQUE INDEX idx_inst_codi ON public.institucion USING btree (inst_codi) WITH (fillfactor='90');


--
-- Name: idx_login; Type: INDEX; Schema: public; Owner: quipux_ucuenca
--

CREATE INDEX idx_login ON public.usuarios USING btree (usua_login) WITH (fillfactor='70');


--
-- Name: idx_nombre; Type: INDEX; Schema: public; Owner: quipux_ucuenca
--

CREATE INDEX idx_nombre ON public.institucion USING btree (inst_nombre) WITH (fillfactor='90');


--
-- Name: idx_usua_cedula; Type: INDEX; Schema: public; Owner: quipux_ucuenca
--

CREATE INDEX idx_usua_cedula ON public.usuarios USING btree (usua_cedula) WITH (fillfactor='70');


--
-- Name: idx_usua_esta; Type: INDEX; Schema: public; Owner: quipux_ucuenca
--

CREATE INDEX idx_usua_esta ON public.usuarios USING btree (usua_esta) WITH (fillfactor='70');


--
-- Name: idx_usuarios_usua_cedula; Type: INDEX; Schema: public; Owner: quipux_ucuenca
--

CREATE INDEX idx_usuarios_usua_cedula ON public.usuarios USING btree (usua_cedula);


--
-- Name: ind_ciu_codigo; Type: INDEX; Schema: public; Owner: quipux_ucuenca
--

CREATE UNIQUE INDEX ind_ciu_codigo ON public.ciudadano USING btree (ciu_codigo) WITH (fillfactor='90');


--
-- Name: ind_ciudad; Type: INDEX; Schema: public; Owner: quipux_ucuenca
--

CREATE UNIQUE INDEX ind_ciudad ON public.ciudad USING btree (id) WITH (fillfactor='100');


--
-- Name: pk_depe; Type: INDEX; Schema: public; Owner: quipux_ucuenca
--

CREATE UNIQUE INDEX pk_depe ON public.dependencia USING btree (depe_codi) WITH (fillfactor='80');


--
-- Name: pkusuarios; Type: INDEX; Schema: public; Owner: quipux_ucuenca
--

CREATE UNIQUE INDEX pkusuarios ON public.usuarios USING btree (usua_codi) WITH (fillfactor='70');


--
-- Name: ciudad trig_actualizar_view_usuario_ciudad; Type: TRIGGER; Schema: public; Owner: quipux_ucuenca
--

CREATE TRIGGER trig_actualizar_view_usuario_ciudad AFTER UPDATE ON public.ciudad FOR EACH ROW EXECUTE FUNCTION public.func_actualizar_view_usuario_ciudad();


--
-- Name: ciudadano trig_actualizar_view_usuario_ciudadano; Type: TRIGGER; Schema: public; Owner: quipux_ucuenca
--

CREATE TRIGGER trig_actualizar_view_usuario_ciudadano AFTER INSERT OR DELETE OR UPDATE ON public.ciudadano FOR EACH ROW EXECUTE FUNCTION public.func_actualizar_view_usuario_ciudadano();


--
-- Name: dependencia trig_actualizar_view_usuario_dependencia; Type: TRIGGER; Schema: public; Owner: quipux_ucuenca
--

CREATE TRIGGER trig_actualizar_view_usuario_dependencia AFTER UPDATE ON public.dependencia FOR EACH ROW EXECUTE FUNCTION public.func_actualizar_view_usuario_dependencia();


--
-- Name: institucion trig_actualizar_view_usuario_institucion; Type: TRIGGER; Schema: public; Owner: quipux_ucuenca
--

CREATE TRIGGER trig_actualizar_view_usuario_institucion AFTER UPDATE ON public.institucion FOR EACH ROW EXECUTE FUNCTION public.func_actualizar_view_usuario_institucion();


--
-- Name: usuarios trig_actualizar_view_usuario_usuarios; Type: TRIGGER; Schema: public; Owner: quipux_ucuenca
--

CREATE TRIGGER trig_actualizar_view_usuario_usuarios AFTER INSERT OR UPDATE ON public.usuarios FOR EACH ROW EXECUTE FUNCTION public.func_actualizar_view_usuario_usuarios();


--
-- Name: usuarios fk_depe_codi; Type: FK CONSTRAINT; Schema: public; Owner: quipux_ucuenca
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT fk_depe_codi FOREIGN KEY (depe_codi) REFERENCES public.dependencia(depe_codi);


--
-- Name: dependencia fk_dependencia_dep_central; Type: FK CONSTRAINT; Schema: public; Owner: quipux_ucuenca
--

ALTER TABLE ONLY public.dependencia
    ADD CONSTRAINT fk_dependencia_dep_central FOREIGN KEY (dep_central) REFERENCES public.dependencia(depe_codi);


--
-- Name: dependencia fk_dependencia_depe_codi_padre; Type: FK CONSTRAINT; Schema: public; Owner: quipux_ucuenca
--

ALTER TABLE ONLY public.dependencia
    ADD CONSTRAINT fk_dependencia_depe_codi_padre FOREIGN KEY (depe_codi_padre) REFERENCES public.dependencia(depe_codi);


--
-- Name: dependencia fk_dependencia_depe_plantilla; Type: FK CONSTRAINT; Schema: public; Owner: quipux_ucuenca
--

ALTER TABLE ONLY public.dependencia
    ADD CONSTRAINT fk_dependencia_depe_plantilla FOREIGN KEY (depe_plantilla) REFERENCES public.dependencia(depe_codi);


--
-- Name: dependencia fk_dependencia_institucion; Type: FK CONSTRAINT; Schema: public; Owner: quipux_ucuenca
--

ALTER TABLE ONLY public.dependencia
    ADD CONSTRAINT fk_dependencia_institucion FOREIGN KEY (inst_codi) REFERENCES public.institucion(inst_codi);


--
-- Name: usuarios fk_usuario_institucion; Type: FK CONSTRAINT; Schema: public; Owner: quipux_ucuenca
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT fk_usuario_institucion FOREIGN KEY (inst_codi) REFERENCES public.institucion(inst_codi);


--
-- Name: usuarios fk_usuarios_tipo_certificado; Type: FK CONSTRAINT; Schema: public; Owner: quipux_ucuenca
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT fk_usuarios_tipo_certificado FOREIGN KEY (usua_tipo_certificado) REFERENCES public.tipo_certificado(tipo_cert_codi);


--
-- PostgreSQL database dump complete
--

\unrestrict ySaoBfyWLeeuCapQV0IbPs3ZhJQVPGd6FzdkR2SrRj1GdNk3VSlBbG1VoWsVNbH

