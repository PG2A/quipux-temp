# Migraciones de base de datos

Quipux no ejecuta migraciones automáticas. Cada cambio de esquema se entrega aquí como un archivo SQL
idempotente, nombrado `AAAA-MM-DD_NN_descripcion.sql`, y se ejecuta a mano en el orden de los nombres:

```
psql -h <host> -U <usuario> -d quipux_ucuenca -f sql/migraciones/2026-09-03_01_membretes.sql
```

Cada archivo termina con el bloque de reversión comentado. El volcado completo de referencia sigue en
`.docs/estructura_db.sql`.

| Archivo | Qué crea | Requerido por |
|---|---|---|
| `2026-09-03_01_membretes.sql` | `membrete`, `membrete_asignacion`, `log_membrete` | Administración → Hojas Membretadas (RQ-T8) |
| `2026-09-04_02_membretes_por_tipo.sql` | `membrete_asignacion.depe_codi` nulo = asignación por tipo de documento para todas las áreas; índice único y trigger actualizados | Hojas Membretadas → Personalizar → Por tipos de documento |
