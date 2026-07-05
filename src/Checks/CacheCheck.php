<?php

namespace PackageHealthChecker\Laravel\Checks;

use Illuminate\Support\Facades\Cache;
use PackageHealthChecker\Laravel\Data\HealthCheckResult;

class CacheCheck extends BaseCheck
{
    public function key(): string
    {
        return 'cache';
    }

    public function label(): string
    {
        return 'Cache';
    }

    public function run(): HealthCheckResult
    {
        return $this->measure(function (): HealthCheckResult {
            $store = (string) config('health-checker.cache.store', config('cache.default'));
            $key = 'health-checker:probe:'.uniqid('', true);

            try {
                Cache::store($store)->put($key, 'ok', 30);
                $value = Cache::store($store)->get($key);
                Cache::store($store)->forget($key);

                if ($value !== 'ok') {
                    return $this->result(HealthCheckResult::FAIL, "Cache read mismatch on store={$store}.");
                }

                return $this->result(HealthCheckResult::PASS, "Cache write/read succeeded on store={$store}.");
            } catch (\Throwable $e) {
                return $this->result(HealthCheckResult::FAIL, "Cache check failed on store={$store}: {$e->getMessage()}");
            }
        });
    }
}

