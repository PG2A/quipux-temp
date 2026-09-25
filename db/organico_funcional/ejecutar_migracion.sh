#!/usr/bin/env bash
# ==============================================================================
# Quipux — Orgánico funcional: catálogo de puestos y jerarquía real de áreas
#          Piloto: Facultad de Ciencias Médicas
# ==============================================================================
# Ejecuta, en orden:
#
#   1. Verificación previa (conexión, tablas, estado actual de la FCM)
#   2. Esquema            (01_esquema_cargo.sql)     tabla cargo, depe_ruta(), triggers
#   3. Jerarquía FCM      (02_fcm_jerarquia.sql)     padre real + nombre corto
#   4. Puestos FCM        (03_fcm_cargos.sql)        catálogo, cargo_id, movimientos
#   5. Verificación       (04_verificacion.sql)
#
# Uso:
#   ./db/organico_funcional/ejecutar_migracion.sh              # ejecuta todo
#   ./db/organico_funcional/ejecutar_migracion.sh --dry-run    # solo comprueba y muestra
#   ./db/organico_funcional/ejecutar_migracion.sh --solo-esquema
#
# Respaldos en la propia base: organico_respaldo_dependencia, organico_respaldo_usuarios.
# Las credenciales se leen de .env y nunca se imprimen.
# ==============================================================================

set -euo pipefail

DRY_RUN=0
SOLO_ESQUEMA=0
for arg in "$@"; do
    case "$arg" in
        --dry-run)      DRY_RUN=1 ;;
        --solo-esquema) SOLO_ESQUEMA=1 ;;
        *) echo "Opción desconocida: $arg"; exit 2 ;;
    esac
done

DIR_SCRIPT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
RAIZ="$(cd "$DIR_SCRIPT/../.." && pwd)"
cd "$RAIZ"

rojo()  { printf '\033[31m%s\033[0m\n' "$*"; }
verde() { printf '\033[32m%s\033[0m\n' "$*"; }
paso()  { printf '\n\033[1;36m== %s\033[0m\n' "$*"; }
fallo() { rojo "ERROR: $*"; exit 1; }

# ------------------------------------------------------------------------------
# Conexión (misma lógica que db/subrogacion/ejecutar_migracion.sh)
# ------------------------------------------------------------------------------
[ -f "$RAIZ/.env" ] || fallo "No se encontró $RAIZ/.env"

leer_env() {
    grep -E "^[[:space:]]*$1[[:space:]]*=" "$RAIZ/.env" \
        | head -1 \
        | cut -d= -f2- \
        | tr -d '\r' \
        | sed -e 's/^[[:space:]]*//' \
              -e 's/[[:space:]]*$//' \
              -e 's/^"\(.*\)"$/\1/' \
              -e "s/^'\(.*\)'\$/\1/"
}

DB_HOST="$(leer_env DB_HOST)"
DB_PORT="$(leer_env DB_PORT)"
DB_NAME="$(leer_env DB_NAME)"
DB_USER="$(leer_env DB_USER)"
PGPASSWORD="$(leer_env DB_PASS)"
export PGPASSWORD
export PGCLIENTENCODING=UTF8

[ -n "$DB_NAME" ] || fallo "DB_NAME vacío en .env"

CANDIDATOS=()
[ -n "${QUIPUX_PSQL:-}" ] && CANDIDATOS+=("$QUIPUX_PSQL")
ruta_path="$(command -v psql 2>/dev/null || true)"
[ -n "$ruta_path" ] && CANDIDATOS+=("$ruta_path")
for v in 17 16 15 14 13; do
    cand="/c/Program Files/PostgreSQL/$v/bin/psql"
    [ -x "$cand" ] && CANDIDATOS+=("$cand")
done
[ ${#CANDIDATOS[@]} -gt 0 ] || fallo "No se encontró ningún cliente psql. Añádalo al PATH o defina QUIPUX_PSQL."

PSQL=""
for cand in "${CANDIDATOS[@]}"; do
    if "$cand" -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -tAc 'select 1' >/dev/null 2>&1; then
        PSQL="$cand"; break
    fi
done
[ -n "$PSQL" ] || fallo "Ningún cliente psql logró conectar con $DB_NAME@$DB_HOST:$DB_PORT. Revise .env o fije QUIPUX_PSQL."

PSQL_ARGS=(-h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -v ON_ERROR_STOP=1)
sql()       { "$PSQL" "${PSQL_ARGS[@]}" -c "$1"; }
sql_valor() { "$PSQL" "${PSQL_ARGS[@]}" -tA -c "$1"; }
sql_file()  { "$PSQL" "${PSQL_ARGS[@]}" -f "$1"; }

echo "=============================================================="
echo " Orgánico funcional — piloto Facultad de Ciencias Médicas"
echo " Base  : $DB_NAME @ $DB_HOST:$DB_PORT (usuario $DB_USER)"
echo " psql  : $PSQL"
echo " Modo  : $([ $DRY_RUN -eq 1 ] && echo 'DRY-RUN (no modifica nada)' || echo 'EJECUCION REAL')"
echo "=============================================================="

# ------------------------------------------------------------------------------
paso "1/5  Verificación previa"
# ------------------------------------------------------------------------------
faltantes="$(sql_valor "
select coalesce(string_agg(t, ', '), '')
  from unnest(array['usuarios','usuario','dependencia','institucion','formato_numeracion','log_view_usuario']) t
 where to_regclass(t) is null;")"
[ -z "$faltantes" ] || fallo "Faltan tablas en la base: $faltantes"

existe_fcm="$(sql_valor "select count(1) from dependencia where depe_codi = 112 and inst_codi = 3;")"
[ "$existe_fcm" = "1" ] || fallo "No existe la dependencia 112 (FACULTAD DE CIENCIAS MÉDICAS) en la institución 3."
verde "Conexión correcta y tablas requeridas presentes."

echo
echo "Estado actual de la FCM:"
sql "
select count(*) filter (where depe_codi_padre = 3)   as cuelgan_de_la_raiz
     , count(*) filter (where depe_codi_padre = 112) as cuelgan_de_la_fcm
     , count(*) filter (where depe_nomb like '% - %') as con_nombre_plano
     , (select count(*) from usuarios u where u.usua_esta = 1 and u.depe_codi in (select depe_codi from dependencia where depe_nomb like 'FACULTAD DE CIENCIAS M%DICAS%')) as usuarios_activos
     , (select count(*) from usuarios where cargo_id is not null) as usuarios_con_cargo_id
     , (to_regclass('cargo') is not null) as existe_tabla_cargo
  from dependencia
 where inst_codi = 3 and depe_nomb like 'FACULTAD DE CIENCIAS M%DICAS - %';"

if [ $DRY_RUN -eq 1 ]; then
    echo
    verde "DRY-RUN: la comprobación termina aquí. No se modificó nada."
    exit 0
fi

# ------------------------------------------------------------------------------
paso "2/5  Esquema: tabla cargo, depe_ruta(), triggers"
# ------------------------------------------------------------------------------
sql_file "$DIR_SCRIPT/01_esquema_cargo.sql"
verde "Esquema aplicado."

if [ $SOLO_ESQUEMA -eq 1 ]; then
    verde "--solo-esquema: se omiten los datos de la FCM."
    exit 0
fi

# ------------------------------------------------------------------------------
paso "3/5  Jerarquía real de la FCM"
# ------------------------------------------------------------------------------
sql_file "$DIR_SCRIPT/02_fcm_jerarquia.sql"
verde "Jerarquía aplicada."

# ------------------------------------------------------------------------------
paso "4/5  Catálogo de puestos y usuarios de la FCM"
# ------------------------------------------------------------------------------
sql_file "$DIR_SCRIPT/03_fcm_cargos.sql"
verde "Puestos aplicados."

# ------------------------------------------------------------------------------
paso "5/5  Verificación"
# ------------------------------------------------------------------------------
sql_file "$DIR_SCRIPT/04_verificacion.sql"

errores="$(sql_valor "select count(*) from log_view_usuario where fecha > now() - interval '1 hour';")"
discrep="$(sql_valor "select count(*) from usuario u where u.depe_codi in (select depe_descendientes(112)) and u.depe_nomb is distinct from depe_ruta(u.depe_codi);")"
sin_cargo="$(sql_valor "select count(*) from usuarios u where u.usua_esta = 1 and u.cargo_id is null and u.depe_codi in (select depe_descendientes(112));")"

echo
if [ "$errores" = "0" ] && [ "$discrep" = "0" ] && [ "$sin_cargo" = "0" ]; then
    verde "Piloto FCM aplicado sin incidencias."
else
    rojo "Revisar: errores de trigger=$errores, discrepancias depe_nomb=$discrep, usuarios FCM sin cargo_id=$sin_cargo"
    exit 1
fi
