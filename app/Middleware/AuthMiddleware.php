<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * AuthMiddleware
 *
 * Guards admin routes: requires an authenticated admin session.
 */
class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (empty($_SESSION['admin'])) {
            if ($request->isApi()) {
                Response::error('Unauthorized', 401);
            }
            Response::redirect('/admin/login');
        }
    }
}
