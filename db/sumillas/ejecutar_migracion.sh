#!/usr/bin/env bash
# ==============================================================================
# Quipux — Despliegue de la Administración de Sumillas
# ==============================================================================
# Ejecuta, en orden, todo lo necesario para dejar la base lista:
#
#   1. Verificación previa (conexión y tablas)
#   2. Esquema        (01_esquema_sumillas.sql)     -> columna accion_activo
#   3. Catálogo base  (02_datos_sumillas.sql)       -> las 26 sumillas de siempre
#   4. Categorías     (03_categorias_sumillas.sql)  -> tabla accion_categoria
#   5. Motivos        (04_datos_categorias.sql)     -> catálogo de motivos
#   6. Registro       (05_registro_sumillas.sql)     -> tabla radicado_sumilla
#   7. Verificación
#
# Uso:
#   ./db/sumillas/ejecutar_migracion.sh                     # todas las instituciones activas
#   ./db/sumillas/ejecutar_migracion.sh --dry-run           # solo comprueba y muestra
#   ./db/sumillas/ejecutar_migracion.sh --instituciones=3   # sólo esa institución
#   ./db/sumillas/ejecutar_migracion.sh --sin-datos         # sólo el esquema, sin catálogo ni categorías
#
# Todos los .sql son idempotentes: repetir la ejecución no duplica ni pisa nada.
# Las credenciales se leen de .env y nunca se imprimen.
# ==============================================================================

set -euo pipefail

DRY_RUN=0
CARGAR_DATOS=1
INSTITUCIONES="all"
for arg in "$@"; do
    case "$arg" in
        --dry-run)          DRY_RUN=1 ;;
        --sin-datos)        CARGAR_DATOS=0 ;;
        --instituciones=*)  INSTITUCIONES="${arg#*=}" ;;
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
# como 'Trámite Legal' viaja como bytes WIN1252 inválidos y el servidor rechaza la
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
# Los argumentos extra (p. ej. -v instituciones=3) van después del archivo.
sql_file()  { local f="$1"; shift; "$PSQL" "${PSQL_ARGS[@]}" "$@" -f "$f"; }

echo "=============================================================="
echo " Administración de Sumillas"
echo " Base  : $DB_NAME @ $DB_HOST:$DB_PORT (usuario $DB_USER)"
echo " psql  : $PSQL"
echo " Inst. : $([ "$INSTITUCIONES" = "all" ] && echo 'todas las activas' || echo "$INSTITUCIONES")"
echo " Datos : $([ $CARGAR_DATOS -eq 1 ] && echo si || echo 'no (--sin-datos)')"
echo " Modo  : $([ $DRY_RUN -eq 1 ] && echo 'DRY-RUN (no modifica nada)' || echo 'EJECUCION REAL')"
echo "=============================================================="

# ------------------------------------------------------------------------------
paso "1/7  Verificación previa"
# ------------------------------------------------------------------------------
faltantes="$(sql_valor "
select coalesce(string_agg(t, ', '), '')
  from unnest(array['accion','institucion']) t
 where to_regclass(t) is null;")"
[ -z "$faltantes" ] || fallo "Faltan tablas en la base: $faltantes"

propietario="$(sql_valor "select tableowner from pg_tables where tablename = 'accion';")"
usuario_actual="$(sql_valor "select current_user;")"
verde "Conexión correcta. La tabla 'accion' existe (propietario: $propietario)."
if [ "$propietario" != "$usuario_actual" ]; then
    rojo "AVISO: '$usuario_actual' no es dueño de 'accion'; el ALTER TABLE del paso 2"
    rojo "       fallará salvo que el rol tenga privilegios suficientes."
fi

echo
echo "Sumillas por institución antes de migrar:"
sql "
select i.inst_codi
     , left(i.inst_nombre, 40) as institucion
     , count(a.accion_codi)    as sumillas
  from institucion i
  left join accion a on a.inst_codi = i.inst_codi
 where i.inst_estado = 1
 group by i.inst_codi, i.inst_nombre
 order by i.inst_codi;"

if [ $DRY_RUN -eq 1 ]; then
    echo
    verde "DRY-RUN: la comprobación termina aquí. No se modificó nada."
    exit 0
fi

# ------------------------------------------------------------------------------
paso "2/7  Esquema"
# ------------------------------------------------------------------------------
sql_file "$DIR_SCRIPT/01_esquema_sumillas.sql"
verde "Esquema aplicado (columna accion_activo disponible)."

# ------------------------------------------------------------------------------
paso "3/7  Catálogo base de sumillas"
# ------------------------------------------------------------------------------
if [ $CARGAR_DATOS -eq 0 ]; then
    echo "Omitido (--sin-datos). Para cargarlo:"
    echo "  psql ... -v instituciones=all -f db/sumillas/02_datos_sumillas.sql"
else
    sql_file "$DIR_SCRIPT/02_datos_sumillas.sql" -v "instituciones=$INSTITUCIONES"
    verde "Catálogo base cargado."
fi

# ------------------------------------------------------------------------------
paso "4/7  Tabla de categorías"
# ------------------------------------------------------------------------------
sql_file "$DIR_SCRIPT/03_categorias_sumillas.sql"
verde "Tabla accion_categoria disponible; accion.cate_codi enlazada."

# ------------------------------------------------------------------------------
paso "5/7  Catálogo de motivos"
# ------------------------------------------------------------------------------
if [ $CARGAR_DATOS -eq 0 ]; then
    echo "Omitido (--sin-datos). Para cargarlas:"
    echo "  psql ... -v instituciones=all -f db/sumillas/04_datos_categorias.sql"
else
    sql_file "$DIR_SCRIPT/04_datos_categorias.sql" -v "instituciones=$INSTITUCIONES"
    verde "Categorías cargadas."
    echo
    rojo "REVISE LA CLASIFICACIÓN: los motivos del paso 5 son una propuesta de"
    rojo "arranque, no salen de ninguna norma. Ajústelas desde Administración ->"
    rojo "Administración de Sumillas con el área que define los motivos."
fi

# ------------------------------------------------------------------------------
paso "6/7  Registro de sumillas por documento"
# ------------------------------------------------------------------------------
sql_file "$DIR_SCRIPT/05_registro_sumillas.sql"
verde "Tabla radicado_sumilla disponible; la hoja de ruta ya puede mostrarlas."

# ------------------------------------------------------------------------------
paso "7/7  Verificación"
# ------------------------------------------------------------------------------
# inst_codi = 0 queda fuera igual que en la carga: es la fila marcador del
# super-administrador, sin usuarios ni bandejas propias.
sin_sumillas="$(sql_valor "
select count(1)
  from institucion i
 where i.inst_estado = 1
   and i.inst_codi > 0
   and not exists (select 1 from accion a where a.inst_codi = i.inst_codi);")"

if [ "$sin_sumillas" = "0" ]; then
    verde "Todas las instituciones activas tienen sumillas cargadas."
else
    rojo "ATENCIÓN: quedan $sin_sumillas institución(es) activas sin sumillas."
    rojo "          Si fue intencional (--instituciones=...), ignore este aviso."
fi

echo
echo "Estado final:"
sql "
select i.inst_codi
     , left(i.inst_nombre, 40)                     as institucion
     , count(a.accion_codi)                        as total
     , count(*) filter (where a.accion_activo = 1) as activas
     , count(*) filter (where a.accion_activo = 0) as inactivas
     , (select count(*) from accion_categoria c where c.inst_codi = i.inst_codi) as categorias
     -- Sin acentos a proposito: lo que va en -c cruza de Git Bash a psql por la
     -- pagina de codigos de Windows y un caracter UTF-8 llega corrupto.
     -- El 'a.accion_codi is not null' descarta la fila vacia que deja el LEFT JOIN
     -- en una institucion sin sumillas; si no, contaria como una sin clasificar.
     , count(*) filter (where a.accion_codi is not null
                          and a.cate_codi is null) as sin_clasificar
  from institucion i
  left join accion a on a.inst_codi = i.inst_codi
 where i.inst_estado = 1
 group by i.inst_codi, i.inst_nombre
 order by i.inst_codi;"

paso "Resultado"
verde "Despliegue completado."
echo
echo "RECOMENDACIÓN DE PRUEBA"
echo "-----------------------"
echo "1. Entre como administrador de institución o super-administrador y abra"
echo "   Administración -> Administración de Sumillas. El listado muestra cada"
echo "   categoría seguida de las sumillas que agrupa."
echo "2. Pulse Categorias: cree un motivo y ordenelo. Vuelva a Ver Sumillas,"
echo "   marque una como Inactivo y mueva otra al motivo nuevo."
echo "3. Abra un documento y use Reasignar. Junto al comentario debe verse el"
echo "   árbol 'Sumilla' con los motivos como ramas plegables. Un clic sobre una"
echo "   sumilla la marca y añade '*<sumilla>' al comentario; otro clic la quita."
echo "   La que desactivó no debe aparecer; la que movió debe estar en su"
echo "   nueva categoría."
echo "4. Complete la reasignación y abra el Recorrido del documento: la sumilla"
echo "   debe figurar en su propia columna, en la fila de esa reasignación."
echo "   Pulse Imprimir y compruebe que también sale en la hoja de ruta en PDF."
echo
echo "NOTA: el árbol sólo se dibuja si el usuario tiene el permiso"
echo "      'Activar Acciones sobre Documentos' (permiso 4). Sin él la pantalla"
echo "      de reasignación muestra únicamente el comentario."
