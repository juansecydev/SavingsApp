<?php

declare(strict_types=1);

namespace Tests\Application\Services;

use App\Application\Services\AccountService;
use App\Application\Services\AccountTransactionService;
use App\Domain\Account\Account;
use App\Domain\AccountTransaction\AccountTransactionRepository;
use App\Domain\TransactionOperation\TransactionOperation;
use App\Infrastructure\Persistence\Database\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Throwable;

class AccountTransactionServiceTest extends TestCase
{
    /**
     * Verify subtraction is applied in minor units and can produce a negative balance.
     */
    public function testCreateTransactionSubtractsAndAllowsNegativeBalance(): void
    {
        $service = $this->createService(1000, 2500, '-');

        $this->assertTrue($service->createTransaction(
            7,
            12,
            new TransactionOperation(2, 'Egress', '-'),
            2500,
            'Rent',
            null,
        ));
    }

    /**
     * Verify the transaction record is written before its balance update.
     */
    public function testCreateTransactionRegistersBeforeUpdatingBalance(): void
    {
        $events = [];
        $account = $this->createMock(AccountService::class);
        $account->method('getAccountByIdForUserForUpdate')
            ->willReturn(new Account(12, 'Main', 1000, 7, 1, 'USD'));
        $account->expects($this->once())
            ->method('updateBalance')
            ->with(12, 3500)
            ->willReturnCallback(static function () use (&$events): bool {
                $events[] = 'balance';
                return true;
            });

        $repository = $this->createMock(AccountTransactionRepository::class);
        $repository->expects($this->once())
            ->method('create')
            ->with(12, 1, 2500, 'Salary', null)
            ->willReturnCallback(static function () use (&$events): bool {
                $events[] = 'transaction';
                return true;
            });

        $service = new AccountTransactionService($repository, $account, $this->createQueryBuilderMock());

        $this->assertTrue($service->createTransaction(
            7,
            12,
            new TransactionOperation(1, 'Income', '+'),
            2500,
            'Salary',
            null,
        ));
        $this->assertSame(['transaction', 'balance'], $events);
    }

    /**
     * Verify failed transaction insertion prevents the balance update and reports failure.
     */
    public function testCreateTransactionDoesNotUpdateBalanceWhenInsertFails(): void
    {
        $account = $this->createMock(AccountService::class);
        $account->method('getAccountByIdForUserForUpdate')
            ->willReturn(new Account(12, 'Main', 1000, 7, 1, 'USD'));
        $account->expects($this->never())->method('updateBalance');

        $repository = $this->createMock(AccountTransactionRepository::class);
        $repository->method('create')->willReturn(false);

        $service = new AccountTransactionService($repository, $account, $this->createQueryBuilderMock());

        $this->assertFalse($service->createTransaction(
            7,
            12,
            new TransactionOperation(1, 'Income', '+'),
            2500,
            'Salary',
            null,
        ));
    }

    /**
     * Verify deleting an income transaction subtracts its amount and removes its record.
     */
    public function testDeleteIncomeTransactionReversesBalanceAndDeletesRecord(): void
    {
        $account = $this->createMock(AccountService::class);
        $account->expects($this->once())
            ->method('getAccountByIdForUserForUpdate')
            ->with(12, 7)
            ->willReturn(new Account(12, 'Main', 3500, 7, 1, 'USD'));
        $account->expects($this->once())
            ->method('updateBalance')
            ->with(12, 1000)
            ->willReturn(true);

        $repository = $this->createMock(AccountTransactionRepository::class);
        $repository->expects($this->once())
            ->method('findByIdForAccount')
            ->with(81, 12)
            ->willReturn([
                'account_transaction_amount' => 2500,
                'transaction_operation_symbol' => '+',
            ]);
        $repository->expects($this->once())
            ->method('deleteForAccount')
            ->with(81, 12)
            ->willReturn(true);

        $service = new AccountTransactionService($repository, $account, $this->createQueryBuilderMock());

        $this->assertTrue($service->deleteTransactionForUser(7, 12, 81));
    }

    /**
     * Verify deleting an expense transaction adds its amount back to the balance.
     */
    public function testDeleteExpenseTransactionReversesBalance(): void
    {
        $account = $this->createMock(AccountService::class);
        $account->method('getAccountByIdForUserForUpdate')
            ->willReturn(new Account(12, 'Main', 1000, 7, 1, 'USD'));
        $account->expects($this->once())
            ->method('updateBalance')
            ->with(12, 3500)
            ->willReturn(true);

        $repository = $this->createMock(AccountTransactionRepository::class);
        $repository->method('findByIdForAccount')->willReturn([
            'account_transaction_amount' => 2500,
            'transaction_operation_symbol' => '-',
        ]);
        $repository->expects($this->once())->method('deleteForAccount')->willReturn(true);

        $service = new AccountTransactionService($repository, $account, $this->createQueryBuilderMock());

        $this->assertTrue($service->deleteTransactionForUser(7, 12, 81));
    }

    /**
     * Verify a transaction not owned by the account is not changed.
     */
    public function testDeleteTransactionReturnsNullWhenTransactionDoesNotBelongToAccount(): void
    {
        $account = $this->createMock(AccountService::class);
        $account->method('getAccountByIdForUserForUpdate')
            ->willReturn(new Account(12, 'Main', 1000, 7, 1, 'USD'));
        $account->expects($this->never())->method('updateBalance');

        $repository = $this->createMock(AccountTransactionRepository::class);
        $repository->expects($this->once())->method('findByIdForAccount')->willReturn(null);
        $repository->expects($this->never())->method('deleteForAccount');

        $service = new AccountTransactionService($repository, $account, $this->createQueryBuilderMock());

        $this->assertNull($service->deleteTransactionForUser(7, 12, 81));
    }

    /**
     * Verify a balance update failure rolls back the transaction deletion.
     */
    public function testDeleteTransactionRollsBackWhenBalanceUpdateFails(): void
    {
        $account = $this->createMock(AccountService::class);
        $account->method('getAccountByIdForUserForUpdate')
            ->willReturn(new Account(12, 'Main', 3500, 7, 1, 'USD'));
        $account->method('updateBalance')->willReturn(false);

        $repository = $this->createMock(AccountTransactionRepository::class);
        $repository->method('findByIdForAccount')->willReturn([
            'account_transaction_amount' => 2500,
            'transaction_operation_symbol' => '+',
        ]);
        $repository->expects($this->once())->method('deleteForAccount')->willReturn(true);

        $service = new AccountTransactionService($repository, $account, $this->createQueryBuilderMock());

        $this->assertFalse($service->deleteTransactionForUser(7, 12, 81));
    }

    /**
     * Verify account creation records a positive opening balance in the same transaction.
     */
    public function testCreateAccountWithOpeningTransactionRecordsPositiveBalance(): void
    {
        $account = $this->createMock(AccountService::class);
        $account->expects($this->once())
            ->method('createAccount')
            ->with(7, 'Savings', 1, 0)
            ->willReturn(12);
        $account->expects($this->once())
            ->method('getAccountByIdForUserForUpdate')
            ->with(12, 7)
            ->willReturn(new Account(12, 'Savings', 0, 7, 1, 'USD'));
        $account->expects($this->once())
            ->method('updateBalance')
            ->with(12, 2500)
            ->willReturn(true);

        $repository = $this->createMock(AccountTransactionRepository::class);
        $repository->expects($this->once())
            ->method('create')
            ->with(12, 1, 2500, 'Account created', null)
            ->willReturn(true);

        $service = new AccountTransactionService(
            $repository,
            $account,
            $this->createQueryBuilderMock(),
        );

        $this->assertTrue($service->createAccountWithOpeningTransaction(
            7,
            'Savings',
            1,
            2500,
            new TransactionOperation(1, 'Income', '+'),
        ));
    }

    /**
     * Verify a negative opening balance is recorded as a positive egress amount.
     */
    public function testCreateAccountWithOpeningTransactionRecordsNegativeBalanceAsEgress(): void
    {
        $account = $this->createMock(AccountService::class);
        $account->expects($this->once())
            ->method('createAccount')
            ->with(7, 'Overdraft', 1, 0)
            ->willReturn(12);
        $account->expects($this->once())
            ->method('getAccountByIdForUserForUpdate')
            ->with(12, 7)
            ->willReturn(new Account(12, 'Overdraft', 0, 7, 1, 'USD'));
        $account->expects($this->once())
            ->method('updateBalance')
            ->with(12, -625)
            ->willReturn(true);

        $repository = $this->createMock(AccountTransactionRepository::class);
        $repository->expects($this->once())
            ->method('create')
            ->with(12, 2, 625, 'Account created', null)
            ->willReturn(true);

        $service = new AccountTransactionService(
            $repository,
            $account,
            $this->createQueryBuilderMock(),
        );

        $this->assertTrue($service->createAccountWithOpeningTransaction(
            7,
            'Overdraft',
            1,
            -625,
            new TransactionOperation(2, 'Egress', '-'),
        ));
    }

    /**
     * Verify the account creation operation rejects an operation with the wrong balance sign.
     */
    public function testCreateAccountWithOpeningTransactionRejectsMismatchedOperation(): void
    {
        $account = $this->createMock(AccountService::class);
        $account->expects($this->never())->method('createAccount');
        $repository = $this->createMock(AccountTransactionRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->never())->method('transaction');

        $service = new AccountTransactionService($repository, $account, $queryBuilder);

        $this->assertFalse($service->createAccountWithOpeningTransaction(
            7,
            'Savings',
            1,
            2500,
            new TransactionOperation(2, 'Egress', '-'),
        ));
    }

    /**
     * Verify opening transaction failure reports account-creation failure.
     */
    public function testCreateAccountWithOpeningTransactionFailsWhenEntryCannotBeInserted(): void
    {
        $account = $this->createMock(AccountService::class);
        $account->method('createAccount')->willReturn(12);
        $account->method('getAccountByIdForUserForUpdate')
            ->willReturn(new Account(12, 'Savings', 0, 7, 1, 'USD'));
        $account->expects($this->never())->method('updateBalance');

        $repository = $this->createMock(AccountTransactionRepository::class);
        $repository->method('create')->willReturn(false);

        $service = new AccountTransactionService(
            $repository,
            $account,
            $this->createQueryBuilderMock(),
        );

        $this->assertFalse($service->createAccountWithOpeningTransaction(
            7,
            'Savings',
            1,
            2500,
            new TransactionOperation(1, 'Income', '+'),
        ));
    }

    /**
     * Provide a test transaction coordinator that executes the callback and models rollback.
     *
     * @return QueryBuilder Mocked transaction coordinator.
     */
    private function createQueryBuilderMock(): QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('transaction')->willReturnCallback(
            static function (callable $operation): mixed {
                try {
                    return $operation();
                } catch (Throwable) {
                    return false;
                }
            },
        );

        return $queryBuilder;
    }

    /**
     * Construct a service with a locked test account and assert the expected balance.
     *
     * @param int $startingBalance Existing balance in minor units.
     * @param int $amount Transaction amount in minor units.
     * @param string $symbol Transaction operation symbol.
     * @return AccountTransactionService Configured service for the transaction.
     */
    private function createService(int $startingBalance, int $amount, string $symbol): AccountTransactionService
    {
        $newBalance = $symbol === '+' ? $startingBalance + $amount : $startingBalance - $amount;
        $account = $this->createMock(AccountService::class);
        $account->method('getAccountByIdForUserForUpdate')
            ->willReturn(new Account(12, 'Main', $startingBalance, 7, 1, 'USD'));
        $account->expects($this->once())
            ->method('updateBalance')
            ->with(12, $newBalance)
            ->willReturn(true);

        $repository = $this->createMock(AccountTransactionRepository::class);
        $repository->method('create')->willReturn(true);

        return new AccountTransactionService($repository, $account, $this->createQueryBuilderMock());
    }
}
