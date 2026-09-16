<?php

declare(strict_types=1);

namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

/**
 * Base action for endpoints that render Twig templates.
 */
abstract class TwigAction extends Action
{
    protected Twig $twig;

    /**
     * @param LoggerInterface $logger Application logger used by the action.
     * @param Twig $twig Twig renderer configured with the application's templates.
     */
    public function __construct(LoggerInterface $logger, Twig $twig)
    {
        parent::__construct($logger);
        $this->twig = $twig;
    }

    /**
     * Render a Twig template using the current response.
     *
     * @param string $template Template path relative to the configured Twig template directory.
     * @param array<string, mixed> $data Variables passed to the template.
     * @param int $statusCode HTTP status code for the rendered response.
     * @return Response The rendered HTTP response.
     */
    protected function renderView(
        string $template,
        array $data = [],
        int $statusCode = 200
    ): Response {
        return $this->twig
            ->render($this->response, $template, $data)
            ->withStatus($statusCode);
    }
}
