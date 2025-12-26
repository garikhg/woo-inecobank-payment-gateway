<?php
/**
 * Plugin Name: Inecobank Payment Gateway for WooCommerce
 * Plugin URI: https://github.com/garikhg/woo-inecobank-payment-gateway
 * Description: Accept payments via Inecobank Payment Gateway
 * Version: 1.1.1
 * Author: Garegin Hakobyan
 * Author URI: https://github.com/garikhg
 * Text Domain: woo-inecobank-payment-gateway
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

// Define plugin constants
define('WOO_INECOBANK_PLUGIN_VERSION', '1.1.1');
define('WOO_INECOBANK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOO_INECOBANK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WOO_INECOBANK_PLUGIN_BASENAME', plugin_basename(__FILE__));


/**
 * Check if WooCommerce is active
 */
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	// add_action( 'admin_notices', 'woocommerce_required_notice' );
	add_action('admin_notices', 'woo_inecobank_woocommerce_missing_notice');

	return;
}

/**
 * Display notice if WooCommerce is not active
 */
function woo_inecobank_woocommerce_missing_notice()
{
	echo '<div class="notice notice-error"><p>' . esc_html__('WooCommerce is not active. Inecobank Payment Gateway requires WooCommerce to be installed and active.', 'woo-inecobank-payment-gateway') . '</p></div>';
}

/**
 * Initialize the plugin
 */
add_action('plugins_loaded', 'woo_inecobank_payment_gateway_init', 11);
function woo_inecobank_payment_gateway_init()
{
	// Check if WC_Payment_Gateway class exists
	if (!class_exists('WC_Payment_Gateway')) {
		return;
	}

	// Load plugin text domain
	add_action('plugins_loaded', 'woo_inecobank_load_textdomain');

	// Include required files
	require_once WOO_INECOBANK_PLUGIN_DIR . 'includes/class-woo-inecobank-logger.php';
	require_once WOO_INECOBANK_PLUGIN_DIR . 'includes/class-woo-inecobank-api.php';
	require_once WOO_INECOBANK_PLUGIN_DIR . 'includes/class-woo-inecobank-webhook.php';
	require_once WOO_INECOBANK_PLUGIN_DIR . 'includes/class-woo-inecobank-refund.php';
	require_once WOO_INECOBANK_PLUGIN_DIR . 'includes/class-woo-inecobank-return-page.php';
	require_once WOO_INECOBANK_PLUGIN_DIR . 'includes/class-woo-inecobank-gateway.php';
	require_once WOO_INECOBANK_PLUGIN_DIR . 'admin/class-woo-inecobank-admin.php';
	require_once WOO_INECOBANK_PLUGIN_DIR . 'admin/class-woo-inecobank-order-actions.php';

	// Initialize admin functionality
	if (is_admin()) {
		new Woo_Inecobank_Admin();
		new Woo_Inecobank_Order_Actions();
	}

	// Add the gateway to WooCommerce
	add_filter('woocommerce_payment_gateways', 'woo_inecobank_add_gateway_class');
}

/**
 * Add Inecobank Gateway to WooCommerce
 *
 * @param array $gateways
 *
 * @return array
 */
function woo_inecobank_add_gateway_class($gateways)
{
	$gateways[] = 'Woo_Inecobank_Gateway';

	return $gateways;
}

/**
 * Add plugin action links
 *
 * @param array $links
 *
 * @return array
 */
add_filter('plugin_action_links_' . WOO_INECOBANK_PLUGIN_BASENAME, 'woo_inecobank_plugin_action_links');
function woo_inecobank_plugin_action_links($links)
{
	$plugin_links = [
		'<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=inecobank') . '">' . __('Settings', 'woo-inecobank-payment-gateway') . '</a>',
		'<a href="https://code-craft.am/support" target="_blank">' . __('Support', 'woo-inecobank-payment-gateway') . '</a>',
	];

	return array_merge($plugin_links, $links);
}

/**
 * Enqueue frontend scripts and styles
 *
 * @return void
 */
add_action('wp_enqueue_scripts', 'woo_inecobank_enqueue_scripts');
function woo_inecobank_enqueue_scripts()
{
	wp_enqueue_style('woo-inecobank-style', WOO_INECOBANK_PLUGIN_URL . 'assets/css/frontend.css', array(), WOO_INECOBANK_PLUGIN_VERSION);
	wp_enqueue_script('woo-inecobank-script', WOO_INECOBANK_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), WOO_INECOBANK_PLUGIN_VERSION, true);
}

/**
 * Enqueue admin scripts and styles
 *
 * @param string $hook
 *
 * @return void
 */
add_action('admin_enqueue_scripts', 'woo_inecobank_admin_enqueue_scripts');
function woo_inecobank_admin_enqueue_scripts($hook)
{
	// Only load on WooCommerce settings page and order edit page
	if ('woocommerce_page_wc-settings' === $hook || 'post.php' === $hook) {
		wp_enqueue_style('woo-inecobank-admin', WOO_INECOBANK_PLUGIN_URL . 'assets/css/admin.css', array(), WOO_INECOBANK_PLUGIN_VERSION);
		wp_enqueue_script('woo-inecobank-admin', WOO_INECOBANK_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), WOO_INECOBANK_PLUGIN_VERSION, true);
	}
}

/**
 * Plugin activation hook
 *
 * @return void
 */
register_activation_hook(__FILE__, 'woo_inecobank_plugin_activate');
function woo_inecobank_plugin_activate()
{
	// Check WooCommerce is active
	if (!class_exists('WooCommerce')) {
		deactivate_plugins(WOO_INECOBANK_PLUGIN_BASENAME);
		wp_die(__('This plugin requires WooCommerce to be installed and active.', 'woo-inecobank-payment-gateway'));
	}

	// Create log directory if it doesn't exist
	$upload_dir = wp_upload_dir();
	$log_dir = $upload_dir['basedir'] . '/inecobank-logs';

	if (!file_exists($log_dir)) {
		wp_mkdir_p($log_dir);

		// Create .htaccess to protect logs
		$htaccess_content = 'deny from all';
		file_put_contents($log_dir . '/.htaccess', $htaccess_content);
	}

	// Create return page
	require_once WOO_INECOBANK_PLUGIN_DIR . 'includes/class-woo-inecobank-return-page.php';
	Woo_Inecobank_Return_Page::create_page();
}

/**
 * Add custom cron schedule intervals
 */
add_filter('cron_schedules', 'woo_inecobank_cron_schedules');
function woo_inecobank_cron_schedules($schedules)
{
	if (!isset($schedules['every_20_minutes'])) {
		$schedules['every_20_minutes'] = array(
			'interval' => 20 * 60, // 20 minutes in seconds
			'display' => __('Every 20 Minutes', 'woo-inecobank-payment-gateway')
		);
	}
	return $schedules;
}

/**
 * Plugin deactivation hook
 */
register_deactivation_hook(__FILE__, 'woo_inecobank_plugin_deactivate');
function woo_inecobank_plugin_deactivate()
{
	// Clear scheduled cron events
	$timestamp = wp_next_scheduled('woo_inecobank_check_pending_orders');
	if ($timestamp) {
		wp_unschedule_event($timestamp, 'woo_inecobank_check_pending_orders');
	}

	// Cleanup tasks if needed
	flush_rewrite_rules();
}
