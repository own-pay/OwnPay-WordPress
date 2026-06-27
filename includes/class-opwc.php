<?php
/**
 * The core plugin class.
 *
 * @package    OPWC
 */

if (!defined('ABSPATH')) exit;

class OPWC
{
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @var      OPWC_Loader    $loader  
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var      string    $plugin_name
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var      string    $version
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 */
	public function __construct()
	{
		if (defined('OPWC_VERSION')) {
			$this->version = OPWC_VERSION;
		} else {
			$this->version = '1.0.0';
		}

		$this->plugin_name = 'ownpay-wordpress';

		$this->load_dependencies();
		$this->define_admin_hooks();
		$this->init_hooks();

		add_action('plugins_loaded', [$this, 'init_ownpay_payment']);
	}

	/**
	 * Load the required dependencies for this plugin.
	 */
	private function load_dependencies()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-opwc-loader.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/functions.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-opwc-hooks.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-opwc-admin.php';

		$this->loader = new OPWC_Loader();
	}

	/**
	 * Register all of the hooks related to the admin area functionality of the plugin.
	 */
	private function define_admin_hooks()
	{
		$plugin_admin = new OPWC_Admin($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'opwc_enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'opwc_enqueue_scripts');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin.
	 *
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @return    OPWC_Loader 
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return    string
	 */
	public function get_version()
	{
		return $this->version;
	}

	/**
	 * Initialize General Hooks.
	 */
	public function init_hooks()
	{
		new OPWC_Hooks();
	}

	/**
	 * Initialize OwnPay Payment.
	 */
	public function init_ownpay_payment()
	{
		$payment_file = plugin_dir_path(dirname(__FILE__)) . 'includes/class-opwc-payment.php';

		if (file_exists($payment_file)) {
			require_once $payment_file;
			new OPWC_Payment();
		}
	}
}
