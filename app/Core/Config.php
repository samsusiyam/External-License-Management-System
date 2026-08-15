<?php

namespace App\Core;

/**
 * Config
 *
 * Minimal .env parser + application config accessor.
 * Values are cached in a static store after the first load.
 */
class Config
{
    /** @var array<string,string> */
    private static array $env = [];

    /** @var array<string,mixed> */
    private static array $items = [];

    private static bool $envLoaded = false;

    /**
     * Parse a .env file into the static env store.
     */
    public static function loadEnv(string $path): void
    {
        if (self::$envLoaded) {
            return;
        }
        self::$envLoaded = true;

        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key   = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // Strip surrounding quotes.
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last  = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            self::$env[$key] = $value;
        }
    }

    /**
     * Get a raw env value.
     */
    public static function env(string $key, ?string $default = null): ?string
    {
        return self::$env[$key] ?? $default;
    }

    /**
     * Get an env value interpreted as boolean.
     */
    public static function envBool(string $key, bool $default = false): bool
    {
        if (!array_key_exists($key, self::$env)) {
            return $default;
        }
        return in_array(strtolower(self::$env[$key]), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Load the full config array (config/config.php) into the store.
     */
    public static function boot(string $configFile): void
    {
        self::$items = require $configFile;
    }

    /**
     * Dot-notation config accessor, e.g. Config::get('db.host').
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value    = self::$items;
        foreach ($segments as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        return $value;
    }

    /**
     * Return the entire config array.
     *
     * @return array<string,mixed>
     */
    public static function all(): array
    {
        return self::$items;
    }
}
