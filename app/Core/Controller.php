<?php

namespace App\Core;

/**
 * Controller
 *
 * Base controller with shared helpers for admin (HTML) controllers.
 */
abstract class Controller
{
    /**
     * Render a view within the admin layout and send it.
     *
     * @param array<string,mixed> $data
     */
    protected function view(string $view, array $data = [], string $layout = 'layouts/app'): never
    {
        Response::html(View::render($view, $data, $layout));
    }

    protected function redirect(string $to): never
    {
        Response::redirect($to);
    }

    /**
     * @param array<string,mixed> $data
     */
    protected function json(array $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    /**
     * Flash a message into the session for the next request.
     */
    protected function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type] = $message;
    }

    /**
     * Retrieve and clear all flash messages.
     *
     * @return array<string,string>
     */
    public static function pullFlash(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }

    protected function currentAdmin(): ?array
    {
        return $_SESSION['admin'] ?? null;
    }
}
