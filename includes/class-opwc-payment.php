<?php
/**
 * Initialize OwnPay Payment Gateway
 *
 * @package    OPWC
 */

if (!defined('ABSPATH')) exit;

class OPWC_Payment extends WC_Payment_Gateway
{
	private $api_url = '';
	private $api_key = '';
	private $webhook_secret = '';
	private $complete_order_after_payment = false;
	private $add_extra_fee = false;
	private $fee_percentage = 0;

	public function __construct()
	{
		$this->id = 'ownpay';
		$this->icon = plugins_url('../assets/logo/payment-method-logo.png', __FILE__);
		$this->method_title = 'OwnPay';
		$this->method_description = 'Accept payments via cards, bank transfer, and mobile banking using OwnPay.';
		$this->has_fields = false;
		$this->supports = array('products');

		$this->init_form_fields();
		$this->init_settings();

		$this->title = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->api_url = rtrim($this->get_option('api_url'), '/');
		$this->api_key = $this->get_option('api_key');
		$this->webhook_secret = $this->get_option('webhook_secret');
		$this->complete_order_after_payment = $this->get_option('complete_order_after_payment') === 'yes' ? true : false;
		$this->add_extra_fee = $this->get_option('add_extra_fee') === 'yes' ? true : false;
		$this->fee_percentage = $this->get_option('fee_percentage');

		// Actions
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_cart_calculate_fees', [$this, 'add_ownpay_payment_fee']);
		
		// Webhook callback registry (woocommerce_api_ownpay)
		add_action('woocommerce_api_ownpay', [$this, 'handle_webhook']);

		// Thank you page status synchronization
		add_action('woocommerce_thankyou_' . $this->id, [$this, 'sync_payment_status']);
	}

	/**
	 * Get the gateway icon HTML with fixed dimensions
	 */
	public function get_icon()
	{
		$custom_logo = $this->get_option('custom_logo');
		$logo_url = !empty($custom_logo) ? esc_url($custom_logo) : $this->icon;

		$icon_html = '';
		if (!empty($logo_url)) {
			$icon_html = sprintf(
				'<img src="%1$s" alt="%2$s" class="opwc-checkout-gateway-logo" style="max-height: 24px; max-width: 100px; width: auto; height: auto; display: inline-block; vertical-align: middle; margin-left: 10px;" />',
				esc_url($logo_url),
				esc_attr($this->get_title())
			);
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- This is a WooCommerce core filter, not our own hook.
		return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
	}

	/**
	 * Render custom media uploader field for gateway settings
	 */
	public function generate_image_upload_html($key, $data)
	{
		$field_key = $this->get_field_key($key);
		$defaults  = array(
			'title'             => '',
			'disabled'          => false,
			'class'             => '',
			'css'               => '',
			'placeholder'       => '',
			'desc_tip'          => false,
			'description'       => '',
			'custom_attributes' => array(),
		);

		$data = wp_parse_args($data, $defaults);
		$value = $this->get_option($key);

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?></label>
				<?php echo wp_kses_post($this->get_tooltip_html($data)); ?>
			</th>
			<td class="forminp">
				<fieldset>
					<input class="input-text regular-input <?php echo esc_attr($data['class']); ?>" type="text" name="<?php echo esc_attr($field_key); ?>" id="<?php echo esc_attr($field_key); ?>" style="width: 350px; <?php echo esc_attr($data['css']); ?>" value="<?php echo esc_attr($value); ?>" placeholder="<?php echo esc_attr($data['placeholder']); ?>" <?php disabled($data['disabled'], true); ?> <?php echo wp_kses_post($this->get_custom_attribute_html($data)); ?> />
					<button type="button" class="button opwc-upload-button" data-input-id="<?php echo esc_attr($field_key); ?>"><?php esc_html_e('Upload / Choose Image', 'ownpay-payment-gateway'); ?></button>
					<button type="button" class="button opwc-clear-button" data-input-id="<?php echo esc_attr($field_key); ?>"><?php esc_html_e('Clear', 'ownpay-payment-gateway'); ?></button>
					<div class="opwc-logo-preview" style="margin-top: 10px;">
						<img id="<?php echo esc_attr($field_key); ?>-preview" src="<?php echo esc_url($value); ?>" style="max-height: 50px; width: auto; height: auto; display: <?php echo !empty($value) ? 'block' : 'none'; ?>; border: 1px solid #ddd; padding: 4px; background: #fff;" />
					</div>
					<?php echo wp_kses_post($this->get_description_html($data)); ?>
				</fieldset>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Configure Gateway Settings Form Fields
	 */
	public function init_form_fields()
	{
		$webhook_url = class_exists('WC') ? WC()->api_request_url('ownpay') : home_url('/?wc-api=ownpay');

		$this->form_fields = array(
			'enabled' => array(
				'title' => __('Enable/Disable', 'ownpay-payment-gateway'),
				'type' => 'checkbox',
				'label' => __('Enable OwnPay Payment', 'ownpay-payment-gateway'),
				'default' => 'yes'
			),
			'title' => array(
				'title' => __('Title', 'ownpay-payment-gateway'),
				'type' => 'text',
				'default' => __('OwnPay Payment', 'ownpay-payment-gateway'),
				'description' => __('This controls the title which the user sees during checkout.', 'ownpay-payment-gateway'),
				'desc_tip'    => true,
			),
			'custom_logo' => array(
				'title'       => __('Custom Gateway Logo', 'ownpay-payment-gateway'),
				'type'        => 'image_upload',
				'default'     => '',
				'placeholder' => 'https://example.com/logo.png',
				'description' => __('Upload or choose a custom image to replace the default OwnPay logo on the checkout page. Leave blank to use the default logo.', 'ownpay-payment-gateway'),
				'desc_tip'    => false,
			),
			'description' => array(
				'title' => __('Description', 'ownpay-payment-gateway'),
				'type' => 'textarea',
				'default' => __('Pay securely via Cards, Bank Transfer, or Mobile Banking.', 'ownpay-payment-gateway'),
				'description' => __('This controls the description which the user sees during checkout.', 'ownpay-payment-gateway'),
				'desc_tip'    => true,
			),
			'api_url' => array(
				'title' => __('OwnPay API Endpoint URL', 'ownpay-payment-gateway'),
				'type' => 'text',
				'default' => '',
				'placeholder' => 'https://pay.ownpay.org',
				'description' => __('The base URL of your OwnPay gateway installation (e.g. https://pay.yourdomain.com).', 'ownpay-payment-gateway'),
				'desc_tip'    => true,
			),
			'api_key' => array(
				'title' => __('API Key', 'ownpay-payment-gateway'),
				'type' => 'password',
				'default' => '',
				'description' => __('The Bearer API Key generated in your OwnPay Admin Panel.', 'ownpay-payment-gateway'),
				'desc_tip'    => true,
			),
			'webhook_secret' => array(
				'title' => __('Webhook Secret', 'ownpay-payment-gateway'),
				'type' => 'password',
				'default' => '',
				'description' => sprintf(
					/* translators: %1$s: Webhook URL wrapped in a code element. */
					__('The shared secret used to verify incoming webhook signatures from OwnPay. You MUST configure this outbound Webhook URL in your OwnPay Merchant Dashboard: %1$s', 'ownpay-payment-gateway'),
					'<br/><code>' . esc_url($webhook_url) . '</code>'
				),
				'desc_tip'    => false,
			),
			'add_extra_fee' => array(
				'title' => __('Add Extra Fee', 'ownpay-payment-gateway'),
				'type' => 'checkbox',
				'label' => __('Enable Extra Fee', 'ownpay-payment-gateway'),
				'default' => 'no',
				'description' => __('Check this if you want to add an extra charge for paying via OwnPay.', 'ownpay-payment-gateway'),
				'desc_tip'    => true,
			),
			'fee_percentage' => array(
				'title' => __('Fee Percentage', 'ownpay-payment-gateway'),
				'type' => 'number',
				'default' => 1.5,
				'custom_attributes' => array(
					'step' => '0.01',
					'min'  => '0',
				),
				'description' => __('Percentage fee to charge. E.g. 1.5 for 1.5%.', 'ownpay-payment-gateway'),
				'desc_tip'    => true,
			),
			'complete_order_after_payment' => array(
				'title' => __('Change Order Status', 'ownpay-payment-gateway'),
				'type' => 'checkbox',
				'label' => __('Complete order after payment success!', 'ownpay-payment-gateway'),
				'default' => 'no',
				'description' => __("If enabled, the order status will transition to 'Completed.' Otherwise, it will remain in 'Processing' status.", 'ownpay-payment-gateway'),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Process checkout payment request and redirect customer to checkout URL
	 */
	public function process_payment($order_id)
	{
		$order = wc_get_order($order_id);
		if (!$order) {
			return array(
				'result' => 'failure',
				'messages' => __('Order not found.', 'ownpay-payment-gateway')
			);
		}

		if (empty($this->api_url) || empty($this->api_key)) {
			wc_add_notice(__('OwnPay Gateway is not fully configured. Please configure API credentials.', 'ownpay-payment-gateway'), 'error');
			return array(
				'result' => 'failure',
				'messages' => __('OwnPay Gateway is not fully configured.', 'ownpay-payment-gateway')
			);
		}

		$initiate_url = $this->api_url . '/api/v1/payments';

		// Construct payload matching OwnPay api initiate parameters
		$body = array(
			'amount'         => (string) $order->get_total(),
			'currency'       => strtoupper($order->get_currency()),
			'callback_url'   => WC()->api_request_url('ownpay'),
			'redirect_url'   => $this->get_return_url($order),
			'cancel_url'     => $order->get_cancel_order_url(),
			'customer_mail'  => $order->get_billing_email(),
			'customer_name'  => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
			'customer_phone' => $order->get_billing_phone(),
			'reference'      => (string) $order_id,
			'metadata'       => array(
				'plugin_version'      => OPWC_VERSION,
				'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : 'unknown',
			),
		);

		$headers = array(
			'Content-Type'  => 'application/json',
			'Accept'        => 'application/json',
			'Authorization' => 'Bearer ' . $this->api_key,
		);

		$response = wp_remote_post($initiate_url, array(
			'headers'   => $headers,
			'body'      => wp_json_encode($body),
			'timeout'   => 30,
			'sslverify' => true,
		));

		if (is_wp_error($response)) {
			$err_msg = esc_html($response->get_error_message());
			wc_add_notice(__('OwnPay Payment Error: Connection failed. ', 'ownpay-payment-gateway') . $err_msg, 'error');
			return array(
				'result'   => 'failure',
				'messages' => $err_msg
			);
		}

		// Save request payload response logs as order meta (HPOS-compatible)
		$response_body = wp_remote_retrieve_body($response);
		$order->update_meta_data('_opwc_create_response', $response_body);
		$order->save();

		$response_code = wp_remote_retrieve_response_code($response);
		$response_data = json_decode($response_body, true);

		if ($response_code !== 201 || !isset($response_data['success']) || $response_data['success'] !== true) {
			$error_message = isset($response_data['error']) ? esc_html($response_data['error']) : __('Could not initiate payment session.', 'ownpay-payment-gateway');
			if (isset($response_data['errors']) && is_array($response_data['errors'])) {
				$messages = [];
				foreach ($response_data['errors'] as $err) {
					$messages[] = esc_html($err['message']);
				}
				$error_message = implode(', ', $messages);
			}
			wc_add_notice(__('OwnPay Payment Error: ', 'ownpay-payment-gateway') . $error_message, 'error');
			return array(
				'result' => 'failure',
				'messages' => $error_message
			);
		}

		$data = $response_data['data'] ?? [];

		if (isset($data['payment_id'], $data['checkout_url'])) {
			$order->update_meta_data('_ownpay_payment_id', sanitize_text_field($data['payment_id']));
			if (isset($data['token'])) {
				$order->update_meta_data('_ownpay_token', sanitize_text_field($data['token']));
			}
			$order->save();

			$order->update_status('pending', __('Awaiting OwnPay payment.', 'ownpay-payment-gateway'));

			return array(
				'result'   => 'success',
				'redirect' => esc_url_raw($data['checkout_url'])
			);
		} else {
			wc_add_notice(__('Invalid response format from OwnPay gateway.', 'ownpay-payment-gateway'), 'error');
			return array(
				'result' => 'failure',
				'messages' => __('Invalid response format from OwnPay gateway.', 'ownpay-payment-gateway')
			);
		}
	}

	/**
	 * Handle server-to-server webhook callbacks from OwnPay
	 */
	public function handle_webhook()
	{
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- php://input is the only way to read the raw POST body for HMAC signature verification; no WordPress API equivalent exists.
		$raw_body = file_get_contents('php://input');
		if (empty($raw_body)) {
			status_header(400);
			echo esc_html__('Empty request body.', 'ownpay-payment-gateway');
			exit;
		}

		// Signature headers checklist
		$signature = '';
		$headers = function_exists('getallheaders') ? getallheaders() : [];
		if (empty($headers)) {
			foreach ($_SERVER as $k => $v) {
				if (strpos($k, 'HTTP_') === 0) {
					$header_name = str_replace('_', '-', substr($k, 5));
					$headers[$header_name] = $v;
				}
			}
		}

		// Convert headers to lowercase for uniform comparison
		$lowercase_headers = array_change_key_case($headers, CASE_LOWER);
		
		if (isset($lowercase_headers['x-signature'])) {
			$signature = $lowercase_headers['x-signature'];
		} elseif (isset($lowercase_headers['x-ownpay-signature'])) {
			$signature = $lowercase_headers['x-ownpay-signature'];
		}

		// Handle sha256= prefix in signature
		if (strpos($signature, 'sha256=') === 0) {
			$signature = substr($signature, 7);
		}

		if (empty($signature)) {
			status_header(401);
			echo esc_html__('Webhook signature header missing.', 'ownpay-payment-gateway');
			exit;
		}

		if (empty($this->webhook_secret)) {
			status_header(500);
			echo esc_html__('Webhook secret is not configured in settings.', 'ownpay-payment-gateway');
			exit;
		}

		// Calculate timing-safe HMAC signature verification
		$expected_signature = hash_hmac('sha256', $raw_body, $this->webhook_secret);

		if (!hash_equals($expected_signature, $signature)) {
			status_header(403);
			echo esc_html__('Signature verification failed.', 'ownpay-payment-gateway');
			exit;
		}

		$payload = json_decode($raw_body, true);
		if (!is_array($payload)) {
			status_header(400);
			echo esc_html__('Invalid JSON payload.', 'ownpay-payment-gateway');
			exit;
		}

		// The webhook event properties mapping (OwnPay envelopes event + data)
		$event_type = $payload['event'] ?? '';
		$event_data = $payload['data'] ?? $payload;

		$transaction_id = $event_data['transaction_id'] ?? '';
		$gateway_trx_id = $event_data['gateway_trx_id'] ?? '';
		$status = $event_data['status'] ?? '';
		$amount = $event_data['amount'] ?? '';
		$reference = $event_data['reference'] ?? '';

		if (is_array($reference)) {
			$reference = $reference['reference'] ?? '';
		}

		// If reference is not found in standard properties, search inside metadata
		if (empty($reference) && isset($event_data['metadata']) && is_array($event_data['metadata'])) {
			$reference = $event_data['metadata']['reference'] ?? '';
		}

		$order_id = absint($reference);
		$order = wc_get_order($order_id);

		if (!$order) {
			// Try looking up order by payment_id meta if reference is missing
			$payment_id = $event_data['id'] ?? $event_data['payment_id'] ?? '';
			if (!empty($payment_id)) {
				$orders = wc_get_orders(array(
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- No HPOS-native alternative exists for looking up orders by custom meta value via wc_get_orders().
					'meta_query' => array(
						array(
							'key'   => '_ownpay_payment_id',
							'value' => sanitize_text_field($payment_id),
						),
					),
					'limit' => 1,
				));
				if (!empty($orders)) {
					$order = $orders[0];
					$order_id = $order->get_id();
				}
			}
		}

		if (!$order) {
			status_header(404);
			echo esc_html__('Order not found matching reference.', 'ownpay-payment-gateway');
			exit;
		}

		// Verify webhook amount and currency against order details
		$order_total    = (float) $order->get_total();
		$order_currency = strtoupper($order->get_currency());
		$webhook_currency = strtoupper($event_data['currency'] ?? '');
		$webhook_amount = isset($event_data['amount']) ? (float) $event_data['amount'] : -1.0;

		if ($webhook_amount <= 0 || abs($webhook_amount - $order_total) > 0.01 || $webhook_currency !== $order_currency) {
			$order->add_order_note(sprintf(
				/* translators: 1: Expected order total amount. 2: Expected currency code. 3: Received amount from webhook. 4: Received currency code from webhook. */
				__('OwnPay Webhook: Currency or Amount mismatch. Expected: %1$s %2$s, Received: %3$s %4$s. Manual review required.', 'ownpay-payment-gateway'),
				$order_total,
				$order_currency,
				$webhook_amount >= 0 ? $webhook_amount : 'missing/invalid',
				$webhook_currency ? $webhook_currency : 'missing/invalid'
			));
			status_header(200); // 200 to prevent OwnPay retries
			echo 'Currency or Amount mismatch. Flagged for review.';
			exit;
		}

		// Save webhook execution response log as order meta (HPOS-compatible), limit to 8KB
		if (strlen($raw_body) < 8192) {
			$order->update_meta_data('_opwc_execute_response', $raw_body);
		} else {
			$order->update_meta_data('_opwc_execute_response', wp_json_encode(['error' => 'Webhook payload size limit exceeded.']));
		}
		$order->save();

		// Process transaction status change
		$status_lower = strtolower($status);
		if ($event_type === 'payment.transaction.completed' || $status_lower === 'completed' || $status_lower === 'paid') {
			if (!$order->is_paid()) {
				$order->payment_complete($gateway_trx_id ? $gateway_trx_id : $transaction_id);

				if ($this->complete_order_after_payment) {
					$order->update_status('completed');
				} else {
					$order->update_status('processing');
				}

				$order->add_order_note(sprintf(
					/* translators: 1: OwnPay internal transaction ID. 2: Downstream gateway transaction ID. */
					__('OwnPay Webhook: Payment completed. Transaction ID: %1$s. Gateway Transaction: %2$s.', 'ownpay-payment-gateway'),
					esc_html($transaction_id),
					esc_html($gateway_trx_id)
				));
			}

			status_header(200);
			echo esc_html__('Webhook processed. Order completed.', 'ownpay-payment-gateway');
			exit;
		} elseif ($status_lower === 'failed') {
			if (!$order->is_paid()) {
				$order->update_status('failed', __('OwnPay Webhook: Payment failed.', 'ownpay-payment-gateway'));
			}
			status_header(200);
			echo esc_html__('Webhook processed. Order marked failed.', 'ownpay-payment-gateway');
			exit;
		} elseif ($status_lower === 'cancelled') {
			if (!$order->is_paid()) {
				$order->update_status('cancelled', __('OwnPay Webhook: Payment cancelled.', 'ownpay-payment-gateway'));
			}
			status_header(200);
			echo esc_html__('Webhook processed. Order marked cancelled.', 'ownpay-payment-gateway');
			exit;
		}

		status_header(200);
		echo esc_html__('Webhook received but event type is ignored.', 'ownpay-payment-gateway');
		exit;
	}

	/**
	 * Synchronize payment status during synchronous customer redirects (fallback)
	 */
	public function sync_payment_status($order_id)
	{
		$order = wc_get_order($order_id);
		if (!$order || $order->is_paid()) {
			return;
		}

		$payment_id = $order->get_meta('_ownpay_payment_id', true);
		if (empty($payment_id)) {
			return;
		}

		if (empty($this->api_url) || empty($this->api_key)) {
			return;
		}

		$query_url = $this->api_url . '/api/v1/payments/' . rawurlencode($payment_id);

		$headers = array(
			'Accept'        => 'application/json',
			'Authorization' => 'Bearer ' . $this->api_key,
		);

		$response = wp_remote_get($query_url, array(
			'headers'   => $headers,
			'timeout'   => 15,
			'sslverify' => true,
		));

		if (is_wp_error($response)) {
			return;
		}

		$response_code = wp_remote_retrieve_response_code($response);
		if ($response_code !== 200) {
			return;
		}

		$response_data = json_decode(wp_remote_retrieve_body($response), true);
		if (!isset($response_data['success']) || $response_data['success'] !== true) {
			return;
		}

		$data = $response_data['data'] ?? [];
		$status = strtolower($data['status'] ?? '');
		$trx_id = $data['trx_id'] ?? '';
		$gateway_trx_id = $data['gateway_trx_id'] ?? '';

		$order_currency = strtoupper($order->get_currency());
		$api_currency = strtoupper($data['currency'] ?? '');
		$order_total = (float) $order->get_total();
		$api_amount = isset($data['amount']) ? (float) $data['amount'] : -1.0;

		if ($api_amount <= 0 || abs($api_amount - $order_total) > 0.01 || $api_currency !== $order_currency) {
			$order->add_order_note(sprintf(
				/* translators: 1: Expected order total amount. 2: Expected currency code. 3: Received amount from API. 4: Received currency code from API. */
				__('OwnPay Redirect: Currency or Amount mismatch during verification. Expected: %1$s %2$s, Received: %3$s %4$s. Manual review required.', 'ownpay-payment-gateway'),
				$order_total,
				$order_currency,
				$api_amount >= 0 ? $api_amount : 'missing/invalid',
				$api_currency ? $api_currency : 'missing/invalid'
			));
			return;
		}

		if ($status === 'completed' || $status === 'paid' || $status === 'success') {
			$fallback_trx_id = $gateway_trx_id ? $gateway_trx_id : ($trx_id ? $trx_id : $payment_id);
			$order->payment_complete($fallback_trx_id);

			if ($this->complete_order_after_payment) {
				$order->update_status('completed');
			} else {
				$order->update_status('processing');
			}

			$order->add_order_note(sprintf(
				/* translators: 1: OwnPay internal transaction ID. 2: Downstream gateway transaction ID. */
				__('OwnPay Redirect: Payment verified. Transaction ID: %1$s. Gateway Transaction: %2$s.', 'ownpay-payment-gateway'),
				esc_html($trx_id),
				esc_html($gateway_trx_id)
			));
		}
	}

	/**
	 * Add extra checkout fee if option is enabled
	 */
	public function add_ownpay_payment_fee($cart)
	{
		if (!$this->add_extra_fee) return;

		if (is_admin() && !defined('DOING_AJAX')) return;

		if (isset(WC()->session->chosen_payment_method) && WC()->session->chosen_payment_method === $this->id) {
			$fee_percentage = (float) $this->fee_percentage;

			if ($fee_percentage > 0) {
				$discounted_subtotal = max(0, $cart->cart_contents_total - $cart->get_discount_total());
				$fee = ($discounted_subtotal + $cart->get_shipping_total()) * ($fee_percentage / 100);
				WC()->cart->add_fee(
					__('OwnPay Processing Fee', 'ownpay-payment-gateway') . ' (' . $fee_percentage . '%)',
					$fee
				);
			}
		}
	}

	/**
	 * Sanitize and validate custom image upload field
	 */
	public function validate_image_upload_field($key, $value)
	{
		return is_null($value) ? '' : esc_url_raw(trim($value));
	}
}
