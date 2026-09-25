#!/usr/bin/env bash
# ==============================================================================
# Quipux — Despliegue del rediseño de Subrogación de Puestos
# ==============================================================================
# Ejecuta, en orden, todo lo necesario para dejar la base lista:
#
#   1. Verificación previa (conexión y tablas)
#   2. Esquema             (01_esquema_subrogacion.sql)
#   3. Resolución de subrogaciones ambiguas
#   4. Migración de clones (02_migracion_clones.sql)
#   5. Subrogación de prueba vigente
#   6. Verificación
#   7. Cron de vigencia
#
# Uso:
#   ./db/subrogacion/ejecutar_migracion.sh              # ejecuta todo
#   ./db/subrogacion/ejecutar_migracion.sh --dry-run    # solo comprueba y muestra
#   ./db/subrogacion/ejecutar_migracion.sh --sin-cron   # omite el paso 7
#
# NO modifica contraseñas ni datos de acceso de ningún usuario.
# Las credenciales se leen de .env y nunca se imprimen.
# ==============================================================================

set -euo pipefail

# ------------------------------------------------------------------------------
# Datos específicos de ESTA base — revisar antes de usar en otra instalación
# ------------------------------------------------------------------------------

# Subrogaciones cuya cuenta real no puede deducirse por cédula, porque la persona
# tiene varias cuentas legítimas. Formato: "subrogacion:usua_codi_real".
OVERRIDES=("70:38432")

# Subrogación que se deja vigente para poder probar el flujo en vivo.
# Dejar vacío ("") para no extender ninguna.
SUBROGACION_PRUEBA="71"
DIAS_VIGENCIA=30

# ------------------------------------------------------------------------------

DRY_RUN=0
CORRER_CRON=1
for arg in "$@"; do
    case "$arg" in
        --dry-run)  DRY_RUN=1 ;;
        --sin-cron) CORRER_CRON=0 ;;
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

# Lee una clave del .env tolerando espacios, CRLF y valores entrecomillados
# ("valor" o 'valor'), que es como suelen escribirse las credenciales.
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

# Los .sql están en UTF-8, pero psql en Windows deduce el encoding del cliente de
# la página de códigos de la consola (normalmente WIN1252). Sin esto, un literal
# como 'Jefe de Área' viaja como bytes WIN1252 inválidos y el servidor rechaza la
# conversión con "byte sequence 0x81 ... has no equivalent in encoding UTF8".
export PGCLIENTENCODING=UTF8

[ -n "$DB_NAME" ] || fallo "DB_NAME vacío en .env"

# Selección del cliente psql.
#
# En un mismo equipo pueden convivir varios clientes (el del PATH, el de la
# instalación de PostgreSQL, el de WSL...). No todos sirven: un cliente antiguo
# no negocia scram-sha-256 y falla con "password authentication failed" aunque
# la contraseña sea correcta. Por eso no se elige por orden, sino probando cuál
# consigue conectar de verdad.
#
# Para forzar uno concreto:  QUIPUX_PSQL=/ruta/psql ./ejecutar_migracion.sh
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

    # En Windows, escribir "bash" desde PowerShell suele lanzar el bash de WSL
    # (C:\Windows\System32\bash.exe), que es otro sistema: la unidad C: está en
    # /mnt/c y 127.0.0.1 es su propio loopback, no el de Windows. Desde ahí no se
    # ve el PostgreSQL de Windows, por muy correcta que sea la contraseña.
    if [ ! -d /c ] && [ -d /mnt/c ]; then
        echo
        rojo "Parece que está ejecutando dentro de WSL."
        echo "Este script debe correrse en Git Bash, que sí ve el PostgreSQL de Windows:"
        echo
        echo "  PowerShell:  & \"C:\\Program Files\\Git\\bin\\bash.exe\" ejecutar_migracion.sh"
        echo "  Git Bash  :  bash ejecutar_migracion.sh"
        echo
    fi

    fallo "Revise DB_HOST/DB_PORT/DB_USER/DB_PASS en .env, o fije QUIPUX_PSQL=/ruta/psql"
fi

PSQL_ARGS=(-h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -v ON_ERROR_STOP=1)

sql()       { "$PSQL" "${PSQL_ARGS[@]}" -c "$1"; }
sql_valor() { "$PSQL" "${PSQL_ARGS[@]}" -tA -c "$1"; }
sql_file()  { "$PSQL" "${PSQL_ARGS[@]}" -f "$1"; }

echo "=============================================================="
echo " Rediseño de Subrogación de Puestos"
echo " Base  : $DB_NAME @ $DB_HOST:$DB_PORT (usuario $DB_USER)"
echo " psql  : $PSQL"
echo " Cron  : $([ $CORRER_CRON -eq 1 ] && echo si || echo no)"
echo " Modo  : $([ $DRY_RUN -eq 1 ] && echo 'DRY-RUN (no modifica nada)' || echo 'EJECUCION REAL')"
echo "=============================================================="

# ------------------------------------------------------------------------------
paso "1/7  Verificación previa"
# ------------------------------------------------------------------------------
faltantes="$(sql_valor "
select coalesce(string_agg(t, ', '), '')
  from unnest(array['usuarios','usuarios_subrogacion','radicado','informados',
                    'carpeta','usuarios_sesion','permiso_usuario',
                    'bandeja_compartida','tarea']) t
 where to_regclass(t) is null;")"
[ -z "$faltantes" ] || fallo "Faltan tablas en la base: $faltantes"
verde "Conexión correcta y todas las tablas requeridas existen."

echo
echo "Subrogaciones activas antes de migrar:"
sql "
select s.usua_subrogacion_codi as subr
     , to_char(s.usua_fecha_inicio,'YYYY-MM-DD') as desde
     , to_char(s.usua_fecha_fin,'YYYY-MM-DD')    as hasta
     , case when s.usua_fecha_fin < now() then 'VENCIDA' else 'vigente' end as periodo
     , s.usua_subrogado  as titular
     , s.usua_subrogante as clon
     , (select count(1) from radicado r
         where r.radi_usua_actu = s.usua_subrogante and r.esta_codi in (1,2)) as pend
  from usuarios_subrogacion s
 where s.usua_visible = 1
 order by 1;"

if [ $DRY_RUN -eq 1 ]; then
    echo
    verde "DRY-RUN: la comprobación termina aquí. No se modificó nada."
    exit 0
fi

# ------------------------------------------------------------------------------
paso "2/7  Esquema"
# ------------------------------------------------------------------------------
sql_file "$DIR_SCRIPT/01_esquema_subrogacion.sql"
verde "Esquema aplicado."

# ------------------------------------------------------------------------------
paso "3/7  Resolución de subrogaciones ambiguas"
# ------------------------------------------------------------------------------
sql "CREATE TABLE IF NOT EXISTS subrogacion_migracion_override (
        usua_subrogacion_codi integer PRIMARY KEY,
        real_codi             integer NOT NULL);" > /dev/null

if [ ${#OVERRIDES[@]} -eq 0 ]; then
    echo "Sin overrides configurados."
else
    for ov in "${OVERRIDES[@]}"; do
        subr="${ov%%:*}"
        real="${ov##*:}"
        sql "INSERT INTO subrogacion_migracion_override (usua_subrogacion_codi, real_codi)
             VALUES ($subr, $real)
             ON CONFLICT (usua_subrogacion_codi)
             DO UPDATE SET real_codi = EXCLUDED.real_codi;" > /dev/null
        echo "  subrogación $subr -> cuenta real $real"
    done
    verde "Overrides registrados."
fi

# ------------------------------------------------------------------------------
paso "4/7  Migración de cuentas clon"
# ------------------------------------------------------------------------------
sql_file "$DIR_SCRIPT/02_migracion_clones.sql"
verde "Migración aplicada."

# ------------------------------------------------------------------------------
paso "5/7  Subrogación de prueba vigente"
# ------------------------------------------------------------------------------
if [ -z "$SUBROGACION_PRUEBA" ]; then
    echo "No se configuró ninguna subrogación de prueba; se omite."
else
    existe="$(sql_valor "select count(1) from usuarios_subrogacion
                          where usua_subrogacion_codi = $SUBROGACION_PRUEBA;")"
    if [ "$existe" = "0" ]; then
        rojo "AVISO: no existe la subrogación $SUBROGACION_PRUEBA; se omite este paso."
    else
        # Sólo se amplía el período, para que el cron no la cierre y quede algo
        # con lo que probar el flujo en vivo.
        sql "update usuarios_subrogacion
                set usua_fecha_inicio = now() - interval '1 day'
                  , usua_fecha_fin    = now() + interval '$DIAS_VIGENCIA days'
              where usua_subrogacion_codi = $SUBROGACION_PRUEBA;" > /dev/null
        verde "Subrogación $SUBROGACION_PRUEBA vigente por $DIAS_VIGENCIA días."
    fi
fi

# ------------------------------------------------------------------------------
paso "6/7  Verificación"
# ------------------------------------------------------------------------------
clones="$(sql_valor "
select count(1) from usuarios_subrogacion s
  join usuarios u on u.usua_codi = s.usua_subrogante
 where s.estado = 1 and coalesce(u.usua_subrogado,0) > 0;")"

if [ "$clones" = "0" ]; then
    verde "No quedan subrogaciones activas apuntando a cuentas clon."
else
    rojo "ATENCIÓN: quedan $clones subrogación(es) apuntando a cuentas clon."
fi

echo
echo "Estado tras la migración:"
sql "
select s.usua_subrogacion_codi as subr
     , s.estado
     , s.usua_subrogado  as titular
     , s.usua_subrogante as subrogante
     , u.usua_login      as subrogante_login
     , to_char(s.usua_fecha_fin,'YYYY-MM-DD HH24:MI') as hasta
  from usuarios_subrogacion s
  left join usuarios u on u.usua_codi = s.usua_subrogante
 where s.estado in (0,1)
 order by 1;"

# ------------------------------------------------------------------------------
paso "7/7  Cron de vigencia"
# ------------------------------------------------------------------------------
if [ $CORRER_CRON -eq 0 ]; then
    echo "Omitido (--sin-cron). Para ejecutarlo:  php cron/procesar_subrogaciones.php"
else
    command -v php > /dev/null || fallo "No se encontró php en el PATH."
    php "$RAIZ/cron/procesar_subrogaciones.php"
fi

# ------------------------------------------------------------------------------
paso "Resultado"
# ------------------------------------------------------------------------------
echo "Subrogaciones que quedan vigentes:"
sql "
select s.usua_subrogacion_codi as subr
     , t.usua_login  as titular_login
     , left(t.usua_cargo, 40) as puesto_subrogado
     , sb.usua_login as subrogante_login
     , to_char(s.usua_fecha_fin,'YYYY-MM-DD HH24:MI') as hasta
  from usuarios_subrogacion s
  left join usuarios t  on t.usua_codi  = s.usua_subrogado
  left join usuarios sb on sb.usua_codi = s.usua_subrogante
 where s.estado = 1
 order by 1;"

echo
verde "Despliegue completado."
echo
echo "RECOMENDACIÓN DE PRUEBA"
echo "-----------------------"
echo "Inicie sesión con el usuario que figura arriba como 'subrogante_login'."
echo "En el menú superior 'Usuario:' debe aparecer una opción rotulada"
echo "'(Subr.) Subrogante de: ...' con el puesto subrogado. Al seleccionarla,"
echo "la sesión pasa a operar con las bandejas, funciones y permisos del cargo,"
echo "y el titular puede seguir trabajando en paralelo sin ser expulsado."
echo
echo "Este script no modifica contraseñas ni cuentas de acceso: utilice un"
echo "usuario cuya clave ya conozca, o gestione el acceso por los medios"
echo "habituales de la instalación."
