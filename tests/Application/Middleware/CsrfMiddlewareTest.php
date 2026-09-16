<?php

declare(strict_types=1);

namespace Tests\Application\Middleware;

use App\Application\Middleware\CsrfMiddleware;
use App\Infrastructure\Security\CSRFValidator;
use App\Infrastructure\Security\Session;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Headers;
use Slim\Psr7\Request;
use Slim\Psr7\Uri;

class CsrfMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        Session::logout();
        parent::tearDown();
    }

    public function testUnsafeRequestWithoutTokenIsRejected(): void
    {
        $response = $this->process($this->request('POST'));

        self::assertSame(403, $response->getStatusCode());
    }

    public function testUnsafeRequestWithInvalidTokenIsRejected(): void
    {
        $response = $this->process($this->request('POST')->withParsedBody([
            'csrf_token' => 'invalid-token',
        ]));

        self::assertSame(403, $response->getStatusCode());
    }

    public function testUnsafeRequestWithValidFormTokenContinues(): void
    {
        $token = CSRFValidator::getCSRFToken();
        $response = $this->process($this->request('POST')->withParsedBody([
            'csrf_token' => $token,
        ]));

        self::assertSame(204, $response->getStatusCode());
    }

    public function testUnsafeRequestAcceptsValidHeaderToken(): void
    {
        $token = CSRFValidator::getCSRFToken();
        $response = $this->process($this->request('DELETE')->withHeader('X-CSRF-Token', $token));

        self::assertSame(204, $response->getStatusCode());
    }

    public function testSafeRequestDoesNotNeedToken(): void
    {
        $response = $this->process($this->request('GET'));

        self::assertSame(204, $response->getStatusCode());
    }

    private function process(ServerRequestInterface $request): ResponseInterface
    {
        $middleware = new CsrfMiddleware(new ResponseFactory());
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new ResponseFactory())->createResponse(204);
            }
        };

        return $middleware->process($request, $handler);
    }

    private function request(string $method): Request
    {
        $stream = (new StreamFactory())->createStream('');

        return new Request($method, new Uri('', '', 80, '/test'), new Headers(), [], [], $stream);
    }
}
