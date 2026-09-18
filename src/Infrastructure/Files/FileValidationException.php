<?php

declare(strict_types=1);

namespace App\Infrastructure\Files;

use RuntimeException;

/**
 * Indicates that a file does not satisfy an allowed upload rule.
 */
class FileValidationException extends RuntimeException
{
}
