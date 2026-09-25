-- ==============================================================================
-- Quipux Ucuenca: Revert 'usuario' VIEW back to the original TABLE
-- ==============================================================================
-- Run this script on the private UCUENCA database to cleanly undo the View 
-- creation and restore your original physical table from the backup we made.
-- ==============================================================================

-- 1. Drop the View that was just created
DROP VIEW IF EXISTS "usuario" CASCADE;

-- 2. Restore your original physical table from the 2026 backup
ALTER TABLE IF EXISTS "usuario_backup_2026" RENAME TO "usuario";

-- 3. (Optional) Verify the table is back and accessible
-- SELECT COUNT(*) FROM usuario;
