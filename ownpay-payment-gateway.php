<?php
/**
 * Plugin Name:     OwnPay Payment Gateway
 * Plugin URI:      https://github.com/own-pay/ownpay-payment-gateway
 * Description:     Accept card, bank transfer, and mobile banking payments in WooCommerce via OwnPay.
 * Author:          OwnPay
 * Author URI:      https://ownpay.org
 * Version:         1.0.0
 * Requires at least: 5.1
 * Requires PHP:    8.0
 * Requires Plugins: woocommerce
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     ownpay-payment-gateway
 * Domain Path:     /languages
 * GitHub Plugin URI: https://github.com/own-pay/ownpay-payment-gateway
 */

if (!defined('ABSPATH')) exit;

if (!defined('WPINC')) die;

/**
 * Current plugin version.
 */
define('OPWC_VERSION', '1.0.0');
define('OPWC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OPWC_ASSETS_DIR', plugin_dir_url(__FILE__) . 'assets/');

if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . '/wp-admin/includes/plugin.php');
}

/**
 * Check for the existence of WooCommerce
 */
function opwc_check_requirements()
{
    if (class_exists('WooCommerce') || is_plugin_active('woocommerce/woocommerce.php')) {
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
    $message = __('OwnPay plugin requires WooCommerce to be installed and active.', 'ownpay-payment-gateway');

    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}

/**
 * The core plugin class
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-opwc.php';

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
