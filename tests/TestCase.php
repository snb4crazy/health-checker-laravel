<?php

namespace PackageHealthChecker\Laravel\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PackageHealthChecker\Laravel\PackageHealthCheckerServiceProvider;
use PackageHealthChecker\Laravel\Tests\Support\FakeFailCheck;
use PackageHealthChecker\Laravel\Tests\Support\FakePassCheck;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PackageHealthCheckerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('health-checker.enabled', true);
        $app['config']->set('health-checker.checks', [
            FakePassCheck::class,
            FakeFailCheck::class,
        ]);
        $app['config']->set('health-checker.alerts.email.enabled', false);
    }
}

