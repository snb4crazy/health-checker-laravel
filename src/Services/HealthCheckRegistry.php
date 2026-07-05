<?php

namespace PackageHealthChecker\Laravel\Services;

use Illuminate\Contracts\Container\Container;
use PackageHealthChecker\Laravel\Contracts\HealthCheck;

class HealthCheckRegistry
{
    public function __construct(protected readonly Container $container) {}

    /**
     * @return array<int, HealthCheck>
     */
    public function all(): array
    {
        $instances = [];

        foreach ((array) config('health-checker.checks', []) as $checkClass) {
            $check = $this->container->make($checkClass);

            if (! $check instanceof HealthCheck) {
                continue;
            }

            $instances[] = $check;
        }

        return $instances;
    }
}

