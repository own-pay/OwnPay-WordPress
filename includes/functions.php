<?php
/**
 * Helper functions to query WooCommerce payment logs
 *
 * @package    OPWC
 */

if (!defined('ABSPATH')) exit;

/**
 * Get detailed logs of orders processed via OwnPay
 */
function opwc_get_payments_details($args = [], $filters = [])
{
	$cache_version = opwc_get_cache_version();
	$cache_key = 'opwc_payments_' . $cache_version . '_' . md5(serialize($args) . serialize($filters));
	$cached_data = wp_cache_get($cache_key);

	if ($cached_data !== false) {
		return $cached_data;
	}

	// Merge caller-supplied args first so limit/offset from the controller are respected.
	// Only fall back to defaults for keys the caller did not provide.
	$defaults = array(
		'payment_method' => 'ownpay',
		'limit'          => 10,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$args = wp_parse_args($args, $defaults);
	$args = opwc_add_status_filter($args, $filters);

	$orders = wc_get_orders($args);
	$orders_data = [];

	if (!empty($orders)) {
		foreach ($orders as $order) {
			$user_id = $order->get_customer_id();
			$user_info = $user_id ? get_userdata($user_id) : null;

			$orders_data[] = [
				'order_id'         => $order->get_id(),
				'username'         => $user_info ? $user_info->user_login : 'Guest',
				'email'            => $order->get_billing_email(),
				'status'           => wc_get_order_status_name($order->get_status()),
				'total_amount'     => $order->get_total(),
				'order_date'       => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : '',
				'create_response'  => $order->get_meta('_opwc_create_response', true),
				'execute_response' => $order->get_meta('_opwc_execute_response', true),
			];
		}
	}
	
	wp_cache_set($cache_key, $orders_data, '', 3600); // Cache for 1 hour
	
	return $orders_data;
}

/**
 * Filter query args based on active filter options with strict whitelisting
 */
function opwc_add_status_filter($args, $filters)
{
	$allowed_keys = ['status', 'date'];
	$filter_key = isset($filters['filter_key']) && in_array($filters['filter_key'], $allowed_keys, true) ? $filters['filter_key'] : '';

	if (!empty($filter_key) && !empty($filters['filter_value'])) {
		if ($filter_key === 'status') {
			$allowed_statuses = ['pending', 'processing', 'completed', 'on-hold', 'failed', 'refunded', 'cancelled'];
			$status_val = strtolower(sanitize_text_field($filters['filter_value']));
			if (in_array($status_val, $allowed_statuses, true)) {
				$args['status'] = $status_val;
			}
		}

		if ($filter_key === 'date') {
			$order_val = strtoupper(sanitize_text_field($filters['filter_value']));
			if (in_array($order_val, ['ASC', 'DESC'], true)) {
				$args['orderby'] = 'date';
				$args['order']   = $order_val;
			}
		}
	}

	return $args;
}

/**
 * Get count of all orders processed via OwnPay
 */
function opwc_get_all_payments_count($filters = [])
{
	$cache_version = opwc_get_cache_version();
	$cache_key = 'opwc_payments_count_' . $cache_version . '_' . md5(serialize($filters));
	$cached_count = wp_cache_get($cache_key);

	if ($cached_count !== false) {
		return $cached_count;
	}

	// Use limit:-1 with return:ids so WooCommerce returns only an array of integers —
	// no WC_Order objects are hydrated, making this an efficient count query.
	$args = array(
		'payment_method' => 'ownpay',
		'limit'          => -1,
		'return'         => 'ids',
	);

	$args = opwc_add_status_filter($args, $filters);
	$orders = wc_get_orders($args);
	$count = is_array($orders) ? count($orders) : 0;

	wp_cache_set($cache_key, $count, '', 3600); // Cache for 1 hour

	return $count;
}

/**
 * Helper to get active cache version prefix
 */
function opwc_get_cache_version()
{
	$version = get_option('opwc_payments_cache_version');
	if (empty($version)) {
		$version = (string) time();
		update_option('opwc_payments_cache_version', $version);
	}
	return $version;
}
