<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Infrastructure\Security\CSRFValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Factory\ResponseFactory;

class CsrfMiddleware implements MiddlewareInterface
{
    private const UNSAFE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function __construct(private ResponseFactory $responseFactory)
    {
    }

    /**
     * Rejects state-changing requests without a valid session CSRF token.
     *
     * Form submissions use the csrf_token field. JavaScript clients may use
     * the X-CSRF-Token header instead.
     *
     * @param Request $request The incoming HTTP request.
     * @param RequestHandler $handler The next middleware or route handler.
     * @return Response The next response or a 403 response.
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        if (!in_array(strtoupper($request->getMethod()), self::UNSAFE_METHODS, true)) {
            return $handler->handle($request);
        }

        $token = $this->getRequestToken($request);
        if ($token === null || !CSRFValidator::validateCSRFToken($token)) {
            $response = $this->responseFactory->createResponse(403);
            $response->getBody()->write('Invalid or missing CSRF token.');

            return $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
        }

        return $handler->handle($request);
    }

    /**
     * Gets a CSRF token from a form body or the JavaScript request header.
     *
     * @param Request $request The incoming HTTP request.
     * @return string|null The supplied token, or null when none is supplied.
     */
    private function getRequestToken(Request $request): ?string
    {
        $body = $request->getParsedBody();
        if (is_array($body) && isset($body['csrf_token']) && is_string($body['csrf_token'])) {
            return $body['csrf_token'];
        }

        $headerToken = $request->getHeaderLine('X-CSRF-Token');

        return $headerToken !== '' ? $headerToken : null;
    }
}
