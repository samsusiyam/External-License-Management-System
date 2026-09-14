<?php

declare(strict_types=1);

use App\Controllers\InstallController;
use App\Core\Config;
use App\Core\Request;

elms_test('installer_requirements_check', function (): void {
    $controller = new InstallController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('checkRequirements');
    $method->setAccessible(true);
    $reqs = $method->invoke($controller);

    if (!is_array($reqs) || !isset($reqs['all_passed'])) {
        throw new RuntimeException('checkRequirements did not return expected structure');
    }
    if (!isset($reqs['requirements']) || !isset($reqs['permissions'])) {
        throw new RuntimeException('checkRequirements missing requirements or permissions keys');
    }
});

elms_test('installer_env_builder', function (): void {
    $controller = new InstallController();
    $reflection = new ReflectionClass($controller);
    $method = $reflection->getMethod('buildEnvFile');
    $method->setAccessible(true);
    $content = $method->invoke($controller, [
        'APP_NAME'     => 'Test App',
        'APP_ENV'      => 'production',
        'APP_DEBUG'    => 'false',
        'APP_URL'      => 'http://localhost/license/public',
        'APP_TIMEZONE' => 'UTC',
        'DB_HOST'      => '127.0.0.1',
        'DB_PORT'      => '3306',
        'DB_NAME'      => 'test_db',
        'DB_USER'      => 'root',
        'DB_PASS'      => 'secret',
        'DB_CHARSET'   => 'utf8mb4',
        'APP_KEY'      => 'random-test-key-0123456789',
    ]);

    if (!str_contains($content, 'APP_NAME="Test App"')) {
        throw new RuntimeException('buildEnvFile missing APP_NAME');
    }
    if (!str_contains($content, 'DB_NAME=test_db')) {
        throw new RuntimeException('buildEnvFile missing DB_NAME');
    }
    if (!str_contains($content, 'APP_KEY=random-test-key-0123456789')) {
        throw new RuntimeException('buildEnvFile missing APP_KEY');
    }
});
