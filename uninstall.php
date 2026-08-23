<?php
/**
 * OwnPay Payment Gateway — Uninstall
 *
 * Removes all plugin data when the plugin is deleted via the WordPress
 * admin dashboard. This file is NOT executed on deactivation — only on
 * full deletion.
 *
 * @package    OPWC
 */

// Prevent direct access outside WordPress uninstall flow.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Determine whether to remove data based on a site option.
// WordPress.org guidelines require giving site owners the choice to keep data.
$opwc_remove_data = get_option('opwc_remove_data_on_uninstall', false);

if (defined('OPWC_REMOVE_ALL_DATA') && OPWC_REMOVE_ALL_DATA) {
    $opwc_remove_data = true;
}

if ($opwc_remove_data) {
    // Remove the custom cache-busting option.
    delete_option('opwc_payments_cache_version');

    // Remove the "remove data" flag itself.
    delete_option('opwc_remove_data_on_uninstall');

    // WooCommerce stores all gateway settings under this single key.
    delete_option('woocommerce_ownpay_settings');

    // Remove any transients that may have been set.
    delete_transient('opwc_payments_cache');
}
