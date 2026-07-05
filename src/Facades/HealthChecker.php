<?php

namespace PackageHealthChecker\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array run(array $only = [], array $skip = [])
 */
class HealthChecker extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \PackageHealthChecker\Laravel\Services\HealthReportRunner::class;
    }
}

