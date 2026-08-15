<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\AdminUser;
use App\Services\AuditService;
use App\Services\LoginThrottle;

/**
 * AuthController
 *
 * Admin login / logout with session hardening and CSRF protection.
 */
class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        if (!empty($_SESSION['admin'])) {
            $this->redirect('/admin');
        }
        $this->view('auth/login', [
            'title' => 'Sign in',
            'flash' => self::pullFlash(),
            'csrf'  => Csrf::token(),
        ], 'layouts/auth');
    }

    public function login(Request $request): void
    {
        if (!Csrf::verify((string) $request->input('_csrf'))) {
            $this->flash('error', 'Invalid session token. Please try again.');
            $this->redirect('/admin/login');
        }

        $throttle = new LoginThrottle($request->ip());
        if ($throttle->isBlocked()) {
            $this->flash('error', 'Too many failed attempts. Please try again later.');
            $this->redirect('/admin/login');
        }

        $username = trim((string) $request->input('username'));
        $password = (string) $request->input('password');

        $model = new AdminUser();
        $user  = $model->findByUsername($username);

        if ($user === null || $user['status'] !== 'active' || !password_verify($password, $user['password_hash'])) {
            $throttle->recordFailure();
            AuditService::log('admin.login_failed', 'admin', $username, null, null,
                ['reason' => 'bad_credentials'], $request->ip());
            $this->flash('error', 'Invalid username or password.');
            $this->redirect('/admin/login');
        }

        // Rehash if needed.
        if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT)) {
            $model->updateById((int) $user['id'], [
                'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            ]);
        }

        // Successful login: clear the failure counter so the admin is not
        // penalised on the next session.
        $throttle->clear();

        // Session fixation defense.
        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id'       => (int) $user['id'],
            'name'     => $user['name'],
            'username' => $user['username'],
            'role'     => $user['role'],
        ];
        $model->touchLogin((int) $user['id']);

        AuditService::log('admin.login', 'admin', $user['username'], null, null, [], $request->ip());
        $this->redirect('/admin');
    }

    public function logout(Request $request): void
    {
        $username = $_SESSION['admin']['username'] ?? null;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        if ($username !== null) {
            AuditService::log('admin.logout', 'admin', $username, null, null, [], $request->ip());
        }
        $this->redirect('/admin/login');
    }
}
