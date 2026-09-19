<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Actions\Action;
use App\Infrastructure\Security\Session;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpNotFoundException;

/**
 * Serves the authenticated user's current profile picture to FilePond.
 */
class GetProfilePictureAction extends Action
{
    private const DEFAULT_ID = 'default';

    /**
     * Return the requested profile picture or the default avatar.
     *
     * Only the picture ID stored in the current session may resolve to a
     * custom image. Invalid, missing, or unavailable images use default.png.
     *
     * @return Response Image response with its detected content type.
     * @throws HttpNotFoundException When the default image is unavailable.
     */
    protected function action(): Response
    {
        $picturePath = $this->resolvePicturePath((string) $this->resolveArg('id'));
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($picturePath);

        if (!is_string($mimeType) || !in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
            throw new HttpNotFoundException($this->request, 'Profile picture not found.');
        }

        $contents = file_get_contents($picturePath);

        if ($contents === false) {
            throw new HttpNotFoundException($this->request, 'Profile picture not found.');
        }

        $this->response->getBody()->write($contents);

        return $this->response
            ->withHeader('Content-Type', $mimeType)
            ->withHeader('Content-Length', (string) filesize($picturePath))
            ->withHeader('Cache-Control', 'private, max-age=3600');
    }

    /**
     * Resolve a session picture ID to an existing safe filesystem path.
     *
     * @param string $pictureId Picture ID supplied by the route.
     * @return string Existing profile-picture path.
     * @throws HttpNotFoundException When default.png is unavailable.
     */
    private function resolvePicturePath(string $pictureId): string
    {
        $defaultPath = STORAGE_USERS_PFP_PATH . 'default.png';
        $sessionPicture = Session::getData('user_profile_picture');

        if (
            $pictureId !== self::DEFAULT_ID
            && is_array($sessionPicture)
            && ($sessionPicture['id'] ?? null) === $pictureId
            && is_string($sessionPicture['path'] ?? null)
        ) {
            $relativePath = str_replace('\\', '/', ltrim($sessionPicture['path'], '/\\'));

            if (strpos($relativePath, 'users/pfp/') === 0) {
                $filename = basename($relativePath);
                $customPath = STORAGE_USERS_PFP_PATH . $filename;

                if (is_file($customPath) && is_readable($customPath)) {
                    return $customPath;
                }
            }
        }

        if (is_file($defaultPath) && is_readable($defaultPath)) {
            return $defaultPath;
        }

        throw new HttpNotFoundException($this->request, 'Default profile picture not found.');
    }
}
