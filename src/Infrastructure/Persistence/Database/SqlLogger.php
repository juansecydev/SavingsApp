<?php

declare(strict_types = 1);

namespace App\Infrastructure\Persistence\Database;

class SqlLogger
{
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
