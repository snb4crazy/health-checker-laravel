<?php

namespace PackageHealthChecker\Laravel\Console;

use Illuminate\Console\Command;
use PackageHealthChecker\Laravel\Data\HealthCheckResult;
use PackageHealthChecker\Laravel\Services\HealthReportRunner;

class HealthCheckCommand extends Command
{
    protected $signature = 'health:check
        {--only= : Comma-separated check keys to run}
        {--skip= : Comma-separated check keys to skip}
        {--skip-ssl : Skip SSL check for this run}
        {--skip-mail : Skip mail check for this run}
        {--json : Output report in JSON format}';

    protected $description = 'Run package health checks and print a report.';

    public function handle(HealthReportRunner $runner): int
    {
        $only = $this->csvOption('only');
        $skip = $this->csvOption('skip');

        if ((bool) $this->option('skip-ssl')) {
            $skip[] = 'ssl';
        }

        if ((bool) $this->option('skip-mail')) {
            $skip[] = 'mail';
        }

        $results = $runner->run($only, array_values(array_unique($skip)));
        $summary = $runner->summarize($results);

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode([
                'summary' => $summary,
                'results' => array_map(fn (HealthCheckResult $result): array => $result->toArray(), $results),
            ], JSON_PRETTY_PRINT));
        } else {
            $this->table(
                ['Check', 'Status', 'Duration ms', 'Message'],
                array_map(fn (HealthCheckResult $result): array => [
                    $result->label,
                    strtoupper($result->status),
                    number_format($result->durationMs, 2),
                    $result->message,
                ], $results),
            );

            $this->newLine();
            $this->components->twoColumnDetail('Pass', (string) $summary['pass']);
            $this->components->twoColumnDetail('Warn', (string) $summary['warn']);
            $this->components->twoColumnDetail('Fail', (string) $summary['fail']);
            $this->components->twoColumnDetail('Skipped', (string) $summary['skipped']);
        }

        return $summary['fail'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    protected function csvOption(string $name): array
    {
        $raw = (string) $this->option($name);

        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}

