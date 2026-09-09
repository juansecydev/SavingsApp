<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use Psr\Container\ContainerInterface;

return function (ContainerInterface $container): void {
    
    $settings = $container->get(SettingsInterface::class);
    $config = $settings->get('config');

    define('APP_ROOT_PATH', dirname(__DIR__) . '/');
    define('APP_PUBLIC_PATH', APP_ROOT_PATH . 'public/');
    define('APP_LOG_PATH', APP_ROOT_PATH . 'logs/');
    define('STORAGE_PATH', $config['storage_path'] );

    if (!is_dir(APP_LOG_PATH)) {
        mkdir(APP_LOG_PATH, 0755, true);
    }

    date_default_timezone_set($config['time_zone']);

    define('APP_NAME', $config['app_name']);
    define('APP_ERROR_LOG', APP_LOG_PATH . 'php-error.log');

    if ($config['production']) {
        ini_set('display_errors', '0');
        ini_set('display_startup_errors', '0');
        ini_set('html_errors', '0');
        ini_set('session.cookie_secure', '1');
    } else {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        ini_set('html_errors', '1');
        error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
    }

    ini_set('log_errors', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_trans_sid', '0');

    /* if (session_status() === PHP_SESSION_NONE) {
        session_start();
    } */
};