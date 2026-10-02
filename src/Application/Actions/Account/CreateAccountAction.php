<?php

declare(strict_types=1);

namespace App\Application\Actions\Account;

use App\Application\Actions\TwigAction;
use App\Application\Services\AccountService;
use App\Application\Services\CurrencyService;
use App\Domain\Currency\Currency;
use App\Domain\User\User;
use App\Infrastructure\Http\Sanitizer;
use App\Infrastructure\Http\Validator;
use App\Infrastructure\Security\CSRFValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

/**
 * Validates submitted account data, converts the balance to minor units, and persists a new account.
 */
class CreateAccountAction extends TwigAction
{
    /**
     * @param \Psr\Log\LoggerInterface $logger Application logger used by the action.
     * @param Twig $twig Twig renderer configured with the application's templates.
     * @param CurrencyService $currencyService Service used to load available currencies.
     * @param AccountService $accountService Service used to persist new accounts.
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        Twig $twig,
        private CurrencyService $currencyService,
        private AccountService $accountService
    ) {
        parent::__construct($logger, $twig);
    }

    /**
     * Validate account form data and either create the account or render the form again with errors.
     *
     * @return Response Redirect to the welcome page on success or the same form on validation failure.
     */
    protected function action(): Response
    {
        /** @var User $user */
        $user = $this->request->getAttribute('user');
        $formData = is_array($this->getFormData()) ? $this->getFormData() : [];

        $accountCurrencyId = $formData['account_currency_id'] ?? null;
        $accountName = $formData['account_name'] ?? null;
        $accountAmountVisual = $formData['account_amount_visual'] ?? null;
        $accountAmount = $formData['account_amount'] ?? null;

        if (!is_int($accountCurrencyId) && !is_string($accountCurrencyId)) {
            $accountCurrencyId = null;
        }

        $rules = [
            'account_user_id' => 'required|integer',
            'account_currency_id' => 'required|integer',
            'account_name' => 'required|string',
            'account_amount_visual' => 'string',
            'account_amount' => 'required|string',
        ];

        $validatedData = [
            'account_user_id' => $user->getId(),
            'account_currency_id' => Sanitizer::int($accountCurrencyId),
            'account_name' => Sanitizer::string($accountName),
            'account_amount_visual' => Sanitizer::string($accountAmountVisual),
            'account_amount' => Sanitizer::string($accountAmount),
        ];

        $validator = Validator::make($validatedData, $rules);
        $validator->validate();
        $errors = $validator->errors();

        $currency = null;

        if (!isset($errors['account_currency_id']) && (int) $accountCurrencyId < 1) {
            $errors['account_currency_id'][] = 'Debe seleccionar una moneda válida.';
        }

        if (!is_null($accountCurrencyId)) {
            $currency = $this->currencyService->getCurrency($accountCurrencyId);

            if (is_null($currency)) {
                $errors['account_currency_id'][] = 'Debe seleccionar una moneda válida dentro de las opciones disponibles.';
            }
        }

        $normalizedAmount = $this->normalizeMoneyInput($validatedData['account_amount'] ?? null);
        if ($normalizedAmount === null) {
            $errors['account_amount_visual'][] = 'El saldo inicial debe ser un número válido.';
        }

        if (!empty($errors)) {
            return $this->renderCreateForm($errors, $accountName, $accountCurrencyId, $accountAmountVisual);
        }

        try {
            $currencyCode = $currency?->getCode();

            if ($currencyCode === null || $currencyCode === '') {
                throw new \RuntimeException('The selected currency could not be resolved.');
            }

            $minorBalance = $this->accountService->convertAmountToMinorUnits($normalizedAmount, $currencyCode);
            $created = $this->accountService->createAccount(
                $user->getId(),
                (string) $validatedData['account_name'],
                (int) $validatedData['account_currency_id'],
                $minorBalance,
            );

            if (!$created) {
                throw new \RuntimeException('Unable to persist the new account.');
            }

            return $this->response
                ->withHeader('Location', '/welcome')
                ->withStatus(302);
        } catch (\Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            $errors['account_amount_visual'][] = 'Ocurrió un error al procesar el saldo inicial. Revisa el valor o intenta nuevamente.';

            return $this->renderCreateForm($errors, $accountName, $accountCurrencyId, $accountAmountVisual);
        }
    }

    /**
     * Normalize the incoming amount string so it is valid for Brick Money and database storage.
     */
    private function normalizeMoneyInput(mixed $rawAmount): ?string
    {
        if (!is_string($rawAmount) && !is_numeric($rawAmount)) {
            return null;
        }

        $normalizedAmount = trim((string) $rawAmount);

        if ($normalizedAmount === '') {
            return '0';
        }

        $normalizedAmount = str_replace(',', '.', $normalizedAmount);

        if (!preg_match('/^-?\d+(?:\.\d+)?$/', $normalizedAmount)) {
            return null;
        }

        return $normalizedAmount;
    }

    /**
     * Render the form again with the current validation context and user-entered values.
     *
     * @param array<string, array<int, string>> $errors Validation errors keyed by field.
     * @param mixed $accountName
     * @param mixed $accountCurrencyId
     * @param mixed $accountAmountVisual
     */
    private function renderCreateForm(array $errors, mixed $accountName, mixed $accountCurrencyId, mixed $accountAmountVisual): Response
    {
        $currencies = array_map(
            static fn (Currency $currency): array => [
                'currency_id' => $currency->getId(),
                'currency_name' => $currency->getName(),
                'currency_code' => $currency->getCode(),
                'currency_symbol' => $currency->getSymbol(),
                'currency_minor_units' => $currency->getDecimals(),
            ],
            $this->currencyService->getCurrencies(),
        );

        return $this->renderView('accounts/create.html.twig', [
            'currencies' => $currencies,
            'csrf_token' => CSRFValidator::getCSRFToken(),
            'errors' => $errors,
            'old_account_name' => is_string($accountName) ? Sanitizer::string($accountName) : '',
            'old_account_amount' => is_string($accountAmountVisual) ? Sanitizer::string($accountAmountVisual) : '0',
            'old_account_currency_id' => is_scalar($accountCurrencyId ?? null)
                ? (string) Sanitizer::int($accountCurrencyId)
                : '',
        ]);
    }
}
