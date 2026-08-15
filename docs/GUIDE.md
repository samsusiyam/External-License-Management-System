# ELMS Documentation (গাইডলাইন)

**External License Management System (ELMS)** — কেন্দ্রীয় লাইসেন্স ম্যানেজমেন্ট প্ল্যাটফর্ম। এই ডকুমেন্টেশনে থাকছে:

1. লাইভ API URL গুলো
2. API কিভাবে কল করবেন (authentication + signature)
3. ELMS সার্ভার নিজে হোস্ট করার স্টেপ
4. WHMCS / WordPress / PHP / Laravel-এ কিভাবে ইন্সটল করবেন

---

## ১. লাইভ API URL

| পরিবেশ | বেস URL |
|--------|---------|
| **Production (লাইভ)** | `https://lic.hostnibo.com` |
| Admin Panel | `https://lic.hostnibo.com/admin` |
| API Base | `https://lic.hostnibo.com/api` |
| Health Check | `https://lic.hostnibo.com/health` |

> নিচের সব API endpoint-এর আগে `https://lic.hostnibo.com` বসবে। যেমন verify করতে: `https://lic.hostnibo.com/api/license/verify`

---

## ২. API Authentication

সব `/api/*` endpoint **POST** method এবং নিচের ৩টি header ছাড়া কাজ করবে না:

| Header | মান |
|--------|-----|
| `X-Api-Key` | আপনার public API key (যেমন `elms_pk_xxx`) |
| `X-Timestamp` | বর্তমান unix time (সেকেন্ড); সার্ভারের সাথে ±300s (৫ মিনিট) এর মধ্যে হতে হবে |
| `X-Signature` | নিচের নিয়মে তৈরি HMAC-SHA256 সিগনেচার |

**Signature তৈরির নিয়ম:**

```
base_string = {timestamp} + "." + {api_key} + "." + sha256({raw_json_body})
signature   = HMAC_SHA256(base_string, {api_secret})
```

সহজ কথায়: আপনার JSON body-এর sha256 নিন, সেটাকে `timestamp.api_key.sha256body` ফরম্যাটে জোড়া দিয়ে secret দিয়ে HMAC করুন।

**Response Signature:** সার্ভারও রেস্পন্সে `X-Signature` + `X-Timestamp` হেডার পাঠায়। ক্লায়েন্ট (SDK) এটা ভেরিফাই করে — তাই MITM attack-এ ভুয়া "valid" রেস্পন্স বানানো যায় না। সব রেডি SDK-এ এই চেক বিল্ট-ইন আছে।

---

## ৩. API Endpoints

সব endpoint POST। Request body = JSON। Response envelope:

```json
{ "status": true, "message": "License Valid", "data": { } }
```

| Endpoint | কাজ |
|----------|-----|
| `/api/license/create` | নতুন লাইসেন্স জেনারেট (প্যারাম: `product_id`, `customer_name`, `customer_email`, `activation_limit`, `expiry_date`) |
| `/api/license/verify` | লাইসেন্স চেক (প্যারাম: `license_key`, `domain`, `ip`, `product`) |
| `/api/license/activate` | অ্যাক্টিভেশন রেজিস্টার (প্যারাম: `license_key`, `domain`, `ip`, `product`, `server_hostname`, `install_path`) |
| `/api/license/deactivate` | অ্যাক্টিভেশন ফ্রি করুন |
| `/api/license/renew` | এক্সপায়ারি বাড়ান (`expiry_date`) |
| `/api/license/reset` | ডোমেইন/IP/অ্যাক্টিভেশন বাইন্ডিং ক্লিয়ার |
| `/api/license/suspend` | স্ট্যাটাস = suspended |
| `/api/license/unsuspend` | স্ট্যাটাস = active |
| `/api/license/terminate` | স্ট্যাটাস = terminated |
| `/api/updates/check` | লেটেস্ট ভার্সন + ডাউনলোড URL (`product`, `current_version`, `license_key`) |

**Verify চেকের অর্ডার:** লাইসেন্স আছে? → প্রোডাক্ট মিলছে? → suspend/terminate নয়? → এক্সপায়ার হয়নি? → ডোমেইন লক → IP লক। Activate করলে অ্যাক্টিভেশন লিমিটও চেক হয়।

### curl দিয়ে উদাহরণ (verify)

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

**API Key কোথায় পাবেন:** Admin Panel → `API Keys` → নতুন key বানিয়ে `api_key` ও `secret_key` কপি করুন।

---

## ৪. ELMS সার্ভার ইন্সটল (নিজে হোস্ট করলে)

প্রয়োজন: **PHP 8.1+** (৮.২ রিকমেন্ডেড), **MySQL/MariaDB**, **Apache** (mod_rewrite) বা PHP built-in server।

1. **রেপো ক্লোন / ফাইল আপলোড:**
   ```bash
   git clone <repo> license
   cd license
   ```

2. **.env তৈরি করুন:**
   ```bash
   cp .env.example .env
   ```
   এডিট করে সেট করুন: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `APP_URL` (আপনার ডোমেইন), আর একটি লম্বা র্যান্ডম `APP_KEY`।

3. **ইন্সটলার চালান** (ডাটাবেস বানায়, স্কিমা লোড করে, admin + API key সিড করে):
   ```bash
   php scripts/install.php --fresh --admin-user=admin --admin-pass="ChangeMe123"
   ```
   আউটপুটে admin password + API key/secret দেখাবে — সেগুলো সেভ করুন।

4. **ওয়েব রুট `public/` দিকে পয়েন্ট করুন।** Apache-এ ডকুমেন্ট রুট `public/` হতে হবে (সাব-ডিরেক্টরিতেও অ্যাপ নিজে base path ডিটেক্ট করে নেয়)।

5. **লোকাল ডেভ সার্ভার:**
   ```bash
   php -S 127.0.0.1:8080 -t public
   ```
   তারপর `http://127.0.0.1:8080/admin`।

> **Security:** সিড করা admin password সাথে সাথে বদলান, `.env` পাবলিক না রাখুন, প্রোডাকশনে HTTPS ব্যবহার করুন।

---

## ৫. WHMCS Module ইন্সটল

ফোল্ডার: `whmcs/modules/addons/external_license_manager/`

1. ফোল্ডারটি কপি করুন:
   ```
   whmcs/modules/addons/external_license_manager/
   ```
   → আপনার WHMCS-এর `modules/addons/external_license_manager/` দিকে।

2. WHMCS admin-এ যান: **Setup → Addon Modules** → "External License Manager" → **Activate**।

3. মডিউল কনফিগার করুন:
   - **License Server URL** = `https://lic.hostnibo.com`
   - **API Key** = ELMS থেকে নেওয়া `api_key`
   - **API Secret** = ELMS থেকে নেওয়া `secret_key`
   - **Default ELMS Product ID** = ELMS-এ বানানো প্রোডাক্টের ID

4. **Lifecycle hooks (অটোমেটিক):**
   - `AfterModuleCreate` → লাইসেন্স তৈরি
   - `AfterModuleSuspend` → suspend
   - `AfterModuleUnsuspend` → unsuspend
   - `AfterModuleTerminate` → terminate

মডিউলটি HMAC সিগনেচার + রেস্পন্স সিগনেচার ভেরিফিকেশন বিল্ট-ইন নিয়ে আসে, তাই আলাদা কনফিগ লাগে না।

---

## ৬. WordPress Plugin ইন্সটল

ফোল্ডার: `sdk/wordpress/`

1. ফোল্ডারটি কপি করুন `sdk/wordpress/` → `wp-content/plugins/elms-license-client/`।

2. WP admin → **Plugins** → "ELMS License Client" → **Activate**।

3. **Settings → ELMS License**-এ কনফিগ করুন (অথবা `wp-config.php` এ কনস্ট্যান্ট):
   ```php
   define('ELMS_SERVER',   'https://lic.hostnibo.com');
   define('ELMS_API_KEY',  'elms_pk_xxx');
   define('ELMS_API_SECRET','elms_sk_xxx');
   define('ELMS_PRODUCT',  'WHMCS-OTP');
   ```
   লাইসেন্স কী সেট করুন: Settings-এ `elms_license_key` অপশনে।

4. কোডে চেক:
   ```php
   if (ELMS_License_Client::isValid()) {
       // লাইসেন্স ভেলিড
   }
   ```
   API secret `wp_options`-এ AES-256-CBC দিয়ে এনক্রিপ্ট করে সেভ হয়, আর ভেরিফাই রেজাল্ট ১০ মিনিট ক্যাশ হয়।

---

## ৭. PHP SDK ইন্সটল

ফাইল: `sdk/php/license.php`

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
    // লাইসেন্স ভেলিড → সফটওয়্যার চালু করুন
} else {
    // ইনভ্যালিড → নোটিশ দেখান
}

// অ্যাক্টিভেট / ডি-অ্যাক্টিভেট
$elms->activate('XXXX-XXXX-XXXX-XXXX', 'example.com');
$elms->deactivate('XXXX-XXXX-XXXX-XXXX', 'example.com');

// আপডেট চেক
$update = $elms->checkUpdate('1.0.0');
```

SDK ফিচার: HMAC রিকোয়েস্ট সিগনেচার, রেস্পন্স সিগনেচার ভেরিফাই, লোকাল ক্যাশ (আউটেজেও সফটওয়্যার চলবে), SSL ভেরিফাই।

---

## ৮. Laravel Package ইন্সটল

ফোল্ডার: `sdk/laravel/`

```bash
composer require elms/laravel-license   # লোকাল/VCS রিপো থেকে
php artisan vendor:publish --tag=elms-config
```

`.env` এ কনফিগ:
```env
ELMS_SERVER=https://lic.hostnibo.com
ELMS_API_KEY=elms_pk_xxx
ELMS_API_SECRET=elms_sk_xxx
ELMS_PRODUCT=WHMCS-OTP
ELMS_CACHE_TTL=43200
```

কোডে ব্যবহার:
```php
if (app('elms.license')->isValid($licenseKey)) {
    // ভেলিড
}
```

---

## ৯. ট্রাবলশুটিং

| সমস্যা | সমাধান |
|--------|--------|
| API থেকে `Untrusted server response` | (a) client-এর `api_secret` সার্ভারের সেই key-এর `secret_key`-এর সাথে মিলছে কিনা চেক করুন, (b) CDN/proxy বডি বদলাচ্ছে কিনা দেখুন |
| `X-Signature` ভুল | `sha256(body)` ঠিক মতো হিসাব করা হয়েছে কিনা এবং timestamp ±300s-এর মধ্যে কিনা দেখুন |
| Admin login ওপেন হয় না | `APP_URL` ঠিক আছে কিনা এবং `.env` লোড হচ্ছে কিনা চেক করুন |
| DB error | `php scripts/install.php` চালিয়ে ডাটাবেস + স্কিমা তৈরি করুন |

---

## ১০. সাপোর্ট / রেফারেন্স

- Admin: `https://lic.hostnibo.com/admin`
- Health: `https://lic.hostnibo.com/health`
- API key/secret: Admin → API Keys
- CLI: `php scripts/install.php`, `php scripts/backup.php --retain-days=14`
