<?php

namespace PackageHealthChecker\Laravel\Checks;

use PackageHealthChecker\Laravel\Data\HealthCheckResult;

class EnvironmentCheck extends BaseCheck
{
    public function key(): string
    {
        return 'environment';
    }

    public function label(): string
    {
        return 'Environment';
    }

    public function run(): HealthCheckResult
    {
        return $this->measure(function (): HealthCheckResult {
            $messages = [];
            $status = HealthCheckResult::PASS;
            $appEnv = (string) config('app.env', 'production');

            $allowed = (array) config('health-checker.environment.allowed_app_envs', []);
            if ($allowed !== [] && ! in_array($appEnv, $allowed, true)) {
                $status = HealthCheckResult::FAIL;
                $messages[] = "APP_ENV={$appEnv} is not in allowed list";
            } else {
                $messages[] = "APP_ENV={$appEnv}";
            }

            foreach ((array) config('health-checker.environment.required', []) as $required) {
                $value = env((string) $required);
                if ($value === null || $value === '') {
                    $status = HealthCheckResult::FAIL;
                    $messages[] = "required {$required}=missing";
                }
            }

            if ($appEnv === 'production') {
                foreach ((array) config('health-checker.environment.forbidden_in_production', []) as $name => $forbiddenValues) {
                    $current = strtolower((string) env((string) $name, ''));
                    $forbidden = array_map('strtolower', (array) $forbiddenValues);

                    if (in_array($current, $forbidden, true)) {
                        $status = HealthCheckResult::FAIL;
                        $messages[] = "{$name} has forbidden value in production";
                    }
                }
            }

            return $this->result($status, implode(' | ', $messages));
        });
    }
}

