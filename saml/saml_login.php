<?php
require_once __DIR__.'/../vendor/autoload.php';
$settings = require __DIR__.'/settings.php';
$auth     = new OneLogin\Saml2\Auth($settings);

// Puedes pasar como RelayState la página a la que quieres volver tras autenticación
$target = isset($_GET['RelayState']) ? $_GET['RelayState'] : '/index_frames.php';
$auth->login($target);

