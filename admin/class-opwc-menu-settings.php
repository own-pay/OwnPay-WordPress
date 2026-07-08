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
        private $page_hook;
        public $class_prefix = 'class-opwc-';

        public function __construct($plugin_name, $version)
        {
            $this->plugin_name = $plugin_name;
            $this->version = $version;
            add_action('admin_menu', [$this, 'register_admin_menu']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_page_scripts']);
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
            $svg_file = plugin_dir_path(__FILE__) . '../assets/logo/icon.svg';
            if (file_exists($svg_file)) {
                $svg_content = file_get_contents($svg_file);
                $menu_icon_url = 'data:image/svg+xml;base64,' . base64_encode($svg_content);
            } else {
                $menu_icon_url = 'dashicons-admin-generic';
            }

            $this->page_hook = add_menu_page(
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
        }

        /**
         * Enqueue scripts only on the OwnPay admin page
         */
        public function enqueue_page_scripts($hook)
        {
            if ($hook !== $this->page_hook) {
                return;
            }
            wp_enqueue_script(
                $this->plugin_name . '-admin-payment-list',
                plugin_dir_url(__FILE__) . 'js/opwc-payment-list.js',
                ['jquery'],
                $this->version,
                true
            );
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
