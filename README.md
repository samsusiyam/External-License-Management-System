# External License Management System (ELMS)

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B%20%2F%20MariaDB-orange.svg)](https://mysql.com)
[![Security](https://img.shields.io/badge/API%20Auth-HMAC--SHA256%20Signed-green.svg)](https://github.com/samsusiyam/External-License-Management-System)
[![WHMCS Ready](https://img.shields.io/badge/WHMCS-Provisioning%20Server%20Module-blueviolet.svg)](https://whmcs.com)

A production-grade, centralized software license management platform for **WHMCS Provisioning, WordPress plugins, PHP scripts, Laravel applications**, and commercial software.

Licenses are issued, verified, activated, domain/IP locked, and managed from a high-performance license server featuring a **Bootstrap 5 Admin Panel** and **HMAC-SHA256 Signed REST API**.

```text
┌─────────────────────────────────────────────────────────────┐
│                     WHMCS / Client Apps                     │
│  (WHMCS Module, WordPress Plugin, Laravel App, PHP SDK)     │
└──────────────────────────────┬──────────────────────────────┘
                               │ HMAC-SHA256 Signed REST API
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                 ELMS License Server Engine                  │
│       (/api/license/*, Verification, Domain/IP Binding)     │
├──────────────────────────────┬──────────────────────────────┤
│    Modern Web Installer      │   Bootstrap 5 Admin Panel    │
│          (/install)          │          (/admin)            │
└──────────────────────────────┴──────────────────────────────┘
```

---

## 🚀 Key Features

* **Full License Lifecycle:** Issue, verify, activate, deactivate, renew, reset bindings, suspend, unsuspend, terminate, and delete licenses.
* **Domain, IP & Activation Limits:** Enforce strict domain name binding, server IP restrictions, and maximum simultaneous activations.
* **Signed REST API:** All requests and responses are signed with `HMAC-SHA256` and protected with timestamp replay protection (±300s window).
* **Interactive Web Installer (`/install`):** 3-step browser installation wizard with server requirements check, live database connection testing, automatic schema execution, and secure `.env` generator.
* **Modern Admin Console:** Fast Bootstrap 5 dashboard with dark/light themes, product manager, API key manager, detailed audit trail, and API request logs.
* **WHMCS Provisioning Server Module:** Complete cPanel-style server module for WHMCS to automate license creation on order payment, suspension on overdue, unsuspension, termination, and client area management.
* **Client SDKs Included:** Native SDKs for standalone PHP scripts, WordPress plugins, and Laravel applications.
* **Automated Daily Backups:** Bundled CLI database backup utility with automatic retention pruning.

---

## 📋 System Requirements

* **PHP:** 8.1+ (PHP 8.2 & 8.3 fully supported)
* **PHP Extensions:** `pdo`, `pdo_mysql`, `curl`, `openssl`, `mbstring`, `json`, `filter`
* **Database:** MySQL 5.7+ or MariaDB 10.3+
* **Web Server:** Apache (with `mod_rewrite` enabled), Nginx, Litespeed, or cPanel/caching VPS

---

## 📂 Directory Layout

```text
├── app/                  # Core MVC framework, controllers, models, services, middleware, views
│   ├── Controllers/      # Admin, API, and Web Installer controllers
│   ├── Core/             # Config, Router, Request, Response, Database, View engines
│   └── Views/            # Blade-style PHP views (Dashboard, Licenses, Products, Installer, etc.)
├── config/               # Application configuration and route definitions
├── database/             # schema.sql and seed.sql
├── docs/                 # Detailed architecture and WHMCS integration guides
├── public/               # Web root (front controller index.php, CSS, JS, assets)
├── scripts/              # CLI tools: install.php, backup.php, smoke_test.php
├── sdk/                  # Client SDKs (php/, wordpress/, laravel/)
├── storage/              # Logs, backups, and installed.lock
├── tests/                # Automated unit and integration test suite
└── whmcs/                # WHMCS Provisioning Server Module & Client Area Hooks
    ├── includes/hooks/   # WHMCS Client Area & theme sync hooks
    └── modules/servers/  # elms_license Provisioning Server Module
```

---

## 🛠️ Installation

### Option 1: Modern Web Installer (Recommended)

1. Clone or upload the repository to your server/hosting:
   ```bash
   git clone https://github.com/samsusiyam/External-License-Management-System.git
   ```
2. Point your web server / domain document root to the `public/` directory.
3. Open your browser and navigate to:
   ```text
   http://your-domain.com/install
   ```
4. Follow the interactive 3-step wizard:
   * **Step 1: System Requirements & Permissions Check** (Verifies PHP extensions and folder permissions).
   * **Step 2: Database Configuration** (Enter DB host, port, name, user, password and click **Test Connection**).
   * **Step 3: Administrator & Site Setup** (Set App URL, Admin Name, Username, Email, and Password).
5. The installer will automatically migrate the database, generate `.env`, lock the installer, and present your generated administrator and API credentials.

---

### Option 2: CLI Installer

1. Copy the environment template:
   ```bash
   cp .env.example .env
   ```
   Configure `DB_*`, `APP_URL`, and a secure `APP_KEY` in `.env`.

2. Run the command-line installer:
   ```bash
   php scripts/install.php --fresh --admin-user=admin --admin-pass="YourSecurePassword"
   ```

3. Start local development server (optional):
   ```bash
   php -S 127.0.0.1:8080 -t public
   ```
   Admin panel will be accessible at `http://127.0.0.1:8080/admin`.

---

## ⚡ WHMCS Integration Guide

ELMS includes a complete **Provisioning Server Module** for WHMCS that automates license issuance, renewals, suspensions, terminations, and client area management.

### 1. Upload Module Files to WHMCS

Copy the contents of the `whmcs/` folder into your WHMCS root directory:

```text
whmcs/modules/servers/elms_license/  -->  <WHMCS_ROOT>/modules/servers/elms_license/
whmcs/includes/hooks/elms_license.php --> <WHMCS_ROOT>/includes/hooks/elms_license.php
```

---

### 2. Configure the License Server in WHMCS

1. In WHMCS Admin, navigate to **Configuration > System Settings > Servers** (or *Setup > Products/Services > Servers* in older WHMCS).
2. Click **Add New Server**:
   * **Name:** `ELMS License Server`
   * **Hostname / IP Address:** `https://license.yourdomain.com` (Your ELMS URL without trailing slash)
   * **Server Type / Module:** Select **`ELMS License Server`**
   * **Username / Access Key:** Enter your ELMS **Public API Key** (`elms_pk_...`)
   * **Password / Hash:** Enter your ELMS **API Secret Key** (`elms_sk_...`)
   * **Secure:** Check `Tick to use SSL Mode` if using HTTPS.
3. Click **Save Changes** & **Test Connection**.

---

### 3. Create a License Product in WHMCS

1. Go to **System Settings > Products/Services > Products/Services** and create/edit a product.
2. Under the **Module Settings** tab:
   * **Module Name:** Select **`ELMS License Server`**
   * **Server Group:** Select the Server or Group created in Step 2.
   * **Product Selection:** Select your product from the dynamic dropdown (automatically fetched from your ELMS server) or enter your **Product Key** (e.g. `WHMCS-OTP`).
   * **Default Status:** `Active`
   * **Max Allowed Activations:** e.g. `1` (or leave empty for unlimited)
   * **Enforce Domain Lock:** Yes / No
   * **Enforce IP Lock:** Yes / No
3. Set **Automatically setup the product as soon as the first payment is received**.

---

### 4. Automated WHMCS Lifecycle Actions

Once configured, WHMCS will automatically communicate with ELMS:

| WHMCS Trigger Event | Action Performed in ELMS |
| :--- | :--- |
| **Order Paid / Accepted (`Create`)** | Issues a new unique License Key (`XXXX-XXXX-XXXX-XXXX`), links it to the client domain/service, and sends the delivery email. |
| **Invoice Overdue / Unpaid (`Suspend`)** | Suspends the license on ELMS; client applications receive `License Suspended`. |
| **Invoice Paid (`Unsuspend`)** | Reactivates the license automatically. |
| **Service Cancelled / Refunded (`Terminate`)** | Permanently terminates the license on ELMS. |
| **Client Area Management** | Clients can view their license key with 1-click copy, see allowed domains/IPs, activation counts, and self-service reset domain bindings. |

---

## 🔒 Signed REST API Reference

Base Endpoint: `/api`  
All endpoints require `POST` requests with JSON payload and the following authentication headers:

| Header | Description |
| :--- | :--- |
| `X-Api-Key` | Your ELMS Public API Key (`elms_pk_...`) |
| `X-Timestamp` | Current Unix timestamp in seconds (must be within ±300s window) |
| `X-Signature` | `HMAC_SHA256(timestamp + "." + api_key + "." + sha256(raw_json_body), api_secret)` |

### API Endpoints

| Endpoint | Description |
| :--- | :--- |
| `POST /api/license/verify` | Verify license validity without consuming an activation slot. |
| `POST /api/license/activate` | Activate a license for a specific domain/IP (increments activation count). |
| `POST /api/license/deactivate` | Deactivate a license and release the activation slot. |
| `POST /api/license/create` | Issue a new license with custom constraints and expiry date. |
| `POST /api/license/renew` | Extend license validity / expiry date. |
| `POST /api/license/reset` | Clear domain, IP, and activation hardware bindings. |
| `POST /api/license/suspend` | Suspend license status. |
| `POST /api/license/unsuspend` | Restore suspended license to active status. |
| `POST /api/license/terminate` | Permanently terminate a license. |
| `POST /api/updates/check` | Check for software updates and retrieve latest version release info. |
| `GET /api/products` | Retrieve active products list for WHMCS / external integration dropdowns. |

---

## 💻 Client SDKs

### PHP Applications
```php
require_once 'sdk/php/license.php';

$elms = new ElmsLicense([
    'server'     => 'https://license.yourdomain.com',
    'api_key'    => 'elms_pk_...',
    'secret'     => 'elms_sk_...',
    'product'    => 'YOUR-PRODUCT-KEY',
]);

$check = $elms->verify('XXXX-XXXX-XXXX-XXXX', 'clientdomain.com');
if ($check['status']) {
    // License is valid!
} else {
    die('License Error: ' . $check['message']);
}
```

### WordPress Plugins
Copy `sdk/wordpress/` to `wp-content/plugins/elms-license-client/` and activate it. Configure your server URL and Product Key under **Settings > ELMS License**.

### Laravel Applications
```bash
composer require elms/laravel-license
php artisan vendor:publish --tag=elms-config
```
```php
use Elms\License\Facades\License;

if (!License::isValid($licenseKey)) {
    abort(403, 'Invalid or expired license.');
}
```

---

## 💾 Automated Database Backups

ELMS includes a secure database backup utility:

```bash
php scripts/backup.php --retain-days=14
```

Add to your server's cron job for automatic daily backups at midnight:
```cron
0 0 * * * /usr/bin/php /path/to/license/scripts/backup.php --retain-days=14 >/dev/null 2>&1
```
Backups are compressed and stored securely in `storage/backups/`.

---

## 🛡️ Security Architecture

* **Database Security:** Strictly uses PDO parameterized prepared statements against SQL injection.
* **Environment Protection:** `.env` and `storage/installed.lock` are strictly gitignored and protected by `.htaccess`.
* **HMAC Request Signing:** Prevents request spoofing and man-in-the-middle (MITM) tampering.
* **Brute-Force & Rate Limiting:** Sliding window rate limiting applied per API key and per IP address.
* **Bcrypt Password Hashing:** Admin passwords use standard cost-factored bcrypt hashing.

---

## 📄 License & Support

Proprietary Software. Developed for professional software authors and hosting providers.  
For documentation and support, refer to the [`docs/`](docs/) directory.
