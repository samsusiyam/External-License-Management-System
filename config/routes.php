<?php

/**
 * ELMS Route Definitions
 *
 * @var \App\Core\Router $router
 *
 * Middleware aliases:
 *   'api'   => ApiAuthMiddleware  (API key + HMAC signature)
 *   'rate'  => RateLimitMiddleware
 *   'auth'  => AuthMiddleware     (admin session)
 */

use App\Core\Router;

/** @var Router $router */

// ---------------------------------------------------------------------------
// Public / redirect
// ---------------------------------------------------------------------------
$router->get('/', function (): void {
    \App\Core\Response::redirect('/admin');
});

$router->get('/health', function (): void {
    \App\Core\Response::json(['status' => true, 'message' => 'ELMS online', 'data' => ['time' => date('c')]]);
});

// ---------------------------------------------------------------------------
// REST API - Client License Verification (Direct key + domain verification)
// ---------------------------------------------------------------------------
$clientMw = ['rate'];

$router->post('/api/license/verify',     'App\Controllers\Api\LicenseApiController@verify', $clientMw);
$router->post('/api/license/activate',   'App\Controllers\Api\LicenseApiController@activate', $clientMw);
$router->post('/api/license/deactivate', 'App\Controllers\Api\LicenseApiController@deactivate', $clientMw);
$router->post('/api/updates/check',      'App\Controllers\Api\UpdateApiController@check', $clientMw);

// ---------------------------------------------------------------------------
// REST API - Administrative Endpoints (Requires API Key)
// ---------------------------------------------------------------------------
$adminApiMw = ['rate', 'api'];

$router->post('/api/license/create',     'App\Controllers\Api\LicenseApiController@create', $adminApiMw);
$router->post('/api/license/renew',      'App\Controllers\Api\LicenseApiController@renew', $adminApiMw);
$router->post('/api/license/reset',      'App\Controllers\Api\LicenseApiController@reset', $adminApiMw);
$router->post('/api/license/suspend',    'App\Controllers\Api\StatusApiController@suspend', $adminApiMw);
$router->post('/api/license/unsuspend',  'App\Controllers\Api\StatusApiController@unsuspend', $adminApiMw);
$router->post('/api/license/terminate',  'App\Controllers\Api\StatusApiController@terminate', $adminApiMw);

// ---------------------------------------------------------------------------
// Admin: authentication
// ---------------------------------------------------------------------------
$router->get('/admin/login',  'App\Controllers\Admin\AuthController@showLogin');
$router->post('/admin/login', 'App\Controllers\Admin\AuthController@login');
$router->get('/admin/logout', 'App\Controllers\Admin\AuthController@logout');
$router->post('/admin/logout', 'App\Controllers\Admin\AuthController@logout');

// ---------------------------------------------------------------------------
// Admin: dashboard + resources (session-guarded)
// ---------------------------------------------------------------------------
$auth = ['auth'];

$router->get('/admin',       'App\Controllers\Admin\DashboardController@index', $auth);
$router->get('/admin/dashboard', 'App\Controllers\Admin\DashboardController@index', $auth);

// Products
$router->get('/admin/products',              'App\Controllers\Admin\ProductController@index', $auth);
$router->get('/admin/products/create',       'App\Controllers\Admin\ProductController@createForm', $auth);
$router->post('/admin/products',             'App\Controllers\Admin\ProductController@store', $auth);
$router->get('/admin/products/{id}/edit',    'App\Controllers\Admin\ProductController@editForm', $auth);
$router->post('/admin/products/{id}/update', 'App\Controllers\Admin\ProductController@update', $auth);
$router->post('/admin/products/{id}/delete', 'App\Controllers\Admin\ProductController@delete', $auth);

// Licenses
$router->get('/admin/licenses',              'App\Controllers\Admin\LicenseController@index', $auth);
$router->get('/admin/licenses/create',       'App\Controllers\Admin\LicenseController@createForm', $auth);
$router->post('/admin/licenses',             'App\Controllers\Admin\LicenseController@store', $auth);
$router->get('/admin/licenses/{id}/edit',    'App\Controllers\Admin\LicenseController@editForm', $auth);
$router->post('/admin/licenses/{id}/update', 'App\Controllers\Admin\LicenseController@update', $auth);
$router->get('/admin/licenses/{id}',         'App\Controllers\Admin\LicenseController@show', $auth);
$router->post('/admin/licenses/{id}/{action}', 'App\Controllers\Admin\LicenseController@action', $auth);

// API keys
$router->get('/admin/apikeys',             'App\Controllers\Admin\ApiKeyController@index', $auth);
$router->post('/admin/apikeys',            'App\Controllers\Admin\ApiKeyController@store', $auth);
$router->post('/admin/apikeys/{id}/revoke',   'App\Controllers\Admin\ApiKeyController@revoke', $auth);
$router->post('/admin/apikeys/{id}/activate', 'App\Controllers\Admin\ApiKeyController@activate', $auth);
$router->post('/admin/apikeys/{id}/delete',   'App\Controllers\Admin\ApiKeyController@delete', $auth);

// Logs
$router->get('/admin/logs/api',   'App\Controllers\Admin\LogController@apiLogs', $auth);
$router->get('/admin/logs/audit', 'App\Controllers\Admin\LogController@auditLogs', $auth);

// Tester & Simulator
$router->get('/admin/tester',     'App\Controllers\Admin\TesterController@index', $auth);
$router->post('/admin/tester/run', 'App\Controllers\Admin\TesterController@run', $auth);

// Documentation
$router->get('/admin/docs', 'App\Controllers\Admin\DocumentationController@show', $auth);
