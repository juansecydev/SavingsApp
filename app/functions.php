<?php
declare(strict_types = 1);
// This file contains general function to use in the app (debugin, show views, etc.)

use App\Infrastructure\Http\Sanitizer;
use App\Infrastructure\Http\Validator;

/**
 * This function is used to get the value of an environment variable, with support for default values and type casting.
 * @param string $key The environment variable key
 * @param mixed $default The default value if the variable is not set
 * @return mixed The value of the environment variable or the default value
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;

    if ($value === false || $value === null) {
        return $default;
    }

    return match (strtolower((string) $value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'empty', '(empty)' => '',
        'null', '(null)' => null,
        default => $value,
    };
}

/**
 * This function is used to debug, the use of the var_dump inside a die function
 * @param mixed $value is the variable to do the debug
*/
function dd(mixed $value, bool $withPre = true){
    
    if($withPre){

        echo "<pre>";
        var_dump($value);
        echo "</pre>";

    }else{
        var_dump($value);
    }
    
    die("End of debug");
}

/**
 * This function return the generated path according the directory separator system of currently the program is executing.
 * @param string[] $directories is an array with each directory name of the path.
 * @return string the full path generated.
*/
function generateSafePath(array $directories): string{
    return implode(DIRECTORY_SEPARATOR, $directories);
}

/**
 * Sanitize a value
 * @param mixed $value
 * @param string $type (string, email, int, float, url, alphanumeric, escape)
 * @return mixed
 */
function sanitize($value, string $type = 'string')
{
    return match($type) {
        'string' => Sanitizer::string($value),
        'email' => Sanitizer::email($value),
        'int', 'integer' => Sanitizer::int($value),
        'float' => Sanitizer::float($value),
        'url' => Sanitizer::url($value),
        'alphanumeric' => Sanitizer::alphanumeric($value),
        'alphanumeric_spaces' => Sanitizer::alphanumericWithSpaces($value),
        'escape' => Sanitizer::escape($value),
        default => Sanitizer::string($value),
    };
}

/**
 * Create a new validator instance
 * @param array $data
 * @param array $rules
 * @param array $messages
 * @return Validator
 */
function validate(array $data, array $rules, array $messages = []): Validator
{
    return Validator::make($data, $rules, $messages);
}

/**
 * Sanitize multiple fields at once
 * @param array $data
 * @param array $rules Format: ['field' => 'sanitizer_type']
 * @return array
 */
function sanitizeBatch(array $data, array $rules): array
{
    return Sanitizer::batch($data, $rules);
}

function getBaseUrl()
{
    // Check if the protocol is HTTPS or HTTP
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";

    // Get the host name (e.g., localhost or example.com)
    $host = $_SERVER['HTTP_HOST'];

    // Get the directory of the current script execution
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

    // Ensure it ends with a single trailing slash
    $basePath = rtrim($scriptDir, '/') . '/';

    return $protocol . $host . $basePath;
}
