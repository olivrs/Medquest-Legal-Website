<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (!file_exists(__DIR__.'/../.env') && file_exists(__DIR__.'/../.env.example')) {
    @copy(__DIR__.'/../.env.example', __DIR__.'/../.env');
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

$mqRevFile = __DIR__.'/../storage/framework/medquest_rev.txt';
if ((!file_exists($mqRevFile) || trim((string) @file_get_contents($mqRevFile)) !== '2026-11-17-v11-english-ui-opcache') && function_exists('opcache_reset')) {
    @opcache_reset();
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
