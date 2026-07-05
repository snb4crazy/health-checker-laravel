<?php

namespace PackageHealthChecker\Laravel\Tests\Feature;

use PackageHealthChecker\Laravel\Tests\TestCase;

class HealthCheckCommandTest extends TestCase
{
    public function test_health_check_command_outputs_table_and_fails_when_any_check_fails(): void
    {
        $this->artisan('health:check')
            ->expectsOutputToContain('Fake Pass')
            ->expectsOutputToContain('Fake Fail')
            ->assertExitCode(1);
    }

    public function test_health_check_command_can_output_json(): void
    {
        $this->artisan('health:check --json')
            ->assertExitCode(1);
    }

    public function test_health_check_command_can_filter_checks_by_only_option(): void
    {
        $this->artisan('health:check --only=fake-pass --json')
            ->expectsOutputToContain('"fake-pass"')
            ->doesntExpectOutputToContain('"fake-fail"')
            ->assertExitCode(0);
    }
}

