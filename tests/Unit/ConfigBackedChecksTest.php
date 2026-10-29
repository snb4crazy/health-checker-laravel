<?php

namespace PackageHealthChecker\Laravel\Tests\Unit;

use PackageHealthChecker\Laravel\Checks\EnvironmentCheck;
use PackageHealthChecker\Laravel\Checks\MailCheck;
use PackageHealthChecker\Laravel\Data\HealthCheckResult;
use PackageHealthChecker\Laravel\Tests\TestCase;

class ConfigBackedChecksTest extends TestCase
{
    public function test_environment_check_reads_config_not_env(): void
    {
        config([
            'app.name' => 'App',
            'app.env' => 'production',
            'app.url' => 'https://example.test',
            'app.debug' => false,
        ]);

        $this->assertSame(HealthCheckResult::PASS, (new EnvironmentCheck())->run()->status);
    }
    
    public function test_environment_check_fails_when_cached_debug_enabled_in_production(): void
    {
        config([
            'app.name' => 'App',
            'app.env' => 'production',
            'app.url' => 'https://example.test',
            'app.debug' => true,
        ]);
        
        $this->assertSame(HealthCheckResult::FAIL, (new EnvironmentCheck())->run()->status);
    }
    
    public function test_environment_check_fails_when_required_config_missing(): void
    {
        config(['app.env' => 'local', 'app.url' => '']);
        
        $this->assertSame(HealthCheckResult::FAIL, (new EnvironmentCheck())->run()->status);
    }
    
    
}
