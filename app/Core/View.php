<?php

namespace App\Core;

/**
 * View
 *
 * Minimal PHP template renderer for the admin panel.
 * Templates live in app/Views and use plain PHP with escaping helpers.
 */
class View
{
    private static string $viewPath = '';

    public static function setPath(string $path): void
    {
        self::$viewPath = rtrim($path, '/\\');
    }

    /**
     * Render a view within a layout.
     *
     * @param array<string,mixed> $data
     */
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): string
    {
        $content = self::renderPartial($view, $data);
        if ($layout === '') {
            return $content;
        }
        $data['content'] = $content;
        return self::renderPartial($layout, $data);
    }

    /**
     * Render a view file without a layout.
     *
     * @param array<string,mixed> $data
     */
    public static function renderPartial(string $view, array $data = []): string
    {
        $file = self::$viewPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $view) . '.php';
        if (!is_file($file)) {
            return "<!-- view not found: {$view} -->";
        }
        // Always expose the mount base path to templates.
        if (!array_key_exists('base', $data)) {
            $data['base'] = self::basePath();
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    /**
     * HTML-escape a value.
     */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Build an application URL relative to the auto-detected base path.
     * Works both under a subdirectory (XAMPP: /license/public) and at
     * the web root (PHP built-in server).
     */
    public static function url(string $path = '/'): string
    {
        $base = self::basePath();
        $path = '/' . ltrim($path, '/');
        return rtrim($base, '/') . $path;
    }

    /**
     * Determine the base path the app is mounted at from SCRIPT_NAME.
     */
    public static function basePath(): string
    {
        $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $dir = str_replace('\\', '/', dirname($script));
        return ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
    }
}
