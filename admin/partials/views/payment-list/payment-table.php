<?php
if (!defined('ABSPATH')) exit;
?>
<div class="payment-table-wrapper">
    <div class="table-header my-2">
        <div class="align-items-center">
            <div class="filter-actions">
                <select name="filter_key" id="filter_key">
                    <option value="date" <?php isset($_GET['key']) && sanitize_text_field(wp_unslash($_GET['key'])) == 'date' ? print 'selected' : '' ?>><?php esc_html_e('By Order Date', 'ownpay-wordpress') ?></option>
                    <option value="status" <?php isset($_GET['key']) && sanitize_text_field(wp_unslash($_GET['key'])) == 'status' ? print 'selected' : '' ?>><?php esc_html_e('By Order Status', 'ownpay-wordpress') ?></option>
                </select>
                <select name="date_filter_value" class="" id="date_filter">
                    <option value="desc" <?php isset($_GET['value']) && sanitize_text_field(wp_unslash($_GET['value'])) == 'desc' ? print 'selected' : '' ?>><?php echo 'DESC'; ?></option>
                    <option value="asc" <?php isset($_GET['value']) && sanitize_text_field(wp_unslash($_GET['value'])) == 'asc' ? print 'selected' : '' ?>><?php echo 'ASC'; ?></option>
                </select>
                <select name="status_filter_value" class="optional_field" id="status_filter">
                    <option value="processing" <?php isset($_GET['value']) && sanitize_text_field(wp_unslash($_GET['value'])) == 'processing' ? print 'selected' : '' ?>><?php esc_html_e('Processing', 'ownpay-wordpress'); ?></option>
                    <option value="failed" <?php isset($_GET['value']) && sanitize_text_field(wp_unslash($_GET['value'])) == 'failed' ? print 'selected' : '' ?>><?php esc_html_e('Failed', 'ownpay-wordpress'); ?></option>
                    <option value="refunded" <?php isset($_GET['value']) && sanitize_text_field(wp_unslash($_GET['value'])) == 'refunded' ? print 'selected' : '' ?>><?php esc_html_e('Refunded', 'ownpay-wordpress'); ?></option>
                    <option value="cancelled" <?php isset($_GET['value']) && sanitize_text_field(wp_unslash($_GET['value'])) == 'cancelled' ? print 'selected' : '' ?>><?php esc_html_e('Cancelled', 'ownpay-wordpress'); ?></option>
                </select>
                <button type="submit" name="search" id="filter" class="button action"><?php esc_html_e('Filter', 'ownpay-wordpress') ?></button>
            </div>
        </div>
    </div>
    <table class="wp-list-table text-center widefat fixed striped table-view-list customer-table">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-id"><?php esc_html_e('Order ID', 'ownpay-wordpress') ?></th>
                <th scope="col" class="manage-column column-username"><?php esc_html_e('Customer Username', 'ownpay-wordpress') ?></th>
                <th scope="col" class="manage-column column-email"><?php esc_html_e('Customer Email', 'ownpay-wordpress') ?></th>
                <th scope="col" class="manage-column column-status"><?php esc_html_e('Status', 'ownpay-wordpress') ?></th>
                <th scope="col" class="manage-column column-total"><?php esc_html_e('Total Amount', 'ownpay-wordpress') ?></th>
                <th scope="col" class="manage-column column-date"><?php esc_html_e('Order Date', 'ownpay-wordpress') ?></th>
                <th scope="col" class="manage-column column-response"><?php esc_html_e('OwnPay API Details', 'ownpay-wordpress') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (is_array($payments_details) && !empty($payments_details)) :
                foreach ($payments_details as $payment_details) :
                    $order_id = $payment_details['order_id'];
                    $order_link = admin_url('post.php?post=' . $order_id . '&action=edit');
                    $order_id_html = '<a href="' . esc_url($order_link) . '" target="_blank">#' . $order_id . '</a>';
                    $username = strval($payment_details['username']);
                    $email = strval($payment_details['email']);
                    $status = strval($payment_details['status']);
                    $total_amount = strval($payment_details['total_amount']);
                    $order_date = strval($payment_details['order_date']);
            ?>
                    <tr>
                        <td class="column-id"><?php echo wp_kses_post($order_id_html) ?></td>
                        <td class="column-username"><?php echo esc_html($username) ?></td>
                        <td class="column-email"><?php echo esc_html($email) ?></td>
                        <td class="column-status"><?php echo esc_html($status) ?></td>
                        <td class="column-total"><?php echo esc_html($total_amount) ?></td>
                        <td class="column-date"><?php echo esc_html($order_date) ?></td>
                        <td class="column-response">
                            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="<?php echo '#staticBackdrop_' . esc_html($order_id) ?>">
                                <?php esc_html_e('View Response', 'ownpay-wordpress') ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach;
                ?>
            <?php else :
            ?>
                <tr>
                    <td class="text-center text-danger" colspan="7"><?php esc_html_e('No payments found!', 'ownpay-wordpress') ?></td>
                </tr>
            <?php endif;
            ?>
        </tbody>
    </table>
    <?php if (is_array($payments_details) && !empty($payments_details)) :
        foreach ($payments_details as $payment_details) :
            $order_id = $payment_details['order_id'];
            $create_payment_response = get_option('opwc_payment_create_response_' . $order_id) ? get_option('opwc_payment_create_response_' . $order_id) : 'N/A';
            $execute_payment_response = get_option('opwc_payment_execute_response_' . $order_id) ? get_option('opwc_payment_execute_response_' . $order_id) : 'N/A';
    ?>
            <div class="modal fade" id="<?php echo esc_html('staticBackdrop_' . $order_id) ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <p class="h5 m-2"><?php esc_html_e('OwnPay API Details', 'ownpay-wordpress'); ?></p>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="modal_inner d-flex justify-content-between">
                                <div class="payment-create-response-section w-50 mx-2">
                                    <p class="response-section-title h6"><?php esc_html_e('Payment Initiation Response', 'ownpay-wordpress') ?></p>
                                    <textarea rows="18" disabled name="create-response" class="border border-rounded w-100"><?php echo wp_json_encode(json_decode($create_payment_response), JSON_PRETTY_PRINT) ?></textarea>
                                </div>
                                <div class="payment-processing-response-section w-50 mx-2">
                                    <p class="response-section-title h6"><?php esc_html_e('Webhook Call Payload', 'ownpay-wordpress') ?></p>
                                    <textarea rows="18" disabled name="execute-response" class="border border-rounded w-100"><?php echo wp_json_encode(json_decode($execute_payment_response), JSON_PRETTY_PRINT) ?></textarea>
                                </div>
                            </div>
                        </div>
                        <div class=" modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e('Close', 'ownpay-wordpress') ?></button>
                        </div>
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
                $current_page = max(1, $paged);

                $pagination_link = paginate_links(array(
                    'base' => add_query_arg('paged', '%#%', esc_url($pagination_base_url)),
                    'format' => '',
                    'current' => esc_html($current_page),
                    'total' => esc_html($total_pages),
                ));

                if ($pagination_link) {
                    echo wp_kses_post($pagination_link);
                }
            }
            ?>
        </div>
    </div>
</div>