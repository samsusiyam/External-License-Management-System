<?php
/**
 * Plugin Name: ELMS License Client
 * Description: Validates this site's license against an ELMS License Server.
 * Version:     1.0.0
 * Author:      ELMS
 *
 * Configure the constants below or via the Settings > ELMS License page.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Bundle the shared PHP SDK.
require_once __DIR__ . '/lib/license.php';

class ELMS_License_Client
{
    private const OPT_KEY = 'elms_license_key';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_init', [self::class, 'registerSettings']);
        add_action('admin_notices', [self::class, 'notice']);
    }

    private static function sdk(): ElmsLicense
    {
        return new ElmsLicense([
            'server'  => defined('ELMS_SERVER') ? ELMS_SERVER : get_option('elms_server', ''),
            'api_key' => defined('ELMS_API_KEY') ? ELMS_API_KEY : get_option('elms_api_key', ''),
            'secret'  => defined('ELMS_API_SECRET') ? ELMS_API_SECRET : get_option('elms_api_secret', ''),
            'product' => defined('ELMS_PRODUCT') ? ELMS_PRODUCT : get_option('elms_product', ''),
            'cache_dir' => wp_upload_dir()['basedir'] . '/elms-cache',
        ]);
    }

    public static function isValid(): bool
    {
        $key = get_option(self::OPT_KEY, '');
        if ($key === '') {
            return false;
        }
        $res = self::sdk()->verify($key, wp_parse_url(home_url(), PHP_URL_HOST));
        return !empty($res['status']);
    }

    public static function menu(): void
    {
        add_options_page('ELMS License', 'ELMS License', 'manage_options', 'elms-license', [self::class, 'page']);
    }

    public static function registerSettings(): void
    {
        register_setting('elms_license', 'elms_server');
        register_setting('elms_license', 'elms_api_key');
        register_setting('elms_license', 'elms_api_secret');
        register_setting('elms_license', 'elms_product');
        register_setting('elms_license', self::OPT_KEY);
    }

    public static function page(): void
    {
        $valid = self::isValid();
        ?>
        <div class="wrap">
            <h1>ELMS License</h1>
            <p>Status:
                <?php if ($valid): ?>
                    <span style="color:#008a00;font-weight:600">Active</span>
                <?php else: ?>
                    <span style="color:#c00;font-weight:600">Inactive / Invalid</span>
                <?php endif; ?>
            </p>
            <form method="post" action="options.php">
                <?php settings_fields('elms_license'); ?>
                <table class="form-table">
                    <tr><th>Server URL</th><td><input type="url" name="elms_server" value="<?php echo esc_attr(get_option('elms_server')); ?>" class="regular-text"></td></tr>
                    <tr><th>API Key</th><td><input type="text" name="elms_api_key" value="<?php echo esc_attr(get_option('elms_api_key')); ?>" class="regular-text"></td></tr>
                    <tr><th>API Secret</th><td><input type="password" name="elms_api_secret" value="<?php echo esc_attr(get_option('elms_api_secret')); ?>" class="regular-text"></td></tr>
                    <tr><th>Product Key</th><td><input type="text" name="elms_product" value="<?php echo esc_attr(get_option('elms_product')); ?>" class="regular-text"></td></tr>
                    <tr><th>License Key</th><td><input type="text" name="<?php echo self::OPT_KEY; ?>" value="<?php echo esc_attr(get_option(self::OPT_KEY)); ?>" class="regular-text"></td></tr>
                </table>
                <?php submit_button('Save & Verify'); ?>
            </form>
        </div>
        <?php
    }

    public static function notice(): void
    {
        if (!self::isValid()) {
            echo '<div class="notice notice-error"><p><strong>ELMS License:</strong> This site is not licensed. Please enter a valid license key under Settings &rarr; ELMS License.</p></div>';
        }
    }
}

register_activation_hook(__FILE__, function () {
    // Attempt activation on the license server when the plugin is enabled.
    $key = get_option('elms_license_key', '');
    if ($key !== '') {
        (new ElmsLicense([
            'server'  => get_option('elms_server', ''),
            'api_key' => get_option('elms_api_key', ''),
            'secret'  => get_option('elms_api_secret', ''),
            'product' => get_option('elms_product', ''),
        ]))->activate($key, wp_parse_url(home_url(), PHP_URL_HOST));
    }
});

ELMS_License_Client::init();
