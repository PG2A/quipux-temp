<?php
// This file is part of Quipux – Document Management System
//
// Quipux is free software and is currently under a process of technical
// modernization and functional improvement carried out by
// EXDUCERE ONLINE CIA. LTDA., as part of the development of a new version
// of the Quipux platform.
//
// Quipux is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Quipux is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Quipux. If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    core
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE <@link https://exducereonline.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

unset($CFG);
global $CFG;
$CFG = new stdClass();

/** Used by library scripts to check they are being called by Moodle */
if (!defined('QUIPUX_INTERNAL')) define('QUIPUX_INTERNAL', true);

// Archivo con algunas configuraciones de términos usados en el sistema
$FILE_LOCAL = "localEcuador.php";

// Activa la funcionalidad para bloquear el sistema;
// Se lo debe activar cuando se programe un bloqueo del sistema para disminuir las consultas a la BDD
$activar_bloqueo_sistema = false;

//Mejora algunos queries y bloquea algunas funcionalidades para reducir la carga a los servidores
$CFG->version_light = false;
$CFG->config_bloquear_acceso_ciudadano = false;
$version_light=false;
$config_numero_meses = 60;
$numeroCaracteresTexto = 0;
$config_bloquear_acceso_ciudadano = false;




require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Configuracion de la conexion con la BD
$CFG->driver = $_ENV['DB_DRIVER'] ?? 'postgres';
$CFG->servidor = ($_ENV['DB_HOST'] ?? 'localhost') . ':' . ($_ENV['DB_PORT'] ?? '5432');
$CFG->db = $_ENV['DB_NAME'] ?? 'quipux_ucuenca';
$CFG->usuario = $_ENV['DB_USER'] ?? 'quipux_ucuenca';
$CFG->contrasena = $_ENV['DB_PASS'] ?? '';

$driver   = $CFG->driver;
$servidor = $CFG->servidor;
$db       = $CFG->db;
$usuario  = $CFG->usuario;
$contrasena = $CFG->contrasena;

// --- DB bodega ---
$servidor_bodega   = ($_ENV['DB_BODEGA_HOST'] ?? 'localhost') . ':' . ($_ENV['DB_BODEGA_PORT'] ?? '5432');
$db_bodega         = $_ENV['DB_BODEGA_NAME'] ?? 'quipux_combodega_ucuenca';
$usuario_bodega    = $_ENV['DB_BODEGA_USER'] ?? 'quipux_combodega_ucuenca';
$contrasena_bodega = $_ENV['DB_BODEGA_PASS'] ?? '';


// Indica si se manejan replicas o conexiones con otras BDD
$CFG->replicacion = false;
$replicacion = false;

// Se definen las mismas variables que en la configuracion por defecto, seguidas por un guion bajo y un nombre que la distinga
// Para utilizar esta funcionalidad se debe enviar el nombre utilizado en las variables como parametro al crear la conexion
// Si se desea se puede ocultar los datos de la conexion en variables del servidor, como en el caso anterior
$usuario_busqueda = "postgres";
$contrasena_busqueda = "postgres";
$servidor_busqueda = "127.0.0.1:5432";
$db_busqueda = "quipux_replica";


//Codigo de aplicacion (en caso de que se manejen varios servidores para distribución de carga)
$appID = 'appID';

//Path en donde se guardan los archivos que anexa el ciudadano para petición de uso de QUIPUX con firma digital
$path_ciudadanos = "/var/www/quipux/bodega/ciudadanos";

//Logs y Mensajes de la aplicacion
//Muestra en pantalla los queries que se ejecutan en la bdd; 0 no muestra ningun mensaje, 1 muestra los errores, 2 muestra todos
$mostrar_logs = 0;
// Graba en la tabla logs de la bdd los queries (inserts y updates) mas importantes ejecutados; 0 no graba nada, 1 graba los errores, 2 graba todos
$grabar_logs = 2;
// Graba en una tabla de logs la página invocada y el IP que la invocó (para identificar posibles ataques desde páginas externas o desde páginas de orfeo...)
$grabar_log_paginas_visitadas = false;
$grabar_log_full_backup = false;


//Email del Super Administrador del Sistema QUIPUX
$amd_email = "sgd@ucuenca.edu.ec";
// email de la cuenta de soporte
$cuenta_mail_soporte = "sgd@ucuenca.edu.ec";
// email de la cuenta desde la que se enviarán los recordatorios a los usuarios
$cuenta_mail_envio = "sgd@ucuenca.edu.ec";

// Configuración para la conexión con otros servidores adicionales
$nombre_servidor="https://docs.ucuenca.edu.ec";
$nombre_servidor_reportes = $nombre_servidor; // en caso que los reportes se lo quiera enviar a un servidor diferente
$nombre_servidor_respaldos = $nombre_servidor; // en caso que los respaldos se requiera sacar en un servidor diferente

$CFG->nombre_servidor_reportes = $nombre_servidor_reportes;

$servidor_firma = "http://firmadigital.ucuenca.edu.ec/firma";
// $servidor_firma = "http://127.0.0.1:8085/firma";
$servidor_ws_firma = "http://firmadigital.ucuenca.edu.ec/firma";

// --- SMART-SIGN API (reemplazo de FirmaEC Transversal) ---
$usar_smart_sign = true; // true = usar Smart-Sign API, false = usar FirmaEC Transversal
$servidor_smart_sign = "https://aynisign.exducereonline.com/api/signature/smart-sign";

$servidor_viajes = "http://nombre_servidor_viajes";

$servidor_pdf = "https://docs.ucuenca.edu.ec/";

//Numero Meses en Reportes
$numeroMeses = 60;
//path de descarga del archivo
$path_acuerdo = "http://www.informatica.gob.ec/index.php/component/docman/doc_download/57-acuerdo-de-uso/Acuerdo de Uso.odt";
//Acceso para Institución de Ciudadanos
$acceso_ciudadano_inst = 1;
//Tipo de Documentos de Ciudadanos
$tipo_doc_ciudadano = "7";
//Número de días de vigencia para descarga de archivos de respaldos
$dias_descarga = 15;
//Correo para recibir notificaciones de respaldos para soporte
$cuenta_mail_respaldo = "respaldo@informatica.gob.ec";
$versionEstable = 25;//version de firefox menores a 17 es soportada
  $api_key_token = "4a27eb19bb3efbe6c6918659be49ef7bea13762ce0c534ff0382ea2e627e3629";
//  $api_key_token = "9dc122a2ea8d6b7e060cd5f032a08d76fc0e75b28288af222417139faf2603f4";
//

$AUTH_MODE = 'db';

// --- QUIPUX ERROR HANDLING ---
// Set to true to see errors on screen, false to log them silently
if (!defined('QUIPUX_DEBUG_MODE')) define('QUIPUX_DEBUG_MODE', true); 
require_once __DIR__ . '/include/errorhandler.php';

?>

