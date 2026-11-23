<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (!file_exists(__DIR__.'/../.env') && file_exists(__DIR__.'/../.env.example')) {
    @copy(__DIR__.'/../.env.example', __DIR__.'/../.env');
}

foreach ([
    __DIR__.'/../storage/app/public',
    __DIR__.'/../storage/app/private',
    __DIR__.'/../storage/framework/cache/data',
    __DIR__.'/../storage/framework/sessions',
    __DIR__.'/../storage/framework/testing',
    __DIR__.'/../storage/framework/views',
    __DIR__.'/../storage/logs',
    __DIR__.'/../bootstrap/cache',
] as $mqRuntimeDir) {
    if (!is_dir($mqRuntimeDir)) {
        @mkdir($mqRuntimeDir, 0775, true);
    }
}

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

$mqRevFile = __DIR__.'/../storage/framework/medquest_rev.txt';
if ((!file_exists($mqRevFile) || trim((string) @file_get_contents($mqRevFile)) !== '2026-11-23-v17-formal-export-form') && function_exists('opcache_reset')) {
    @opcache_reset();
}

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
