<?php

namespace PackageHealthChecker\Laravel\Checks;

use PackageHealthChecker\Laravel\Contracts\HealthCheck;
use PackageHealthChecker\Laravel\Data\HealthCheckResult;

abstract class BaseCheck implements HealthCheck
{
    protected float $startedAt = 0.0;

    /**
     * @param  callable(): HealthCheckResult  $callback
     */
    protected function measure(callable $callback): HealthCheckResult
    {
        $this->startedAt = microtime(true);

        try {
            $result = $callback();

            return new HealthCheckResult(
                key: $result->key,
                label: $result->label,
                status: $result->status,
                message: $result->message,
                durationMs: (microtime(true) - $this->startedAt) * 1000,
                meta: $result->meta,
            );
        } catch (\Throwable $e) {
            return $this->result(HealthCheckResult::FAIL, $e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    protected function result(string $status, string $message, array $meta = []): HealthCheckResult
    {
        return new HealthCheckResult(
            key: $this->key(),
            label: $this->label(),
            status: $status,
            message: $message,
            durationMs: (microtime(true) - $this->startedAt) * 1000,
            meta: $meta,
        );
    }
}

