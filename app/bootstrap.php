<?php

/**
 * ELMS Bootstrap
 *
 * PSR-4-ish autoloader for the App\ namespace, config boot,
 * session start, error handling, and timezone setup.
 * Included by public/index.php and CLI scripts.
 */

declare(strict_types=1);

define('ELMS_ROOT', dirname(__DIR__));
define('ELMS_APP', ELMS_ROOT . DIRECTORY_SEPARATOR . 'app');

// --- Autoloader -----------------------------------------------------------
spl_autoload_register(static function (string $class): void {
    $prefix  = 'App\\';
    $baseDir = ELMS_APP . DIRECTORY_SEPARATOR;

    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// --- Config ---------------------------------------------------------------
use App\Core\Config;

Config::boot(ELMS_ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php');

// --- Timezone -------------------------------------------------------------
date_default_timezone_set((string) Config::get('app.timezone', 'UTC'));

// --- Error handling -------------------------------------------------------
$debug = (bool) Config::get('app.debug', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');

$logDir = (string) Config::get('log.path');
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
ini_set('error_log', $logDir . DIRECTORY_SEPARATOR . 'php-error.log');

return true;
