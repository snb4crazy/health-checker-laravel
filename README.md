# Laravel Health Checker

[![Latest Version on Packagist](https://img.shields.io/packagist/v/snb4crazy/health-checker-laravel.svg?label=packagist)](https://packagist.org/packages/snb4crazy/health-checker-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/snb4crazy/health-checker-laravel.svg)](https://packagist.org/packages/snb4crazy/health-checker-laravel)
[![PHP Version](https://img.shields.io/packagist/php-v/snb4crazy/health-checker-laravel.svg)](https://packagist.org/packages/snb4crazy/health-checker-laravel)
[![License](https://img.shields.io/packagist/l/snb4crazy/health-checker-laravel.svg)](LICENSE)

`snb4crazy/health-checker-laravel` runs operational health checks for Laravel apps and returns a clear summary for humans and automation.

## What it checks

| Check key | What it validates |
| --- | --- |
| `database` | configured DB connections + optional remote DB replicas |
| `redis` | configured Redis connections + optional remote Redis instances |
| `cache` | write/read/delete probe against selected cache store |
| `queue` | queue backend reachability and basic driver sanity |
| `storage` | write/read/delete probe on configured disks |
| `mail` | mail transport (and optional test email send) |
| `disk-space` | free disk thresholds by configured path |
| `ssl` | SSL certificate validity / expiry windows |
| `environment` | required env vars and production safety rules |

## Requirements

| Dependency | Version |
| --- | --- |
| PHP | 8.2+ |
| Laravel | 10, 11, 12, 13 |

## Installation

```bash
composer require snb4crazy/health-checker-laravel
php artisan vendor:publish --tag=health-checker-config
```

The package uses Laravel auto-discovery.

## Quick start

```bash
# Human-readable table output
php artisan health:check

# JSON output for logs/automation
php artisan health:check --json
```

Exit code is `0` when there are no failed checks, otherwise `1`.

## Command options

```bash
# Run only selected checks
php artisan health:check --only=database,redis,queue

# Skip selected checks
php artisan health:check --skip=ssl,mail

# Convenience flags
php artisan health:check --skip-ssl --skip-mail --json
```

## Scheduler examples

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('health:check --skip=ssl,mail --json')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('health:check --only=ssl,mail --json')
    ->dailyAt('03:00')
    ->withoutOverlapping();
```

## Configuration guide

Publish and edit `config/health-checker.php`.

### Core sections

- `enabled`: global on/off switch.
- `checks`: ordered list of check classes to execute.
- `log_channel`: optional Laravel log channel for failures/warnings.
- `alerts.email.*`: email notifications for failed checks (and optionally warnings).

### Infrastructure targets

- `database.connections` and `database.remotes`
- `redis.connections` and `redis.remotes`
- `cache.store`
- `queue.connection` / `queue.queue`
- `storage.disks` / `storage.probe_directory`
- `disk_space.paths`
- `ssl.enabled` / `ssl.targets` / `ssl.warn_days_before_expiry`

### Mail check behavior

- `mail.enabled=false` skips the mail check.
- `mail.send_test_email=true` sends a test email and requires `mail.to`.

### Environment safeguards

- `environment.allowed_app_envs`
- `environment.required`
- `environment.forbidden_in_production`

## Remote target examples

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

'redis' => [
    'connections' => ['default'],
    'remotes' => [
        [
            'name' => 'redis-cache',
            'host' => '10.10.2.5',
            'port' => 6379,
            'password' => null,
            'database' => 0,
            'timeout' => 3,
        ],
    ],
],

'ssl' => [
    'enabled' => true,
    'warn_days_before_expiry' => 14,
    'targets' => [
        ['name' => 'frontend', 'host' => 'example.com', 'port' => 443, 'timeout' => 5],
    ],
],
```

## Extending with custom checks

1. Create a check class that implements `PackageHealthChecker\Laravel\Contracts\HealthCheck`.
2. Return a `PackageHealthChecker\Laravel\Data\HealthCheckResult` from `run()`.
3. Register the class in `health-checker.checks`.

## Programmatic usage

```php
use PackageHealthChecker\Laravel\Facades\HealthChecker;

$results = HealthChecker::run(only: ['database'], skip: []);
```

## Testing

```bash
composer test
```

## License

MIT
