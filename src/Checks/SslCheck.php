<?php

namespace PackageHealthChecker\Laravel\Checks;

use PackageHealthChecker\Laravel\Data\HealthCheckResult;

class SslCheck extends BaseCheck
{
    public function key(): string
    {
        return 'ssl';
    }

    public function label(): string
    {
        return 'SSL';
    }

    public function run(): HealthCheckResult
    {
        return $this->measure(function (): HealthCheckResult {
            if (! (bool) config('health-checker.ssl.enabled', false)) {
                return $this->result(HealthCheckResult::SKIPPED, 'SSL check disabled.');
            }

            $targets = (array) config('health-checker.ssl.targets', []);
            if ($targets === []) {
                return $this->result(HealthCheckResult::SKIPPED, 'No SSL targets configured.');
            }

            $warnDays = (int) config('health-checker.ssl.warn_days_before_expiry', 14);
            $messages = [];
            $status = HealthCheckResult::PASS;

            foreach ($targets as $target) {
                $name = (string) ($target['name'] ?? $target['host'] ?? 'ssl-target');
                $host = (string) ($target['host'] ?? '');
                $port = (int) ($target['port'] ?? 443);
                $timeout = (int) ($target['timeout'] ?? 5);

                if ($host === '') {
                    $status = HealthCheckResult::FAIL;
                    $messages[] = "target={$name}:missing-host";
                    continue;
                }

                try {
                    $remainingDays = $this->remainingDays($host, $port, $timeout);

                    if ($remainingDays <= 0) {
                        $status = HealthCheckResult::FAIL;
                        $messages[] = "target={$name}:expired";
                        continue;
                    }

                    if ($remainingDays <= $warnDays && $status !== HealthCheckResult::FAIL) {
                        $status = HealthCheckResult::WARN;
                        $messages[] = "target={$name}:expires-in={$remainingDays}d";
                        continue;
                    }

                    $messages[] = "target={$name}:ok({$remainingDays}d)";
                } catch (\Throwable $e) {
                    $status = HealthCheckResult::FAIL;
                    $messages[] = "target={$name}:fail";
                }
            }

            return $this->result($status, implode(' | ', $messages));
        });
    }

    protected function remainingDays(string $host, int $port, int $timeout): int
    {
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        $client = @stream_socket_client(
            "ssl://{$host}:{$port}",
            $errorNumber,
            $errorString,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context,
        );

        if (! is_resource($client)) {
            throw new \RuntimeException("SSL connect failed: {$errorNumber} {$errorString}");
        }

        $params = stream_context_get_params($client);
        fclose($client);

        $certificate = $params['options']['ssl']['peer_certificate'] ?? null;
        if (! $certificate) {
            throw new \RuntimeException('Peer certificate not found.');
        }

        $parsed = openssl_x509_parse($certificate);
        $validTo = (int) ($parsed['validTo_time_t'] ?? 0);

        if ($validTo <= 0) {
            throw new \RuntimeException('Certificate expiration is unreadable.');
        }

        return (int) floor(($validTo - time()) / 86400);
    }
}

