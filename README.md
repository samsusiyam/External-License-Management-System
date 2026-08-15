# External License Management System (ELMS)

A production-grade, centralized license management platform for WHMCS
modules, PHP scripts, WordPress plugins, Laravel applications, and other
commercial software. Licenses are issued, verified, activated, locked, and
managed from a single license server exposing a signed REST API plus a
Bootstrap 5 admin panel.

```
WHMCS / Software  →  ELMS REST API  →  License Database
                         ↑
                   Admin Panel (Bootstrap 5)
```

## Features

- License lifecycle: create, verify, activate, deactivate, renew, reset,
  suspend, unsuspend, terminate, delete
- Domain lock, IP lock, and activation limits
- Automatic expiry handling
- Signed REST API (API key + HMAC-SHA256 + timestamp replay protection)
- Per-key / per-IP rate limiting
- Admin dashboard with license, product, API-key, API-log and audit-log management
- WHMCS addon module + lifecycle hooks
- PHP SDK, WordPress plugin, and Laravel package
- Full audit + API request logging
- CLI installer and daily database backup script

## Requirements

- PHP 8.1+ (developed on 8.2) with PDO, cURL, OpenSSL
- MySQL 5.7+ / MariaDB 10.3+
- Apache with `mod_rewrite` (or PHP built-in server for local dev)

## Directory Layout

```
public/        Web root (front controller + assets)
app/           Core framework, controllers, models, services, middleware, views
config/        config.php (reads .env) + routes.php
database/      schema.sql + seed.sql
scripts/       install.php, backup.php, smoke_test.php
sdk/           php/ wordpress/ laravel/ client SDKs
whmcs/         WHMCS addon module (modules/addons/external_license_manager)
storage/       logs + backups
```

## Installation

1. Copy the environment file and edit credentials:
   ```
   cp .env.example .env
   ```
   Set `DB_*`, `APP_URL`, and a strong random `APP_KEY`.

2. Run the installer (creates the DB, loads schema, seeds admin + API key):
   ```
   php scripts/install.php --fresh --admin-user=admin --admin-pass="ChangeMe123"
   ```
   The installer prints the admin credentials and a generated API key/secret.
   Store them securely.

3. Point your web server's document root at `public/`. For Apache under a
   subdirectory (e.g. XAMPP `htdocs/license`), the included `.htaccess`
   files handle rewriting; the app auto-detects its base path.

4. For local development you can use the PHP built-in server:
   ```
   php -S 127.0.0.1:8080 -t public
   ```
   Then open `http://127.0.0.1:8080/admin`.

> Security: change the seeded admin password immediately, restrict access to
> `.env`, and serve the panel over HTTPS in production.

## REST API

Base path: `/api`. All endpoints are `POST` and require these headers:

| Header        | Value                                                        |
|---------------|--------------------------------------------------------------|
| `X-Api-Key`   | Public API key                                               |
| `X-Timestamp` | Current unix time (seconds); must be within ±300s            |
| `X-Signature` | `HMAC_SHA256(timestamp + "." + api_key + "." + sha256(body), secret)` |

Signature base string:
```
{timestamp}.{api_key}.{sha256(raw_json_body)}
```

### Endpoints

| Endpoint                   | Purpose                              |
|----------------------------|--------------------------------------|
| `/api/license/create`      | Generate a license                   |
| `/api/license/verify`      | Verify a license (no activation)     |
| `/api/license/activate`    | Register an activation               |
| `/api/license/deactivate`  | Free an activation slot              |
| `/api/license/renew`       | Extend expiry                        |
| `/api/license/reset`       | Clear domain/IP/activation bindings  |
| `/api/license/suspend`     | Set status = suspended               |
| `/api/license/unsuspend`   | Set status = active                  |
| `/api/license/terminate`   | Set status = terminated              |
| `/api/updates/check`       | Latest version / download URL        |

All responses use the envelope:
```json
{ "status": true, "message": "License Valid", "data": { } }
```

### Verify checks (in order)

1. License exists
2. Product key matches (if provided)
3. Not suspended / terminated
4. Not expired
5. Domain lock (if enabled)
6. IP lock (if enabled)

Activation additionally enforces the activation limit.

### Example (curl)

```bash
TS=$(date +%s)
BODY='{"license_key":"XXXX-XXXX-XXXX-XXXX","domain":"example.com","product":"WHMCS-OTP"}'
SIG=$(printf '%s.%s.%s' "$TS" "$API_KEY" "$(printf '%s' "$BODY" | sha256sum | cut -d' ' -f1)" \
      | openssl dgst -sha256 -hmac "$API_SECRET" | cut -d' ' -f2)

curl -X POST https://license.example.com/api/license/verify \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: $API_KEY" \
  -H "X-Timestamp: $TS" \
  -H "X-Signature: $SIG" \
  -d "$BODY"
```

You can run the bundled smoke test against a running server:
```
php scripts/smoke_test.php <api_key> <api_secret> http://127.0.0.1:8080
```

## Client SDKs

### PHP
```php
require 'sdk/php/license.php';
$elms = new ElmsLicense([
    'server' => 'https://license.example.com',
    'api_key' => 'elms_pk_...', 'secret' => 'elms_sk_...',
    'product' => 'WHMCS-OTP',
]);
if ($elms->verify('XXXX-XXXX-XXXX-XXXX', 'example.com')['status']) {
    // valid
}
```

### WordPress
Copy `sdk/wordpress/` into `wp-content/plugins/elms-license-client/`, activate,
and configure under Settings → ELMS License.

### Laravel
```
composer require elms/laravel-license   # from a local/VCS repo
php artisan vendor:publish --tag=elms-config
```
```php
app('elms.license')->isValid($licenseKey);
```

## WHMCS Integration

Copy `whmcs/modules/addons/external_license_manager/` into your WHMCS
`modules/addons/` directory, activate it under Setup → Addon Modules, and
enter the License Server URL, API key, and secret. Lifecycle hooks:

- `AfterModuleCreate` → create license
- `AfterModuleSuspend` → suspend
- `AfterModuleUnsuspend` → unsuspend
- `AfterModuleTerminate` → terminate

## Backups

```
php scripts/backup.php --retain-days=14
```
Schedule daily via cron (Linux) or Task Scheduler (Windows). Dumps are written
to `storage/backups/` and old dumps are pruned automatically.

## Security Notes

- All queries use PDO prepared statements.
- Admin auth is session-based with CSRF protection and bcrypt password hashing.
- API auth uses API key + HMAC-SHA256 with a timestamp replay window.
- Rate limiting is applied per API key and per IP.
- Security headers and `.htaccess` restrict access to the web root and dotfiles.
- Never commit your real `.env`; it is gitignored.

## License

Proprietary. All rights reserved.
