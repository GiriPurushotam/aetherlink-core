<?php

declare(strict_types=1);

namespace AetherLink\Core\Config;

use AetherLink\Core\Exceptions\InvalidEnvironmentException;


final class Env
{
    private static bool $loaded = false;
    /**
     * Load and parse a raw .env file into $_ENV.
     */
    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        $realPath = realpath($path);
        if ($realPath === false || !file_exists($realPath)) {
            throw new InvalidEnvironmentException(sprintf(
                'Environment Bootstrap Fault: Target configuration file [%s] was not found on path [%s].',
                $path,
                $realPath ?: 'UNRESOLVED_PATH'
            ));
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new InvalidEnvironmentException(sprintf('Environment Bootstrap fault: Unable to read file [%s].', $realPath));
        }
        foreach ($lines as $line) {
            $line = trim($line);

            //Skip comments or malformed lines
            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, "\t\n\r\0\x0B\"'");

            if ($key !== '') {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        self::$loaded = true;
    }
    public static function get(string $key): string
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null || trim((string) $value) === '') {
            throw InvalidEnvironmentException::missingVariable($key);
        }

        return (string) $value;
    }

    /**
     * Retrive a integer environment variable with strick range validation. 
     */

    public static function getInt(string $key): int
    {
        $value = self::get($key);

        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            throw InvalidEnvironmentException::invalidType($key, 'int', $value);
        }

        return (int) $value;
    }

    /**
     * Retrive a boolean environment variable
     */

    public static function getBool(string $key): bool
    {
        $value = self::get($key);
        return filter_var($value, FILTER_VALIDATE_BOOL);
    }
}
