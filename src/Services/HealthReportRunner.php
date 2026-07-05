<?php

namespace PackageHealthChecker\Laravel\Services;

use PackageHealthChecker\Laravel\Data\HealthCheckResult;

class HealthReportRunner
{
    public function __construct(
        protected readonly HealthCheckRegistry $registry,
        protected readonly FailureNotifier $notifier,
    ) {}

    /**
     * @param  array<int, string>  $only
     * @param  array<int, string>  $skip
     * @return array<int, HealthCheckResult>
     */
    public function run(array $only = [], array $skip = []): array
    {
        if (! (bool) config('health-checker.enabled', true)) {
            return [
                new HealthCheckResult('health-checker', 'Health Checker', HealthCheckResult::SKIPPED, 'Health checker is disabled.', 0),
            ];
        }

        $only = array_values(array_filter(array_map('trim', $only)));
        $skip = array_values(array_filter(array_map('trim', $skip)));

        $results = [];

        foreach ($this->registry->all() as $check) {
            if ($only !== [] && ! in_array($check->key(), $only, true)) {
                continue;
            }

            if (in_array($check->key(), $skip, true)) {
                continue;
            }

            $results[] = $check->run();
        }

        $this->notifier->notify($results);

        return $results;
    }

    /**
     * @param  array<int, HealthCheckResult>  $results
     * @return array{pass:int,warn:int,fail:int,skipped:int}
     */
    public function summarize(array $results): array
    {
        $summary = [
            HealthCheckResult::PASS => 0,
            HealthCheckResult::WARN => 0,
            HealthCheckResult::FAIL => 0,
            HealthCheckResult::SKIPPED => 0,
        ];

        foreach ($results as $result) {
            $summary[$result->status] = ($summary[$result->status] ?? 0) + 1;
        }

        return [
            'pass' => $summary[HealthCheckResult::PASS],
            'warn' => $summary[HealthCheckResult::WARN],
            'fail' => $summary[HealthCheckResult::FAIL],
            'skipped' => $summary[HealthCheckResult::SKIPPED],
        ];
    }
}

