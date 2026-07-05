<?php

namespace PackageHealthChecker\Laravel\Checks;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use PackageHealthChecker\Laravel\Data\HealthCheckResult;

class QueueCheck extends BaseCheck
{
    public function key(): string
    {
        return 'queue';
    }

    public function label(): string
    {
        return 'Queue';
    }

    public function run(): HealthCheckResult
    {
        return $this->measure(function (): HealthCheckResult {
            $connection = (string) config('health-checker.queue.connection', config('queue.default', 'sync'));

            if ($connection === 'sync') {
                return $this->result(HealthCheckResult::WARN, 'Queue connection is sync; async workers are not exercised.');
            }

            $driver = (string) config("queue.connections.{$connection}.driver", $connection);

            try {
                if ($driver === 'database') {
                    if (! Schema::hasTable('jobs')) {
                        return $this->result(HealthCheckResult::FAIL, 'Queue driver=database but jobs table is missing.');
                    }

                    DB::table('jobs')->limit(1)->get();

                    return $this->result(HealthCheckResult::PASS, 'Queue database backend is reachable.');
                }

                if ($driver === 'redis') {
                    $redisConnection = (string) config("queue.connections.{$connection}.connection", 'default');
                    Redis::connection($redisConnection)->ping();

                    return $this->result(HealthCheckResult::PASS, "Queue Redis backend connection={$redisConnection} is reachable.");
                }

                return $this->result(HealthCheckResult::PASS, "Queue driver={$driver} is configured (basic check).", ['driver' => $driver]);
            } catch (\Throwable $e) {
                return $this->result(HealthCheckResult::FAIL, "Queue check failed: {$e->getMessage()}", ['driver' => $driver]);
            }
        });
    }
}

