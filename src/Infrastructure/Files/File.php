<?php

declare(strict_types=1);

namespace App\Infrastructure\Files;

use InvalidArgumentException;
use RuntimeException;

/**
 * Represents a file on the local filesystem and exposes safe metadata helpers.
 */
class File
{
    private string $path;

    /**
     * @param string $path Absolute or relative path to an existing file.
     * @throws InvalidArgumentException When the path is not a regular file.
     */
    public function __construct(string $path)
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException('The file does not exist or cannot be read.');
        }

        $this->path = $path;
    }

    /**
     * Return the file name including its extension.
     *
     * @return string The file name without its parent directories.
     */
    public function getName(): string
    {
        return basename($this->path);
    }

    /**
     * Return the file name without its extension.
     *
     * @return string The file stem.
     */
    public function getNameWithoutExtension(): string
    {
        return pathinfo($this->getName(), PATHINFO_FILENAME);
    }

    /**
     * Return the path supplied to the object.
     *
     * @return string The file path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Resolve the file path to its canonical filesystem path.
     *
     * @return string The canonical path.
     * @throws RuntimeException When the path cannot be resolved.
     */
    public function getRealPath(): string
    {
        $realPath = realpath($this->path);

        if ($realPath === false) {
            throw new RuntimeException('The file real path could not be resolved.');
        }

        return $realPath;
    }

    /**
     * Return the lowercase file extension.
     *
     * @return string The extension without a leading dot.
     */
    public function getExtension(): string
    {
        return strtolower((string) pathinfo($this->getName(), PATHINFO_EXTENSION));
    }

    /**
     * Detect the file MIME type from its contents.
     *
     * @return string The detected MIME type.
     * @throws RuntimeException When MIME detection fails.
     */
    public function getMimeType(): string
    {
        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->file($this->path);
        
        if (!is_string($mimeType) || $mimeType === '') {
            throw new RuntimeException('The file MIME type could not be detected.');
        }

        return $mimeType;
    }

    /**
     * Return the file size in bytes.
     *
     * @return int The file size.
     * @throws RuntimeException When the size cannot be read.
     */
    public function getSize(): int
    {
        $size = filesize($this->path);

        if ($size === false) {
            throw new RuntimeException('The file size could not be read.');
        }

        return $size;
    }

    /**
     * Determine whether the file contains a readable image.
     *
     * @return bool True when image metadata can be read.
     */
    public function isImage(): bool
    {
        return @getimagesize($this->path) !== false;
    }

    /**
     * Validate the file MIME type.
     *
     * @param string[] $allowedMimeTypes MIME types accepted by the caller.
     * @return void
     * @throws InvalidArgumentException When the MIME type is not allowed.
     */
    public function validateMimeType(array $allowedMimeTypes): void
    {
        if (!in_array($this->getMimeType(), $allowedMimeTypes, true)) {
            throw new InvalidArgumentException('The file MIME type is not allowed.');
        }
    }

    /**
     * Validate the file extension.
     *
     * @param string[] $allowedExtensions Extensions accepted without leading dots.
     * @return void
     * @throws InvalidArgumentException When the extension is not allowed.
     */
    public function validateExtension(array $allowedExtensions): void
    {
        $extension = $this->getExtension();
        $allowedExtensions = array_map('strtolower', $allowedExtensions);

        if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
            throw new InvalidArgumentException('The file extension is not allowed.');
        }
    }

    /**
     * Validate the maximum file size.
     *
     * @param int $maximumBytes Maximum accepted size in bytes.
     * @return void
     * @throws InvalidArgumentException When the file is too large.
     */
    public function validateSize(int $maximumBytes): void
    {
        if ($maximumBytes < 0 || $this->getSize() > $maximumBytes) {
            throw new InvalidArgumentException('The file size is not allowed.');
        }
    }

    /**
     * Require the file to contain readable image data.
     *
     * @return void
     * @throws InvalidArgumentException When the file is not an image.
     */
    public function validateImage(): void
    {
        if (!$this->isImage()) {
            throw new InvalidArgumentException('The file is not a valid image.');
        }
    }

    /**
     * Validate MIME type, extension, size, and image content together.
     *
     * @param string[] $allowedMimeTypes MIME types accepted by the caller.
     * @param string[] $allowedExtensions Extensions accepted without leading dots.
     * @param int $maximumBytes Maximum accepted size in bytes.
     * @return void
     * @throws InvalidArgumentException When any validation fails.
     */
    public function validate(array $allowedMimeTypes, array $allowedExtensions, int $maximumBytes): void
    {
        $this->validateMimeType($allowedMimeTypes);
        $this->validateExtension($allowedExtensions);
        $this->validateSize($maximumBytes);
        $this->validateImage();
    }

    /**
     * Sanitize a client-provided file name and optionally replace it randomly.
     *
     * The result contains only ASCII letters, digits, underscores, and one
     * extension separator, and is limited to 50 characters.
     *
     * @param string $fileName Client-provided file name.
     * @param bool $useUniqueName Replace the stem with cryptographically random bytes.
     * @return string A safe file name no longer than 50 characters.
     */
    public static function sanitizeFileName(string $fileName, bool $useUniqueName = false): string
    {
        $extension = strtolower((string) pathinfo($fileName, PATHINFO_EXTENSION));
        $extension = preg_replace('/[^a-z0-9]/', '', $extension) ?? '';
        $extension = substr($extension, 0, 47);
        $suffix = $extension === '' ? '' : '.' . $extension;

        if ($useUniqueName) {
            return substr(bin2hex(random_bytes(16)), 0, 50 - strlen($suffix)) . $suffix;
        }

        $stem = (string) pathinfo($fileName, PATHINFO_FILENAME);
        $stem = preg_replace('/[^a-zA-Z0-9]/', '_', $stem) ?? 'file';
        $stem = trim($stem, '_');
        $stem = $stem === '' ? 'file' : $stem;

        $maximumStemLength = max(1, 50 - strlen($suffix));

        return substr($stem, 0, $maximumStemLength) . $suffix;
    }
}
