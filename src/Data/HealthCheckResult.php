<?php

namespace PackageHealthChecker\Laravel\Data;

class HealthCheckResult
{
    public const PASS = 'pass';
    public const WARN = 'warn';
    public const FAIL = 'fail';
    public const SKIPPED = 'skipped';

    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $status,
        public string $message,
        public float $durationMs,
        public array $meta = [],
    ) {}

    public function isFailure(bool $includeWarn): bool
    {
        if ($this->status === self::FAIL) {
            return true;
        }

        return $includeWarn && $this->status === self::WARN;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'status' => $this->status,
            'message' => $this->message,
            'duration_ms' => round($this->durationMs, 2),
            'meta' => $this->meta,
        ];
    }
}

