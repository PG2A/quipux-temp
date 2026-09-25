<?php
// /data/sites/devdocs.ucuenca.edu.ec/quipux/saml/metadata.php
require_once __DIR__.'/../vendor/autoload.php';
$settings = require __DIR__.'/settings.php';

use OneLogin\Saml2\Settings;

// Construye las opciones SAML y genera el metadato
try {
    $samlSettings = new Settings($settings, true);
    $metadata     = $samlSettings->getSPMetadata();
    $errors       = $samlSettings->validateMetadata($metadata);
    if (!empty($errors)) {
        header('Content-Type: text/plain');
        echo 'Invalid SP metadata: '.implode(', ', $errors);
        exit;
    }
    header('Content-Type: text/xml');
    echo $metadata;
} catch (Exception $e) {
    header('Content-Type: text/plain');
    echo 'Error generating metadata: '.$e->getMessage();
}

