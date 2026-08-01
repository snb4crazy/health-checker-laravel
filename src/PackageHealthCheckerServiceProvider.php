<?php

namespace PackageHealthChecker\Laravel;

use Illuminate\Support\ServiceProvider;
use PackageHealthChecker\Laravel\Console\HealthCheckCommand;

class PackageHealthCheckerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/health-checker.php', 'health-checker');
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/health-checker.php' => config_path('health-checker.php'),
        ], 'health-checker-config');

        $this->commands([
            HealthCheckCommand::class,
        ]);
    }
}
