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
    }

    /**
     * Register hooks. Called externally after construction.
     */
    public function init()
    {
        add_filter('woocommerce_payment_gateways', [$this, 'add_ownpay_gateway']);
        add_action('woocommerce_thankyou', [$this, 'custom_thankyou_page_status_notices'], 10, 1);
        add_action('wp_enqueue_scripts', [$this, 'trigger_recalculation_on_payment_method_change']);
        add_action('woocommerce_order_details_after_order_table', [$this, 'add_ownpay_details_to_order_table'], 10, 1);

        // Handle OwnPay redirect status (cancel/failed) from URL params
        add_action('template_redirect', [$this, 'handle_redirect_status']);
        add_action('wp_loaded', [$this, 'show_redirect_notice']);

        // Cache invalidation hooks
        add_action('woocommerce_update_order', [$this, 'clear_payments_cache']);
        add_action('woocommerce_new_order', [$this, 'clear_payments_cache']);
        add_action('woocommerce_order_status_changed', [$this, 'clear_payments_cache']);
        add_action('woocommerce_delete_order', [$this, 'clear_payments_cache']);

        // WooCommerce Blocks checkout integration
        add_action('woocommerce_blocks_loaded', [$this, 'register_block_support']);
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
     * Register OwnPay with the WooCommerce Blocks payment method registry.
     *
     * Only loaded when the AbstractPaymentMethodType API is present (WC 7.6+),
     * so the plugin remains compatible with older WooCommerce versions.
     */
    public function register_block_support()
    {
        if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            return;
        }

        require_once plugin_dir_path(__FILE__) . 'class-opwc-blocks.php';

        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $registry) {
                $registry->register(new OPWC_Blocks());
            }
        );
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
            echo '<strong>' . esc_html__('Success!', 'ownpay-payment-gateway') . '</strong> ';
            echo esc_html__('Your payment has been completed successfully.', 'ownpay-payment-gateway');
            echo '</div>';
        }

        if ($order->has_status('failed')) {
            echo '<div class="woocommerce-error" role="alert">';
            echo '<strong>' . esc_html__('Error:', 'ownpay-payment-gateway') . '</strong> ';
            echo esc_html__('Your payment has failed. Please try again or contact support.', 'ownpay-payment-gateway');
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
            $event_data = isset($response['data']) && is_array($response['data']) ? $response['data'] : $response;

            $transaction_id = sanitize_text_field($event_data['transaction_id'] ?? '');
            $gateway_trx_id = sanitize_text_field($event_data['gateway_trx_id'] ?? '');
            $gateway        = sanitize_text_field($event_data['gateway'] ?? '');

            if ($transaction_id || $gateway_trx_id) {
                echo '<h3>' . esc_html__('OwnPay Payment Details', 'ownpay-payment-gateway') . '</h3>';
                echo '<table class="shop_table order_details">';

                if ($gateway) {
                    echo '<tr><th>' . esc_html__('Payment Channel:', 'ownpay-payment-gateway') . '</th><td>' . esc_html(ucfirst($gateway)) . '</td></tr>';
                }

                if ($transaction_id) {
                    echo '<tr><th>' . esc_html__('Transaction ID:', 'ownpay-payment-gateway') . '</th><td>' . esc_html($transaction_id) . '</td></tr>';
                }

                if ($gateway_trx_id) {
                    echo '<tr><th>' . esc_html__('Gateway Transaction ID:', 'ownpay-payment-gateway') . '</th><td>' . esc_html($gateway_trx_id) . '</td></tr>';
                }

                echo '</table>';
            }
        }
    }

    /**
     * Handle OwnPay redirect status parameters on return from payment page.
     *
     * When OwnPay redirects back after a cancelled or failed payment, the URL
     * contains payment_id and status query parameters. This method verifies the
     * status server-side, updates the order, sets a one-time transient for the
     * customer notice, and redirects to the same page without the OwnPay params.
     */
    public function handle_redirect_status()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET params originate from OwnPay server redirect, not from a form submission. No nonce is available or applicable.
        $payment_id = isset($_GET['payment_id']) ? sanitize_text_field(wp_unslash($_GET['payment_id'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Same redirect source as above.
        $status_param = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';

        // Only act when both OwnPay params are present
        if (empty($payment_id) || empty($status_param)) {
            return;
        }

        // Only handle failed or cancelled statuses
        if (!in_array($status_param, array('failed', 'cancelled'), true)) {
            return;
        }

        // Find the order by payment_id meta
        $orders = wc_get_orders(array(
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- No HPOS-native alternative exists for looking up orders by custom meta value via wc_get_orders().
            'meta_query' => array(
                array(
                    'key'   => '_ownpay_payment_id',
                    'value' => $payment_id,
                ),
            ),
            'limit' => 1,
        ));

        if (empty($orders)) {
            return;
        }

        $order = $orders[0];
        if ($order->get_payment_method() !== 'ownpay') {
            return;
        }

        $order_id = $order->get_id();

        // Verify status server-side if order is not yet paid
        if (!$order->is_paid()) {
            $gateways = WC()->payment_gateways()->payment_gateways();
            if (isset($gateways['ownpay']) && method_exists($gateways['ownpay'], 'verify_payment_by_id')) {
                $data = $gateways['ownpay']->verify_payment_by_id($payment_id, $order);

                if (!empty($data)) {
                    $verified_status = sanitize_key($data['status'] ?? '');

                    if ($verified_status === 'failed' && !$order->is_paid()) {
                        $order->update_status('failed', __('OwnPay Redirect: Payment failed.', 'ownpay-payment-gateway'));
                    } elseif ($verified_status === 'cancelled' && !$order->is_paid()) {
                        $order->update_status('cancelled', __('OwnPay Redirect: Payment cancelled.', 'ownpay-payment-gateway'));
                    }
                } else {
                    // API verification failed; still show notice but do not change order status
                    $order->add_order_note(sprintf(
                        /* translators: 1: OwnPay payment ID. 2: Redirect status. */
                        __('OwnPay Redirect: Could not verify payment %1$s status via API. Customer was redirected with status: %2$s.', 'ownpay-payment-gateway'),
                        $payment_id,
                        $status_param
                    ));
                }
            }
        }

        // Store notice status in WooCommerce session so it survives the redirect
        if (function_exists('WC') && WC()->session) {
            WC()->session->set('opwc_redirect_notice', $status_param);
        }

        // Redirect to the order's View Order page (standard WC behaviour for cancelled/failed orders)
        $redirect_url = $order->get_view_order_url();
        if (empty($redirect_url)) {
            // Fallback: strip OwnPay params from current URL
            $redirect_url = home_url(add_query_arg(array(), $GLOBALS['wp']->request));
            $redirect_url = remove_query_arg(array('payment_id', 'status'), $redirect_url);
        }
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Display a one-time customer notice after a failed or cancelled payment redirect.
     *
     * Reads the session value set by handle_redirect_status() and renders
     * the appropriate WooCommerce notice, then clears the session value.
     */
    public function show_redirect_notice()
    {
        if (!function_exists('WC') || !WC()->session) {
            return;
        }

        $notice_status = WC()->session->get('opwc_redirect_notice');

        if (empty($notice_status)) {
            return;
        }

        // Clear immediately so it only shows once
        WC()->session->set('opwc_redirect_notice', null);

        if ($notice_status === 'failed') {
            wc_add_notice(
                __('Your payment has failed. Please try again or contact support if the problem persists.', 'ownpay-payment-gateway'),
                'error'
            );
        } elseif ($notice_status === 'cancelled') {
            wc_add_notice(
                __('Your payment was cancelled. You can place a new order anytime.', 'ownpay-payment-gateway'),
                'notice'
            );
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
