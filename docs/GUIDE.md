# ELMS Documentation (Guideline)

**External License Management System (ELMS)** — a centralized license management
platform. This documentation contains:

1. Live API URLs
2. How to call the API (authentication + signature)
3. Steps to self-host the ELMS server
4. How to install it as a module for WHMCS / WordPress / PHP / Laravel

---

## 1. Live API URL

| Environment | Base URL |
|-------------|----------|
| **Production (live)** | `https://lic.hostnibo.com` |
| Admin Panel | `https://lic.hostnibo.com/admin` |
| API Base | `https://lic.hostnibo.com/api` |
| Health Check | `https://lic.hostnibo.com/health` |

> All API endpoints below are prefixed with `https://lic.hostnibo.com`. For
> example, to verify: `https://lic.hostnibo.com/api/license/verify`

---

## 2. API Authentication

Every `/api/*` endpoint is a **POST** and requires these 3 headers, otherwise
it will not work:

| Header | Value |
|--------|-------|
| `X-Api-Key` | Your public API key (e.g. `elms_pk_xxx`) |
| `X-Timestamp` | Current unix time (seconds); must be within ±300s (5 minutes) of the server |
| `X-Signature` | HMAC-SHA256 signature built using the rule below |

**How the signature is built:**

```
base_string = {timestamp} + "." + {api_key} + "." + sha256({raw_json_body})
signature   = HMAC_SHA256(base_string, {api_secret})
```

In plain words: take the sha256 of your JSON body, join it as
`timestamp.api_key.sha256body`, then HMAC it with your secret.

**Response Signature:** the server also returns `X-Signature` + `X-Timestamp`
headers on the response. The client (SDK) verifies them — so a MITM attack
cannot forge a fake "valid" response. All ready-made SDKs have this check
built in.

---

## 3. API Endpoints

All endpoints are POST. Request body = JSON. Response envelope:

```json
{ "status": true, "message": "License Valid", "data": { } }
```

| Endpoint | Purpose |
|----------|---------|
| `/api/license/create` | Generate a license (params: `product_id`, `customer_name`, `customer_email`, `activation_limit`, `expiry_date`) |
| `/api/license/verify` | Check a license (params: `license_key`, `domain`, `ip`, `product`) |
| `/api/license/activate` | Register an activation (params: `license_key`, `domain`, `ip`, `product`, `server_hostname`, `install_path`) |
| `/api/license/deactivate` | Free an activation slot |
| `/api/license/renew` | Extend expiry (`expiry_date`) |
| `/api/license/reset` | Clear domain/IP/activation bindings |
| `/api/license/suspend` | Set status = suspended |
| `/api/license/unsuspend` | Set status = active |
| `/api/license/terminate` | Set status = terminated |
| `/api/updates/check` | Latest version + download URL (`product`, `current_version`, `license_key`) |

**Verify checks (in order):** license exists? → product matches? → not
suspended/terminated? → not expired? → domain lock → IP lock. Activate also
enforces the activation limit.

### curl example (verify)

```bash
API_KEY="elms_pk_xxx"
API_SECRET="elms_sk_xxx"
TS=$(date +%s)
BODY='{"license_key":"XXXX-XXXX-XXXX-XXXX","domain":"example.com","product":"WHMCS-OTP"}'
BODY_HASH=$(printf '%s' "$BODY" | sha256sum | cut -d' ' -f1)
SIG=$(printf '%s.%s.%s' "$TS" "$API_KEY" "$BODY_HASH" | openssl dgst -sha256 -hmac "$API_SECRET" | cut -d' ' -f2)

curl -X POST https://lic.hostnibo.com/api/license/verify \
  -H "Content-Type: application/json" \
  -H "X-Api-Key: $API_KEY" \
  -H "X-Timestamp: $TS" \
  -H "X-Signature: $SIG" \
  -d "$BODY"
```

**Where to get the API key:** Admin Panel → `API Keys` → create a new key and
copy the `api_key` and `secret_key`.

---

## 4. Install the ELMS server (self-host)

Requirements: **PHP 8.1+** (8.2 recommended), **MySQL/MariaDB**, **Apache**
(mod_rewrite) or the PHP built-in server.

1. **Clone the repo / upload files:**
   ```bash
   git clone <repo> license
   cd license
   ```

2. **Create the .env file:**
   ```bash
   cp .env.example .env
   ```
   Edit it and set: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_URL`
   (your domain), and a long random `APP_KEY`.

3. **Run the installer** (creates the database, loads the schema, seeds the
   admin user + API key):
   ```bash
   php scripts/install.php --fresh --admin-user=admin --admin-pass="ChangeMe123"
   ```
   The output prints the admin password + API key/secret — save them.

4. **Point the web root at `public/`.** In Apache the document root must be
   `public/` (the app auto-detects its base path even in a subdirectory).

5. **Local dev server:**
   ```bash
   php -S 127.0.0.1:8080 -t public
   ```
   Then open `http://127.0.0.1:8080/admin`.

> **Security:** change the seeded admin password immediately, do not expose
> `.env` publicly, and use HTTPS in production.

---

## 5. Install the WHMCS module

Folder: `whmcs/modules/addons/external_license_manager/`

1. Copy the folder:
   ```
   whmcs/modules/addons/external_license_manager/
   ```
   → into your WHMCS `modules/addons/external_license_manager/`.

2. In WHMCS admin go to: **Setup → Addon Modules** → "External License
   Manager" → **Activate**.

3. Configure the module:
   - **License Server URL** = `https://lic.hostnibo.com`
   - **API Key** = the `api_key` from ELMS
   - **API Secret** = the `secret_key` from ELMS
   - **Default ELMS Product ID** = the ID of the product created in ELMS

4. **Lifecycle hooks (automatic):**
   - `AfterModuleCreate` → create license
   - `AfterModuleSuspend` → suspend
   - `AfterModuleUnsuspend` → unsuspend
   - `AfterModuleTerminate` → terminate

The module ships with HMAC request signing + response signature verification
built in, so no extra configuration is needed.

---

## 6. Install the WordPress plugin

Folder: `sdk/wordpress/`

1. Copy the folder `sdk/wordpress/` → `wp-content/plugins/elms-license-client/`.

2. WP admin → **Plugins** → "ELMS License Client" → **Activate**.

3. Configure under **Settings → ELMS License** (or via constants in
   `wp-config.php`):
   ```php
   define('ELMS_SERVER',    'https://lic.hostnibo.com');
   define('ELMS_API_KEY',   'elms_pk_xxx');
   define('ELMS_API_SECRET', 'elms_sk_xxx');
   define('ELMS_PRODUCT',   'WHMCS-OTP');
   ```
   Set the license key in the `elms_license_key` option.

4. Check in code:
   ```php
   if (ELMS_License_Client::isValid()) {
       // license is valid
   }
   ```
   The API secret is stored encrypted (AES-256-CBC) in `wp_options`, and the
   verification result is cached for 10 minutes.

---

## 7. Install the PHP SDK

File: `sdk/php/license.php`

```php
require 'sdk/php/license.php';

$elms = new ElmsLicense([
    'server'  => 'https://lic.hostnibo.com',
    'api_key' => 'elms_pk_xxx',
    'secret'  => 'elms_sk_xxx',
    'product' => 'WHMCS-OTP',
]);

$result = $elms->verify('XXXX-XXXX-XXXX-XXXX', 'example.com');
if ($result['status']) {
    // license valid -> start the software
} else {
    // invalid -> show notice
}

// activate / deactivate
$elms->activate('XXXX-XXXX-XXXX-XXXX', 'example.com');
$elms->deactivate('XXXX-XXXX-XXXX-XXXX', 'example.com');

// check for updates
$update = $elms->checkUpdate('1.0.0');
```

SDK features: HMAC request signing, response signature verification, local
caching (software keeps working during outages), SSL verification.

---

## 8. Install the Laravel package

Folder: `sdk/laravel/`

```bash
composer require elms/laravel-license   # from a local/VCS repo
php artisan vendor:publish --tag=elms-config
```

Config in `.env`:
```env
ELMS_SERVER=https://lic.hostnibo.com
ELMS_API_KEY=elms_pk_xxx
ELMS_API_SECRET=elms_sk_xxx
ELMS_PRODUCT=WHMCS-OTP
ELMS_CACHE_TTL=43200
```

Usage in code:
```php
if (app('elms.license')->isValid($licenseKey)) {
    // valid
}
```

---

## 9. Troubleshooting

| Problem | Solution |
|---------|----------|
| API returns `Untrusted server response` | (a) check the client `api_secret` matches the `secret_key` of that key in ELMS, (b) check a CDN/proxy is not altering the body |
| `X-Signature` is wrong | verify `sha256(body)` is computed correctly and the timestamp is within ±300s |
| Admin login does not open | check `APP_URL` is correct and `.env` is loaded |
| DB error | run `php scripts/install.php` to create the database + schema |

---

## 10. Support / Reference

- Admin: `https://lic.hostnibo.com/admin`
- Health: `https://lic.hostnibo.com/health`
- API key/secret: Admin → API Keys
- CLI: `php scripts/install.php`, `php scripts/backup.php --retain-days=14`
