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
		$section = isset($_GET['section']) ? sanitize_key(wp_unslash($_GET['section'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL param used only to determine which assets to load; no data is processed.
		$is_wc_ownpay_settings = ($hook === 'woocommerce_page_wc-settings' && $section === 'ownpay');

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
		$page    = isset($_GET['page'])    ? sanitize_key(wp_unslash($_GET['page']))    : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL param used only to determine which assets to load; no data is processed.
		$section = isset($_GET['section']) ? sanitize_key(wp_unslash($_GET['section'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only URL param used only to determine which assets to load; no data is processed.

		if ($page === 'wc-settings' && $section === 'ownpay') {
			wp_enqueue_media();
			wp_enqueue_script('opwc-admin-upload', plugin_dir_url(__FILE__) . 'js/opwc-admin-upload.js', ['jquery'], $this->version, true);
			wp_localize_script('opwc-admin-upload', 'opwcUploadI18n', array(
				'mediaTitle'  => __('Select or Upload Payment Gateway Logo', 'ownpay-payment-gateway'),
				'mediaButton' => __('Use this Image', 'ownpay-payment-gateway'),
			));
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
