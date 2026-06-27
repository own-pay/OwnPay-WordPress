<?php
/**
 * Register filters and actions for checkout integration
 *
 * @package    OPWC
 */

if (!defined('ABSPATH')) exit;

class OPWC_Hooks
{
	public function __construct()
	{
		add_filter('woocommerce_payment_gateways', [$this, 'add_ownpay_gateway']);
		add_action('woocommerce_thankyou', [$this, 'custom_thankyou_page_status_notices'], 10, 1);
		add_action('wp_enqueue_scripts', [$this, 'trigger_recalculation_on_payment_method_change']);
		add_action('woocommerce_order_details_after_order_table', [$this, 'add_ownpay_details_to_order_table'], 10, 1);

		// Cache invalidation hooks
		add_action('woocommerce_update_order', [$this, 'clear_payments_cache']);
		add_action('woocommerce_new_order', [$this, 'clear_payments_cache']);
		add_action('woocommerce_order_status_changed', [$this, 'clear_payments_cache']);
		add_action('woocommerce_delete_order', [$this, 'clear_payments_cache']);
	}

	/**
	 * Register Gateway Class
	 */
	public function add_ownpay_gateway($gateways)
	{
		$gateways[] = 'OPWC_Payment';
		return $gateways;
	}

	/**
	 * Display notices on checkout thank you page
	 */
	public function custom_thankyou_page_status_notices($order_id)
	{
		if (!$order_id) {
			return;
		}

		$order = wc_get_order($order_id);
		if (!$order || $order->get_payment_method() !== 'ownpay') {
			return;
		}

		if ($order->is_paid()) {
			echo '<div class="woocommerce-message" role="alert">';
			echo '<strong>' . esc_html__('Success!', 'ownpay-wordpress') . '</strong> ';
			echo esc_html__('Your payment has been completed successfully.', 'ownpay-wordpress');
			echo '</div>';
		}

		if ($order->has_status('failed')) {
			echo '<div class="woocommerce-error" role="alert">';
			echo '<strong>' . esc_html__('Error:', 'ownpay-wordpress') . '</strong> ';
			echo esc_html__('Your payment has failed. Please try again or contact support.', 'ownpay-wordpress');
			echo '</div>';
		}
	}

	/**
	 * Recalculate checkout on method changes
	 */
	public function trigger_recalculation_on_payment_method_change()
	{
		if (is_checkout()) {
			wp_enqueue_script('opwc-frontend-script', OPWC_ASSETS_DIR . 'js/opwc-frontend.js', ['jquery'], OPWC_VERSION, true);
		}
	}

	/**
	 * Add payment details to customer receipt page and emails
	 */
	public function add_ownpay_details_to_order_table($order)
	{
		if (!$order instanceof WC_Order) {
			return;
		}

		if ('ownpay' === $order->get_payment_method()) {
			$order_id = $order->get_id();
			$raw_response = $order->get_meta('_opwc_execute_response', true);
			if (empty($raw_response)) {
				return;
			}

			$response = json_decode($raw_response, true);
			if (!is_array($response)) {
				return;
			}

			// Extract properties from payload data envelope
			$event_data = $response['data'] ?? $response;

			$transaction_id = $event_data['transaction_id'] ?? '';
			$gateway_trx_id = $event_data['gateway_trx_id'] ?? '';
			$gateway = $event_data['gateway'] ?? '';

			if ($transaction_id || $gateway_trx_id) {
				echo '<h3>' . esc_html__('OwnPay Payment Details', 'ownpay-wordpress') . '</h3>';
				echo '<table class="shop_table order_details">';

				if ($gateway) {
					echo '<tr><th>' . esc_html__('Payment Channel:', 'ownpay-wordpress') . '</th><td>' . esc_html(ucfirst($gateway)) . '</td></tr>';
				}

				if ($transaction_id) {
					echo '<tr><th>' . esc_html__('Transaction ID:', 'ownpay-wordpress') . '</th><td>' . esc_html($transaction_id) . '</td></tr>';
				}

				if ($gateway_trx_id) {
					echo '<tr><th>' . esc_html__('Gateway Transaction ID:', 'ownpay-wordpress') . '</th><td>' . esc_html($gateway_trx_id) . '</td></tr>';
				}

				echo '</table>';
			}
		}
	}

	/**
	 * Clear cached payment logs by incrementing the cache version
	 */
	public function clear_payments_cache()
	{
		update_option('opwc_payments_cache_version', (string) time());
	}
}
