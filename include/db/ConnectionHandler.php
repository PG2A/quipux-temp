<?php
/**
 * Gestor de Conexión a Base de Datos - Versión Mejorada
 * 
 * Compatible con:
 * - Nueva estructura (lib/adodb/)
 * - PHP 8.x
 * - Prepared statements
 * - Logging
 * 
 * @package    class
 * @author     2025 Casen Xu <casenxu@exducereonline.com>
 * @copyright  EXDUCERE ONLINE
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class ConnectionHandler {
    
    // ============================================
    // PROPIEDADES
    // ============================================
    
    private $error = '';
    private $id_query = '';
    public $driver = 'postgres';
    public $rutaRaiz = '';
    private $entidad = '';
    private $entidad_largo = '';
    private $entidad_tel = '';
    private $entidad_dir = '';
    private $querySql = '';
    private $logger = null;

    public $conn = null;

    private $rootPath = '';
    
    // ============================================
    // CONSTRUCTOR
    // ============================================
    
    /**
     * Constructor
     * 
     * @param string $ruta_raiz Ruta raíz del proyecto
     * @param string $servidor_bdd Servidor de BD (opcional)
     */
    public function __construct($ruta_raiz = '', $db_connection = '') {
        $this->rutaRaiz = $ruta_raiz;
        $this->_init($db_connection);
    }
    
    // ============================================
    // INICIALIZACIÓN
    // ============================================
    
    /**
     * Inicializar conexión
     * 
     * @param string $ruta_raiz Ruta raíz
     * @param string $servidor_bdd Servidor (opcional)
     */
    private function _init($db_connection = '') {
        try {

            $this->entidad = $db_connection;

            // Definir constantes si no existen
            if (!defined('ADODB_ASSOC_CASE')) {
                define('ADODB_ASSOC_CASE', 1);
            }

            // Determinar rutas
            $this->_setupPaths();

            // Cargar ADODB
            $this->_loadADOdb();



            // Cargar configuración
            $this->_loadConfig();


            
            // Crear conexión
            $this->_createConnection($db_connection);
            
            // Validar conexión
            $this->_validateConnection();
            
            // Ajustar timestamps
            $this->_setupTimestamps();
            
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            error_log('ConnectionHandler Error: ' . $this->error);
            die('Error de conexión: ' . htmlspecialchars($this->error));
        }
    }
    
    /**
     * Configurar rutas
     */
    private function _setupPaths() {
        // Define a path of an adodb library
        if (!defined('LIB_PATH')) {
            $base_path = dirname(__DIR__, 2);
            define('LIB_PATH', $base_path . '/lib');
        }

        if (!defined('BASE_PATH')) {
            $base_path = dirname(__DIR__, 2);
            define('BASE_PATH', $base_path);
        }
    }
    
    /**
     * Cargar ADODB
     */
    private function _loadADOdb() {
        // Try composer path first
        $adodbPath = BASE_PATH . '/vendor/adodb/adodb-php/adodb.inc.php';

        if (!file_exists($adodbPath)) {
            // Fallback to legacy path if needed (though we deleted it)
            $adodbPath = LIB_PATH . '/adodb/adodb.inc.php';
        }

        if (!file_exists($adodbPath)) {
            throw new Exception("ADODB no encontrado en: $adodbPath");
        }
        
        require_once $adodbPath;

        // El helper _adodb_getmenu() (adodb-lib.inc.php) decide como leer la 2a
        // columna del recordset consultando la GLOBAL $ADODB_FETCH_MODE, no el modo
        // de la conexion. SetFetchMode() solo escribe en la conexion, asi que la
        // global se quedaba en ADODB_FETCH_DEFAULT mientras los recordsets llegaban
        // en PGSQL_ASSOC: el acceso por indice numerico fallaba y GetMenu/GetMenu2
        // generaba todos los <option> con value="". Alinear ambos aqui, una sola vez.
        global $ADODB_FETCH_MODE;
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;

        // Cargar extensiones opcionales
        $pagerPath = dirname($adodbPath) . '/adodb-paginacion.inc.php';
        if (file_exists($pagerPath)) {
             @include_once $pagerPath;
        } else {
             @include_once LIB_PATH . '/adodb/adodb-paginacion.inc.php';
        }
        
        if (!function_exists('rs2html')) {
             @include_once LIB_PATH . '/adodb/tohtml.inc.php';
        }
    }
    
    /**
     * Cargar configuración
     */
    private function _loadConfig() {
        // Usar config.php si existe
        $configPath = BASE_PATH . '/config.php';

        if (!file_exists($configPath)) {
            throw new Exception("Error: config.php no encontrado en: $configPath");
        }

        require_once $configPath;

        foreach (get_defined_vars() as $nombre => $valor) {
            if ($nombre === 'configPath' || $nombre === 'db') continue;
            if (!array_key_exists($nombre, $GLOBALS)) $GLOBALS[$nombre] = $valor;
        }
    }
    
    /**
     * Crear conexión
     */
    private function _configuracionDestino($db_connection) {
        global $CFG;

        $base = array(
            'servidor'   => $CFG->servidor,
            'usuario'    => $CFG->usuario,
            'contrasena' => $CFG->contrasena,
            'db'         => $CFG->db
        );

        $sufijo = strtolower(trim((string)$db_connection));
        if ($sufijo === '') return $base;

        if ($sufijo === 'bloquear') {
            die("<center><h3><br><br>Lo sentimos, actualmente esta funcionalidad no se encuentra disponible."
              . "<br><br>Por favor vuelva a intentarlo m&aacute;s tarde.</h3></center>");
        }

        $replicacion = !empty($CFG->replicacion);
        if (!$replicacion && strpos($sufijo, 'bodega') !== 0) return $base;

        $env = strtoupper($sufijo);
        $servidor = '';
        if (!empty($_ENV["DB_{$env}_HOST"]))
            $servidor = $_ENV["DB_{$env}_HOST"] . ':' . ($_ENV["DB_{$env}_PORT"] ?? '5432');
        elseif (!empty($GLOBALS["servidor_$sufijo"]))
            $servidor = $GLOBALS["servidor_$sufijo"];

        $db         = $_ENV["DB_{$env}_NAME"] ?? ($GLOBALS["db_$sufijo"] ?? '');
        $usuario    = $_ENV["DB_{$env}_USER"] ?? ($GLOBALS["usuario_$sufijo"] ?? '');
        $contrasena = $_ENV["DB_{$env}_PASS"] ?? ($GLOBALS["contrasena_$sufijo"] ?? '');

        if ($servidor === '' || $db === '' || $usuario === '') return $base;

        return array(
            'servidor'   => $servidor,
            'usuario'    => $usuario,
            'contrasena' => $contrasena,
            'db'         => $db
        );
    }

    private function _createConnection($db_connection = '') {
        global $CFG;

        // Validar variables
        if (empty($CFG->servidor) || empty($CFG->usuario) || empty($CFG->db)) {
            throw new Exception(
                "Variables de conexión incompletas. " .
                "Servidor: '$CFG->servidor', Usuario: '$CFG->usuario', BD: '$CFG->db'"
            );
        }
        
        // Establecer driver
        $this->driver = $CFG->driver;
        
        // Crear conexión
        $this->conn = ADONewConnection($this->driver);
        
        if ($this->conn) {
            $this->conn->nameQuote = '';
        }
        
        if (!$this->conn) {
            throw new Exception("No se pudo crear conexión ADOdb para driver: $CFG->driver");
        }
        
        $destino = $this->_configuracionDestino($db_connection);

        // Parsear host:puerto
        $host = $destino['servidor'];
        $port = $this->_getDefaultPort($CFG->driver);

        if (strpos($host, ':') !== false) {
            list($host, $port) = explode(':', $host, 2);
        }

        // Conectar
        $ok = @$this->conn->Connect("$host:$port", $destino['usuario'], $destino['contrasena'], $destino['db']);

        if ((!$ok || !$this->conn->_connectionID) && $destino['db'] != $CFG->db) {
            error_log("QUIPUX: no se pudo conectar a la base '" . $destino['db'] . "' (" . $destino['servidor']
                . "); se usa la base principal '" . $CFG->db . "'. Revise la configuracion DB_" . strtoupper((string)$db_connection) . "_*");
            $host = $CFG->servidor;
            $port = $this->_getDefaultPort($CFG->driver);
            if (strpos($host, ':') !== false) list($host, $port) = explode(':', $host, 2);
            $ok = @$this->conn->Connect("$host:$port", $CFG->usuario, $CFG->contrasena, $CFG->db);
        }

        if (!$ok || !$this->conn->_connectionID) {
            throw new Exception(
                "No se pudo conectar a BD: " . $this->conn->ErrorMsg() .
                " (Host: $host, Puerto: $port, BD: " . $destino['db'] . ")"
            );
        }

        // Configurar charset
        if ($this->driver === 'mysql') {
            $this->conn->Execute("SET NAMES utf8mb4");
            $this->conn->Execute("SET CHARACTER SET utf8mb4");
        }

        // Mantener el modo de la conexion alineado con $ADODB_FETCH_MODE. Sin esto
        // una conexion que nadie inicialice desde fuera queda en fetchMode=false y
        // vuelve a divergir de la global.
        $this->conn->SetFetchMode(ADODB_FETCH_ASSOC);
    }
    
    /**
     * Obtener puerto por defecto según driver
     */
    private function _getDefaultPort($driver) {
        $ports = array(
            'postgres' => '5432',
            'mysql' => '3306',
            'oracle' => '1521',
            'mssql' => '1433'
        );
        
        return $ports[$driver] ?? '5432';
    }
    
    /**
     * Validar conexión
     */
    private function _validateConnection() {
        if (!($this->conn instanceof ADOConnection)) {
            $tipo = is_object($this->conn) ? get_class($this->conn) : gettype($this->conn);
            throw new Exception("Conexión ADOdb no válida. Tipo: $tipo");
        }
        
        if (!$this->conn->_connectionID) {
            throw new Exception("Conexión ADOdb sin identificador activo");
        }

        // La app entera asume fetch asociativo (index.php lo fija, obtenerdatos.php tambien).
        // Si la global queda desincronizada, _adodb_getmenu() emite <option value=""> y
        // rs2html() desalinea columnas en las paginas que no pasan por index.php.
        $this->conn->SetFetchMode(ADODB_FETCH_ASSOC);
        $GLOBALS['ADODB_FETCH_MODE'] = ADODB_FETCH_ASSOC;
    }
    
    /**
     * Configurar timestamps
     */
    private function _setupTimestamps() {
        try {
            if ($this->driver === 'postgres') {
                $rs = @$this->conn->Execute("select now() as fecha1, now()::date as fecha2");
                if ($rs && !$rs->EOF) {
                    $this->conn->sysTimeStamp = "('" . $rs->fields["FECHA1"] . "'::timestamp)";
                    $this->conn->sysDate = "'" . $rs->fields["FECHA2"] . "'::date";
                }
            } else {
                $this->conn->sysDate = "'" . date("Y-m-d") . "'";
                $this->conn->sysTimeStamp = "'" . date("Y-m-d H:i:s") . "'";
            }
        } catch (Exception $e) {
            error_log("Error configurando timestamps: " . $e->getMessage());
        }
    }
    
    // ============================================
    // MÉTODOS PÚBLICOS
    // ============================================
    
    /**
     * Ejecutar query
     * 
     * @param string $sql SQL a ejecutar
     * @param array $params Parámetros (opcional)
     * @return ADORecordSet|false
     */
    public function query($sql, $params = array()) {
        $this->querySql = $sql;
        
        if (!empty($params)) {
            return $this->conn->Execute($sql, $params);
        }
        
        return $this->conn->Execute($sql);
    }
    
    /**
     * Obtener resultado
     * 
     * @param string $sql SQL a ejecutar
     * @return ADORecordSet|false
     */
    public function getResult($sql) {
        if (empty($sql)) {
            $this->error = "No ha especificado una consulta SQL";
            error_log($this->error);
            return false;
        }
        
        return $this->query($sql);
    }
    
    /**
     * Insertar registro
     * 
     * @param string $table Tabla
     * @param array $record Registro
     * @return ADORecordSet|false
     */
    public function insert($table, $record) {
        if (empty($table) || empty($record)) {
            $this->error = "Tabla o registro vacío";
            return false;
        }
        
        $fields = array_keys($record);
        $values = array_values($record);
        
        // Usar prepared statement
        $placeholders = array_fill(0, count($values), '?');
        $sql = "INSERT INTO $table (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
        
        $this->querySql = $sql;
        
        return $this->conn->Execute($sql, $values);
    }
    
    /**
     * Actualizar registro
     * 
     * @param string $table Tabla
     * @param array $record Registro a actualizar
     * @param array $where Condición WHERE
     * @return ADORecordSet|false
     */
    public function update($table, $record, $where) {
        if (empty($table) || empty($record) || empty($where)) {
            $this->error = "Parámetros incompletos";
            return false;
        }
        
        $setFields = array_keys($record);
        $setValues = array_values($record);
        
        $whereFields = array_keys($where);
        $whereValues = array_values($where);
        
        // Construir SET
        $setPart = implode(' = ?, ', $setFields) . ' = ?';
        
        // Construir WHERE
        $wherePart = implode(' = ? AND ', $whereFields) . ' = ?';
        
        // Combinar parámetros
        $params = array_merge($setValues, $whereValues);
        
        $sql = "UPDATE $table SET $setPart WHERE $wherePart";
        
        $this->querySql = $sql;
        
        return $this->conn->Execute($sql, $params);
    }
    
    /**
     * Eliminar registro
     * 
     * @param string $table Tabla
     * @param array $where Condición WHERE
     * @return ADORecordSet|false
     */
    public function delete($table, $where) {
        if (empty($table) || empty($where)) {
            $this->error = "Parámetros incompletos";
            return false;
        }
        
        $whereFields = array_keys($where);
        $whereValues = array_values($where);
        
        $wherePart = implode(' = ? AND ', $whereFields) . ' = ?';
        
        $sql = "DELETE FROM $table WHERE $wherePart";
        
        $this->querySql = $sql;
        
        return $this->conn->Execute($sql, $whereValues);
    }
    
    /**
     * Obtener siguiente ID de secuencia
     * 
     * @param string $secName Nombre de secuencia
     * @return int
     */
    public function nextId($secName) {
        if ($this->conn->hasGenID) {
            return $this->conn->GenID($secName);
        }
        
        $retorno = -1;
        
        if ($this->driver === "oracle") {
            $q = "SELECT $secName.nextval AS SEC FROM dual";
            $this->conn->SetFetchMode(ADODB_FETCH_ASSOC);
            $rs = $this->query($q);
            
            if ($rs && !$rs->EOF) {
                $retorno = $rs->fields['SEC'];
            }
        }
        
        return $retorno;
    }
    
    /**
     * Cerrar conexión
     */
    public function close() {
        if ($this->conn) {
            $this->conn->Close();
            $this->conn = null;
        }
    }
    
    /**
     * Establecer modo de fetch ASSOCIATIVE
     */
    public function setFetchAssoc() {
        $this->conn->SetFetchMode(ADODB_FETCH_ASSOC);
    }
    
    /**
     * Escapar string para SQL
     * 
     * @param string $val Valor a escapar
     * @return string
     */
    public function qstr($val) {
        return $this->conn->qstr($val);
    }
    
    /**
     * Replace (Insert or Update)
     * 
     * @param string $table Tabla
     * @param array $record Registro
     * @param string $keyCol Columna clave
     * @param bool $autoQuote Auto quote
     * @param bool $hasAutoInc Auto increment
     * @param bool $forceUpdate Forzar update
     * @param bool $magicq Magic quotes
     * @return ADORecordSet|false
     */
    public function replace($table, $record, $keyCol = "", $autoQuote = false, $hasAutoInc = false, $forceUpdate = false, $magicq = false) {
        return $this->conn->Replace($table, $record, $keyCol, $autoQuote, $hasAutoInc, $forceUpdate, $magicq);
    }

    // ============================================
    // GETTERS Y SETTERS
    // ============================================
    
    /**
     * Obtener error
     */
    public function getError() {
        return $this->error;
    }
    
    /**
     * Obtener último SQL
     */
    public function getQuerySql() {
        return $this->querySql;
    }
    
    /**
     * Obtener conexión
     */
    public function getConnection() {
        return $this->conn;
    }
    
    /**
     * Obtener driver
     */
    public function getDriver() {
        return $this->driver;
    }
}
