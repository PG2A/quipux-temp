#!/usr/bin/env bash
# ==============================================================================
# Quipux — Despliegue de Solicitud y aprobación de ciudadanos (RQT-7)
# ==============================================================================
# Ejecuta, en orden, todo lo necesario para dejar la base lista:
#
#   1. Verificación previa (conexión y tablas)
#   2. Esquema   (01_esquema_solicitud_ciudadano.sql) -> tabla solicitud_ciudadano
#                                                        + transacciones 89/90 del histórico
#   3. Permiso   (02_permiso_aprobar_ciudadano.sql)   -> perm_aprobar_ciudadano
#   4. Verificación
#
# Uso:
#   ./db/ciudadanos_solicitud/ejecutar_migracion.sh             # aplica todo
#   ./db/ciudadanos_solicitud/ejecutar_migracion.sh --dry-run   # solo comprueba y muestra
#
# Los .sql son idempotentes: repetir la ejecución no duplica ni pisa nada.
# Las credenciales se leen de .env y nunca se imprimen.
# ==============================================================================

set -euo pipefail

DRY_RUN=0
for arg in "$@"; do
    case "$arg" in
        --dry-run) DRY_RUN=1 ;;
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
# Conexión
# ------------------------------------------------------------------------------
[ -f "$RAIZ/.env" ] || fallo "No se encontró $RAIZ/.env"

# Lee una clave del .env tolerando espacios, CRLF y valores entrecomillados.
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

# Los .sql están en UTF-8; sin esto psql en Windows los lee como WIN1252.
export PGCLIENTENCODING=UTF8

[ -n "$DB_NAME" ] || fallo "DB_NAME vacío en .env"

# Selección del cliente psql: se prueba cuál consigue conectar de verdad (un
# cliente antiguo no negocia scram-sha-256). Para forzar uno:
#   QUIPUX_PSQL=/ruta/psql ./ejecutar_migracion.sh
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
detalle_errores=""
for cand in "${CANDIDATOS[@]}"; do
    if salida="$("$cand" -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" \
                        -tAc 'select 1' 2>&1)"; then
        PSQL="$cand"
        break
    fi
    detalle_errores="$detalle_errores  $cand
      $(printf '%s' "$salida" | head -1)
"
done

if [ -z "$PSQL" ]; then
    rojo "Ningún cliente psql logró conectar con $DB_NAME@$DB_HOST:$DB_PORT"
    printf '%s' "$detalle_errores"
    if [ ! -d /c ] && [ -d /mnt/c ]; then
        echo
        rojo "Parece que está ejecutando dentro de WSL. Use Git Bash:"
        echo "  PowerShell:  & \"C:\\Program Files\\Git\\bin\\bash.exe\" ejecutar_migracion.sh"
        echo "  Git Bash  :  bash ejecutar_migracion.sh"
    fi
    fallo "Revise DB_HOST/DB_PORT/DB_USER/DB_PASS en .env, o fije QUIPUX_PSQL=/ruta/psql"
fi

PSQL_ARGS=(-h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -v ON_ERROR_STOP=1)

sql()       { "$PSQL" "${PSQL_ARGS[@]}" -c "$1"; }
sql_valor() { "$PSQL" "${PSQL_ARGS[@]}" -tA -c "$1"; }
sql_file()  { local f="$1"; shift; "$PSQL" "${PSQL_ARGS[@]}" "$@" -f "$f"; }

echo "=============================================================="
echo " Solicitud y aprobacion de ciudadanos (RQT-7)"
echo " Base  : $DB_NAME @ $DB_HOST:$DB_PORT (usuario $DB_USER)"
echo " psql  : $PSQL"
echo " Modo  : $([ $DRY_RUN -eq 1 ] && echo 'DRY-RUN (no modifica nada)' || echo 'EJECUCION REAL')"
echo "=============================================================="

# ------------------------------------------------------------------------------
paso "1/4  Verificación previa"
# ------------------------------------------------------------------------------
faltantes="$(sql_valor "
select coalesce(string_agg(t, ', '), '')
  from unnest(array['ciudadano','usuario','radicado','permiso','permiso_usuario','sgd_ttr_transaccion','hist_eventos']) t
 where to_regclass(t) is null;")"
[ -z "$faltantes" ] || fallo "Faltan tablas en la base: $faltantes"

verde "Conexión correcta. Tablas base presentes."

echo
echo "Estado actual:"
sql "
select (select count(*) from ciudadano where ciu_estado = 2)                     as ciudadanos_pendientes
     , to_regclass('solicitud_ciudadano') is not null                            as tabla_solicitud
     , exists (select 1 from permiso where nombre = 'perm_aprobar_ciudadano')    as permiso_creado
     , exists (select 1 from sgd_ttr_transaccion where sgd_ttr_codigo in (89,90)) as transacciones_hist;"

if [ $DRY_RUN -eq 1 ]; then
    echo
    verde "DRY-RUN: la comprobación termina aquí. No se modificó nada."
    exit 0
fi

# ------------------------------------------------------------------------------
paso "2/4  Esquema"
# ------------------------------------------------------------------------------
sql_file "$DIR_SCRIPT/01_esquema_solicitud_ciudadano.sql"
verde "Tabla solicitud_ciudadano y transacciones 89/90 disponibles."

# ------------------------------------------------------------------------------
paso "3/4  Permiso"
# ------------------------------------------------------------------------------
sql_file "$DIR_SCRIPT/02_permiso_aprobar_ciudadano.sql"
verde "Permiso perm_aprobar_ciudadano disponible."

# ------------------------------------------------------------------------------
paso "4/4  Verificación"
# ------------------------------------------------------------------------------
sql "
select p.id_permiso, p.nombre, p.descripcion, p.perfil, p.estado
     , (select count(*) from permiso_usuario pu where pu.id_permiso = p.id_permiso) as usuarios_con_permiso
  from permiso p
 where p.nombre = 'perm_aprobar_ciudadano';"

paso "Resultado"
verde "Despliegue completado."
echo
echo "SIGUIENTE PASO"
echo "--------------"
echo "1. Asigne el permiso 'Aprobar solicitudes de nuevos ciudadanos' (grupo"
echo "   Administracion) a los usuarios que resolveran las solicitudes:"
echo "   Administracion -> Usuarios internos -> Editar -> Permisos. Deben volver"
echo "   a iniciar sesion. O directo en la base:"
echo "     insert into permiso_usuario (id_permiso, usua_codi)"
echo "     select id_permiso, <usua_codi> from permiso where nombre='perm_aprobar_ciudadano';"
echo "2. Prueba: redacte un documento, en Buscar De/Para elija Tipo de Usuario ="
echo "   Ciudadano y pulse 'Solicitar Ciudadano'. Complete el formulario; el"
echo "   ciudadano vuelve al popup como destinatario '(pendiente de aprobacion)'."
echo "   Guarde el documento: 'Firmar y Enviar' queda bloqueado hasta la aprobacion."
echo "3. Con un aprobador: Administracion -> Solicitudes de ciudadanos -> Aprobar."
echo "   El ciudadano recibe usuario y clave (su cedula); el solicitante recibe el"
echo "   aviso y ya puede enviar el documento."
