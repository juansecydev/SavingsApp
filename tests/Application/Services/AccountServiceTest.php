<?php

declare(strict_types=1);

namespace Tests\Application\Services;

use App\Application\Services\AccountService;
use App\Domain\Account\AccountRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AccountServiceTest extends TestCase
{
    public function testConvertAmountToMinorUnitsSupportsZeroPositiveAndNegativeValues(): void
    {
        $service = new AccountService($this->createMock(AccountRepository::class));

        $this->assertSame(0, $service->convertAmountToMinorUnits('0', 'COP'));
        $this->assertSame(121211, $service->convertAmountToMinorUnits('1212.11', 'COP'));
        $this->assertSame(-3250, $service->convertAmountToMinorUnits('-32.50', 'COP'));
    }

    public function testConvertAmountToMinorUnitsRejectsInvalidNumericValues(): void
    {
        $service = new AccountService($this->createMock(AccountRepository::class));

        $this->expectException(InvalidArgumentException::class);
        $service->convertAmountToMinorUnits('10,50,20', 'COP');
    }

    public function testDeleteAccountForUserDelegatesToRepository(): void
    {
        $repository = $this->createMock(AccountRepository::class);
        $repository->expects($this->once())
            ->method('deleteOneByUser')
            ->with(12, 34)
            ->willReturn(true);

        $service = new AccountService($repository);

        $this->assertTrue($service->deleteAccountForUser(12, 34));
    }
}
