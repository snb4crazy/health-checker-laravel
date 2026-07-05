<?php

namespace PackageHealthChecker\Laravel\Checks;

use Illuminate\Support\Facades\Storage;
use PackageHealthChecker\Laravel\Data\HealthCheckResult;

class StorageCheck extends BaseCheck
{
    public function key(): string
    {
        return 'storage';
    }

    public function label(): string
    {
        return 'Storage';
    }

    public function run(): HealthCheckResult
    {
        return $this->measure(function (): HealthCheckResult {
            $disks = (array) config('health-checker.storage.disks', []);
            $directory = (string) config('health-checker.storage.probe_directory', 'health-checker');

            if ($disks === []) {
                return $this->result(HealthCheckResult::SKIPPED, 'No storage disks configured.');
            }

            $messages = [];
            $failed = false;

            foreach ($disks as $disk) {
                $path = trim($directory, '/').'/probe-'.uniqid('', true).'.txt';

                try {
                    Storage::disk((string) $disk)->put($path, 'ok');
                    $content = Storage::disk((string) $disk)->get($path);
                    Storage::disk((string) $disk)->delete($path);

                    if ($content !== 'ok') {
                        throw new \RuntimeException('Read mismatch.');
                    }

                    $messages[] = "disk={$disk}:ok";
                } catch (\Throwable $e) {
                    $failed = true;
                    $messages[] = "disk={$disk}:fail";
                }
            }

            return $this->result(
                $failed ? HealthCheckResult::FAIL : HealthCheckResult::PASS,
                implode(' | ', $messages),
            );
        });
    }
}

