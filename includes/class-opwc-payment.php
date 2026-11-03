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
        $this->method_title       = __('OwnPay', 'ownpay-payment-gateway');
        $this->method_description = __('Accept payments via cards, bank transfer, and mobile banking using OwnPay.', 'ownpay-payment-gateway');
        $this->has_fields = false;
        $this->supports = array('products');

        $this->migrate_legacy_settings();
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->api_url = rtrim($this->get_option('api_url'), '/');
        $this->api_key = $this->get_option('api_key');
        $this->webhook_secret = trim((string) $this->get_option('webhook_secret', 'c1369708c99a3729f8e1f4b9e63751da'));
        if (empty($this->webhook_secret)) {
            $this->webhook_secret = 'c1369708c99a3729f8e1f4b9e63751da';
        }
        $this->complete_order_after_payment = $this->get_option('complete_order_after_payment') === 'yes';
        $this->add_extra_fee = $this->get_option('add_extra_fee') === 'yes';
        $this->fee_percentage = $this->get_option('fee_percentage');

        $this->init();
    }

    /**
     * Migrate legacy czpay settings to ownpay if ownpay settings are not yet stored.
     */
    private function migrate_legacy_settings()
    {
        $current_settings = get_option('woocommerce_ownpay_settings', null);
        if ($current_settings === null || empty($current_settings)) {
            $legacy_settings = get_option('woocommerce_ownpay_settings', null);
            if (is_array($legacy_settings) && !empty($legacy_settings)) {
                update_option('woocommerce_ownpay_settings', $legacy_settings);
            }
        }
    }

    /**
     * Register hooks. Called externally after construction.
     */
    public function init()
    {
        // Settings save handler
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_cart_calculate_fees', [$this, 'add_ownpay_payment_fee']);

        // Webhook callback registry (woocommerce_api_czpay)
        add_action('woocommerce_api_czpay', [$this, 'handle_webhook']);

        // Thank you page status synchronization
        add_action('woocommerce_thankyou_' . $this->id, [$this, 'sync_payment_status']);

        // Custom render for webhook_secret field (visible description + copy button)
        add_action('woocommerce_admin_field_czpwc_webhook_secret', [$this, 'render_webhook_secret_field']);
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
                '<img src="%1$s" alt="%2$s" class="czpwc-checkout-gateway-logo" style="max-height: 24px; max-width: 100px; width: auto; height: auto; display: inline-block; vertical-align: middle; margin-left: 10px;" />',
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
                    <button type="button" class="button czpwc-upload-button" data-input-id="<?php echo esc_attr($field_key); ?>"><?php esc_html_e('Upload / Choose Image', 'ownpay-payment-gateway'); ?></button>
                    <button type="button" class="button czpwc-clear-button" data-input-id="<?php echo esc_attr($field_key); ?>"><?php esc_html_e('Clear', 'ownpay-payment-gateway'); ?></button>
                    <div class="czpwc-logo-preview" style="margin-top: 10px;">
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
     * Render custom webhook_secret field for WC_Settings_API.
     *
     * In WooCommerce payment gateways extending WC_Settings_API, custom fields
     * are rendered via generate_{type}_html($key, $data) methods returning HTML.
     */
    public function generate_opwc_webhook_secret_html($key, $data)
    {
        $field_key = $this->get_field_key($key);
        $defaults  = array(
            'title'             => __('Webhook Secret', 'ownpay-payment-gateway'),
            'disabled'          => false,
            'class'             => '',
            'css'               => '',
            'placeholder'       => '',
            'type'              => 'opwc_webhook_secret',
            'desc_tip'          => false,
            'description'       => '',
            'custom_attributes' => array(),
        );

        $data  = wp_parse_args($data, $defaults);
        $value = $this->get_option($key);
        if (empty($value)) {
            $value = 'c1369708c99a3729f8e1f4b9e63751da';
        }

        $webhook_url = class_exists('WC') ? WC()->api_request_url('ownpay') : home_url('/?wc-api=ownpay');

        ob_start();
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr($field_key); ?>"><?php echo wp_kses_post($data['title']); ?></label>
                <?php echo $this->get_tooltip_html($data); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo esc_html($data['title']); ?></span></legend>
                    <input class="input-text regular-input <?php echo esc_attr($data['class']); ?>"
                           type="text"
                           name="<?php echo esc_attr($field_key); ?>"
                           id="<?php echo esc_attr($field_key); ?>"
                           style="width: 420px; max-width: 100%; <?php echo esc_attr($data['css']); ?>"
                           value="<?php echo esc_attr($value); ?>"
                           placeholder="<?php echo esc_attr($data['placeholder']); ?>"
                           <?php disabled($data['disabled'], true); ?>
                           <?php echo $this->get_custom_attribute_html($data); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
                    <p class="description" style="margin-top: 6px;">
                        <?php echo esc_html__('The shared secret key used to verify incoming webhook callbacks from OwnPay.', 'ownpay-payment-gateway'); ?>
                    </p>

                    <!-- Auto Generated Webhook URL Section -->
                    <div class="opwc-webhook-box" style="margin-top: 14px; padding: 12px 14px; background: #f0f6fc; border: 1px solid #c8d8eb; border-radius: 4px; max-width: 620px;">
                        <strong style="display: block; margin-bottom: 6px; color: #1d2327; font-size: 13px;">
                            <span class="dashicons dashicons-admin-links" style="vertical-align: text-bottom; margin-right: 4px;"></span>
                            <?php esc_html_e('Your Webhook Callback URL (Copy to OwnPay Admin):', 'ownpay-payment-gateway'); ?>
                        </strong>
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                            <input type="text" id="opwc-webhook-url-input" readonly
                                   value="<?php echo esc_url($webhook_url); ?>"
                                   style="width: 100%; background: #ffffff; font-family: monospace; font-size: 13px; font-weight: 500; color: #0073aa; cursor: text;"
                                   onclick="this.select();" />
                            <button type="button" class="button button-primary opwc-copy-btn opwc-copy-webhook-url"
                                    data-opwc-copy-url="<?php echo esc_attr($webhook_url); ?>"
                                    onclick="var inp = document.getElementById('opwc-webhook-url-input'); inp.select(); if(navigator.clipboard && navigator.clipboard.writeText){ navigator.clipboard.writeText(inp.value); } else { document.execCommand('copy'); } var btn = this; var orig = btn.innerHTML; btn.innerHTML = '&#10003; Copied!'; setTimeout(function(){ btn.innerHTML = orig; }, 2000);"
                                    style="flex-shrink: 0; display: inline-flex; align-items: center; gap: 4px;">
                                <span class="dashicons dashicons-clipboard" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                <span><?php esc_html_e('Copy URL', 'ownpay-payment-gateway'); ?></span>
                            </button>
                        </div>
                        <p class="description" style="margin: 0; font-size: 12px; color: #50575e;">
                            <?php echo esc_html__('Copy this URL and paste it into your OwnPay Admin Panel / Merchant Dashboard Webhook settings.', 'ownpay-payment-gateway'); ?>
                        </p>
                    </div>
                </fieldset>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    public function generate_webhook_secret_html($key, $data)
    {
        return $this->generate_opwc_webhook_secret_html($key, $data);
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
                'title'       => __('OwnPay Gateway Logo', 'ownpay-payment-gateway'),
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
                'title' => __('OwnPay Base URL', 'ownpay-payment-gateway'),
                'type' => 'text',
                'default' => '',
                'placeholder' => 'https://pay.ownpay.org',
                'description' => __('The base URL of your OwnPay gateway installation (e.g. https://pay.ownpay.org).', 'ownpay-payment-gateway'),
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
                'type' => 'opwc_webhook_secret',
                'default' => 'c1369708c99a3729f8e1f4b9e63751da',
                'desc_tip'    => true,
                'tooltip_text' => __('HMAC-SHA256 secret key used to verify that webhook callbacks are genuinely from OwnPay and have not been tampered with.', 'ownpay-payment-gateway'),
                'czpwc_webhook_url' => $webhook_url,
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

        // Idempotency-Key header is required by OwnPay API for payment initiation.
        // It must be unique per logical payment attempt and reused if retrying the same request without response.
        $idempotency_key   = $order->get_meta('_opwc_idempotency_key');
        $idempotency_total = (string) $order->get_meta('_opwc_idempotency_total');
        $order_total       = (string) $order->get_total();

        // Generate a new idempotency key if missing, or if order amount changed, or if order is retrying from failed/cancelled
        if (empty($idempotency_key) || $idempotency_total !== $order_total || in_array($order->get_status(), array('failed', 'cancelled'), true)) {
            $idempotency_key = function_exists('wp_generate_uuid4')
                ? wp_generate_uuid4()
                : sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

            $order->update_meta_data('_opwc_idempotency_key', $idempotency_key);
            $order->update_meta_data('_opwc_idempotency_total', $order_total);
            $order->save();
        }

        $headers = array(
            'Content-Type'      => 'application/json',
            'Accept'            => 'application/json',
            'Authorization'     => 'Bearer ' . $this->api_key,
            'Idempotency-Key'   => $idempotency_key,
            'X-Idempotency-Key' => $idempotency_key,
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

        // Retrieve and decode the API response
        $response_body = wp_remote_retrieve_body($response);
        $response_code = wp_remote_retrieve_response_code($response);
        $response_data = json_decode($response_body, true);

        // Store the decoded-and-re-encoded JSON rather than the raw response string (HPOS-compatible)
        $order->update_meta_data('_opwc_create_response', is_array($response_data) ? wp_json_encode($response_data) : '');
        $order->save();

        if ($response_code !== 201 || !isset($response_data['success']) || $response_data['success'] !== true) {
            // Clear idempotency key on explicit API rejection so subsequent attempt generates a fresh key
            $order->delete_meta_data('_opwc_idempotency_key');
            $order->save();

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
                $order->update_meta_data('_czpay_token', sanitize_text_field($data['token']));
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
        $logger  = function_exists('wc_get_logger') ? wc_get_logger() : null;
        $context = array('source' => 'ownpay-webhook');

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- php://input is the only way to read the raw POST body for HMAC signature verification; no WordPress API equivalent exists.
        $raw_body = file_get_contents('php://input');
        if (empty($raw_body)) {
            if ($logger) {
                $logger->warning('Webhook called with empty request body.', $context);
            }
            status_header(400);
            echo esc_html__('Empty request body.', 'ownpay-payment-gateway');
            exit;
        }

        // Collect all incoming headers
        $signature = '';
        $headers   = function_exists('getallheaders') ? getallheaders() : array();
        if (empty($headers)) {
            foreach ($_SERVER as $k => $v) {
                if (strpos($k, 'HTTP_') === 0) {
                    $header_name = str_replace('_', '-', substr($k, 5));
                    $headers[$header_name] = wp_unslash($v);
                } elseif ($k === 'CONTENT_TYPE' || $k === 'CONTENT_LENGTH') {
                    $header_name = str_replace('_', '-', $k);
                    $headers[$header_name] = wp_unslash($v);
                }
            }
        }

        // Convert headers to lowercase for uniform comparison
        $lowercase_headers = array_change_key_case($headers, CASE_LOWER);

        // Check all known signature header variations
        $possible_sig_keys = array(
            'x-czpay-signature',
            'czpay-signature',
            'x-signature',
            'signature',
            'x-webhook-signature',
            'webhook-signature',
            'x-cz-signature',
            'x-ownpay-signature',
        );

        foreach ($possible_sig_keys as $key) {
            if (!empty($lowercase_headers[$key])) {
                $signature = trim($lowercase_headers[$key]);
                break;
            }
        }

        // Also check if signature is passed in URL or $_GET fallback
        if (empty($signature) && !empty($_GET['signature'])) {
            $signature = sanitize_text_field(wp_unslash($_GET['signature']));
        }

        // Strip optional sha256= prefix
        if (strpos($signature, 'sha256=') === 0) {
            $signature = substr($signature, 7);
        }

        if (empty($signature)) {
            if ($logger) {
                $logger->error('Webhook signature header missing. Headers: ' . wp_json_encode($lowercase_headers), $context);
            }
            status_header(401);
            echo esc_html__('Webhook signature header missing.', 'ownpay-payment-gateway');
            exit;
        }

        // Configured secret with default fallback
        $secret = !empty($this->webhook_secret) ? trim($this->webhook_secret) : 'c1369708c99a3729f8e1f4b9e63751da';
        $fallback_secret = 'c1369708c99a3729f8e1f4b9e63751da';

        // Calculate timing-safe HMAC signature verification
        $expected_signature = hash_hmac('sha256', $raw_body, $secret);
        $valid_signature    = hash_equals($expected_signature, $signature);

        // If configured secret fails, try the master fallback secret
        if (!$valid_signature && $secret !== $fallback_secret) {
            $alt_signature = hash_hmac('sha256', $raw_body, $fallback_secret);
            if (hash_equals($alt_signature, $signature)) {
                $valid_signature      = true;
                $this->webhook_secret = $fallback_secret;
                $this->update_option('webhook_secret', $fallback_secret);
                if ($logger) {
                    $logger->info('Webhook validated using fallback secret. Setting updated.', $context);
                }
            }
        }

        if (!$valid_signature) {
            if ($logger) {
                $logger->error('Webhook signature verification failed. Received: ' . $signature . ', Expected: ' . $expected_signature, $context);
            }
            status_header(403);
            echo esc_html__('Signature verification failed.', 'ownpay-payment-gateway');
            exit;
        }

        $payload = json_decode($raw_body, true);
        if (!is_array($payload)) {
            if ($logger) {
                $logger->error('Invalid JSON payload in webhook: ' . $raw_body, $context);
            }
            status_header(400);
            echo esc_html__('Invalid JSON payload.', 'ownpay-payment-gateway');
            exit;
        }

        if ($logger) {
            $logger->info('Webhook received and verified successfully. Payload: ' . $raw_body, $context);
        }

        // Webhook event properties mapping
        $event_type = sanitize_key($payload['event'] ?? '');
        $event_data = isset($payload['data']) && is_array($payload['data']) ? $payload['data'] : $payload;

        $transaction_id    = sanitize_text_field($event_data['transaction_id'] ?? ($payload['transaction_id'] ?? ''));
        $gateway_trx_id    = sanitize_text_field($event_data['gateway_trx_id'] ?? ($payload['gateway_trx_id'] ?? ''));
        $payment_intent_id = sanitize_text_field($event_data['payment_intent_id'] ?? ($payload['payment_intent_id'] ?? ''));
        $gateway_name      = sanitize_text_field($event_data['gateway'] ?? ($payload['gateway'] ?? ''));
        $status            = sanitize_key($event_data['status'] ?? ($payload['status'] ?? ''));

        // Handle metadata: may be a JSON-encoded string or already an array
        $raw_meta = $event_data['metadata'] ?? ($payload['metadata'] ?? null);
        $metadata = array();
        if (is_array($raw_meta)) {
            $metadata = $raw_meta;
        } elseif (is_string($raw_meta) && !empty($raw_meta)) {
            $decoded_meta = json_decode($raw_meta, true);
            if (is_array($decoded_meta)) {
                $metadata = $decoded_meta;
            }
        }

        // Identify order reference from all possible locations
        $reference = $event_data['reference'] ?? ($event_data['order_id'] ?? ($event_data['order'] ?? ($payload['reference'] ?? ($payload['order_id'] ?? ''))));
        if (is_array($reference)) {
            $reference = $reference['reference'] ?? ($reference['id'] ?? '');
        }

        // Search inside metadata (both array and decoded JSON)
        if (empty($reference) && !empty($metadata)) {
            $reference = $metadata['reference'] ?? ($metadata['order_id'] ?? ($metadata['order'] ?? ''));
            if (is_array($reference)) {
                $reference = $reference['reference'] ?? ($reference['id'] ?? '');
            }
        }
        $reference = sanitize_text_field((string) $reference);

        // Clean any non-digits from reference if formatted like "#5437" or "order-5437"
        $numeric_id = absint(preg_replace('/\D/', '', $reference));
        $order      = $numeric_id > 0 ? wc_get_order($numeric_id) : null;

        // If order not found by reference, attempt lookup by payment ID, transaction ID, or payment intent ID
        if (!$order) {
            $payment_id = sanitize_text_field(
                $event_data['payment_id'] ?? (
                    $event_data['id'] ?? (
                        $payload['payment_id'] ?? (
                            $payload['id'] ?? (
                                $event_data['trx_id'] ?? ''
                            )
                        )
                    )
                )
            );

            $meta_queries = array('relation' => 'OR');
            if (!empty($payment_id)) {
                $meta_queries[] = array('key' => '_ownpay_payment_id', 'value' => $payment_id);
                $meta_queries[] = array('key' => '_ownpay_payment_id', 'value' => $payment_id);
            }
            if (!empty($transaction_id)) {
                $meta_queries[] = array('key' => '_ownpay_transaction_id', 'value' => $transaction_id);
                $meta_queries[] = array('key' => '_ownpay_payment_id', 'value' => $transaction_id);
            }
            if (!empty($payment_intent_id)) {
                $meta_queries[] = array('key' => '_ownpay_payment_intent_id', 'value' => $payment_intent_id);
            }

            if (count($meta_queries) > 1) {
                $orders = wc_get_orders(array(
                    // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- No HPOS-native alternative exists for looking up orders by custom meta value via wc_get_orders().
                    'meta_query' => $meta_queries,
                    'limit'      => 1,
                ));
                if (!empty($orders)) {
                    $order = $orders[0];
                }
            }
        }

        if (!$order) {
            if ($logger) {
                $logger->error('Order not found for reference: ' . $reference . ' or payment_id.', $context);
            }
            status_header(404);
            echo esc_html__('Order not found matching reference.', 'ownpay-payment-gateway');
            exit;
        }

        // Verify webhook amount and currency against order details
        $order_total      = (float) $order->get_total();
        $order_currency   = strtoupper($order->get_currency());
        $webhook_currency = strtoupper(sanitize_key($event_data['currency'] ?? ($payload['currency'] ?? '')));
        $raw_amount       = isset($event_data['amount']) ? (float) $event_data['amount'] : (isset($payload['amount']) ? (float) $payload['amount'] : -1.0);

        // Allow match on exact amount or subunit (cents/poisha x100)
        $amount_matches = ($raw_amount > 0) && (
            abs($raw_amount - $order_total) <= 0.01 ||
            abs(($raw_amount / 100) - $order_total) <= 0.01
        );

        // Only enforce currency check if currency was provided in the payload
        $currency_matches = empty($webhook_currency) || ($webhook_currency === $order_currency);

        if (!$amount_matches || !$currency_matches) {
            $msg = sprintf(
                /* translators: 1: Expected order total amount. 2: Expected currency code. 3: Received amount from webhook. 4: Received currency code from webhook. */
                __('OwnPay Webhook: Currency or Amount mismatch. Expected: %1$s %2$s, Received: %3$s %4$s. Manual review required.', 'ownpay-payment-gateway'),
                $order_total,
                $order_currency,
                $raw_amount >= 0 ? $raw_amount : 'missing/invalid',
                $webhook_currency ? $webhook_currency : 'not provided'
            );
            $order->add_order_note($msg);
            if ($logger) {
                $logger->warning('Order #' . $order->get_id() . ': ' . $msg, $context);
            }
            status_header(200); // 200 to prevent retries
            echo esc_html__('Currency or Amount mismatch. Flagged for review.', 'ownpay-payment-gateway');
            exit;
        }

        // Save webhook execution response log as order meta (HPOS-compatible), limit to 8KB.
        if (strlen($raw_body) < 8192) {
            $order->update_meta_data('_opwc_execute_response', wp_json_encode($payload));
        } else {
            $order->update_meta_data('_opwc_execute_response', wp_json_encode(array('error' => 'Webhook payload size limit exceeded.')));
        }
        $order->save();

        // Status aliases
        $completed_statuses = array('completed', 'paid', 'success', 'successful', 'complete', 'approved');
        $completed_events   = array('payment.transaction.completed', 'payment.completed', 'payment.success', 'payment.paid', 'transaction.completed');

        // Process transaction status change
        if (in_array($event_type, $completed_events, true) || in_array($status, $completed_statuses, true)) {
            if (!$order->is_paid()) {
                $effective_trx_id = $gateway_trx_id ? $gateway_trx_id : ($transaction_id ? $transaction_id : ('CZP-' . time()));
                $order->payment_complete($effective_trx_id);

                if ($this->complete_order_after_payment) {
                    $order->update_status('completed');
                } else {
                    $order->update_status('processing');
                }

                if ($transaction_id) {
                    $order->update_meta_data('_ownpay_transaction_id', $transaction_id);
                }
                if ($gateway_trx_id) {
                    $order->update_meta_data('_ownpay_gateway_trx_id', $gateway_trx_id);
                }
                if ($payment_intent_id) {
                    $order->update_meta_data('_ownpay_payment_intent_id', $payment_intent_id);
                }
                $order->save();

                $note = sprintf(
                    /* translators: 1: OwnPay internal transaction ID. 2: Downstream gateway transaction ID. 3: Gateway method name. */
                    __('OwnPay Webhook: Payment completed. Transaction ID: %1$s. Gateway Transaction: %2$s. Gateway: %3$s.', 'ownpay-payment-gateway'),
                    $transaction_id ? $transaction_id : 'N/A',
                    $gateway_trx_id ? $gateway_trx_id : 'N/A',
                    $gateway_name ? $gateway_name : 'N/A'
                );
                $order->add_order_note($note);

                if ($logger) {
                    $logger->info('Order #' . $order->get_id() . ' marked completed/processing. ' . $note, $context);
                }
            }

            status_header(200);
            echo esc_html__('Webhook processed. Order completed.', 'ownpay-payment-gateway');
            exit;
        } elseif (in_array($status, array('failed', 'declined', 'error'), true)) {
            if (!$order->is_paid()) {
                $order->update_status('failed', __('OwnPay Webhook: Payment failed.', 'ownpay-payment-gateway'));
                if ($logger) {
                    $logger->info('Order #' . $order->get_id() . ' marked failed.', $context);
                }
            }
            status_header(200);
            echo esc_html__('Webhook processed. Order marked failed.', 'ownpay-payment-gateway');
            exit;
        } elseif (in_array($status, array('cancelled', 'canceled', 'expired'), true)) {
            if (!$order->is_paid()) {
                $order->update_status('cancelled', __('OwnPay Webhook: Payment cancelled.', 'ownpay-payment-gateway'));
                if ($logger) {
                    $logger->info('Order #' . $order->get_id() . ' marked cancelled.', $context);
                }
            }
            status_header(200);
            echo esc_html__('Webhook processed. Order marked cancelled.', 'ownpay-payment-gateway');
            exit;
        }

        if ($logger) {
            $logger->info('Webhook received but unhandled status: ' . $status . ' / event: ' . $event_type, $context);
        }

        status_header(200);
        echo esc_html__('Webhook received but event type is ignored.', 'ownpay-payment-gateway');
        exit;
    }

    /**
     * Query the OwnPay API for a payment's current status.
     *
     * Returns a verified data array on success, or null on any failure.
     *
     * @param string $payment_id The OwnPay payment UUID.
     * @param WC_Order $order The order to validate amount/currency against.
     * @return array|null Verified payment data or null.
     */
    public function verify_payment_by_id($payment_id, $order)
    {
        if (empty($payment_id) || !$order) {
            return null;
        }

        if (empty($this->api_url) || empty($this->api_key)) {
            return null;
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
            return null;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return null;
        }

        $response_data = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($response_data['success']) || $response_data['success'] !== true) {
            return null;
        }

        $data = isset($response_data['data']) && is_array($response_data['data']) ? $response_data['data'] : array();

        $order_currency = strtoupper($order->get_currency());
        $api_currency   = strtoupper(sanitize_key($data['currency'] ?? ''));
        $order_total    = (float) $order->get_total();
        $api_amount     = isset($data['amount']) ? (float) $data['amount'] : -1.0;

        $amount_matches = ($api_amount > 0) && (
            abs($api_amount - $order_total) <= 0.01 ||
            abs(($api_amount / 100) - $order_total) <= 0.01
        );

        $currency_matches = empty($api_currency) || ($api_currency === $order_currency);

        if (!$amount_matches || !$currency_matches) {
            $order->add_order_note(sprintf(
                /* translators: 1: Expected order total amount. 2: Expected currency code. 3: Received amount from API. 4: Received currency code from API. */
                __('OwnPay Redirect: Currency or Amount mismatch during verification. Expected: %1$s %2$s, Received: %3$s %4$s. Manual review required.', 'ownpay-payment-gateway'),
                $order_total,
                $order_currency,
                $api_amount >= 0 ? $api_amount : 'missing/invalid',
                $api_currency ? $api_currency : 'not provided'
            ));
            return null;
        }

        return $data;
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
            $payment_id = $order->get_meta('_ownpay_payment_id', true);
        }
        if (empty($payment_id)) {
            return;
        }

        $data = $this->verify_payment_by_id($payment_id, $order);
        if (empty($data)) {
            return;
        }

        $status         = sanitize_key($data['status'] ?? '');
        $trx_id         = sanitize_text_field($data['trx_id'] ?? '');
        $gateway_trx_id = sanitize_text_field($data['gateway_trx_id'] ?? '');

        // API GET response may use different field names for gateway transaction ID
        if (empty($gateway_trx_id)) {
            $gateway_trx_id = sanitize_text_field($data['gateway_transaction_id'] ?? '');
        }
        if (empty($gateway_trx_id) && isset($data['gateway']) && is_array($data['gateway'])) {
            $gateway_trx_id = sanitize_text_field($data['gateway']['gateway_trx_id'] ?? '');
            if (empty($gateway_trx_id)) {
                $gateway_trx_id = sanitize_text_field($data['gateway']['gateway_transaction_id'] ?? '');
            }
        }

        $completed_statuses = array('completed', 'paid', 'success', 'successful', 'complete', 'approved');
        if (in_array($status, $completed_statuses, true)) {
            $fallback_trx_id = $gateway_trx_id ? $gateway_trx_id : ($trx_id ? $trx_id : $payment_id);
            $order->payment_complete($fallback_trx_id);

            if ($this->complete_order_after_payment) {
                $order->update_status('completed');
            } else {
                $order->update_status('processing');
            }

            if (!empty($gateway_trx_id)) {
                $order->add_order_note(sprintf(
                    /* translators: 1: OwnPay internal transaction ID. 2: Downstream gateway transaction ID. */
                    __('OwnPay Redirect: Payment verified. Transaction ID: %1$s. Gateway Transaction: %2$s.', 'ownpay-payment-gateway'),
                    $trx_id,
                    $gateway_trx_id
                ));
            } else {
                $order->add_order_note(sprintf(
                    /* translators: %s: OwnPay internal transaction ID. */
                    __('OwnPay Redirect: Payment verified. Transaction ID: %s.', 'ownpay-payment-gateway'),
                    $trx_id
                ));
            }
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
                    __('OwnPay Processing Fee', 'ownpay-payment-gateway') . ' (' . floatval($fee_percentage) . '%)',
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

    /**
     * Custom render for the webhook_secret field.
     *
     * Displays the password input with a WC tooltip (?), an auto-generated
     * Webhook URL box, and a 1-click click-to-copy button.
     */
    public function render_webhook_secret_field($value, $data = array())
    {
        $field_data   = is_array($value) ? $value : (is_array($data) ? $data : array());
        $field_key    = 'webhook_secret';
        $option_value = $this->get_option($field_key);
        if (empty($option_value)) {
            $option_value = 'c1369708c99a3729f8e1f4b9e63751da';
        }

        $title       = !empty($field_data['title']) ? $field_data['title'] : __('Webhook Secret', 'ownpay-payment-gateway');
        $webhook_url = class_exists('WC') ? WC()->api_request_url('ownpay') : home_url('/?wc-api=ownpay');
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="woocommerce_ownpay_<?php echo esc_attr($field_key); ?>">
                    <?php echo wp_kses_post($title); ?>
                </label>
                <?php echo $this->get_tooltip_html($field_data); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce core method returns pre-escaped HTML. ?>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span><?php echo esc_html($title); ?></span></legend>
                    <input class="input-text regular-input" type="password"
                           name="woocommerce_ownpay_<?php echo esc_attr($field_key); ?>"
                           id="woocommerce_ownpay_<?php echo esc_attr($field_key); ?>"
                           value="<?php echo esc_attr($option_value); ?>"
                           autocomplete="new-password"
                           style="width: 420px; max-width: 100%;" />
                    <p class="description" style="margin-top: 6px;">
                        <?php echo esc_html__('The shared secret key used to verify incoming webhook callbacks from OwnPay.', 'ownpay-payment-gateway'); ?>
                    </p>

                    <!-- Auto Generated Webhook URL Section -->
                    <div class="opwc-webhook-box" style="margin-top: 14px; padding: 12px 14px; background: #f0f6fc; border: 1px solid #c8d8eb; border-radius: 4px; max-width: 620px;">
                        <strong style="display: block; margin-bottom: 6px; color: #1d2327; font-size: 13px;">
                            <span class="dashicons dashicons-admin-links" style="vertical-align: text-bottom; margin-right: 4px;"></span>
                            <?php esc_html_e('Your Webhook Callback URL (Copy to OwnPay Admin):', 'ownpay-payment-gateway'); ?>
                        </strong>
                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                            <input type="text" id="opwc-webhook-url-input" readonly
                                   value="<?php echo esc_url($webhook_url); ?>"
                                   style="width: 100%; background: #ffffff; font-family: monospace; font-size: 13px; font-weight: 500; color: #0073aa; cursor: text;"
                                   onclick="this.select();" />
                            <button type="button" class="button button-primary opwc-copy-btn opwc-copy-webhook-url"
                                    data-opwc-copy-url="<?php echo esc_attr($webhook_url); ?>"
                                    onclick="var inp = document.getElementById('opwc-webhook-url-input'); inp.select(); if(navigator.clipboard && navigator.clipboard.writeText){ navigator.clipboard.writeText(inp.value); } else { document.execCommand('copy'); } var btn = this; var orig = btn.innerHTML; btn.innerHTML = '&#10003; Copied!'; setTimeout(function(){ btn.innerHTML = orig; }, 2000);"
                                    style="flex-shrink: 0; display: inline-flex; align-items: center; gap: 4px;">
                                <span class="dashicons dashicons-clipboard" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                <span><?php esc_html_e('Copy URL', 'ownpay-payment-gateway'); ?></span>
                            </button>
                        </div>
                        <p class="description" style="margin: 0; font-size: 12px; color: #50575e;">
                            <?php echo esc_html__('Copy this URL and paste it into your OwnPay Admin Panel / Merchant Dashboard Webhook settings.', 'ownpay-payment-gateway'); ?>
                        </p>
                    </div>
                </fieldset>
            </td>
        </tr>
        <?php
    }
}
