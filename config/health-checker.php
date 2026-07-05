<?php

use PackageHealthChecker\Laravel\Checks\CacheCheck;
use PackageHealthChecker\Laravel\Checks\DatabaseCheck;
use PackageHealthChecker\Laravel\Checks\DiskSpaceCheck;
use PackageHealthChecker\Laravel\Checks\EnvironmentCheck;
use PackageHealthChecker\Laravel\Checks\MailCheck;
use PackageHealthChecker\Laravel\Checks\QueueCheck;
use PackageHealthChecker\Laravel\Checks\RedisCheck;
use PackageHealthChecker\Laravel\Checks\SslCheck;
use PackageHealthChecker\Laravel\Checks\StorageCheck;

return [
    'enabled' => env('HEALTH_CHECKER_ENABLED', true),

    'log_channel' => env('HEALTH_CHECKER_LOG_CHANNEL'),

    'checks' => [
        DatabaseCheck::class,
        RedisCheck::class,
        CacheCheck::class,
        QueueCheck::class,
        StorageCheck::class,
        MailCheck::class,
        DiskSpaceCheck::class,
        SslCheck::class,
        EnvironmentCheck::class,
    ],

    'alerts' => [
        'email' => [
            'enabled' => env('HEALTH_CHECKER_ALERT_EMAIL_ENABLED', false),
            'to' => env('HEALTH_CHECKER_ALERT_EMAIL_TO'),
            'subject' => env('HEALTH_CHECKER_ALERT_SUBJECT', 'Health check failed'),
            'include_warn' => env('HEALTH_CHECKER_ALERT_INCLUDE_WARN', true),
        ],
    ],

    'database' => [
        'connections' => [env('HEALTH_CHECKER_DB_CONNECTION', env('DB_CONNECTION', 'sqlite'))],
        'remotes' => [
            // [
            //     'name' => 'replica-eu1',
            //     'driver' => 'mysql',
            //     'host' => '10.10.1.11',
            //     'port' => 3306,
            //     'database' => 'app',
            //     'username' => 'health',
            //     'password' => 'secret',
            //     'timeout' => 3,
            // ],
        ],
    ],

    'redis' => [
        'connections' => [env('HEALTH_CHECKER_REDIS_CONNECTION', env('REDIS_CLIENT', 'default'))],
        'remotes' => [
            // [
            //     'name' => 'redis-cache',
            //     'host' => '10.10.2.5',
            //     'port' => 6379,
            //     'password' => null,
            //     'database' => 0,
            //     'timeout' => 3,
            // ],
        ],
    ],

    'cache' => [
        'store' => env('HEALTH_CHECKER_CACHE_STORE', env('CACHE_STORE', 'file')),
    ],

    'queue' => [
        'connection' => env('HEALTH_CHECKER_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'sync')),
        'queue' => env('HEALTH_CHECKER_QUEUE_NAME', 'default'),
    ],

    'storage' => [
        'disks' => ['local'],
        'probe_directory' => 'health-checker',
    ],

    'mail' => [
        'enabled' => env('HEALTH_CHECKER_MAIL_ENABLED', false),
        'mailer' => env('HEALTH_CHECKER_MAILER', env('MAIL_MAILER', 'smtp')),
        'send_test_email' => env('HEALTH_CHECKER_MAIL_SEND_TEST', false),
        'to' => env('HEALTH_CHECKER_MAIL_TO'),
    ],

    'disk_space' => [
        'paths' => [
            [
                'path' => base_path(),
                'warn_below_percent' => 15,
                'fail_below_percent' => 5,
            ],
        ],
    ],

    'ssl' => [
        'enabled' => env('HEALTH_CHECKER_SSL_ENABLED', false),
        'warn_days_before_expiry' => (int) env('HEALTH_CHECKER_SSL_WARN_DAYS', 14),
        'targets' => [
            // [
            //     'name' => 'frontend',
            //     'host' => 'example.com',
            //     'port' => 443,
            //     'timeout' => 5,
            // ],
        ],
    ],

    'environment' => [
        'allowed_app_envs' => ['local', 'staging', 'production'],
        'required' => ['APP_NAME', 'APP_ENV', 'APP_URL'],
        'forbidden_in_production' => [
            'APP_DEBUG' => ['1', 'true', 'on'],
        ],
    ],
];

