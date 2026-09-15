<?php

declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Level;

return function (ContainerBuilder $containerBuilder) {

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            return new Settings([
                'app_production' => (bool) env('APP_PRODUCTION', false),
                'displayErrorDetails' => (bool) env('APP_DEBUG', true),
                'logError'            => (bool) env('LOG_ERRORS', false),
                'logErrorDetails'     => (bool) env('LOG_ERROR_DETAILS', false),
                'app_name' => env('APP_NAME', 'SavingsApp'),
                'databases' => [
                    'mysql' => [
                        'driver' => 'mysql',
                        'host' => env('DB_HOST'),
                        'port' => (int) env('DB_PORT'),
                        'name' => env('DB_NAME'),
                        'user' => env('DB_USER'),
                        'password' => env('DB_PASSWORD'),
                        'charset' => env('DB_CHARSET', 'utf8'),
                        'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
                    ],
                    'sqlite' => [
                        'driver' => 'sqlite',
                        'database' => env('SQLITE_DATABASE'),
                    ],
                ],
                'time_zone' => env('APP_TIMEZONE', 'America/Bogota'),
                'front_cache_twig' => (bool) env('FRONT_CACHE_TWIG', false),
                'debug_mode_twig' => (bool) env('DEBUG_MODE_TWIG', true),
                'session_timeout' => (int) env('SESSION_TIMEOUT', 1800),
                'storage_path' => env('STORAGE_PATH', __DIR__ . '/../storage/'),
                'logger' => [
                    'name' => env('LOG_NAME', 'slim-app'),
                    'path' => env('LOG_PATH', isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log'),
                    'level' => Level::fromName((string) env('LOG_LEVEL', 'Debug')),
                ],
                'sql_logger' => [
                    'name' => env('SQL_LOG_NAME', 'sql'),
                    'path' => env('SQL_LOG_PATH', __DIR__ . '/../logs/sql.log'),
                    'level' => Level::fromName((string) env('SQL_LOG_LEVEL', 'Debug')),
                ],
            ]);
        }
    ]);
};
