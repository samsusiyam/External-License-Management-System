# Host Nibo ELMS Licensing System — সম্পূর্ণ ইন্টিগ্রেশন গাইড

এই গাইডটিতে হোস্ট নিবো এক্সটার্নাল লাইসেন্স ম্যানেজমেন্ট সিস্টেম (ELMS)-এর ক্লায়েন্ট ইঞ্জিন আপনার WHMCS অ্যাডন মডিউলে কীভাবে যুক্ত করবেন, তার পূর্ণাঙ্গ ধাপগুলো ব্যাখ্যা করা হয়েছে।

---

## 📁 ১. ফোল্ডার স্ট্রাকচার (Directory Structure)

আপনার মডিউলের নাম যদি হয় `my_module`, তাহলে ফোল্ডার স্ট্রাকচার হবে এরকম:

```text
modules/addons/my_module/
├── app/
│   └── License/
│       └── LicenseManager.php    <-- (১) লাইসেন্স ক্লায়েন্ট ইঞ্জিন
├── admin/
│   └── license.php               <-- (২) অ্যাডমিন লাইসেন্স UI ভিউ
├── storage/
│   └── license/
│       └── .gitignore            <-- (৩) অফলাইন ক্যাশ ফোল্ডার
└── my_module.php                 <-- মূল মডিউল ফাইল (Gatekeeper)
```

---

## 📄 ২. ফাইল ১: `app/License/LicenseManager.php`

**কাজ:** লাইসেন্স সার্ভার (`https://lic.hostnibo.com`)-এর সাথে API কানেকশন, ডোমেন/আইপি ডিটেকশন, ভেরিফিকেশন, অ্যাক্টিভেশন এবং ১৫ মিনিটের অটোমেটিক অফলাইন ক্যাশ ম্যানেজ করা।

```php
<?php
/**
 * Host Nibo ELMS License Client Engine
 * 
 * Website: https://hostnibo.com
 * Support: https://hostnibo.com/contact
 * License Server: https://lic.hostnibo.com
 */
namespace MyModule\License; // <-- আপনার মডিউলের Namespace দিন

use WHMCS\Database\Capsule;

class LicenseManager
{
    // ১. আপনার লাইসেন্স সার্ভার URL
    public const DEFAULT_SERVER_URL  = 'https://lic.hostnibo.com';

    // ২. ELMS সার্ভারে তৈরি করা আপনার প্রোডাক্ট কোড (Product Key)
    public const DEFAULT_PRODUCT_KEY = 'YOUR-PRODUCT-KEY'; // <-- এখানে Product Key বসান (যেমন: ADVANCED-INVOICE)

    // ৩. আপনার মডিউলের ফোল্ডার নাম (WHMCS Addon Directory Name)
    public const MODULE_NAME         = 'my_module'; // <-- এখানে মডিউলের ফোল্ডার নাম দিন

    // ৪. অফলাইন ক্যাশ মেয়াদ (১৫ মিনিট = ৯০০ সেকেন্ড)
    public const CACHE_TTL_SECONDS   = 900;

    private static ?self $instance = null;
    private string $serverUrl;
    private string $productKey;
    private string $cacheDir;

    public function __construct()
    {
        $this->serverUrl  = self::DEFAULT_SERVER_URL;
        $this->productKey = self::DEFAULT_PRODUCT_KEY;
        $this->cacheDir   = dirname(__DIR__, 2) . '/storage/license';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * গেটকিপার চেক: লাইসেন্স একটিভ কিনা তা চেক করে true/false রিটার্ন করে
     */
    public static function isLicensed(?bool $forceRemote = null): bool
    {
        if ($forceRemote === null) {
            $isAdmin = defined('ADMINAREA') && ADMINAREA;
            $forceRemote = $isAdmin;
        }
        return self::getInstance()->checkLicenseValid($forceRemote);
    }

    /**
     * WHMCS ডাটাবেস থেকে সংরক্ষিত লাইসেন্স কি নিয়ে আসা
     */
    public function getLicenseKey(): string
    {
        try {
            $key = Capsule::table('tbladdonmodules')
                ->where('module', self::MODULE_NAME)
                ->where('setting', 'license_key')
                ->value('value');
            return trim((string)$key);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * WHMCS ডাটাবেসে নতুন লাইসেন্স কি সেভ করা
     */
    public function saveLicenseKey(string $key): void
    {
        $key = trim($key);
        try {
            Capsule::table('tbladdonmodules')->updateOrInsert(
                ['module' => self::MODULE_NAME, 'setting' => 'license_key'],
                ['value' => $key]
            );
        } catch (\Throwable $e) {}
    }

    /**
     * সার্ভার ডোমেন বের করা (Clean Domain)
     */
    public function getDomain(): string
    {
        $domain = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
        $domain = preg_replace('/:\d+$/', '', (string)$domain);
        $domain = strtolower(trim($domain));
        return preg_replace('/^www\./', '', $domain) ?: 'localhost';
    }

    /**
     * সার্ভার আইপি বের করা
     */
    public function getIp(): string
    {
        $ip = $_SERVER['SERVER_ADDR'] ?? ($_SERVER['LOCAL_ADDR'] ?? '');
        if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
            $ip = gethostbyname(gethostname()) ?: '127.0.0.1';
        }
        return trim($ip);
    }

    public function checkLicenseValid(bool $forceRemote = true): bool
    {
        $key = $this->getLicenseKey();
        if (empty($key)) {
            return false;
        }
        if ($forceRemote) {
            $res = $this->verify(true);
            return !empty($res['status']);
        }
        $cached = $this->readCache($key, $this->getDomain());
        if ($cached !== null) {
            return !empty($cached['status']);
        }
        $res = $this->verify(true);
        return !empty($res['status']);
    }

    /**
     * লাইসেন্স সার্ভারে ভেরিফিকেশন কল পাঠানো
     */
    public function verify(bool $forceRemote = true): array
    {
        $key    = $this->getLicenseKey();
        $domain = $this->getDomain();
        $ip     = $this->getIp();

        if (empty($key)) {
            return ['status' => false, 'message' => 'No license key entered.', 'data' => []];
        }

        if (!$forceRemote) {
            $cached = $this->readCache($key, $domain);
            if ($cached !== null) {
                return $cached;
            }
        }

        try {
            $res = $this->post('/api/license/verify', [
                'license_key' => $key,
                'domain'      => $domain,
                'ip'          => $ip,
                'product'     => $this->productKey,
            ]);

            if (!empty($res['status'])) {
                $this->writeCache($key, $domain, $ip, $res);
            } else {
                $this->clearCache($key, $domain);
            }
            return $res;
        } catch (\Throwable $e) {
            $cached = $this->readCache($key, $domain);
            if ($cached !== null) {
                return $cached;
            }
            return ['status' => false, 'message' => $e->getMessage(), 'data' => []];
        }
    }

    /**
     * লাইসেন্স অ্যাক্টিভেট করা
     */
    public function activate(string $newKey): array
    {
        $newKey = trim($newKey);
        if ($newKey !== '') {
            $this->saveLicenseKey($newKey);
        }

        $domain = $this->getDomain();
        $ip     = $this->getIp();

        try {
            $res = $this->post('/api/license/activate', [
                'license_key'     => $newKey,
                'domain'          => $domain,
                'ip'              => $ip,
                'product'         => $this->productKey,
                'server_hostname' => gethostname() ?: 'unknown',
            ]);

            if (!empty($res['status']) || ($res['message'] ?? '') === 'Already activated') {
                $this->writeCache($newKey, $domain, $ip, $res);
                return ['status' => true, 'message' => 'License activated successfully!'];
            }
            $this->clearCache($newKey, $domain);
            return $res;
        } catch (\Throwable $e) {
            return ['status' => false, 'message' => 'Activation failed: ' . $e->getMessage()];
        }
    }

    /**
     * UI-তে দেখানোর জন্য বিস্তারিত লাইসেন্স ইনফরমেশন রিটার্ন করে
     */
    public function getDetails(bool $refreshLive = false): array
    {
        $key = $this->getLicenseKey();
        $details = [
            'status'       => 'unlicensed',
            'expiry'       => 'Lifetime',
            'product_name' => 'Host Nibo Product',
            'product_key'  => $this->productKey,
        ];

        if (!empty($key)) {
            $check = $refreshLive ? $this->verify(true) : ($this->readCache($key, $this->getDomain()) ?? $this->verify(true));
            if (!empty($check['status'])) {
                $details['status']       = 'active';
                $details['expiry']       = $check['data']['expiry'] ?? ($check['data']['expires_at'] ?? 'Lifetime');
                $details['product_name'] = $check['data']['product_name'] ?? ($check['data']['product'] ?? $this->productKey);
                $details['product_key']  = $check['data']['product_key'] ?? $this->productKey;
            } else {
                $msg = strtolower($check['message'] ?? '');
                if (strpos($msg, 'suspend') !== false) {
                    $details['status'] = 'suspended';
                } elseif (strpos($msg, 'terminate') !== false) {
                    $details['status'] = 'terminated';
                } elseif (strpos($msg, 'domain') !== false) {
                    $details['status'] = 'domain_mismatch';
                } elseif (strpos($msg, 'expired') !== false || strpos($msg, 'expire') !== false) {
                    $details['status'] = 'expired';
                } elseif (strpos($msg, 'cancel') !== false) {
                    $details['status'] = 'cancelled';
                } elseif (strpos($msg, 'pending') !== false) {
                    $details['status'] = 'pending';
                } elseif (strpos($msg, 'mismatch') !== false || strpos($msg, 'product') !== false) {
                    $details['status'] = 'product_mismatch';
                } else {
                    $details['status'] = 'invalid';
                }
            }
        }

        $masked = !empty($key) && strlen($key) >= 8 
            ? substr($key, 0, 4) . '-****-****-' . substr($key, -4) 
            : (!empty($key) ? '****' : 'None');

        return [
            'license_key'  => $key,
            'masked_key'   => $masked,
            'status'       => $details['status'],
            'is_licensed'  => ($details['status'] === 'active'),
            'expiry_date'  => $details['expiry'],
            'domain'       => $this->getDomain(),
            'ip'           => $this->getIp(),
            'product_name' => $details['product_name'],
            'product_key'  => $details['product_key'],
            'server_url'   => $this->serverUrl,
        ];
    }

    private function post(string $path, array $payload): array
    {
        $body = json_encode($payload);
        $ch = curl_init($this->serverUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
        ]);
        $resp = curl_exec($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($resp === false) {
            return ['status' => false, 'message' => 'Connection to license server failed: ' . $curlErr];
        }

        $decoded = json_decode((string)$resp, true);
        return is_array($decoded) ? $decoded : ['status' => false, 'message' => 'Invalid response from license server'];
    }

    private function getCacheFile(string $key, string $domain): string
    {
        return $this->cacheDir . '/lic_' . substr(hash('sha256', $key . '|' . $this->productKey . '|' . $domain), 0, 32) . '.json';
    }

    private function writeCache(string $key, string $domain, string $ip, array $payload): void
    {
        @file_put_contents(
            $this->getCacheFile($key, $domain),
            json_encode(['ts' => time(), 'domain' => $domain, 'payload' => $payload])
        );
    }

    private function readCache(string $key, string $domain): ?array
    {
        $file = $this->getCacheFile($key, $domain);
        if (!is_file($file)) return null;
        $data = json_decode(@file_get_contents($file) ?: '', true);
        if (!is_array($data) || (time() - (int)($data['ts'] ?? 0)) > self::CACHE_TTL_SECONDS) return null;
        if (strtolower($data['domain'] ?? '') !== strtolower($domain)) return null;
        return $data['payload'] ?? null;
    }

    private function clearCache(string $key, string $domain): void
    {
        @unlink($this->getCacheFile($key, $domain));
    }
}
```

---

## 📄 ৩. ফাইল ২: `admin/license.php`

**কাজ:** অ্যাডমিন প্যানেলে লাইসেন্স অ্যাক্টিভেশন পেজ, লাইসেন্স স্ট্যাটাস কার্ড (Active, Expired, Suspended, Domain Mismatch, ইত্যাদি), Re-verify বাটন এবং সাপোর্ট লিঙ্ক রেন্ডার করা।

```php
<?php
/**
 * Host Nibo ELMS - Admin License View
 *
 * Developed by Host Nibo
 * Website: https://hostnibo.com
 * Support: https://hostnibo.com/contact
 */
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use MyModule\License\LicenseManager; // <-- আপনার Namespace দিন

$licenseManager = LicenseManager::getInstance();
$successMsg     = '';
$errorMsg       = '';

// হ্যান্ডেল অ্যাকশন (Activate / Re-verify)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['activate_license'])) {
        $inputKey = trim($_POST['license_key'] ?? '');
        $res = $licenseManager->activate($inputKey);
        if (!empty($res['status'])) {
            $successMsg = $res['message'] ?? 'License activated successfully!';
        } else {
            $errorMsg = $res['message'] ?? 'Activation failed. Please check your license key.';
        }
    } elseif (isset($_POST['reverify_license'])) {
        $res = $licenseManager->verify(true);
        if (!empty($res['status'])) {
            $successMsg = 'License verified! Your license is active and valid.';
        } else {
            $errorMsg = 'License check failed: ' . ($res['message'] ?? 'Invalid license status');
        }
    }
}

$details = $licenseManager->getDetails(true);
$status  = strtolower($details['status']);

// লোগো রেজোলিউশন
$logoFile = dirname(__DIR__) . '/logo.png';
$logoSrc  = file_exists($logoFile) ? ('data:image/png;base64,' . base64_encode(file_get_contents($logoFile))) : '../modules/addons/' . LicenseManager::MODULE_NAME . '/logo.png';
$moduleUrl = 'addonmodules.php?module=' . LicenseManager::MODULE_NAME;
?>
<style>
    .hn-lic-container {
        max-width: 960px;
        margin: 20px auto 40px auto;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        color: #1e293b;
    }
    .hn-lic-container * { box-sizing: border-box; }
    .hn-lic-header {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        padding: 22px 26px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04);
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
    }
    .hn-lic-header-left { display: flex; align-items: center; gap: 18px; }
    .hn-lic-logo { max-height: 48px; width: auto; object-fit: contain; }
    .hn-lic-title h2 { font-size: 20px; font-weight: 700; margin: 0 0 4px 0; color: #0f172a; display: flex; align-items: center; gap: 8px; }
    .hn-lic-title p { margin: 0; color: #64748b; font-size: 13.5px; }
    .hn-lic-status-card {
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .hn-lic-active-bg { background: #f0fdf4; border: 1px solid #bbf7d0; }
    .hn-lic-inactive-bg { background: #fef2f2; border: 1px solid #fecaca; }
    .hn-lic-status-info h3 { margin: 0 0 6px 0; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    .hn-lic-meta { font-size: 13px; color: #475569; margin: 0; line-height: 1.6; }
    .hn-lic-meta strong { color: #0f172a; }
    .hn-lic-card {
        background: #ffffff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.04);
        padding: 26px;
        margin-bottom: 24px;
    }
    .hn-lic-card h3 { font-size: 16px; font-weight: 700; margin: 0 0 16px 0; color: #0f172a; display: flex; align-items: center; gap: 8px; }
    .hn-form-group { margin-bottom: 18px; }
    .hn-form-group label { display: block; font-size: 13.5px; font-weight: 600; color: #334155; margin-bottom: 8px; }
    .hn-lic-input {
        width: 100%;
        padding: 11px 16px;
        font-size: 14px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        outline: none;
        transition: all 0.2s ease;
        color: #0f172a;
        font-family: monospace;
        letter-spacing: 0.5px;
    }
    .hn-lic-input:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }
    .hn-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        font-size: 13.5px;
        font-weight: 600;
        border-radius: 8px;
        text-decoration: none !important;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 1px solid transparent;
        line-height: 1.4;
    }
    .hn-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(0,0,0,0.08); }
    .hn-btn-primary { background: #2563eb; color: #ffffff !important; border-color: #2563eb; }
    .hn-btn-primary:hover { background: #1d4ed8; }
    .hn-btn-outline { background: #ffffff; color: #334155 !important; border-color: #cbd5e1; }
    .hn-btn-outline:hover { background: #f8fafc; border-color: #94a3b8; }
    .hn-alert-msg { padding: 14px 18px; border-radius: 8px; font-size: 13.5px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    .hn-alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
    .hn-alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
    .hn-info-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 10px; padding: 18px 22px; font-size: 13px; color: #64748b; line-height: 1.6; }
</style>
<div class="hn-lic-container">
    <div class="hn-lic-header">
        <div class="hn-lic-header-left">
            <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="Host Nibo" class="hn-lic-logo">
            <div class="hn-lic-title">
                <h2><i class="fa fa-shield"></i> Module License Management</h2>
                <p>Host Nibo ELMS Licensing Protection & Activation</p>
            </div>
        </div>
        <div>
            <?php if ($details['is_licensed']): ?>
                <a href="<?php echo htmlspecialchars($moduleUrl); ?>" class="hn-btn hn-btn-primary">
                    <i class="fa fa-arrow-left"></i> Back to Dashboard
                </a>
            <?php else: ?>
                <a href="https://hostnibo.com/contact" target="_blank" class="hn-btn hn-btn-outline">
                    <i class="fa fa-life-ring"></i> Get License Support
                </a>
            <?php endif; ?>
        </div>
    </div>
    <!-- Alert Messages -->
    <?php if (!empty($successMsg)): ?>
        <div class="hn-alert-msg hn-alert-success">
            <i class="fa fa-check-circle" style="font-size: 16px;"></i>
            <div><?php echo htmlspecialchars($successMsg); ?></div>
        </div>
    <?php endif; ?>
    <?php if (!empty($errorMsg)): ?>
        <div class="hn-alert-msg hn-alert-danger">
            <i class="fa fa-times-circle" style="font-size: 16px;"></i>
            <div><?php echo htmlspecialchars($errorMsg); ?></div>
        </div>
    <?php endif; ?>
    <!-- Status Banner -->
    <div class="hn-lic-status-card <?php echo $status === 'active' ? 'hn-lic-active-bg' : 'hn-lic-inactive-bg'; ?>">
        <div class="hn-lic-status-info">
            <h3 style="color: <?php echo $status === 'active' ? '#166534' : '#991b1b'; ?>;">
                <?php if ($status === 'active'): ?>
                    <i class="fa fa-check-circle"></i> License Status: ACTIVE
                <?php elseif ($status === 'expired'): ?>
                    <i class="fa fa-calendar-times-o"></i> License Status: EXPIRED
                <?php elseif ($status === 'suspended'): ?>
                    <i class="fa fa-ban"></i> License Status: SUSPENDED
                <?php elseif ($status === 'domain_mismatch'): ?>
                    <i class="fa fa-globe"></i> License Status: DOMAIN MISMATCH
                <?php elseif ($status === 'product_mismatch'): ?>
                    <i class="fa fa-cubes"></i> License Status: PRODUCT MISMATCH
                <?php else: ?>
                    <i class="fa fa-exclamation-triangle"></i> License Status: <?php echo strtoupper(str_replace('_', ' ', $status)); ?>
                <?php endif; ?>
            </h3>
            <?php if ($status === 'expired'): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 6px 12px; border-radius: 6px; font-size: 12.5px; margin: 4px 0 8px 0; font-weight: 600;">
                    <i class="fa fa-clock-o"></i> Your license subscription has expired. Please renew your license to continue using this module.
                </div>
            <?php elseif ($status === 'suspended'): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 6px 12px; border-radius: 6px; font-size: 12.5px; margin: 4px 0 8px 0; font-weight: 600;">
                    <i class="fa fa-ban"></i> This license has been suspended. Please contact Host Nibo Support.
                </div>
            <?php endif; ?>
            <p class="hn-lic-meta">
                Product: <strong><?php echo htmlspecialchars($details['product_name']); ?></strong> (<code><?php echo htmlspecialchars($details['product_key']); ?></code>)<br>
                Domain: <strong><?php echo htmlspecialchars($details['domain']); ?></strong> &bull;
                Server IP: <strong><?php echo htmlspecialchars($details['ip']); ?></strong><br>
                Expiry Date: <strong><?php echo htmlspecialchars($details['expiry_date']); ?></strong> &bull;
                Key: <code><?php echo htmlspecialchars($details['masked_key']); ?></code>
            </p>
        </div>
        <form method="POST" style="margin: 0;">
            <button type="submit" name="reverify_license" value="1" class="hn-btn hn-btn-outline">
                <i class="fa fa-refresh"></i> Re-verify License
            </button>
        </form>
    </div>
    <!-- Activation Form Card -->
    <div class="hn-lic-card">
        <h3><i class="fa fa-key"></i> <?php echo $details['is_licensed'] ? 'Change / Update License Key' : 'Enter License Key to Activate'; ?></h3>
        <form method="POST">
            <div class="hn-form-group">
                <label for="license_key">Product License Key:</label>
                <input type="text" id="license_key" name="license_key" class="hn-lic-input" placeholder="Enter your license key..." value="<?php echo htmlspecialchars($details['license_key']); ?>" required autocomplete="off">
                <span style="font-size: 12px; color: #64748b; margin-top: 6px; display: block;">
                    Enter the license key provided upon purchase or assigned in your Host Nibo Client Portal.
                </span>
            </div>
            <button type="submit" name="activate_license" value="1" class="hn-btn hn-btn-primary">
                <i class="fa fa-lock"></i> Activate Product License
            </button>
        </form>
    </div>
    <!-- Instructions / Support Box -->
    <div class="hn-info-box">
        <h4 style="margin: 0 0 6px 0; color: #334155; font-size: 13.5px; font-weight: 700;">
            <i class="fa fa-info-circle"></i> Need Help with Licensing?
        </h4>
        <p style="margin: 0 0 8px 0;">
            Each license is uniquely bound to your domain (<strong><?php echo htmlspecialchars($details['domain']); ?></strong>) and server IP (<strong><?php echo htmlspecialchars($details['ip']); ?></strong>).
        </p>
        <p style="margin: 0;">
            If you need to reissue your license for a domain change or server migration, please contact our support team at <a href="https://hostnibo.com/contact" target="_blank" style="color: #2563eb; text-decoration: underline; font-weight: 600;">Host Nibo Support</a> or visit <a href="https://hostnibo.com" target="_blank" style="color: #2563eb; text-decoration: underline; font-weight: 600;">hostnibo.com</a>.
        </p>
    </div>
</div>
```

---

## 📄 ৪. ফাইল ৩: `storage/license/.gitignore`

**কাজ:** রানটাইমে তৈরি হওয়া `.json` অফলাইন ক্যাশ ফাইলগুলোকে গিট ট্র্যাক থেকে বাদ দেওয়া।

```gitignore
# Ignore license runtime cache files
*.json
!.gitignore
```

---

## 📄 ৫. ফাইল ৪: মূল মডিউল ফাইল `my_module.php` (Gatekeeper ইন্টিগ্রেশন)

**কাজ:** মডিউলের শুরুতে লাইসেন্স গেটকিপার চেক চালানো। লাইসেন্স ইনভ্যালিড/মেয়াদোত্তীর্ণ হলে মূল ড্যাশবোর্ড লক করে রাখা।

```php
<?php
/**
 * My Module Main Entry File
 */
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// ১. লাইসেন্স ক্লায়েন্ট ফাইল ইনক্লুড করুন
require_once __DIR__ . '/app/License/LicenseManager.php';
use MyModule\License\LicenseManager;

/**
 * মডিউল কনফিগারেশন
 */
function my_module_config() {
    return [
        'name'        => 'My Awesome Module',
        'description' => 'Advanced module protected with Host Nibo ELMS Licensing.',
        'version'     => '1.0.0',
        'author'      => '<a href="https://hostnibo.com" target="_blank">Host Nibo</a>',
        'language'    => 'english',
        'fields'      => [
            'license_key' => [
                'FriendlyName' => 'License Key',
                'Type'         => 'text',
                'Size'         => '40',
                'Description'  => 'Enter your Host Nibo ELMS license key',
                'Default'      => ''
            ]
        ]
    ];
}

/**
 * মডিউল আউটপুট (Gatekeeper)
 */
function my_module_output($vars) {
    $action = $_GET['action'] ?? '';

    // 🔒 ELMS লাইসেন্স গেটকিপার: লাইসেন্স সক্রিয় না থাকলে ড্যাশবোর্ড লক থাকবে
    $isLicensed = LicenseManager::isLicensed(true);
    if (!$isLicensed && $action !== 'license') {
        require_once __DIR__ . '/admin/license.php';
        return;
    }

    // লাইসেন্স ম্যানেজমেন্ট পেজে যাওয়ার রাউট
    if ($action === 'license') {
        require_once __DIR__ . '/admin/license.php';
        return;
    }

    // ✅ লাইসেন্স ভ্যালিড হলে এখান থেকে আপনার মূল মডিউল ড্যাশবোর্ড লোড হবে
    echo '<h2>Welcome to My Module Dashboard!</h2>';
    echo '<p><a href="addonmodules.php?module=my_module&action=license">Manage License</a></p>';
}
```

---

## ⚡ ৬. সংক্ষেপে ৩টি পরিবর্তন যা করতে হবে:

নতুন যেকোনো মডিউলে ফাইলগুলো কপি করার পর শুধু ৩টি জিনিস পরিবর্তন করবেন:

1. **`LicenseManager.php`-এর `DEFAULT_PRODUCT_KEY`:** আপনার ELMS সার্ভারের প্রোডাক্ট কি (যেমন: `MY-NEW-PRODUCT`).
2. **`LicenseManager.php`-এর `MODULE_NAME`:** মডিউলের ফোল্ডারের নাম (যেমন: `my_module`).
3. **`LicenseManager.php` ও `admin/license.php`-এর `namespace`:** মডিউল অনুযায়ী সঠিক নেইমস্পেস (যেমন: `MyModule\License`).
