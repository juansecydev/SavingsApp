<?php

declare(strict_types=1);

namespace App\Application\Middleware;

use App\Infrastructure\Security\Session;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * Prevents processing the profile image already represented by the session.
 */
class ProfilePictureProcessMiddleware implements MiddlewareInterface
{
    private const IMAGE_ID_HEADER = 'X-Profile-Picture-Id';

    public function __construct(private ResponseFactory $responseFactory)
    {
    }

    /**
     * Validate the FilePond image-operation identifier before processing.
     *
    * A missing identifier, default identifier, or current session identifier
    * is rejected. Newly selected files use FilePond's generated file ID.
     *
     * @param Request $request Incoming FilePond process request.
     * @param RequestHandler $handler Next middleware or action.
     * @return Response The action response or a 400 validation response.
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $imageId = trim($request->getHeaderLine(self::IMAGE_ID_HEADER));
        $sessionPicture = Session::getData('user_profile_picture');
        $currentImageId = is_array($sessionPicture)
            && is_string($sessionPicture['id'] ?? null)
            ? $sessionPicture['id']
            : null;
        if ($imageId === '' || $imageId === 'default' || $imageId === $currentImageId) {
            $response = $this->responseFactory->createResponse(400);
            $response->getBody()->write('The profile picture cannot be the same as the current image.');

            return $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
        }

        return $handler->handle($request);
    }
}
