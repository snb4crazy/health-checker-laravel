# health-checker-laravel

Starter Laravel package for operational health checks with a clean report output.

It checks:

- queues
- storage
- Redis
- cache
- database (including optional remote replicas)
- mail (optional)
- disk space
- SSL certificates (optional)
- environment consistency

## Why this starter

You can centralize the standalone script logic you already use, then tune each check for your infrastructure.

This package is intentionally opinionated but extensible:

- command-based execution: `php artisan health:check`
- JSON output for cron + log parsing: `php artisan health:check --json`
- selective checks: `--only=database,redis` / `--skip=mail,ssl`
- optional alerts by email when anything fails

## Requirements

| Dependency | Version |
|------------|---------|
| PHP        | 8.2+    |
| Laravel    | 10-13   |

## Installation

```bash
composer require snb4crazy/package-health-checker-laravel
php artisan vendor:publish --tag=health-checker-config
```

## Usage

Run all checks:

```bash
php artisan health:check
```

Run as machine-readable JSON:

```bash
php artisan health:check --json
```

Skip expensive checks in hourly cron:

```bash
php artisan health:check --skip=ssl,mail
```

Run only target checks:

```bash
php artisan health:check --only=database,redis,queue
```

## Scheduler example

```php
// routes/console.php or app/Console/Kernel.php
use Illuminate\Support\Facades\Schedule;

Schedule::command('health:check --skip=ssl,mail --json')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('health:check --only=ssl,mail --json')
    ->dailyAt('03:00')
    ->withoutOverlapping();
```

## Config highlights

Published config file: `config/health-checker.php`

Main sections:

- `checks`: list of check classes (you can swap with your own)
- `database.remotes`: remote DB replicas with credentials
- `redis.remotes`: remote Redis instances
- `ssl.targets`: frontend/API hosts for certificate checks
- `mail.enabled`: turn mail check on/off
- `alerts.email.*`: email notifications on fail/warn

### Remote DB example

```php
'database' => [
    'connections' => ['mysql'],
    'remotes' => [
        [
            'name' => 'replica-eu1',
            'driver' => 'mysql',
            'host' => '10.10.1.11',
            'port' => 3306,
            'database' => 'app',
            'username' => 'health',
            'password' => 'secret',
            'timeout' => 3,
        ],
    ],
],
```

### SSL target example

```php
'ssl' => [
    'enabled' => true,
    'warn_days_before_expiry' => 14,
    'targets' => [
        ['name' => 'frontend', 'host' => 'example.com', 'port' => 443, 'timeout' => 5],
    ],
],
```

## Extending checks

Create your own check class implementing `PackageHealthChecker\Laravel\Contracts\HealthCheck` and add it to `health-checker.checks`.

## Testing

```bash
composer test
```

## License

MIT

