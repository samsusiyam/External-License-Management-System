<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\KeyGenerator;
use PDO;
use PDOException;
use Throwable;

/**
 * InstallController
 *
 * Handles the step-by-step Web Installation Wizard for ELMS.
 */
class InstallController
{
    private static string $lockFile = ELMS_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'installed.lock';

    public static function isInstalled(): bool
    {
        return is_file(self::$lockFile);
    }

    public function index(Request $request): void
    {
        if (self::isInstalled()) {
            Response::html(View::renderPartial('install/installed', [
                'title' => 'ELMS Already Installed',
            ]));
        }

        $requirements = $this->checkRequirements();
        $allPassed    = $requirements['all_passed'];

        // Auto-detect App URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443 ? 'https' : 'http';
        $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base     = Request::basePath();
        $autoUrl  = rtrim("{$protocol}://{$host}{$base}", '/');

        Response::html(View::renderPartial('install/index', [
            'title'        => 'ELMS Installation Wizard',
            'requirements' => $requirements,
            'allPassed'    => $allPassed,
            'autoUrl'      => $autoUrl,
        ]));
    }

    public function testDb(Request $request): void
    {
        if (self::isInstalled()) {
            Response::error('Application is already installed.', 403);
        }

        $host = trim((string) $request->input('db_host', '127.0.0.1'));
        $port = (int) ($request->input('db_port') ?: 3306);
        $name = trim((string) $request->input('db_name', ''));
        $user = trim((string) $request->input('db_user', 'root'));
        $pass = (string) $request->input('db_pass', '');

        if ($host === '' || $name === '' || $user === '') {
            Response::error('Host, Database Name, and User are required fields.');
        }

        try {
            // Attempt 1: Connect directly to MySQL server
            try {
                $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);

                // Check if DB exists
                $stmt = $pdo->prepare('SHOW DATABASES LIKE :dbname');
                $stmt->execute([':dbname' => $name]);
                $dbExists = (bool) $stmt->fetchColumn();

                Response::success('Database connection successful!', [
                    'database_exists' => $dbExists,
                    'message' => $dbExists
                        ? "Connected to MySQL and database '{$name}' exists."
                        : "Connected to MySQL. Database '{$name}' will be automatically created during installation.",
                ]);
            } catch (PDOException $e) {
                // Attempt 2 (cPanel / Restricted MySQL users who can only connect to specific db):
                $pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);

                Response::success('Database connection successful!', [
                    'database_exists' => true,
                    'message' => "Connected successfully to existing database '{$name}'.",
                ]);
            }
        } catch (PDOException $e) {
            Response::error('Database connection failed: ' . $e->getMessage());
        } catch (Throwable $e) {
            Response::error('Connection error: ' . $e->getMessage());
        }
    }

    public function process(Request $request): void
    {
        if (self::isInstalled()) {
            Response::error('Application is already installed.', 403);
        }

        // Validate Requirements
        $reqs = $this->checkRequirements();
        if (!$reqs['all_passed']) {
            Response::error('Server requirements or directory permissions are not met. Please resolve them first.');
        }

        // DB inputs
        $dbHost = trim((string) $request->input('db_host', '127.0.0.1'));
        $dbPort = (int) ($request->input('db_port') ?: 3306);
        $dbName = trim((string) $request->input('db_name', ''));
        $dbUser = trim((string) $request->input('db_user', 'root'));
        $dbPass = (string) $request->input('db_pass', '');

        // App inputs
        $appName = trim((string) $request->input('app_name', 'External License Manager'));
        $appUrl  = trim((string) $request->input('app_url', 'http://localhost'));
        $appUrl  = rtrim($appUrl, '/');

        // Admin inputs
        $adminName  = trim((string) $request->input('admin_name', 'Administrator'));
        $adminUser  = trim((string) $request->input('admin_user', 'admin'));
        $adminEmail = trim((string) $request->input('admin_email', 'admin@example.com'));
        $adminPass  = (string) $request->input('admin_pass', '');

        if ($dbHost === '' || $dbName === '' || $dbUser === '') {
            Response::error('Please fill in all database fields.');
        }
        if ($adminUser === '' || $adminEmail === '' || strlen($adminPass) < 6) {
            Response::error('Admin username, email, and a password of at least 6 characters are required.');
        }

        try {
            // 1. Connect to MySQL server and try creating DB (if permitted)
            try {
                $server = new PDO("mysql:host={$dbHost};port={$dbPort};charset=utf8mb4", $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
                $server->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (PDOException) {
                // In cPanel / restricted environments, user creates the database beforehand
            }

            // 2. Connect to the specific database
            $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            // 3. Load and execute database schema
            $schemaFile = ELMS_ROOT . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'schema.sql';
            if (!is_file($schemaFile)) {
                Response::error('Schema file database/schema.sql not found.');
            }
            $sql = file_get_contents($schemaFile);
            if ($sql === false) {
                Response::error('Unable to read database schema file.');
            }
            $pdo->exec($sql);

            // 4. Seed Admin user (if not exists)
            $hasAdmin = (int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
            if ($hasAdmin === 0) {
                $hash = password_hash($adminPass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO admin_users (name, email, username, password_hash, role, status) VALUES (:n, :e, :u, :p, "admin", "active")');
                $stmt->execute([
                    'n' => $adminName ?: 'Administrator',
                    'e' => $adminEmail,
                    'u' => $adminUser,
                    'p' => $hash,
                ]);
            }

            // 5. Seed default product (if not exists)
            $hasProd = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
            if ($hasProd === 0) {
                $pdo->exec("INSERT INTO products (product_name, product_key, description, latest_version, status)
                            VALUES ('WHMCS OTP Module', 'WHMCS-OTP', 'Email-based OTP 2FA module for WHMCS.', '1.0.0', 'active')");
            }

            // 6. Seed default API Key (if not exists)
            $hasKeys = (int) $pdo->query('SELECT COUNT(*) FROM api_keys')->fetchColumn();
            $apiKey    = KeyGenerator::apiKey();
            $apiSecret = KeyGenerator::apiSecret();
            if ($hasKeys === 0) {
                $stmt = $pdo->prepare('INSERT INTO api_keys (name, api_key, secret_key, status) VALUES (:n, :k, :s, "active")');
                $stmt->execute([
                    'n' => 'Default WHMCS Integration',
                    'k' => $apiKey,
                    's' => $apiSecret,
                ]);
            }

            // 7. Generate random APP_KEY
            $appKey = bin2hex(random_bytes(16));

            // 8. Generate .env file content
            $envContent = $this->buildEnvFile([
                'APP_NAME'     => $appName,
                'APP_ENV'      => 'production',
                'APP_DEBUG'    => 'false',
                'APP_URL'      => $appUrl,
                'APP_TIMEZONE' => 'UTC',
                'DB_HOST'      => $dbHost,
                'DB_PORT'      => (string) $dbPort,
                'DB_NAME'      => $dbName,
                'DB_USER'      => $dbUser,
                'DB_PASS'      => $dbPass,
                'DB_CHARSET'   => 'utf8mb4',
                'APP_KEY'      => $appKey,
            ]);

            $envPath = ELMS_ROOT . DIRECTORY_SEPARATOR . '.env';
            if (file_put_contents($envPath, $envContent) === false) {
                Response::error('Failed to write .env file. Please check folder write permissions.');
            }

            // 9. Write installed.lock
            $lockData = json_encode([
                'installed_at' => date('c'),
                'app_name'     => $appName,
                'app_url'      => $appUrl,
                'version'      => '1.0.0',
            ], JSON_PRETTY_PRINT);

            $storageDir = ELMS_ROOT . DIRECTORY_SEPARATOR . 'storage';
            if (!is_dir($storageDir)) {
                @mkdir($storageDir, 0775, true);
            }
            file_put_contents(self::$lockFile, $lockData);

            // Store summary in session for success display
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['elms_install_summary'] = [
                'admin_user' => $adminUser,
                'admin_pass' => $adminPass,
                'api_key'    => $apiKey,
                'api_secret' => $apiSecret,
                'app_url'    => $appUrl,
                'login_url'  => View::url('/admin/login'),
            ];

            Response::success('Installation completed successfully!', [
                'redirect' => View::url('/install/success'),
            ]);
        } catch (PDOException $e) {
            Response::error('Database Error: ' . $e->getMessage());
        } catch (Throwable $e) {
            Response::error('Installation Error: ' . $e->getMessage());
        }
    }

    public function success(Request $request): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $summary = $_SESSION['elms_install_summary'] ?? [
            'admin_user' => 'admin',
            'admin_pass' => '******',
            'api_key'    => 'Stored in Database',
            'api_secret' => 'Stored in Database',
            'app_url'    => Config::get('app.url', 'http://localhost'),
            'login_url'  => View::url('/admin/login'),
        ];

        Response::html(View::renderPartial('install/success', [
            'title'   => 'Installation Successful',
            'summary' => $summary,
        ]));
    }

    /**
     * @return array{requirements: array<int, array<string, mixed>>, permissions: array<int, array<string, mixed>>, all_passed: bool}
     */
    private function checkRequirements(): array
    {
        $requirements = [];
        $permissions  = [];
        $allPassed    = true;

        // PHP Version Check (>= 8.1)
        $phpPass = version_compare(PHP_VERSION, '8.1.0', '>=');
        $requirements[] = [
            'name'     => 'PHP Version (>= 8.1.0)',
            'current'  => PHP_VERSION,
            'required' => '>= 8.1.0',
            'passed'   => $phpPass,
        ];
        if (!$phpPass) {
            $allPassed = false;
        }

        // Required Extensions
        $requiredExtensions = [
            'pdo'        => 'PDO Extension',
            'pdo_mysql'  => 'PDO MySQL Driver',
            'mbstring'   => 'Mbstring Extension',
            'openssl'    => 'OpenSSL Extension',
            'json'       => 'JSON Extension',
            'curl'       => 'cURL Extension',
            'filter'     => 'Filter Extension',
        ];

        foreach ($requiredExtensions as $ext => $label) {
            $extLoaded = extension_loaded($ext);
            $requirements[] = [
                'name'     => $label,
                'current'  => $extLoaded ? 'Enabled' : 'Missing',
                'required' => 'Enabled',
                'passed'   => $extLoaded,
            ];
            if (!$extLoaded) {
                $allPassed = false;
            }
        }

        // Directory Permissions
        $dirsToCheck = [
            ELMS_ROOT . DIRECTORY_SEPARATOR . 'storage'          => 'storage/',
            ELMS_ROOT . DIRECTORY_SEPARATOR . 'storage/logs'     => 'storage/logs/',
            ELMS_ROOT . DIRECTORY_SEPARATOR . 'storage/backups'  => 'storage/backups/',
            ELMS_ROOT                                            => 'Root Directory (for .env)',
        ];

        foreach ($dirsToCheck as $dir => $label) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $isWritable = is_writable($dir);
            $permissions[] = [
                'path'     => $label,
                'current'  => $isWritable ? 'Writable' : 'Not Writable',
                'required' => 'Writable',
                'passed'   => $isWritable,
            ];
            if (!$isWritable) {
                $allPassed = false;
            }
        }

        return [
            'requirements' => $requirements,
            'permissions'  => $permissions,
            'all_passed'   => $allPassed,
        ];
    }

    /**
     * Build .env configuration string.
     *
     * @param array<string,string> $params
     */
    private function buildEnvFile(array $params): string
    {
        return <<<ENV
# ================================================================
# ELMS - External License Management System Configuration
# Generated automatically by ELMS Web Installer
# ================================================================

APP_NAME="{$params['APP_NAME']}"
APP_ENV={$params['APP_ENV']}
APP_DEBUG={$params['APP_DEBUG']}
APP_URL={$params['APP_URL']}
APP_TIMEZONE={$params['APP_TIMEZONE']}

API_BASE_PATH=/api

DB_HOST={$params['DB_HOST']}
DB_PORT={$params['DB_PORT']}
DB_NAME={$params['DB_NAME']}
DB_USER={$params['DB_USER']}
DB_PASS={$params['DB_PASS']}
DB_CHARSET={$params['DB_CHARSET']}

APP_KEY={$params['APP_KEY']}

SESSION_NAME=elms_session
SESSION_LIFETIME=7200

SIGNATURE_MAX_SKEW=300
RATE_LIMIT_MAX=120
RATE_LIMIT_WINDOW=60
LOGIN_MAX_ATTEMPTS=5
LOGIN_WINDOW=900

LOG_PATH=storage/logs

ENV;
    }
}
