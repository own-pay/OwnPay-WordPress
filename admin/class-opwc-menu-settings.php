<?php
/**
 * Register administrative menus for payment oversight
 *
 * @package     OPWC
 */

if (!defined('ABSPATH')) exit;

if (!class_exists('OPWC_Menu_Settings')) {

    class OPWC_Menu_Settings
    {
        private $plugin_name;
        private $version;
        public $class_prefix = 'class-opwc-';

        public function __construct($plugin_name, $version)
        {
            $this->plugin_name = $plugin_name;
            $this->version = $version;
            add_action('admin_menu', [$this, 'register_admin_menu']);
        }

        public function get_submenu_list()
        {
            $submenu_list = [];
            return $submenu_list;
        }

        public function register_admin_menu()
        {
            $main_menu_title = 'OwnPay';
            $parent_slug = 'opwc';
            $capability = 'manage_options';
            $menu_icon_url = plugins_url('../assets/logo/dashboard-menu-icon.jpg', __FILE__);

            add_menu_page(
                $main_menu_title,
                $main_menu_title,
                $capability,
                $parent_slug,
                [$this, 'payment_list_page'],
                $menu_icon_url
            );

            $submenu_list = $this->get_submenu_list();

            if (empty($submenu_list)) return;

            foreach ($submenu_list as $submenu_title) {
                add_submenu_page(
                    $parent_slug,
                    ucwords(str_replace(['-', '_'], ' ', $submenu_title)),
                    ucwords(str_replace(['-', '_'], ' ', $submenu_title)),
                    'manage_options',
                    $parent_slug . '-' . strtolower(str_replace(['_', ' '], '-', $submenu_title)),
                    [$this, strtolower($submenu_title) . '_page'],
                );
            }
        }

        public function payment_list_page()
        {
            $file_name = 'class-opwc-payment-list.php';
            $this->include_template_file($file_name);

            if (class_exists('OPWC_Payment_List')) {
                $menu_class = new OPWC_Payment_List();
                $menu_class->menu_page();
            }

            wp_enqueue_script($this->plugin_name . '-admin-payment-list', plugin_dir_url(__FILE__) . 'js/opwc-payment-list.js', ['jquery'], $this->version, false);
            wp_enqueue_script('opwc-bootstrap', OPWC_ASSETS_DIR . 'bootstrap/js/bootstrap.bundle.js', [], '5.3.3', false);
            wp_enqueue_style('opwc-bootstrap', OPWC_ASSETS_DIR . 'bootstrap/css/bootstrap.min.css', [], '5.3.3', 'all');
        }

        public function include_template_file($file_name)
        {
            $file_name = basename($file_name);
            $template_file = plugin_dir_path(dirname(__FILE__)) . 'admin/partials/' . $file_name;

            if (file_exists($template_file)) {
                include $template_file;
            }
        }
    }
}
