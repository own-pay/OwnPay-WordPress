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
        $paged = isset($_GET['paged']) ? absint(sanitize_text_field($_GET['paged'])) : 1;
        $args = $this->get_query_args($paged);
        $current_url = admin_url('admin.php');
        $payments_details = opwc_get_payments_details($args, $filters);
        $payments_count = opwc_get_all_payments_count($filters);
        $total_pages = ceil($payments_count / $args['limit']);
        $pagination_base_url = add_query_arg(array(
            'page' => sanitize_text_field(wp_unslash($_GET['page'])),
            'paged' => $paged,
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

        $filters['filter_key'] = isset($_GET['key']) ? strval(sanitize_text_field(wp_unslash($_GET['key']))) : '';
        $filters['filter_value'] = isset($_GET['value']) ? strval(sanitize_text_field(wp_unslash($_GET['value']))) : '';

        return $filters;
    }
}
