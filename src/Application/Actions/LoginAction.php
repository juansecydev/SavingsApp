<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Infrastructure\Http\Sanitizer;
use App\Infrastructure\Http\Validator;
use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class LoginAction
{
    public function __construct(private Twig $twig, private PDO $db) {}

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        if ($request->getMethod() === 'GET') {
            return $this->twig->render($response, 'login.html.twig');
        }

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

        if (empty($errors) && ($credentials['email'] ?? '') !== 'marin.kitagawa@email.com') {
            $errors['credentials'][] = 'Las credenciales no son válidas';
        }

        if (empty($errors) && ($credentials['password'] ?? '') !== '12345') {
            $errors['credentials'][] = 'Las credenciales no son válidas';
        }

        if (!empty($errors)) {
            return $this->twig->render($response, 'login_error.html.twig', [
                'title' => 'Error al iniciar sesión',
                'errors' => $errors,
                'old_email' => $credentials['email'] ?? '',
            ]);
        }

        return $this->twig->render($response, 'welcome.html.twig', [
            'user' => [
                'user_name' => 'Marin',
                'user_lastname' => 'Kitagawa',
                'user_email' => $credentials['email'],
                'created_at' => null,
            ],
            'accounts' => [],
        ]);
    }
}