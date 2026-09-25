-- ==============================================================================
-- Quipux: Solicitud y aprobación de nuevos ciudadanos (RQT-7) — Permiso
-- ==============================================================================
-- Este script es IDEMPOTENTE.
--
-- Crea el permiso 'perm_aprobar_ciudadano'. Los permisos se cargan a la sesión
-- por su columna 'nombre' (login.php), así que basta con esta fila para que
-- $_SESSION["perm_aprobar_ciudadano"] exista, y aparece solo en la pestaña de
-- permisos del usuario (util_ciudadano.php agrupa por 'perfil'; 4 = Administración).
--
-- Se elige el primer id_permiso libre a partir del 37 (el catálogo termina en 36
-- en esta base y la secuencia permisos_id_permiso_seq no está sincronizada).
-- ==============================================================================

SET client_encoding TO 'UTF8';

BEGIN;

DO $$
DECLARE
    v_id    integer;
    v_orden integer;
BEGIN
    IF EXISTS (SELECT 1 FROM permiso WHERE nombre = 'perm_aprobar_ciudadano') THEN
        RAISE NOTICE 'El permiso perm_aprobar_ciudadano ya existe; no se hace nada.';
        RETURN;
    END IF;

    SELECT COALESCE(MAX(id_permiso), 36) + 1, COALESCE(MAX(orden), 36) + 1
      INTO v_id, v_orden
      FROM permiso;

    INSERT INTO permiso (id_permiso, nombre, descripcion, descripcion_larga, perfil, orden, estado)
    VALUES (v_id,
            'perm_aprobar_ciudadano',
            'Aprobar solicitudes de nuevos ciudadanos',
            'Permite ver, aprobar o rechazar los ciudadanos registrados desde la búsqueda de destinatarios (Para/Copia) antes de que puedan usarse.',
            4, v_orden, 1);

    -- Mantener la secuencia por encima del máximo, por si alguna pantalla la usa.
    PERFORM setval('permisos_id_permiso_seq', (SELECT MAX(id_permiso) FROM permiso), true);

    RAISE NOTICE 'Permiso perm_aprobar_ciudadano creado con id_permiso = %', v_id;
END $$;

COMMIT;
