<?php

declare(strict_types=1);

namespace App\Application\Actions\Account;

use App\Application\Actions\TwigAction;
use App\Application\Services\CurrencyService;
use App\Domain\Currency\Currency;
use App\Infrastructure\Security\CSRFValidator;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Renders the account-creation form with its available currencies.
 */
class ViewCreateAccountAction extends TwigAction
{
    /**
     * @param \Psr\Log\LoggerInterface $logger Application logger used by the action.
     * @param \Slim\Views\Twig $twig Twig renderer configured with the application's templates.
     * @param CurrencyService $currencyService Service used to load available currencies.
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Slim\Views\Twig $twig,
        private CurrencyService $currencyService
    ) {
        parent::__construct($logger, $twig);
    }

    /**
     * Render the account-creation form and its currency options.
     *
     * @return Response Account-creation form response.
     */
    protected function action(): Response
    {
        $currencies = array_map(
            static fn (Currency $currency): array => [
                'currency_id' => $currency->getId(),
                'currency_name' => $currency->getName(),
                'currency_code' => $currency->getCode(),
                'currency_symbol' => $currency->getSymbol(),
                'currency_decimals' => $currency->getDecimals(),
            ],
            $this->currencyService->getCurrencies(),
        );

        return $this->renderView('accounts/create.html.twig', [
            'csrf_token' => CSRFValidator::getCSRFToken(),
            'currencies' => $currencies,
        ]);
    }
}
