<?php
/**
 * WooCommerce Blocks payment method integration for OwnPay
 *
 * Registers OwnPay with the WooCommerce Blocks payment method registry so the
 * gateway is fully compatible with the block-based Cart and Checkout blocks.
 *
 * @package    OPWC
 */

if (!defined('ABSPATH')) exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * OPWC_Blocks class.
 *
 * Extends AbstractPaymentMethodType to integrate OwnPay with the WooCommerce
 * Blocks checkout experience. This class is only loaded when the Blocks
 * integration API is available (WooCommerce 7.6+).
 */
final class OPWC_Blocks extends AbstractPaymentMethodType
{
	/**
	 * Payment method name — must match the gateway id.
	 *
	 * @var string
	 */
	protected $name = 'ownpay';

	/**
	 * Initialize settings from WooCommerce options.
	 */
	public function initialize()
	{
		$this->settings = get_option('woocommerce_ownpay_settings', []);
	}

	/**
	 * Returns whether this payment method should be active on the frontend.
	 *
	 * @return bool
	 */
	public function is_active()
	{
		return !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
	}

	/**
	 * Register and return the script handle(s) required for the block checkout.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles()
	{
		wp_register_script(
			'opwc-checkout-blocks',
			OPWC_PLUGIN_URL . 'assets/js/opwc-checkout-blocks.js',
			[
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
			],
			OPWC_VERSION,
			true
		);

		return ['opwc-checkout-blocks'];
	}

	/**
	 * Returns an array of key-value pairs of data made available to the
	 * payment method script via getSetting('ownpay_data').
	 *
	 * @return array<string, mixed>
	 */
	public function get_payment_method_data()
	{
		$title       = $this->get_setting('title', __('OwnPay Payment', 'ownpay-payment-gateway'));
		$description = $this->get_setting('description', '');
		$custom_logo = $this->get_setting('custom_logo', '');
		$icon        = !empty($custom_logo)
			? esc_url($custom_logo)
			: OPWC_PLUGIN_URL . 'assets/logo/payment-method-logo.png';

		return [
			'title'       => $title,
			'description' => $description,
			'icon'        => $icon,
			'supports'    => $this->get_supported_features(),
		];
	}
}
