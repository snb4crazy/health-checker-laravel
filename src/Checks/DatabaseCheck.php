<?php

namespace PackageHealthChecker\Laravel\Checks;

use Illuminate\Support\Facades\DB;
use PackageHealthChecker\Laravel\Data\HealthCheckResult;

class DatabaseCheck extends BaseCheck
{
    public function key(): string
    {
        return 'database';
    }

    public function label(): string
    {
        return 'Database';
    }

    public function run(): HealthCheckResult
    {
        return $this->measure(function (): HealthCheckResult {
            $messages = [];
            $meta = ['connections' => [], 'remotes' => []];
            $failed = false;

            foreach ((array) config('health-checker.database.connections', []) as $connection) {
                try {
                    DB::connection($connection)->select('select 1 as ok');
                    $messages[] = "connection={$connection}:ok";
                    $meta['connections'][$connection] = 'ok';
                } catch (\Throwable $e) {
                    $failed = true;
                    $messages[] = "connection={$connection}:fail";
                    $meta['connections'][$connection] = $e->getMessage();
                }
            }

            foreach ((array) config('health-checker.database.remotes', []) as $remote) {
                $name = (string) ($remote['name'] ?? $remote['host'] ?? 'remote-db');

                try {
                    $pdo = new \PDO(
                        $this->dsn($remote),
                        (string) ($remote['username'] ?? ''),
                        (string) ($remote['password'] ?? ''),
                        [
                            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                            \PDO::ATTR_TIMEOUT => (int) ($remote['timeout'] ?? 3),
                        ],
                    );
                    $pdo->query('select 1');
                    $messages[] = "remote={$name}:ok";
                    $meta['remotes'][$name] = 'ok';
                } catch (\Throwable $e) {
                    $failed = true;
                    $messages[] = "remote={$name}:fail";
                    $meta['remotes'][$name] = $e->getMessage();
                }
            }

            if ($messages === []) {
                return $this->result(HealthCheckResult::SKIPPED, 'No database targets configured.', $meta);
            }

            return $this->result(
                $failed ? HealthCheckResult::FAIL : HealthCheckResult::PASS,
                implode(' | ', $messages),
                $meta,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $remote
     */
    protected function dsn(array $remote): string
    {
        $driver = (string) ($remote['driver'] ?? 'mysql');
        $host = (string) ($remote['host'] ?? '127.0.0.1');
        $port = (int) ($remote['port'] ?? 3306);
        $database = (string) ($remote['database'] ?? '');

        return match ($driver) {
            'pgsql' => "pgsql:host={$host};port={$port};dbname={$database}",
            'sqlsrv' => "sqlsrv:Server={$host},{$port};Database={$database}",
            default => "mysql:host={$host};port={$port};dbname={$database}",
        };
    }
}

