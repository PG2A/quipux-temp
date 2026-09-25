-- ==============================================================================
-- Quipux Ucuenca: Recreate 'usuario' VIEW
-- ==============================================================================
-- This script safely drops the static 'usuario' table (if it exists) and 
-- restores the official Quipux dynamic VIEW 'usuario'.
-- This natively links the 'usuarios' (internal) and 'ciudadano' (external) 
-- tables, ensuring all PHP system queries like `SELECT * FROM usuario` 
-- instantly query real-time data from the underlying physical tables.
-- ==============================================================================

-- Instead of dropping the static table, we securely RENAME IT to a backup.
-- This guarantees absolutely zero data loss in case you need to verify anything later.
ALTER TABLE IF EXISTS "usuario" RENAME TO "usuario_backup_2026";
DROP VIEW IF EXISTS "usuario" CASCADE;

CREATE OR REPLACE VIEW "usuario" AS 
SELECT 
    u.depe_codi,
    u.usua_tipo_certificado,
    u.usua_subrogado,
    u.inst_codi,
    u.visible_sub,
    u.cargo_tipo,
    i.inst_estado,
    u.ciu_codi,
    u.usua_nuevo,
    u.tipo_identificacion,
    u.usua_esta,
    0 AS inst_adscrita,
    1 AS tipo_usuario,
    u.usua_codi,
    u.usua_firma_path,
    d.depe_nomb,
    d.dep_sigla,
    i.inst_nombre,
    i.inst_sigla,
    ''::character varying AS usua_ciudad,
    ''::character varying AS usua_datos,
    ''::character varying AS inst_padre_nombre,
    ''::character varying AS inst_padre_sigla,
    u.usua_cedula,
    u.usua_nomb,
    u.usua_apellido,
    (u.usua_nomb::text || ' '::text) || u.usua_apellido::text AS usua_nombre,
    u.usua_cargo,
    u.usua_login,
    u.usua_pasw,
    u.usua_email,
    u.usua_titulo,
    u.usua_abr_titulo,
    u.usua_cargo_cabecera,
    u.usua_direccion,
    u.usua_telefono
FROM usuarios u
LEFT JOIN dependencia d ON u.depe_codi = d.depe_codi
LEFT JOIN institucion i ON u.inst_codi = i.inst_codi
UNION
SELECT 
    0 AS depe_codi,
    0 AS usua_tipo_certificado,
    0 AS usua_subrogado,
    0 AS inst_codi,
    0 AS visible_sub,
    0 AS cargo_tipo,
    1 AS inst_estado,
    c.ciu_codigo AS ciu_codi,
    c.ciu_nuevo AS usua_nuevo,
    0 AS tipo_identificacion,
    c.ciu_estado AS usua_esta,
    0 AS inst_adscrita,
    2 AS tipo_usuario,
    c.ciu_codigo AS usua_codi,
    ''::character varying AS usua_firma_path,
    ''::character varying AS depe_nomb,
    ''::character varying AS dep_sigla,
    c.ciu_empresa AS inst_nombre,
    ''::character varying AS inst_sigla,
    ''::character varying AS usua_ciudad,
    ''::character varying AS usua_datos,
    ''::character varying AS inst_padre_nombre,
    ''::character varying AS inst_padre_sigla,
    c.ciu_cedula AS usua_cedula,
    c.ciu_nombre AS usua_nomb,
    c.ciu_apellido AS usua_apellido,
    (c.ciu_nombre::text || ' '::text) || c.ciu_apellido::text AS usua_nombre,
    c.ciu_cargo AS usua_cargo,
    c.ciu_cedula AS usua_login,
    c.ciu_pasw AS usua_pasw,
    c.ciu_email AS usua_email,
    c.ciu_titulo AS usua_titulo,
    c.ciu_abr_titulo AS usua_abr_titulo,
    ''::character varying AS usua_cargo_cabecera,
    c.ciu_direccion AS usua_direccion,
    c.ciu_telefono AS usua_telefono
FROM ciudadano c;
