<?php

namespace Elms\License;

use Illuminate\Support\ServiceProvider;

/**
 * ELMS License Service Provider (Laravel).
 *
 * Publishes config and binds the ElmsLicenseManager singleton.
 * Register in config/app.php providers (Laravel <11) or it will be
 * auto-discovered via composer "extra.laravel.providers".
 */
class ElmsLicenseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/elms.php', 'elms');

        $this->app->singleton(ElmsLicenseManager::class, function ($app) {
            /** @var array<string,mixed> $cfg */
            $cfg = $app['config']->get('elms');
            return new ElmsLicenseManager($cfg);
        });

        $this->app->alias(ElmsLicenseManager::class, 'elms.license');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/elms.php' => function_exists('config_path') ? config_path('elms.php') : base_path('config/elms.php'),
        ], 'elms-config');
    }
}
