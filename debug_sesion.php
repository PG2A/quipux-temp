<?php
/**
 * debug_sesion.php - Script de debugging para verificar sesión
 * 
 * Uso: Acceder a http://servidor/quipux/debug_sesion.php después del login
 */

session_start();

echo "<h1>Debug de Sesión</h1>";
echo "<hr>";

// ============================================
// 1. INFORMACIÓN DE SESIÓN
// ============================================

echo "<h2>1. Información de Sesión</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "</pre>";

// ============================================
// 2. VARIABLES DE SESIÓN
// ============================================

echo "<h2>2. Variables de Sesión (\$_SESSION)</h2>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

// ============================================
// 3. VERIFICAR VARIABLES CRÍTICAS
// ============================================

echo "<h2>3. Verificar Variables Críticas</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Variable</th><th>Existe</th><th>Valor</th></tr>";

$variables_criticas = array(
    'krd' => 'Usuario',
    'usua_codi' => 'Código de Usuario',
    'session_initiated' => 'Sesión Iniciada',
    'session_ip' => 'IP de Sesión',
    'session_user_agent' => 'User Agent Hash',
    'session_created' => 'Fecha Creación',
    'session_last_activity' => 'Última Actividad',
    'initiated' => 'Iniciada',
    'hora_session' => 'Hora de Sesión',
);

foreach ($variables_criticas as $var => $desc) {
    $existe = isset($_SESSION[$var]) ? 'SÍ' : 'NO';
    $valor = isset($_SESSION[$var]) ? $_SESSION[$var] : 'N/A';
    
    // Truncar valores largos
    if (strlen($valor) > 50) {
        $valor = substr($valor, 0, 50) . '...';
    }
    
    echo "<tr>";
    echo "<td><strong>$var</strong><br>($desc)</td>";
    echo "<td>$existe</td>";
    echo "<td><pre>$valor</pre></td>";
    echo "</tr>";
}

echo "</table>";

// ============================================
// 4. INFORMACIÓN DEL SERVIDOR
// ============================================

echo "<h2>4. Información del Servidor</h2>";
echo "<pre>";
echo "IP del Cliente: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . "\n";
echo "User Agent Hash: " . hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . "\n";
echo "Tiempo Actual: " . time() . "\n";
echo "Fecha Actual: " . date('Y-m-d H:i:s') . "\n";
echo "</pre>";

// ============================================
// 5. VALIDAR SESIÓN EN BASE DE DATOS
// ============================================

echo "<h2>5. Validar Sesión en Base de Datos</h2>";

try {
    include_once(__DIR__ . '/include/db/ConnectionHandler.php');
    include_once(__DIR__ . '/config.php');
    
    $db = new ConnectionHandler(__DIR__);
    $db->conn->SetFetchMode(ADODB_FETCH_ASSOC);
    
    if ($db->conn->_connectionID) {
        echo "<p><strong>Conexión a BD: OK</strong></p>";
        
        if (isset($_SESSION['usua_codi'])) {
            $usua_codi = (int)$_SESSION['usua_codi'];
            $sesion_id = $db->conn->qstr(session_id());
            
            $query = "SELECT * FROM usuarios_sesion 
                      WHERE usua_codi = $usua_codi 
                      AND usua_sesion = $sesion_id 
                      LIMIT 1";
            
            echo "<p><strong>Query:</strong></p>";
            echo "<pre>$query</pre>";
            
            $rs = $db->conn->Execute($query);
            
            if ($rs && !$rs->EOF) {
                echo "<p><strong>Resultado: ENCONTRADA en BD</strong></p>";
                echo "<pre>";
                var_dump($rs->fields);
                echo "</pre>";
            } else {
                echo "<p><strong style='color: red;'>Resultado: NO ENCONTRADA en BD</strong></p>";
            }
        } else {
            echo "<p><strong style='color: red;'>ERROR: usua_codi no está en sesión</strong></p>";
        }
    } else {
        echo "<p><strong style='color: red;'>ERROR: No hay conexión a BD</strong></p>";
    }
} catch (Exception $e) {
    echo "<p><strong style='color: red;'>ERROR: " . $e->getMessage() . "</strong></p>";
}

// ============================================
// 6. VALIDAR CON SessionManager
// ============================================

echo "<h2>6. Validar con SessionManager</h2>";

if (file_exists(__DIR__ . '/SessionManager.php')) {
    try {
        require_once(__DIR__ . '/SessionManager.php');
        
        $sessionManager = new SessionManager($db);
        
        $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        echo "<p><strong>Validando con SessionManager...</strong></p>";
        
        if ($sessionManager->validateSession($clientIP, $userAgent)) {
            echo "<p><strong style='color: green;'>SessionManager: VÁLIDA</strong></p>";
        } else {
            echo "<p><strong style='color: red;'>SessionManager: INVÁLIDA</strong></p>";
        }
    } catch (Exception $e) {
        echo "<p><strong style='color: red;'>SessionManager ERROR: " . $e->getMessage() . "</strong></p>";
    }
} else {
    echo "<p><strong style='color: orange;'>SessionManager.php no encontrado</strong></p>";
}

// ============================================
// 7. LOGS
// ============================================

echo "<h2>7. Últimos Logs</h2>";

if (file_exists('/var/log/php-errors.log')) {
    echo "<p><strong>Últimas 20 líneas de /var/log/php-errors.log:</strong></p>";
    echo "<pre>";
    $logs = shell_exec("tail -20 /var/log/php-errors.log");
    echo htmlspecialchars($logs);
    echo "</pre>";
} else {
    echo "<p><strong style='color: orange;'>Archivo de logs no encontrado</strong></p>";
}

?>
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        background-color: #f5f5f5;
    }
    h1, h2 {
        color: #333;
    }
    table {
        background-color: white;
        margin: 20px 0;
    }
    pre {
        background-color: #f0f0f0;
        padding: 10px;
        border-radius: 5px;
        overflow-x: auto;
    }
    p {
        background-color: white;
        padding: 10px;
        border-radius: 5px;
        margin: 10px 0;
    }
</style>
