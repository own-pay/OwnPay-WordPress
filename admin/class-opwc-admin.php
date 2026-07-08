<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    OPWC
 */

if (!defined('ABSPATH')) exit;

class OPWC_Admin
{
	private $plugin_name;
	private $version;

	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->opwc_add_menu();
	}

	public function opwc_enqueue_styles($hook)
	{
		// Only load plugin styles on OwnPay admin pages and WooCommerce payment settings.
		$is_opwc_page = strpos($hook, 'opwc') !== false;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check for conditional asset loading, no data processing.
		$is_wc_ownpay_settings = ($hook === 'woocommerce_page_wc-settings' && isset($_GET['section']) && $_GET['section'] === 'ownpay');

		if (!$is_opwc_page && !$is_wc_ownpay_settings) {
			return;
		}

		$admin_styles = $this->opwc_get_admin_styles();

		if (!empty($admin_styles) && is_array($admin_styles)) {
			foreach ($admin_styles as $handle => $style) {
				wp_enqueue_style($handle, $style['src'], $style['deps'], $style['version'], $style['media']);
			}
		}
	}

	public function opwc_enqueue_scripts()
	{
		// Conditional check for WooCommerce OwnPay settings section — read-only routing checks, no data processing.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- These are read-only URL params used only for conditional script enqueueing, not for processing any form data.
		if (isset($_GET['page']) && $_GET['page'] === 'wc-settings' && isset($_GET['section']) && $_GET['section'] === 'ownpay') {
			wp_enqueue_media();
			wp_enqueue_script('opwc-admin-upload', plugin_dir_url(__FILE__) . 'js/opwc-admin-upload.js', ['jquery'], $this->version, false);
		}

		$admin_scripts = $this->opwc_get_admin_scripts();

		if (!empty($admin_scripts) && is_array($admin_scripts)) {
			foreach ($admin_scripts as $handle => $script) {
				wp_enqueue_script($handle, $script['src'], $script['deps'], $script['version'], $script['args']);
			}
		}
	}

	public function opwc_add_menu()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-opwc-menu-settings.php';
		$admin_menu = new OPWC_Menu_Settings($this->plugin_name, $this->version);
		return $admin_menu;
	}

	public function opwc_get_admin_styles()
	{
		$admin_style_list = [
			$this->plugin_name . '-admin' => [
				'src'     => plugin_dir_url(__FILE__) . 'css/opwc-admin-common.css',
				'version' => $this->version,
				'deps'    => [],
				'media'    => 'all',
			],
		];

		return $admin_style_list;
	}

	public function opwc_get_admin_scripts()
	{
		$admin_script_list = [];
		return $admin_script_list;
	}
}
