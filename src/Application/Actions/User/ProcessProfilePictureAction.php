<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Actions\Action;
use App\Infrastructure\Files\FileValidationException;
use App\Infrastructure\Files\ProfilePictureUploadService;
use App\Infrastructure\Security\Session;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Throwable;

/**
 * Handles FilePond profile-picture processing requests.
 */
class ProcessProfilePictureAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        private ProfilePictureUploadService $uploadService
    ) {
        parent::__construct($logger);
    }

    /**
     * Validate and temporarily store the uploaded profile picture.
     *
    * Validation failures return HTTP 400 with their simple validation
    * message. Unexpected failures are logged and return a generic message.
    *
    * @return Response A plain-text FilePond upload ID.
    * @throws HttpBadRequestException When the upload cannot be processed.
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

        try {
            $storedFile = $this->uploadService->process($uploadedFile);
            $uploadId = bin2hex(random_bytes(16));
            $uploads = Session::getData(Session::IMG_DATA_KEY);
            $uploads = is_array($uploads) ? $uploads : [];
            $uploads[$uploadId] = $storedFile;
            Session::setData(Session::IMG_DATA_KEY, $uploads);

            $this->response->getBody()->write($uploadId);

            return $this->response
                ->withHeader('Content-Type', 'text/plain; charset=utf-8')
                ->withStatus(200);
        } catch (FileValidationException $exception) {
            throw new HttpBadRequestException(
                $this->request,
                $exception->getMessage(),
                $exception
            );
        } catch (Throwable $exception) {
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
}
