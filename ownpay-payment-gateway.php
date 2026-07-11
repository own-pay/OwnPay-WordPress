<?php
/**
 * Plugin Name:     OwnPay Payment Gateway
 * Plugin URI:      https://github.com/own-pay/OwnPay-WordPress
 * Description:     Accept card, bank transfer, and mobile banking payments in WooCommerce via OwnPay.
 * Author:          OwnPay
 * Author URI:      https://ownpay.org
 * Version:         1.1.0
 * Requires at least: 5.1
 * Requires PHP:    8.0
 * Requires Plugins: woocommerce
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     ownpay-payment-gateway
 * Domain Path:     /languages
 */

if (!defined('ABSPATH')) exit;

if (!defined('WPINC')) die;

/**
 * Current plugin version.
 */
define('OPWC_VERSION', '1.0.0');
define('OPWC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OPWC_ASSETS_DIR', plugin_dir_url(__FILE__) . 'assets/');



/**
 * Check for the existence of WooCommerce.
 * Uses class_exists() which works on both front-end and admin.
 * is_plugin_active() is intentionally omitted here as it is only loaded
 * in admin context; the 'Requires Plugins' header enforces the dependency at activation.
 */
function opwc_check_requirements()
{
    if (class_exists('WooCommerce')) {
        return true;
    } else {
        if (is_admin()) {
            add_action('admin_notices', 'opwc_missing_wc_notice');
        }
        return false;
    }
}

/**
 * WooCommerce required message
 */
function opwc_missing_wc_notice()
{
    $class = 'notice notice-error';
    $message = __('OwnPay Payment Gateway requires WooCommerce to be installed and active.', 'ownpay-payment-gateway');

    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}

/**
 * The core plugin class
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-opwc.php';

/**
 * Plugin activation: seed the cache version option so the getter never
 * calls update_option() on every page load.
 */
function opwc_activate()
{
    add_option('opwc_payments_cache_version', (string) time(), '', 'no');
}
register_activation_hook(__FILE__, 'opwc_activate');

/**
 * Begins execution of the plugin.
 */
function opwc_run_plugin()
{
    if (opwc_check_requirements()) {
        $plugin = new OPWC();
        $plugin->run();
    }
}

add_action('plugins_loaded', 'opwc_run_plugin');
