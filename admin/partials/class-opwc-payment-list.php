<?php
/**
 * Admin submenu list handler class
 *
 * @package     OPWC
 */

if (!defined('ABSPATH')) exit;

class OPWC_Payment_List
{
    /**
     * Menu page handler
     *
     * @return void
     */
    public function menu_page()
    {
        $page_template = __DIR__ . '/views/payment-list/payment-list.php';

        if (file_exists($page_template)) {
            include $page_template;
        }
    }

    public function render_payment_table()
    {
        $filters = $this->get_query_filters();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- `paged` is a read-only pagination offset sanitized via absint(), not a form data submission.
		$paged = isset($_GET['paged']) ? absint(wp_unslash($_GET['paged'])) : 1;
        $args = $this->get_query_args($paged);
        $current_url = admin_url('admin.php');
        $payments_details = opwc_get_payments_details($args, $filters);
        $payments_count = opwc_get_all_payments_count($filters);
        $total_pages = ceil($payments_count / $args['limit']);
        $nonce = wp_create_nonce('opwc_filter_nonce');
        $pagination_base_url = add_query_arg(array(
            'page'        => 'opwc',
            'paged'       => $paged,
            '_opwc_nonce' => $nonce,
        ), $current_url);

        $table_template = __DIR__ . '/views/payment-list/payment-table.php';

        if (file_exists($table_template)) {
            include $table_template;
        }
    }

    public function get_query_args($paged)
    {
        $args = [];
        $args['limit'] = 10;

        if ($paged && $paged  > 1) {
            $args['offset'] = ($paged - 1) * $args['limit'];
        } else {
            $args['offset'] = 0;
        }

        return $args;
    }

    public function get_query_filters()
    {
        $filters = [];

        // Verify nonce if filter parameters are present
        if (isset($_GET['key']) || isset($_GET['value'])) {
            $nonce = isset($_GET['_opwc_nonce']) ? sanitize_text_field(wp_unslash($_GET['_opwc_nonce'])) : '';
            if (!wp_verify_nonce($nonce, 'opwc_filter_nonce')) {
                // Invalid or missing nonce — return empty filters (no filtering applied)
                return $filters;
            }
        }

        $filters['filter_key'] = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
        $filters['filter_value'] = isset($_GET['value']) ? sanitize_text_field(wp_unslash($_GET['value'])) : '';

        return $filters;
    }
}
