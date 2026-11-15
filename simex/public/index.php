<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// The PHP version is set per domain in cPanel; a subdomain or add-on domain can run an older one
// than the main domain. Show which version actually serves this address.
if (PHP_VERSION_ID < 80300) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    echo '<!DOCTYPE html><html lang="ro"><meta charset="utf-8"><title>Versiune PHP nepotrivită</title>'
        .'<body style="font-family:sans-serif;max-width:640px;margin:12vh auto;padding:0 20px;line-height:1.5">'
        .'<h1 style="font-size:22px">Platforma cere PHP 8.3 sau mai nou</h1>'
        .'<p>Această adresă (<strong>'.htmlspecialchars($_SERVER['HTTP_HOST'] ?? '', ENT_QUOTES).'</strong>) este servită acum cu PHP <strong>'.PHP_VERSION.'</strong>.</p>'
        .'<p>În cPanel → MultiPHP Manager (sau Select PHP Version), alegeți PHP 8.3 pentru exact acest domeniu sau subdomeniu. '
        .'Versiunea se setează separat pentru fiecare domeniu.</p></body></html>';
    exit;
}

// Not installed yet: send the visitor to the installer instead of an error page.
if (! is_file(__DIR__.'/../install.lock') && is_file(__DIR__.'/../install.php') && ! is_file(__DIR__.'/../.env')) {
    header('Location: /install.php', true, 302);
    exit;
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
