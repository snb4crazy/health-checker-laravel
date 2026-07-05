<?php

namespace PackageHealthChecker\Laravel\Checks;

use Illuminate\Support\Facades\Redis;
use PackageHealthChecker\Laravel\Data\HealthCheckResult;

class RedisCheck extends BaseCheck
{
    public function key(): string
    {
        return 'redis';
    }

    public function label(): string
    {
        return 'Redis';
    }

    public function run(): HealthCheckResult
    {
        return $this->measure(function (): HealthCheckResult {
            $messages = [];
            $meta = ['connections' => [], 'remotes' => []];
            $failed = false;

            foreach ((array) config('health-checker.redis.connections', []) as $connection) {
                try {
                    Redis::connection($connection)->ping();
                    $messages[] = "connection={$connection}:pong";
                    $meta['connections'][$connection] = 'pong';
                } catch (\Throwable $e) {
                    $failed = true;
                    $messages[] = "connection={$connection}:fail";
                    $meta['connections'][$connection] = $e->getMessage();
                }
            }

            foreach ((array) config('health-checker.redis.remotes', []) as $remote) {
                $name = (string) ($remote['name'] ?? $remote['host'] ?? 'remote-redis');

                try {
                    if (! class_exists(\Redis::class)) {
                        throw new \RuntimeException('phpredis extension is required for remote Redis checks.');
                    }

                    $client = new \Redis;
                    $client->connect(
                        (string) ($remote['host'] ?? '127.0.0.1'),
                        (int) ($remote['port'] ?? 6379),
                        (float) ($remote['timeout'] ?? 3),
                    );

                    if (! empty($remote['password'])) {
                        $client->auth((string) $remote['password']);
                    }

                    if (isset($remote['database'])) {
                        $client->select((int) $remote['database']);
                    }

                    $client->ping();
                    $client->close();

                    $messages[] = "remote={$name}:pong";
                    $meta['remotes'][$name] = 'pong';
                } catch (\Throwable $e) {
                    $failed = true;
                    $messages[] = "remote={$name}:fail";
                    $meta['remotes'][$name] = $e->getMessage();
                }
            }

            if ($messages === []) {
                return $this->result(HealthCheckResult::SKIPPED, 'No Redis targets configured.', $meta);
            }

            return $this->result(
                $failed ? HealthCheckResult::FAIL : HealthCheckResult::PASS,
                implode(' | ', $messages),
                $meta,
            );
        });
    }
}

