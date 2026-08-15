<?php
/**
 * ELMS Configuration loader.
 * Reads values from the .env file at the project root and exposes
 * them as a structured config array. No external dependencies.
 */

use App\Core\Config;

$root = dirname(__DIR__);
Config::loadEnv($root . DIRECTORY_SEPARATOR . '.env');

return [
    'app' => [
        'name'     => Config::env('APP_NAME', 'External License Manager'),
        'env'      => Config::env('APP_ENV', 'production'),
        'debug'    => Config::envBool('APP_DEBUG', false),
        'url'      => rtrim(Config::env('APP_URL', 'http://localhost'), '/'),
        'timezone' => Config::env('APP_TIMEZONE', 'UTC'),
        'key'      => Config::env('APP_KEY', ''),
        'root'     => $root,
    ],

    'api' => [
        'base_path' => Config::env('API_BASE_PATH', '/api'),
    ],

    'db' => [
        'host'    => Config::env('DB_HOST', '127.0.0.1'),
        'port'    => (int) Config::env('DB_PORT', '3306'),
        'name'    => Config::env('DB_NAME', 'elms'),
        'user'    => Config::env('DB_USER', 'root'),
        'pass'    => Config::env('DB_PASS', ''),
        'charset' => Config::env('DB_CHARSET', 'utf8mb4'),
    ],

    'session' => [
        'name'     => Config::env('SESSION_NAME', 'elms_session'),
        'lifetime' => (int) Config::env('SESSION_LIFETIME', '7200'),
    ],

    'security' => [
        'signature_max_skew' => (int) Config::env('SIGNATURE_MAX_SKEW', '300'),
        'rate_limit_max'     => (int) Config::env('RATE_LIMIT_MAX', '120'),
        'rate_limit_window'  => (int) Config::env('RATE_LIMIT_WINDOW', '60'),
        'login_max_attempts' => (int) Config::env('LOGIN_MAX_ATTEMPTS', '5'),
        'login_window'       => (int) Config::env('LOGIN_WINDOW', '900'),
    ],

    'log' => [
        'path' => $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, Config::env('LOG_PATH', 'storage/logs')),
    ],
];
