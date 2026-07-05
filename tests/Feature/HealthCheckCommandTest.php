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


}



