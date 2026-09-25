# Orgánico funcional: jerarquía real de áreas y catálogo de puestos

Piloto aplicado a la **Facultad de Ciencias Médicas** (`depe_codi = 112`). Los
usuarios (`usuarios` / `ciudadano` / `usuario`) se dejan como están.

## Qué cambia

| Antes | Después |
|---|---|
| `dependencia.depe_nomb = 'FACULTAD DE CIENCIAS MÉDICAS - VICEDECANATO'`, `depe_codi_padre = 3` | `depe_nomb = 'VICEDECANATO'`, `depe_codi_padre = 112` |
| `usuario.depe_nomb` copia literal de `dependencia.depe_nomb` | `usuario.depe_nomb = depe_ruta(depe_codi)` → sigue diciendo `FACULTAD DE CIENCIAS MÉDICAS - VICEDECANATO` |
| `usuarios.usua_cargo` texto libre; `cargo_id` apuntaba a una tabla borrada | tabla `cargo` (un puesto pertenece a un área hoja); `usuarios.cargo_id` con FK |

`usua_cargo` y `usua_cargo_cabecera` **no se modifican**: son el pie de firma y la
cabecera de los documentos. El catálogo los enlaza, no los reemplaza.

## Archivos

| Archivo | Alcance |
|---|---|
| `01_esquema_cargo.sql` | Toda la base. Tabla `cargo`, FK, `depe_ruta()`, `depe_descendientes()`, triggers de `usuario`. Pone en NULL los `cargo_id` huérfanos. |
| `02_fcm_jerarquia.sql` | FCM. Mapeo explícito de 35 áreas → padre + nombre corto. |
| `03_fcm_cargos.sql` | FCM. 12 usuarios movidos a su hoja, numeración, carga de 149 puestos, `cargo_id`. |
| `04_verificacion.sql` | Solo lectura. |
| `05_estructura_y_nivel.sql` | Añade `dependencia.estructura_organica` (boolean, default false) y `cargo.cargo_nivel` (integer, nullable). Idempotente. |
| `06_migracion_general.sql` | Generaliza a toda la UC: jerarquiza las áreas `PADRE - HIJA` que resuelven sin ambigüedad, cataloga los puestos de todas las áreas con usuarios activos, enlaza `cargo_id`. Lo dudoso queda en `organico_excluidos`. Idempotente. |
| `07_resolucion_excluidos.sql` | Resuelve los excluidos por **prefijo más largo existente** (el guion interno queda en el nombre corto, no se parte en 3); crea el área `DIRECCIÓN ADMINISTRATIVA FINANCIERA` (distinta de 644 y 11) y cuelga sus coordinaciones. Idempotente. |
| `exclusiones_revision.csv` | Salida: áreas y puestos que siguen requiriendo decisión manual. |
| `ejecutar_migracion.sh` | Orquesta todo. `--dry-run`, `--solo-esquema`. Idempotente. |

Respaldos en la base: `organico_respaldo_dependencia`, `organico_respaldo_usuarios`
(columna `respaldo_lote`).

## Decisiones tomadas en el piloto (revisables)

1. **Posgrados bajo CENTRO DE POSGRADOS.** 174, 178, 179, 348 y 349 colgaban de la
   raíz; se pusieron bajo 176 como ya estaba 402. Cambiar el padre en
   `02_fcm_jerarquia.sql` si no se quiere.
2. **Usuarios movidos** solo cuando el puesto nombra la hoja sin ambigüedad
   (`Docente` → PERSONAL DOCENTE, `Directora de la Especialización en X` → área X…).
   Lista completa en `03_fcm_cargos.sql`. Quien no está en la lista se queda donde estaba.
3. **Numeración.** Un área que nunca numeró (sin filas en `formato_numeracion`) y
   recibe usuarios, se configura con `depe_numeracion` = área de origen: sus
   documentos salen con la sigla y contador de antes (402, 348, 349 → FCMCP). Las
   áreas que ya tenían configuración propia no se tocan; quien llega adopta la del área
   (los 4 docentes movidos a 635 numeran ahora `UC-FCMD-PD-…`, no `UC-FCMD-…`).
4. **`cargo_tipo` del catálogo** = 1 si algún titular actual del puesto es Jefe.
5. **Nombres de puesto** se agrupan sin mayúsculas ni espacios dobles; los acentos
   distinguen (`Asistente de Gestion` ≠ `Asistente de Gestión`). Se unifican a mano después.

## Impactos que hay que tener presentes

- **Reasignación.** `tx/tx_cargar_combos.php` usa `coalesce(depe_codi_padre, depe_codi)`.
  Con árbol real, un Jefe de área hoja ve padre + hermanas (antes, con todo
  colgando de 3, veía las 417 áreas). Un usuario Normal en hoja solo ve su hoja.
  Válvula: permiso 26 `perm_saltar_organico_funcional`.
- **Ámbito de administradores.** `usuario_dependencia.depe_codi_tmp` es una lista
  plana; un admin con 112 **no** ve usuarios de 177. Ampliar la lista o hacer que
  `obtenerAreasAdmin()` expanda con `depe_descendientes()`.
- **Combos de áreas** que leen `dependencia.depe_nomb` directamente
  (`cuerpoUsuario.php`, `adm_usuario.php`, `tx_cargar_combos.php`…) ahora muestran
  `VICEDECANATO` sin contexto entre 400 nombres largos. Siguiente paso de código:
  usar `depe_ruta(depe_codi)` en esos SELECT.
- **Un solo Jefe por área** (`grabar_usuario.php:96`). Las hojas nuevas no tienen Jefe.

## Interfaz de administración de Áreas (rediseñada)

`Administración → Áreas` ya no abre el menú de 3 opciones ni el árbol. Ahora es una
sola pantalla al estilo del catálogo de sumillas (`Administracion/dependencias/`):

| Archivo | Rol |
|---|---|
| `areas.php` | Listado único: búsqueda + botón **Registrar Área** + tabla paginada |
| `areas_paginador.php` | Filas con columnas **Área** (ruta), **Sigla**, **Estado**, **Sub áreas**, **Puestos**, **Acción** (Editar) |
| `areas_form.php` | Alta/edición en una pantalla: nombre, sigla, ciudad, área padre, estado |
| `areas_grabar.php` | Guardado (misma lógica de `usuario_dependencia` y `Replace` que el flujo anterior) |
| `areas_puestos.php` | Listado de puestos del área (pantalla completa) con botón **Registrar nuevo puesto** y acciones Editar / Activar / Desactivar |
| `areas_puesto_form.php` | Alta/edición de un puesto en ventana propia; al guardar regresa al listado |
| `areas_puesto_grabar.php` | Guardado del puesto (alta, edición con propagación opcional, cambio de estado) |
| `puestos_area_ajax.php` | Panel ajax de puestos del árbol viejo (sólo lo usa `adm_dependencias_nuevo.php`, ya sin enlace) |
| `areas_jefe.php` | Jefe de Área y bandeja compartida (reusa `administrar_jefe_ajax.php` y `compartirBandeja_ajax.php`) |

Columnas del listado: **Área** (ruta), **Sigla**, **Estado**, **Sub áreas**, **Puestos**, **Jefe**, **Acción**.

- **Sub áreas**: `N sub área(s)` abre otro listado igual (`areas.php?padre=N`) con las
  hijas de esa área; desde ahí se puede seguir bajando de nivel y volver con
  "Subir un nivel" / "Ver todas las áreas" (o el botón atrás del navegador). Las áreas
  sin hijas muestran un `0` sin enlace. Navegación por niveles, sin árbol.
- **Puestos**: `N puesto(s)` abre `areas_puestos.php` (listado de puestos del área).
  El alta se hace con **Registrar nuevo puesto** → ventana aparte (`areas_puesto_form.php`)
  → al guardar regresa al listado con un mensaje.
- **Jefe**: muestra `Jefe` / `Sin jefe` y abre `areas_jefe.php`, donde se asigna/quita
  el Jefe de Área y se comparte su bandeja (misma funcionalidad del árbol viejo).
- El menú apunta a `areas.php` (`formAdministracion.php:117`). Los archivos viejos
  (`mnu_dependencias.php`, `adm_dependencias_nuevo.php`) siguen en disco pero ya no se
  enlazan; sus paneles ajax (`administrar_jefe_ajax.php`, `compartirBandeja_ajax.php`,
  `admDependencias_ajax.php`) sí se reutilizan desde las nuevas pantallas.

## Combos del formulario de creación/edición de usuario (hecho)

`Administracion/usuarios/adm_usuario.php`:
- El combo **Área** muestra la ruta jerárquica (`depe_ruta`) en vez del nombre corto,
  para distinguir sub áreas homónimas.
- Nuevo **combo de Puestos** (`puestos_combo_ajax.php`) filtrado por el área elegida
  (catálogo `cargo`, sólo activos). Al elegir un puesto rellena *Puesto*, *Puesto
  Cabecera* y *Perfil*, y fija el `cargo_id` oculto. Los campos *Puesto* y *Puesto
  Cabecera* son de **sólo lectura**: el puesto se elige, no se escribe. Un área sin
  puestos en el catálogo bloquea el alta hasta registrarlos.
- `grabar_usuario.php`: si el `cargo_id` pertenece al área, toma del catálogo el
  puesto, la cabecera y el perfil (gobierna la validación de Jefe único) e ignora lo
  que llegue en el POST; guarda `usuarios.cargo_id` (NULL si no es válido) y copia
  `cargo_nivel` a `usuarios.nivel_jerarquico`.

## Migración general aplicada (06)

En la base local se aplicó `06_migracion_general.sql`:
- **292 áreas** jerarquizadas (padre real + nombre corto); **15 excluidas**.
- **1130 puestos** en el catálogo (149 FCM + 981 del resto); **1540** usuarios activos
  de la UC con `cargo_id`.
- 0 errores de trigger. Las 3 "discrepancias" de `usuario.depe_nomb` son cuentas admin
  con `depe_codi` nulo (pre-existentes, ajenas a la migración).

**Casos excluidos (`exclusiones_revision.csv`, 18 filas):**

| Motivo | N | Qué revisar |
|---|---|---|
| `PADRE_NO_ENCONTRADO` | 12 | El prefijo no coincide con ningún área. Incluye la familia `DIRECCIÓN ADMINISTRATIVA FINANCIERA - …` (¿crear ese padre o fusionar con `DIRECCION ADMINISTRATIVA` 644?), IDIOMAS (239/376, que ya colgaban de 238), `VICERRECTORADO - …`, guiones legítimos (212, 642). |
| `SIN_CORTO` | 3 | Nombres con guion interno sin espacios (`PRE-PROFESIONALES`, `DIES-MUNICH`, `2021-2025`): decidir el nombre corto y el padre a mano. |
| `PUESTO_VACIO` | 3 | Usuarios activos sin texto de puesto (cuentas admin/prueba): asignarles un puesto del catálogo. |

Nada de lo excluido se modificó: conserva su nombre y su padre originales.

## Pendiente

1. Resolver a mano los 18 casos del CSV (sobre todo la familia `DIRECCIÓN ADMINISTRATIVA
   FINANCIERA` y los 3-niveles de IDIOMAS/PSICOLOGÍA).
2. `obtenerAreasAdmin()` jerárquico (que un admin con un área padre vea sus hijas).
3. Decisiones funcionales antes del despliegue: alcance de reasignación, numeración por
   familia, jefe por área hoja.
