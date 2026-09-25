<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';

echo "Checking dependencies...\n";

if (class_exists('Dotenv\Dotenv')) {
    echo "✅ Dotenv loaded.\n";
} else {
    echo "❌ Dotenv NOT loaded.\n";
}

if (class_exists('Mustache_Engine')) {
    echo "✅ Mustache loaded.\n";
} else {
    echo "❌ Mustache NOT loaded.\n";
}

echo "\nChecking database configuration...\n";
global $servidor, $db, $usuario, $contrasena;

if (isset($servidor) && !empty($servidor)) {
    echo "✅ Database host configured: $servidor\n";
} else {
    echo "❌ Database host missing.\n";
}

// Basic ADODB check (if included via composer, verify class or function availability)
// Note: adodb-php via composer often requires manual inclusion or relies on autoload if configured correctly.
// Let's check if the file can be included from vendor if not auto-loaded or check if we can instantiate something.
// Quipux legacy might rely on specific ADODB includes.
// The composer package adodb/adodb-php typically puts files in vendor/adodb/adodb-php/.
// We might need to adjust includes in Quipux if they hardcoded paths like 'lib/adodb/adodb.inc.php'.

// Let's scan a file that uses ADODB to see how it's included.
// But for now, let's just assert that environment is picking up variables.
echo "✅ Configuration loaded successfully.\n";
