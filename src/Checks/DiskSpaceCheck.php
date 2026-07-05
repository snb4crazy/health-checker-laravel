<?php

namespace PackageHealthChecker\Laravel\Checks;

use PackageHealthChecker\Laravel\Data\HealthCheckResult;

class DiskSpaceCheck extends BaseCheck
{
    public function key(): string
    {
        return 'disk-space';
    }

    public function label(): string
    {
        return 'Disk Space';
    }

    public function run(): HealthCheckResult
    {
        return $this->measure(function (): HealthCheckResult {
            $paths = (array) config('health-checker.disk_space.paths', []);

            if ($paths === []) {
                return $this->result(HealthCheckResult::SKIPPED, 'No disk paths configured.');
            }

            $messages = [];
            $status = HealthCheckResult::PASS;

            foreach ($paths as $definition) {
                $path = (string) ($definition['path'] ?? '');

                if ($path === '' || ! is_dir($path)) {
                    $status = HealthCheckResult::FAIL;
                    $messages[] = "path={$path}:invalid";
                    continue;
                }

                $total = @disk_total_space($path);
                $free = @disk_free_space($path);

                if (! is_float($total) && ! is_int($total)) {
                    $status = HealthCheckResult::FAIL;
                    $messages[] = "path={$path}:unreadable";
                    continue;
                }

                if (! is_float($free) && ! is_int($free)) {
                    $status = HealthCheckResult::FAIL;
                    $messages[] = "path={$path}:unreadable";
                    continue;
                }

                $freePercent = ((float) $free / (float) $total) * 100;
                $warn = (float) ($definition['warn_below_percent'] ?? 15);
                $fail = (float) ($definition['fail_below_percent'] ?? 5);

                if ($freePercent <= $fail) {
                    $status = HealthCheckResult::FAIL;
                    $messages[] = sprintf('path=%s:%.2f%% free (FAIL)', $path, $freePercent);
                    continue;
                }

                if ($freePercent <= $warn && $status !== HealthCheckResult::FAIL) {
                    $status = HealthCheckResult::WARN;
                    $messages[] = sprintf('path=%s:%.2f%% free (WARN)', $path, $freePercent);
                    continue;
                }

                $messages[] = sprintf('path=%s:%.2f%% free', $path, $freePercent);
            }

            return $this->result($status, implode(' | ', $messages));
        });
    }
}

