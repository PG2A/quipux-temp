-- ==============================================================================
-- Quipux Ucuenca: Sync Trigger for 'usuarios' -> 'usuario' Table
-- ==============================================================================
-- Since 'usuario' is strictly a physical table in this RDS database rather 
-- than a view, this trigger automatically copies any new or updated internal 
-- users from the 'usuarios' table over to the 'usuario' table so they can
-- instantly be searched and emailed properly by the system.
-- ==============================================================================

-- 1. Create the synchronization function
CREATE OR REPLACE FUNCTION sync_usuario_from_usuarios()
RETURNS TRIGGER AS $$
BEGIN
    -- If updating or already exists, delete the old row from the 'usuario' static table
    DELETE FROM usuario WHERE usua_codi = NEW.usua_codi AND tipo_usuario = 1;

    -- Insert the freshly saved row into the 'usuario' table
    INSERT INTO usuario (
        usua_codi, usua_nomb, usua_apellido, usua_nombre, usua_login, usua_pasw, 
        usua_esta, usua_nuevo, usua_email, inst_codi, depe_codi, 
        tipo_identificacion, usua_cedula, usua_titulo, usua_abr_titulo, 
        usua_cargo, usua_cargo_cabecera, usua_direccion, usua_telefono, 
        tipo_usuario, usua_firma_path, inst_nombre, depe_nomb
    ) VALUES (
        NEW.usua_codi, 
        NEW.usua_nomb, 
        NEW.usua_apellido, 
        (NEW.usua_nomb || ' ' || NEW.usua_apellido), 
        NEW.usua_login, 
        NEW.usua_pasw,
        NEW.usua_esta, 
        NEW.usua_nuevo, 
        NEW.usua_email, 
        NEW.inst_codi, 
        NEW.depe_codi,
        NEW.tipo_identificacion, 
        NEW.usua_cedula, 
        NEW.usua_titulo, 
        NEW.usua_abr_titulo,
        NEW.usua_cargo, 
        NEW.usua_cargo_cabecera, 
        NEW.usua_direccion, 
        NEW.usua_telefono,
        1, -- 1 designates an internal public servant (tipo_usuario)
        NEW.usua_firma_path,
        (SELECT inst_nombre FROM institucion WHERE inst_codi = NEW.inst_codi LIMIT 1),
        (SELECT depe_nomb FROM dependencia WHERE depe_codi = NEW.depe_codi LIMIT 1)
    );
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- 2. Bind the trigger to the 'usuarios' table
DROP TRIGGER IF EXISTS trg_sync_usuario_usuarios ON usuarios;

CREATE TRIGGER trg_sync_usuario_usuarios
AFTER INSERT OR UPDATE ON usuarios
FOR EACH ROW
EXECUTE FUNCTION sync_usuario_from_usuarios();

-- ==============================================================================
-- 3. MANUAL SYNC FOR ALREADY BROKEN USERS (Executes once)
-- ==============================================================================
-- This manually pushes the user you just created (U0940811128) into the table
-- so you don't have to recreate them in PHP!

DELETE FROM usuario WHERE usua_codi = 39016 AND tipo_usuario = 1;

INSERT INTO usuario (
    usua_codi, usua_nomb, usua_apellido, usua_nombre, usua_login, usua_pasw, 
    usua_esta, usua_nuevo, usua_email, inst_codi, depe_codi, 
    tipo_identificacion, usua_cedula, usua_titulo, usua_abr_titulo, 
    usua_cargo, usua_cargo_cabecera, usua_direccion, usua_telefono, 
    tipo_usuario, usua_firma_path, inst_nombre, depe_nomb
) 
SELECT 
    u.usua_codi, u.usua_nomb, u.usua_apellido, (u.usua_nomb || ' ' || u.usua_apellido), u.usua_login, u.usua_pasw, 
    u.usua_esta, u.usua_nuevo, u.usua_email, u.inst_codi, u.depe_codi, 
    u.tipo_identificacion, u.usua_cedula, u.usua_titulo, u.usua_abr_titulo, 
    u.usua_cargo, u.usua_cargo_cabecera, u.usua_direccion, u.usua_telefono, 
    1, u.usua_firma_path, i.inst_nombre, d.depe_nomb
FROM usuarios u
LEFT JOIN institucion i ON u.inst_codi = i.inst_codi
LEFT JOIN dependencia d ON u.depe_codi = d.depe_codi
WHERE u.usua_codi = 39016;
