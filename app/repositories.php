<?php

declare(strict_types=1);

use App\Domain\User\UserRepository;
use App\Domain\Currency\CurrencyRepository;
use App\Infrastructure\Persistence\Currency\DatabaseCurrencyRepository;
use App\Infrastructure\Persistence\User\DatabaseUserRepository;
use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        UserRepository::class => \DI\autowire(DatabaseUserRepository::class),
        CurrencyRepository::class => \DI\autowire(DatabaseCurrencyRepository::class),
    ]);
};
