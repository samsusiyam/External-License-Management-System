<?php

/**
 * ELMS Installer (CLI)
 *
 * Creates the database (if missing), loads the schema, and seeds a
 * fresh admin account + API key with freshly generated credentials.
 *
 * Usage:
 *   php scripts/install.php
 *   php scripts/install.php --admin-user=admin --admin-pass=Secret123 --fresh
 *
 * Flags:
 *   --fresh              Drop & recreate all tables before loading schema.
 *   --admin-user=NAME    Admin username (default: admin)
 *   --admin-pass=PASS    Admin password (default: randomly generated)
 *   --admin-email=EMAIL  Admin email (default: admin@example.com)
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Config;
use App\Services\KeyGenerator;

/**
 * @return array<string,string|bool>
 */
function parseArgs(array $argv): array
{
    $out = [];
    foreach (array_slice($argv, 1) as $arg) {
        if (str_starts_with($arg, '--')) {
            $arg = substr($arg, 2);
            if (str_contains($arg, '=')) {
                [$k, $v] = explode('=', $arg, 2);
                $out[$k] = $v;
            } else {
                $out[$arg] = true;
            }
        }
    }
    return $out;
}

$args = parseArgs($argv);

$host    = (string) Config::get('db.host');
$port    = (int) Config::get('db.port');
$name    = (string) Config::get('db.name');
$user    = (string) Config::get('db.user');
$pass    = (string) Config::get('db.pass');
$charset = (string) Config::get('db.charset', 'utf8mb4');

echo "== ELMS Installer ==\n";
echo "Database: {$name} @ {$host}:{$port}\n";

// 1) Connect to the server (no DB selected) and create the database.
try {
    $server = new PDO("mysql:host={$host};port={$port};charset={$charset}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $server->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "[ok] Database ready.\n";
} catch (PDOException $e) {
    fwrite(STDERR, "[fail] Could not connect/create database: {$e->getMessage()}\n");
    exit(1);
}

// 2) Connect to the database.
$pdo = new PDO("mysql:host={$host};port={$port};dbname={$name};charset={$charset}", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// 3) Optionally drop existing tables.
if (!empty($args['fresh'])) {
    echo "[..] --fresh: dropping existing tables.\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['api_logs','audit_logs','rate_limits','activations','licenses','api_keys','products','admin_users'] as $t) {
        $pdo->exec("DROP TABLE IF EXISTS `{$t}`");
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
}

// 4) Load schema.
$schema = file_get_contents(dirname(__DIR__) . '/database/schema.sql');
if ($schema === false) {
    fwrite(STDERR, "[fail] Could not read schema.sql\n");
    exit(1);
}
$pdo->exec($schema);
echo "[ok] Schema loaded.\n";

// 5) Seed admin + API key (only if tables are empty).
$hasAdmin = (int) $pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();

if ($hasAdmin === 0) {
    $adminUser  = is_string($args['admin-user'] ?? null) ? $args['admin-user'] : 'admin';
    $adminEmail = is_string($args['admin-email'] ?? null) ? $args['admin-email'] : 'admin@example.com';
    $adminPass  = is_string($args['admin-pass'] ?? null) ? $args['admin-pass'] : bin2hex(random_bytes(6));
    $hash = password_hash($adminPass, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare(
        'INSERT INTO admin_users (name, email, username, password_hash, role, status)
         VALUES (:n, :e, :u, :p, "admin", "active")'
    );
    $stmt->execute([
        'n' => 'Administrator', 'e' => $adminEmail, 'u' => $adminUser, 'p' => $hash,
    ]);

    // Sample product.
    $pdo->exec(
        "INSERT INTO products (product_name, product_key, description, latest_version, status)
         VALUES ('WHMCS OTP Module', 'WHMCS-OTP', 'Email-based OTP 2FA module for WHMCS.', '1.0.0', 'active')"
    );

    // API key.
    $apiKey    = KeyGenerator::apiKey();
    $apiSecret = KeyGenerator::apiSecret();
    $stmt = $pdo->prepare(
        'INSERT INTO api_keys (name, api_key, secret_key, status) VALUES (:n, :k, :s, "active")'
    );
    $stmt->execute(['n' => 'Default Integration', 'k' => $apiKey, 's' => $apiSecret]);

    echo "\n== Credentials (store these securely) ==\n";
    echo "Admin username : {$adminUser}\n";
    echo "Admin password : {$adminPass}\n";
    echo "API Key        : {$apiKey}\n";
    echo "API Secret     : {$apiSecret}\n";
    echo "========================================\n";
} else {
    echo "[skip] admin_users already populated; not re-seeding.\n";
}

echo "\n[done] Installation complete. Admin panel: " . Config::get('app.url') . "/admin\n";
