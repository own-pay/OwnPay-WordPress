<?php
if (!defined('ABSPATH')) exit;
?>
<div class="payment-table-wrapper">
    <input type="hidden" id="opwc_filter_nonce_field" value="<?php echo esc_attr($nonce); ?>">
    <div class="table-header my-2">
        <div>
            <div class="filter-actions">
                <?php
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified in OPWC_Payment_List::get_query_filters() before this template is included. These reads are read-only for pre-selecting the UI state.
                $opwc_current_key   = isset($_GET['key'])   ? sanitize_key(wp_unslash($_GET['key']))   : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $opwc_current_value = isset($_GET['value']) ? sanitize_key(wp_unslash($_GET['value'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                ?>
                <select name="filter_key" id="filter_key">
                    <option value="date" <?php selected($opwc_current_key, 'date'); ?>><?php esc_html_e('By Order Date', 'ownpay-payment-gateway') ?></option>
                    <option value="status" <?php selected($opwc_current_key, 'status'); ?>><?php esc_html_e('By Order Status', 'ownpay-payment-gateway') ?></option>
                </select>
                <select name="date_filter_value" class="" id="date_filter">
                    <option value="desc" <?php selected($opwc_current_value, 'desc'); ?>><?php echo esc_html('DESC'); ?></option>
                    <option value="asc" <?php selected($opwc_current_value, 'asc'); ?>><?php echo esc_html('ASC'); ?></option>
                </select>
                <select name="status_filter_value" class="optional_field" id="status_filter">
                    <option value="pending" <?php selected($opwc_current_value, 'pending'); ?>><?php esc_html_e('Pending Payment', 'ownpay-payment-gateway'); ?></option>
                    <option value="processing" <?php selected($opwc_current_value, 'processing'); ?>><?php esc_html_e('Processing', 'ownpay-payment-gateway'); ?></option>
                    <option value="completed" <?php selected($opwc_current_value, 'completed'); ?>><?php esc_html_e('Completed', 'ownpay-payment-gateway'); ?></option>
                    <option value="on-hold" <?php selected($opwc_current_value, 'on-hold'); ?>><?php esc_html_e('On Hold', 'ownpay-payment-gateway'); ?></option>
                    <option value="failed" <?php selected($opwc_current_value, 'failed'); ?>><?php esc_html_e('Failed', 'ownpay-payment-gateway'); ?></option>
                    <option value="refunded" <?php selected($opwc_current_value, 'refunded'); ?>><?php esc_html_e('Refunded', 'ownpay-payment-gateway'); ?></option>
                    <option value="cancelled" <?php selected($opwc_current_value, 'cancelled'); ?>><?php esc_html_e('Cancelled', 'ownpay-payment-gateway'); ?></option>
                </select>
                <button type="submit" name="search" id="filter" class="button action"><?php esc_html_e('Filter', 'ownpay-payment-gateway') ?></button>
            </div>
        </div>
    </div>
    <table class="wp-list-table text-center widefat fixed striped table-view-list customer-table">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-id"><?php esc_html_e('Order ID', 'ownpay-payment-gateway') ?></th>
                <th scope="col" class="manage-column column-username"><?php esc_html_e('Customer Username', 'ownpay-payment-gateway') ?></th>
                <th scope="col" class="manage-column column-email"><?php esc_html_e('Customer Email', 'ownpay-payment-gateway') ?></th>
                <th scope="col" class="manage-column column-status"><?php esc_html_e('Status', 'ownpay-payment-gateway') ?></th>
                <th scope="col" class="manage-column column-total"><?php esc_html_e('Total Amount', 'ownpay-payment-gateway') ?></th>
                <th scope="col" class="manage-column column-date"><?php esc_html_e('Order Date', 'ownpay-payment-gateway') ?></th>
                <th scope="col" class="manage-column column-response"><?php esc_html_e('OwnPay API Details', 'ownpay-payment-gateway') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (is_array($payments_details) && !empty($payments_details)) :
                // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- These are local template variables extracted from $payment_details in a template include, not global-scope declarations.
                foreach ($payments_details as $payment_details) :
                    $order_id = $payment_details['order_id'];
                    $order_link = class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && method_exists('\Automattic\WooCommerce\Utilities\OrderUtil', 'get_order_admin_edit_url')
                        ? \Automattic\WooCommerce\Utilities\OrderUtil::get_order_admin_edit_url($order_id)
                        : admin_url('post.php?post=' . $order_id . '&action=edit');
                    $order_id_html = '<a href="' . esc_url($order_link) . '" target="_blank">#' . absint($order_id) . '</a>';
                    $username = strval($payment_details['username']);
                    $email = strval($payment_details['email']);
                    $status = strval($payment_details['status']);
                    $total_amount = strval($payment_details['total_amount']);
                    $order_date = strval($payment_details['order_date']);
                    // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
            ?>
                    <tr>
                        <td class="column-id"><?php echo wp_kses_post($order_id_html) ?></td>
                        <td class="column-username"><?php echo esc_html($username) ?></td>
                        <td class="column-email"><?php echo esc_html($email) ?></td>
                        <td class="column-status"><?php echo esc_html($status) ?></td>
                        <td class="column-total"><?php echo esc_html($total_amount) ?></td>
                        <td class="column-date"><?php echo esc_html($order_date) ?></td>
                        <td class="column-response">
                            <button type="button" class="button" data-opwc-modal="<?php echo esc_attr('#opwc-modal-' . absint($order_id)) ?>">
                                <?php esc_html_e('View Response', 'ownpay-payment-gateway') ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach;
                ?>
            <?php else :
            ?>
                <tr>
                    <td class="opwc-text-center opwc-text-danger" colspan="7"><?php esc_html_e('No payments found!', 'ownpay-payment-gateway') ?></td>
                </tr>
            <?php endif;
            ?>
        </tbody>
    </table>
    <?php if (is_array($payments_details) && !empty($payments_details)) :
        // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local template variables in a template include, not global declarations.
        foreach ($payments_details as $payment_details) :
            $order_id = $payment_details['order_id'];
            $create_payment_response = $payment_details['create_response'] ?? '';
            $create_decoded = !empty($create_payment_response) ? json_decode($create_payment_response, true) : null;
            $create_payment_formatted = is_array($create_decoded) ? wp_json_encode($create_decoded, JSON_PRETTY_PRINT) : 'N/A';

            $execute_payment_response = $payment_details['execute_response'] ?? '';
            $execute_decoded = !empty($execute_payment_response) ? json_decode($execute_payment_response, true) : null;
            $execute_payment_formatted = is_array($execute_decoded) ? wp_json_encode($execute_decoded, JSON_PRETTY_PRINT) : 'N/A';
            // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
    ?>
            <div class="opwc-modal-overlay" id="<?php echo esc_attr('opwc-modal-' . absint($order_id)) ?>">
                <div class="opwc-modal">
                    <div class="opwc-modal-header">
                        <p><?php esc_html_e('OwnPay API Details', 'ownpay-payment-gateway'); ?></p>
                        <button type="button" class="opwc-modal-close" aria-label="Close">&times;</button>
                    </div>
                    <div class="opwc-modal-body">
                        <div class="opwc-modal-columns">
                            <div class="opwc-modal-column">
                                <p><?php esc_html_e('Payment Initiation Response', 'ownpay-payment-gateway') ?></p>
                                <textarea rows="18" disabled name="create-response"><?php echo esc_textarea($create_payment_formatted) ?></textarea>
                            </div>
                            <div class="opwc-modal-column">
                                <p><?php esc_html_e('Webhook Call Payload', 'ownpay-payment-gateway') ?></p>
                                <textarea rows="18" disabled name="execute-response"><?php echo esc_textarea($execute_payment_formatted) ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="opwc-modal-footer">
                        <button type="button" class="button"><?php esc_html_e('Close', 'ownpay-payment-gateway') ?></button>
                    </div>
                </div>
            </div>
    <?php
        endforeach;
    endif; ?>
    <div class="footer-actions">
        <div class="pagination mt-2">
            <?php
            if ($total_pages > 1) {
                // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local template variables for pagination, not global declarations.
                $current_page = max(1, $paged);

                $pagination_link = paginate_links(array(
                    'base' => add_query_arg('paged', '%#%', esc_url($pagination_base_url)),
                    'format' => '',
                    'current' => (int) $current_page,
                    'total'   => (int) $total_pages,
                ));
                // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound

                if ($pagination_link) {
                    echo wp_kses_post($pagination_link);
                }
            }
            ?>
        </div>
    </div>
</div>