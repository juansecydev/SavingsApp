<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Application\Services\LoginService;
use App\Infrastructure\Http\Sanitizer;
use App\Infrastructure\Http\Validator;
use App\Infrastructure\Security\CSRFValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Views\Twig;

class LoginMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoginService $loginService,
        private Twig $twig,
        private ResponseFactory $responseFactory
    ) {}

    public function process(Request $request, RequestHandler $handler): Response
    {
        $formData = $request->getParsedBody();
        $formData = is_array($formData) ? $formData : [];
        $credentials = Sanitizer::batch($formData, [
            'email' => 'email',
            'password' => 'string',
        ]);

        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $errors = [];
        if (!$validator->validate()) {
            $errors = $validator->errors();
        }

        $user = empty($errors)
            ? $this->loginService->authenticate($credentials['email'], $credentials['password'])
            : null;

        if (empty($errors) && $user === null) {
            $errors['credentials'][] = 'Las credenciales no son válidas';
        }

        if (!empty($errors)) {
            return $this->twig->render($this->responseFactory->createResponse(), 'login.html.twig', [
                'title' => 'Error al iniciar sesión',
                'errors' => $errors,
                'old_email' => $credentials['email'] ?? '',
                'csrf_token' => CSRFValidator::getCSRFToken(),
            ]);
        }

        return $handler->handle($request->withAttribute('user', $user));
    }
}