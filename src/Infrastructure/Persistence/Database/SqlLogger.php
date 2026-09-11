<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Database;

/**
 * Formats bound values and creates readable SQL for diagnostic logging.
 *
 * This class does not execute SQL and must not be used to build statements
 * for execution. Its interpolated output is an approximation for logs only.
 */
class SqlLogger
{
    /**
     * Convert bound values to safe, readable scalar log values.
     *
     * Binary strings are represented as hexadecimal text. Booleans and null
     * are represented by their SQL-like words.
     *
     * @param array<int|string, mixed> $bindings Values bound to a statement.
     * @return array<int|string, string> Formatted values for diagnostic output.
     */
    public static function formatBindings(array $bindings): array
    {
        return array_map(static function ($binding) {
            if (is_string($binding)) {
                if (!mb_check_encoding($binding, 'UTF-8')) {
                    return bin2hex($binding);
                }

                return $binding;
            }

            if (is_bool($binding)) {
                return $binding ? 'true' : 'false';
            }

            if ($binding === null) {
                return 'null';
            }

            return (string) $binding;
        }, $bindings);
    }

    /**
     * Replace positional question-mark placeholders with readable values.
     *
     * Strings are quoted and single quotes are escaped. Missing bindings leave
     * their placeholders unchanged; this method never validates or executes SQL.
     *
     * @param string $sql Parameterized SQL statement.
     * @param array<int|string, mixed> $bindings Values bound positionally.
     * @return string An emulated SQL statement for logging.
     */
    public static function interpolateQuery(string $sql, array $bindings): string
    {
        $formatted = self::formatBindings($bindings);

        $indexed = 0;
        $result = preg_replace_callback('/\?/', static function ($matches) use (&$indexed, $formatted) {
            if (!array_key_exists($indexed, $formatted)) {
                return $matches[0];
            }

            $value = $formatted[$indexed];
            $indexed++;

            if ($value === 'null' || $value === 'true' || $value === 'false' || is_numeric($value)) {
                return $value;
            }

            return "'" . str_replace("'", "''", $value) . "'";
        }, $sql);

        return $result;
    }
}
