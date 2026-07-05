<?php

namespace PackageHealthChecker\Laravel\Contracts;

use PackageHealthChecker\Laravel\Data\HealthCheckResult;

interface HealthCheck
{
    public function key(): string;

    public function label(): string;

    public function run(): HealthCheckResult;
}

