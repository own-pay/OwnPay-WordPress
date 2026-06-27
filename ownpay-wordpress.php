<?php
/**
 * Plugin Name:     OwnPay WordPress
 * Plugin URI:      https://github.com/own-pay/OwnPay-WordPress
 * Description:     OwnPay WordPress Plugin adds a simple, secure, and modern payment solution for your online store, allowing customers to pay via card, bank transfer, and mobile banking.
 * Author:          OwnPay
 * Author URI:      https://ownpay.org
 * Version:         1.0.0
 * License:         GPL-2.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     ownpay-wordpress
 * Domain Path:     /languages
 * GitHub Plugin URI: https://github.com/own-pay/OwnPay-WordPress
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
    $message = __('OwnPay plugin requires WooCommerce to be installed and active.', 'ownpay-wordpress');

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
