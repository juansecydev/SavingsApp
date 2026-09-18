<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Actions\Action;
use App\Infrastructure\Files\File;
use App\Infrastructure\Security\Session;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Throwable;

/**
 * Validates and stages a profile image for a later profile update.
 */
class ProcessProfilePictureAction extends Action
{
    private const MAXIMUM_SIZE = 5242880;
    private const SESSION_KEY = 'profile_picture_uploads';

    /** @var string[] */
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png'];

    /** @var string[] */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    /**
     * Process one profile image and return its temporary FilePond server ID.
     *
     * The uploaded file is moved to storage/tmp and its absolute path is
     * associated with a random ID in the authenticated user's session.
     *
     * @return Response A plain-text FilePond server ID.
     * @throws HttpBadRequestException When the upload is missing or invalid.
     */
    protected function action(): Response
    {
        $uploadedFiles = $this->request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['profile_picture'] ?? null;

        if (!$uploadedFile instanceof UploadedFileInterface) {
            throw new HttpBadRequestException($this->request, 'A profile picture is required.');
        }

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new HttpBadRequestException($this->request, 'The profile picture upload failed.');
        }

        $clientFilename = $uploadedFile->getClientFilename() ?? 'profile_picture';
        $stagingFilename = File::sanitizeFileName($clientFilename, true);
        $stagingPath = STORAGE_TMP_PATH . $stagingFilename;

        try {
            $this->assertWritableDirectory(STORAGE_TMP_PATH);
            $this->assertWritableDirectory(STORAGE_USERS_PFP_PATH);

            $uploadedFile->moveTo($stagingPath);
            $file = new File($stagingPath);
            $file->validate(
                self::ALLOWED_MIME_TYPES,
                self::ALLOWED_EXTENSIONS,
                self::MAXIMUM_SIZE
            );

            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();
            $storedFilename = $this->createStoredFilename($mimeType);
            $storedPath = STORAGE_USERS_PFP_PATH . $storedFilename;

            if (!rename($stagingPath, $storedPath)) {
                throw new InvalidArgumentException('The temporary profile picture could not be stored.');
            }

            $uploadId = bin2hex(random_bytes(16));
            $uploads = Session::getData(self::SESSION_KEY);
            $uploads = is_array($uploads) ? $uploads : [];
            $uploads[$uploadId] = [
                'path' => $storedPath,
                'mime_type' => $mimeType,
                'size' => $fileSize,
            ];
            Session::setData(self::SESSION_KEY, $uploads);

            $this->response->getBody()->write($uploadId);

            return $this->response
                ->withHeader('Content-Type', 'text/plain; charset=utf-8')
                ->withStatus(200);
        } catch (InvalidArgumentException $exception) {
            $this->removeFile($stagingPath);
            throw new HttpBadRequestException(
                $this->request,
                $exception->getMessage(),
                $exception
            );
        } catch (Throwable $exception) {
            $this->removeFile($stagingPath);
            $this->logger->error('Profile picture processing failed.', [
                'exception' => $exception,
            ]);

            throw new HttpBadRequestException(
                $this->request,
                'The profile picture could not be processed.',
                $exception
            );
        }
    }

    /**
     * Verify that a directory exists and can receive uploaded files.
     *
     * @param string $directory Directory path used by the upload operation.
     * @return void
     * @throws InvalidArgumentException When the directory is unavailable.
     */
    private function assertWritableDirectory(string $directory): void
    {
        clearstatcache(true, $directory);

        if (!is_dir($directory) || !is_writable($directory)) {
            $this->logger->error('Profile picture directory is not writable.', [
                'directory' => $directory,
                'sapi' => PHP_SAPI,
                'php_binary' => PHP_BINARY,
                'current_user' => get_current_user(),
            ]);

            throw new InvalidArgumentException(
                'The profile picture storage directory is not writable by the web server.'
            );
        }
    }

    /**
     * Generate a random stored filename using the validated MIME type.
     *
     * @param string $mimeType Validated image MIME type.
     * @return string A random filename with a safe extension.
     * @throws InvalidArgumentException When the MIME type is unsupported.
     */
    private function createStoredFilename(string $mimeType): string
    {
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        if (!isset($extensions[$mimeType])) {
            throw new InvalidArgumentException('The profile picture MIME type is not allowed.');
        }

        return bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
    }

    /**
     * Remove a file when processing fails.
     *
     * @param string $path File path that may need cleanup.
     * @return void
     */
    private function removeFile(string $path): void
    {
        if (is_file($path)) {
            unlink($path);
        }
    }
}
