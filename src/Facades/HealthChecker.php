<?php

namespace PackageHealthChecker\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use PackageHealthChecker\Laravel\Services\HealthReportRunner;

/**
 * @method static array run(array $only = [], array $skip = [])
 */
class HealthChecker extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return HealthReportRunner::class;
    }
}
