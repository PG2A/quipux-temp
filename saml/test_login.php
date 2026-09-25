<?php
// /data/sites/devdocs.ucuenca.edu.ec/quipux/saml/test_login.php
session_start();
// Activa el modo de prueba
$_SESSION['saml_test'] = true;

// Usa la misma configuración que el resto de tu aplicación
require_once __DIR__.'/../vendor/autoload.php';
$settings = require __DIR__.'/settings.php';
$auth = new OneLogin\Saml2\Auth($settings);

// Lanza la autenticación contra el IdP. No especificamos returnTo para que vuelva al ACS por defecto.
$auth->login();

