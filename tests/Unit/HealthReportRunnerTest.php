<?php

namespace PackageHealthChecker\Laravel\Tests\Unit;

use PackageHealthChecker\Laravel\Services\HealthReportRunner;
use PackageHealthChecker\Laravel\Tests\TestCase;

class HealthReportRunnerTest extends TestCase
{
    public function test_summary_counts_results_by_status(): void
    {
        $runner = app(HealthReportRunner::class);

        $results = $runner->run();
        $summary = $runner->summarize($results);

        $this->assertSame(1, $summary['pass']);
        $this->assertSame(0, $summary['warn']);
        $this->assertSame(1, $summary['fail']);
        $this->assertSame(0, $summary['skipped']);
    }
}

