<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');
error_reporting(E_ALL);

session_start();

echo "<pre>";
echo "DEBUG LOGIN SAML INICIADO\n";

try {
    echo "PHP_VERSION: " . PHP_VERSION . "\n";
    echo "__DIR__: " . __DIR__ . "\n";

    $autoload = __DIR__ . '/../vendor/autoload.php';
    $settingsFile = __DIR__ . '/settings.php';

    echo "autoload: $autoload\n";
    echo "settings: $settingsFile\n";

    if (!is_file($autoload)) {
        throw new Exception("No existe vendor/autoload.php en: $autoload");
    }

    if (!is_readable($autoload)) {
        throw new Exception("No se puede leer vendor/autoload.php: $autoload");
    }

    if (!is_file($settingsFile)) {
        throw new Exception("No existe settings.php en: $settingsFile");
    }

    if (!is_readable($settingsFile)) {
        throw new Exception("No se puede leer settings.php: $settingsFile");
    }

    require_once $autoload;

    echo "autoload cargado OK\n";

    $settings = require $settingsFile;

    $settings['sp']['assertionConsumerService']['url'] = 'https://devdocs.ucuenca.edu.ec/saml/accs.php';

    echo "settings cargado OK\n";

    if (!class_exists('OneLogin\Saml2\Auth')) {
        throw new Exception("No existe la clase OneLogin\\Saml2\\Auth. Revisar composer/vendor.");
    }

    echo "Clase OneLogin\\Saml2\\Auth encontrada OK\n";

    echo "\nSP entityId: " . ($settings['sp']['entityId'] ?? 'NO DEFINIDO') . "\n";
    echo "ACS URL: " . ($settings['sp']['assertionConsumerService']['url'] ?? 'NO DEFINIDO') . "\n";
    echo "IdP entityId: " . ($settings['idp']['entityId'] ?? 'NO DEFINIDO') . "\n";
    echo "IdP SSO: " . ($settings['idp']['singleSignOnService']['url'] ?? 'NO DEFINIDO') . "\n";

    $expectedUser = 'pedro.gutierreza@ucuenca.edu.ec';

    $_SESSION['saml_debug'] = true;
    $_SESSION['saml_debug_expected_user'] = strtolower(trim($expectedUser));
    $_SESSION['saml_debug_started_at'] = date('Y-m-d H:i:s');

    echo "\nUsuario esperado: $expectedUser\n";

    $auth = new OneLogin\Saml2\Auth($settings);

    echo "Objeto Auth creado OK\n";

    $returnTo = 'https://devdocs.ucuenca.edu.ec/saml/accs.php?debug=1';

    $params = [
        'login_hint' => $expectedUser,
    ];

    echo "ReturnTo: $returnTo\n";
    echo "\nGenerando URL de login...\n";

    /*
     * Importante:
     * getLoginUrl permite ver la URL generada sin hacer redirect automático.
     */
    $loginUrl = $auth->login($returnTo, $params, false, false, true);

    echo "\nURL generada:\n";
    echo htmlspecialchars($loginUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    echo "\n\n<a href=\"" . htmlspecialchars($loginUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\">Continuar hacia ADFS</a>\n";

} catch (Throwable $e) {
    echo "\n\nERROR CAPTURADO\n";
    echo "Clase: " . get_class($e) . "\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Linea: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
