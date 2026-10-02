<?php

declare(strict_types=1);

use App\Domain\Account\AccountRepository;
use App\Domain\Currency\CurrencyRepository;
use App\Domain\TransactionOperation\TransactionOperationRepository;
use App\Domain\User\UserRepository;
use App\Infrastructure\Persistence\Account\DatabaseAccountRepository;
use App\Infrastructure\Persistence\Currency\DatabaseCurrencyRepository;
use App\Infrastructure\Persistence\TransactionOperation\DatabaseTransactionOperationRepository;
use App\Infrastructure\Persistence\User\DatabaseUserRepository;
use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        UserRepository::class => \DI\autowire(DatabaseUserRepository::class),
        CurrencyRepository::class => \DI\autowire(DatabaseCurrencyRepository::class),
        AccountRepository::class => \DI\autowire(DatabaseAccountRepository::class),
        TransactionOperationRepository::class => \DI\autowire(DatabaseTransactionOperationRepository::class),
    ]);
};
