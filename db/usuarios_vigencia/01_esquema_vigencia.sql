-- ==============================================================================
-- Quipux: vigencia de las cuentas de usuario interno (fecha inicio / fecha fin)
-- ==============================================================================
-- usuarios.usua_vigencia_desde / usua_vigencia_hasta (date, NULL = sin límite).
-- Una cuenta está VIGENTE si hoy está dentro del rango. Fuera de vigencia:
--   * no se puede usar para entrar ni aparece en el combo "Usuario:"
--     (login.php, AuthenticationManager.php, cargo_usuario.php, reiniciar_session.php)
--   * el cron cron/procesar_vigencia_usuarios.php desactiva (usua_esta=0) las que
--     ya pasaron su fecha fin.
-- Se usan nombres distintos a usua_fecha_inicio/fin, que ya existen en
-- usuarios_subrogacion y chocarían en los joins.
-- Idempotente.
-- ==============================================================================
SET client_encoding TO 'UTF8';
BEGIN;

ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS usua_vigencia_desde date;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS usua_vigencia_hasta date;
COMMENT ON COLUMN usuarios.usua_vigencia_desde IS 'Inicio de vigencia de la cuenta (NULL = sin límite).';
COMMENT ON COLUMN usuarios.usua_vigencia_hasta IS 'Fin de vigencia de la cuenta; el cron la desactiva al pasar esta fecha (NULL = sin límite).';

DO $$ BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_usuarios_vigencia') THEN
        ALTER TABLE usuarios ADD CONSTRAINT ck_usuarios_vigencia
            CHECK (usua_vigencia_desde IS NULL OR usua_vigencia_hasta IS NULL OR usua_vigencia_hasta >= usua_vigencia_desde);
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_usuarios_vigencia_hasta ON usuarios (usua_vigencia_hasta)
    WHERE usua_vigencia_hasta IS NOT NULL;

-- Regla única de vigencia. Las cuentas que no están en 'usuarios' (ciudadanos)
-- se consideran siempre vigentes.
CREATE OR REPLACE FUNCTION usuario_vigente(p_usua integer)
RETURNS boolean LANGUAGE sql STABLE AS $$
    SELECT coalesce(
        (SELECT (usua_vigencia_desde IS NULL OR usua_vigencia_desde <= current_date)
            AND (usua_vigencia_hasta IS NULL OR usua_vigencia_hasta >= current_date)
           FROM usuarios WHERE usua_codi = p_usua),
        true);
$$;

COMMIT;
