<?php

namespace PackageHealthChecker\Laravel\Tests\Support;

use PackageHealthChecker\Laravel\Checks\BaseCheck;
use PackageHealthChecker\Laravel\Data\HealthCheckResult;

class FakeFailCheck extends BaseCheck
{
    public function key(): string
    {
        return 'fake-fail';
    }

    public function label(): string
    {
        return 'Fake Fail';
    }

    public function run(): HealthCheckResult
    {
        return $this->measure(fn (): HealthCheckResult => $this->result(HealthCheckResult::FAIL, 'Dependency not responding.'));
    }
}

