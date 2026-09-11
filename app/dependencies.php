<?php

declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Twig\Extension\DebugExtension;
use App\Infrastructure\Persistence\Database\QueryBuilder;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        'sql_logger' => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $loggerSettings = $settings->get('sql_logger');
            $logger = new Logger($loggerSettings['name']);
            $logger->pushHandler(new StreamHandler($loggerSettings['path'], $loggerSettings['level']));

            return $logger;
        },
        QueryBuilder::class => function (ContainerInterface $c) {
            return new QueryBuilder($c->get(PDO::class), $c->get('sql_logger'));
        },
        Twig::class => function (ContainerInterface $c) {

            $settings = $c->get(SettingsInterface::class);
            $twig = Twig::create(TEMPLATES_PATH, [
                'cache' => $settings->get('front_cache_twig', false),
                'debug' => $settings->get('debug_mode_twig', false)
            ]);

            if($settings->get('debug_mode_twig')) {
                $twig->addExtension(new DebugExtension());
            }
            
            return $twig;
        },
        PDO::class => function (ContainerInterface $c) {
            
            $settings = $c->get(SettingsInterface::class);
            $dbSettings = $settings->get('databases');

            if($dbSettings === null) {
                throw new RuntimeException('Database settings not found in configuration.');
            }

            if(env('DB_DRIVER') == 'mysql') {
                $dbSettings = $dbSettings['mysql'];
            } elseif(env('DB_DRIVER') == 'sqlite') {
                $dbSettings = $dbSettings['sqlite'];
            }else{
                throw new RuntimeException('Database connection type not supported: ' . env('DB_DRIVER'));
            }

            $options = [
                // Throws PDOExceptions on errors (Required for safe error handling)
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
                // Returns rows as clean associative arrays by default
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
                // Emulates prepared statements if necessary, turned off for native security
                PDO::ATTR_EMULATE_PREPARES   => false,                  
            ];

            if ($dbSettings['driver'] === 'sqlite') {
                return new PDO('sqlite:' . $dbSettings['database'], null, null, $options);
            }

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $dbSettings['host'],
                $dbSettings['port'],
                $dbSettings['name'],
                $dbSettings['charset']
            );

            return new PDO($dsn, $dbSettings['user'], $dbSettings['password'], $options);
        }
    ]);
};
