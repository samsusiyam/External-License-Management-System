<?php

namespace App\Core;

/**
 * Request
 *
 * Encapsulates the incoming HTTP request: method, path, headers,
 * query params, and parsed body (JSON or form).
 */
class Request
{
    private string $method;
    private string $path;
    /** @var array<string,string> */
    private array $headers;
    /** @var array<string,mixed> */
    private array $query;
    /** @var array<string,mixed> */
    private array $body;
    private string $rawBody;

    public function __construct()
    {
        $this->method  = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path    = $this->resolvePath();
        $this->headers = $this->resolveHeaders();
        $this->query   = $_GET;
        $this->rawBody = file_get_contents('php://input') ?: '';
        $this->body    = $this->resolveBody();
    }

    private function resolvePath(): string
    {
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rawurldecode($path);

        // Strip the base path the app is mounted under (e.g. /license/public
        // when served by Apache from a subdirectory) so route patterns can
        // stay root-relative.
        $base = self::basePath();
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        // Normalise: strip trailing slash except for root.
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }

    /**
     * The directory the front controller is mounted at, derived from
     * SCRIPT_NAME. Empty string at web root.
     */
    public static function basePath(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $dir = str_replace('\\', '/', dirname($script));
        return ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
    }

    /**
     * @return array<string,string>
     */
    private function resolveHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        return $headers;
    }

    /**
     * @return array<string,mixed>
     */
    private function resolveBody(): array
    {
        $contentType = $this->header('content-type', '');
        if (str_contains($contentType, 'application/json')) {
            $decoded = json_decode($this->rawBody, true);
            return is_array($decoded) ? $decoded : [];
        }
        return $_POST;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    /**
     * @return array<string,string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    /**
     * @return array<string,mixed>
     */
    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    /**
     * @return array<string,mixed>
     */
    public function body(): array
    {
        return $this->body;
    }

    public function rawBody(): string
    {
        return $this->rawBody;
    }

    public function ip(): string
    {
        // Respect common proxy headers but fall back to REMOTE_ADDR.
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'] as $key) {
            if (!empty($_SERVER[$key])) {
                $parts = explode(',', $_SERVER[$key]);
                $ip = trim($parts[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function isApi(): bool
    {
        $base = rtrim((string) Config::get('api.base_path', '/api'), '/');
        return str_starts_with($this->path, $base . '/') || $this->path === $base;
    }
}
