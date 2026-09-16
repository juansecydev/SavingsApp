<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Infrastructure\Security\CSRFValidator;
use Psr\Http\Message\ResponseInterface as Response;

class WelcomeAction extends TwigAction
{
    protected function action(): Response
    {
        return $this->renderView('landing.html.twig', [
            'csrf_token' => CSRFValidator::getCSRFToken(),
        ]);
    }
}
