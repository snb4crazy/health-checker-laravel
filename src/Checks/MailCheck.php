<?php

namespace PackageHealthChecker\Laravel\Checks;

use Illuminate\Support\Facades\Mail;
use PackageHealthChecker\Laravel\Data\HealthCheckResult;

class MailCheck extends BaseCheck
{
    public function key(): string
    {
        return 'mail';
    }

    public function label(): string
    {
        return 'Mail';
    }

    public function run(): HealthCheckResult
    {
        return $this->measure(function (): HealthCheckResult {
            if (! (bool) config('health-checker.mail.enabled', false)) {
                return $this->result(HealthCheckResult::SKIPPED, 'Mail check disabled.');
            }

            $mailer = (string) config('health-checker.mail.mailer', config('mail.default', 'smtp'));

            try {
                app('mail.manager')->mailer($mailer)->getSymfonyTransport();
            } catch (\Throwable $e) {
                return $this->result(HealthCheckResult::FAIL, "Mail transport check failed: {$e->getMessage()}");
            }

            if ((bool) config('health-checker.mail.send_test_email', false)) {
                $to = (string) config('health-checker.mail.to', '');

                if ($to === '') {
                    return $this->result(HealthCheckResult::FAIL, 'health-checker.mail.to is required when send_test_email=true.');
                }

                try {
                    Mail::mailer($mailer)->raw('Health checker test email.', function ($message) use ($to): void {
                        $message->to($to)->subject('Health checker test email');
                    });
                } catch (\Throwable $e) {
                    return $this->result(HealthCheckResult::FAIL, "Mail send test failed: {$e->getMessage()}");
                }
            }

            return $this->result(HealthCheckResult::PASS, "Mail check succeeded for mailer={$mailer}.");
        });
    }
}

