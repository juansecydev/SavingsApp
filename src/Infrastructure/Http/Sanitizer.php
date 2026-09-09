<?php
declare(strict_types=1);

namespace App\Infrastructure\Http;

/**
 * Sanitizer class to clean and filter input data.
 * @author: Adevlinux
 */
class Sanitizer
{
    /**
     * Sanitize as string - remove tags and trim
     * @param string $value
     * @return string
     */
    public static function string($value): string
    {
        if (!is_string($value)) {
            $value = (string) $value;
        }
        return trim(strip_tags($value)); // TODO: Consider using htmlspecialchars for better XSS protection or a more robust library like HTMLPurifier for complex inputs.
    }

    /**
     * Sanitize as email
     * @param string $value
     * @return string
     */
    public static function email($value): string
    {
        return trim(strtolower(filter_var($value, FILTER_SANITIZE_EMAIL)));
    }

    /**
     * Sanitize as integer
     * @param mixed $value
     * @return int
     */
    public static function int($value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize as float
     * @param mixed $value
     * @return float
     */
    public static function float($value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, 
            FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
    }

    /**
     * Sanitize as URL
     * @param string $value
     * @return string
     */
    public static function url($value): string
    {
        return filter_var($value, FILTER_SANITIZE_URL);
    }

    /**
     * Sanitize as alphanumeric only
     * @param string $value
     * @return string
     */
    public static function alphanumeric($value): string
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $value);
    }

    /**
     * Sanitize as alphanumeric with spaces
     * @param string $value
     * @return string
     */
    public static function alphanumericWithSpaces($value): string
    {
        return preg_replace('/[^a-zA-Z0-9\s]/', '', $value);
    }

    /**
     * Escape HTML entities
     * @param string $value
     * @return string
     */
    public static function escape($value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize array of values
     * @param array $data
     * @param array $rules Format: ['field' => 'sanitizer_type']
     * @return array
     */
    public static function batch(array $data, array $rules): array
    {
        $sanitized = [];

        foreach ($rules as $field => $type) {
            if (!isset($data[$field])) {
                continue;
            }

            $value = $data[$field];

            switch ($type) {
                case 'string':
                    $sanitized[$field] = self::string($value);
                    break;
                case 'email':
                    $sanitized[$field] = self::email($value);
                    break;
                case 'int':
                case 'integer':
                    $sanitized[$field] = self::int($value);
                    break;
                case 'float':
                    $sanitized[$field] = self::float($value);
                    break;
                case 'url':
                    $sanitized[$field] = self::url($value);
                    break;
                case 'alphanumeric':
                    $sanitized[$field] = self::alphanumeric($value);
                    break;
                case 'alphanumeric_spaces':
                    $sanitized[$field] = self::alphanumericWithSpaces($value);
                    break;
                case 'escape':
                    $sanitized[$field] = self::escape($value);
                    break;
                default:
                    $sanitized[$field] = self::string($value);
            }
        }

        return $sanitized;
    }
}
