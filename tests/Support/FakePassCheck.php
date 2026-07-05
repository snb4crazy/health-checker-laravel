<?php

namespace PackageHealthChecker\Laravel\Tests\Support;

use PackageHealthChecker\Laravel\Checks\BaseCheck;
use PackageHealthChecker\Laravel\Data\HealthCheckResult;

class FakePassCheck extends BaseCheck
{
    public function key(): string
    {
        return 'fake-pass';
    }

    public function label(): string
    {
        return 'Fake Pass';
    }

    public function run(): HealthCheckResult
    {
        return $this->measure(fn (): HealthCheckResult => $this->result(HealthCheckResult::PASS, 'Everything is healthy.'));
    }
}

