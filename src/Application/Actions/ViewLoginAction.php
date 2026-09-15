<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Infrastructure\Security\CSRFValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ViewLoginAction
{
    public function __construct(private Twig $twig) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $token = CSRFValidator::getCSRFToken();
        return $this->twig->render($response, 'login.html.twig', [
            'csrf_token' => $token,
        ]);
    }
}