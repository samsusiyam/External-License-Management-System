<?php

/**
 * ELMS Front Controller
 *
 * All web + API requests are routed through this single entry point.
 */

declare(strict_types=1);

// Bootstrap: autoloader, config, error handling.
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\View;

// --- Session (hardened) ----------------------------------------------------
$sessionName = (string) Config::get('session.name', 'elms_session');
session_name($sessionName);
session_set_cookie_params([
    'lifetime' => (int) Config::get('session.lifetime', 7200),
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure'   => (($_SERVER['HTTPS'] ?? '') === 'on'),
]);
session_start();

// --- Views -----------------------------------------------------------------
View::setPath(ELMS_APP . DIRECTORY_SEPARATOR . 'Views');

// --- Router ----------------------------------------------------------------
$router = new Router();
$router->registerMiddleware('auth', \App\Middleware\AuthMiddleware::class);
$router->registerMiddleware('api',  \App\Middleware\ApiAuthMiddleware::class);
$router->registerMiddleware('rate', \App\Middleware\RateLimitMiddleware::class);

require ELMS_ROOT . '/config/routes.php';

$request = new Request();

try {
    $router->dispatch($request);
} catch (\Throwable $e) {
    error_log('[ELMS] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    $debug = (bool) Config::get('app.debug', false);
    if ($request->isApi()) {
        Response::error(
            $debug ? $e->getMessage() : 'Internal server error',
            500
        );
    }
    Response::html(
        $debug
            ? '<h1>500 Error</h1><pre>' . htmlspecialchars($e->getMessage()) . '</pre>'
            : '<h1>500 Internal Server Error</h1>',
        500
    );
}
