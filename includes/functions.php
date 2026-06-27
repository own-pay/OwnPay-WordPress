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
	$cache_key = 'opwc_payments_' . md5(serialize($args) . serialize($filters));
	$cached_data = wp_cache_get($cache_key);

	if ($cached_data !== false) {
		return $cached_data;
	}

	$defaults = array(
		'payment_method' => 'ownpay',
		'limit'          => -1,
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
				'order_id' => $order->get_id(),
				'username' => $user_info ? $user_info->user_login : 'Guest',
				'email' => $order->get_billing_email(),
				'status' => wc_get_order_status_name($order->get_status()),
				'total_amount' => $order->get_total(),
				'order_date' => $order->get_date_created()->date('Y-m-d H:i:s'),
			];
		}
	}

	$orders_data = opwc_sort_date_wise($orders_data, $filters);
	
	// Cache for 5 minutes
	wp_cache_set($cache_key, $orders_data, '', 300);
	
	return $orders_data;
}

/**
 * Filter query args based on active filter options
 */
function opwc_add_status_filter($args, $filters)
{
	if (!empty($filters['filter_key']) && !empty($filters['filter_value'])) {
		if ($filters['filter_key'] === 'status') {
			$args['status'] = sanitize_text_field($filters['filter_value']);
		}
	}

	return $args;
}

/**
 * Get count of all orders processed via OwnPay
 */
function opwc_get_all_payments_count($filters = [])
{
	$cache_key = 'opwc_payments_count_' . md5(serialize($filters));
	$cached_count = wp_cache_get($cache_key);

	if ($cached_count !== false) {
		return $cached_count;
	}

	$args = array(
		'payment_method' => 'ownpay',
		'limit'          => -1,
		'return'         => 'ids', // Only get IDs for performance
	);

	$args = opwc_add_status_filter($args, $filters);
	$orders = wc_get_orders($args);
	$count = is_array($orders) ? count($orders) : 0;

	// Cache for 5 minutes
	wp_cache_set($cache_key, $count, '', 300);

	return $count;
}

/**
 * Sort payments list date wise
 */
function opwc_sort_date_wise($orders_data, $filters)
{
	if (!empty($filters['filter_key']) && !empty($filters['filter_value']) && 
		$filters['filter_key'] === 'date') {

		$order_by = strtolower($filters['filter_value']);
		
		if ($order_by === 'asc' || $order_by === 'desc') {
			usort($orders_data, function($a, $b) use ($order_by) {
				$date_a = strtotime($a['order_date']);
				$date_b = strtotime($b['order_date']);
				
				return $order_by === 'asc' ? 
					($date_a - $date_b) : 
					($date_b - $date_a);
			});
		}
	}

	return $orders_data;
}
