<?php

namespace PackageHealthChecker\Laravel\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PackageHealthChecker\Laravel\Data\HealthCheckResult;

class FailureNotifier
{
    /**
     * @param  array<int, HealthCheckResult>  $results
     */
    public function notify(array $results): void
    {
        $includeWarn = (bool) config('health-checker.alerts.email.include_warn', true);

        $failures = array_values(array_filter(
            $results,
            fn (HealthCheckResult $result): bool => $result->isFailure($includeWarn),
        ));

        if ($failures === []) {
            return;
        }

        $this->logFailures($failures);
        $this->sendEmailIfEnabled($failures);
    }

    /**
     * @param  array<int, HealthCheckResult>  $failures
     */
    protected function logFailures(array $failures): void
    {
        foreach ($failures as $failure) {
            Log::channel(config('health-checker.log_channel'))->warning('Health check issue detected.', $failure->toArray());
        }
    }

    /**
     * @param  array<int, HealthCheckResult>  $failures
     */
    protected function sendEmailIfEnabled(array $failures): void
    {
        if (! (bool) config('health-checker.alerts.email.enabled', false)) {
            return;
        }

        $recipient = (string) config('health-checker.alerts.email.to', '');

        if ($recipient === '') {
            Log::channel(config('health-checker.log_channel'))
                ->warning('Health checker email alert is enabled but no recipient is configured.');

            return;
        }

        $subject = (string) config('health-checker.alerts.email.subject', 'Health check failed');
        $lines = array_map(
            fn (HealthCheckResult $result): string => sprintf('[%s] %s: %s', strtoupper($result->status), $result->label, $result->message),
            $failures,
        );

        Mail::raw(implode("\n", $lines), function ($message) use ($recipient, $subject): void {
            $message->to($recipient)->subject($subject);
        });
    }
}

