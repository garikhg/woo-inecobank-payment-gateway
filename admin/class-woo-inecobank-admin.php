<?php
/**
 * Inecobank Admin
 *
 * @package WooCommerce Inecobank Payment Gateway
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Inecobank_Admin class
 */
class Woo_Inecobank_Admin
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'admin_notices']);
        add_filter('plugin_action_links_' . WOO_INECOBANK_PLUGIN_BASENAME, [$this, 'plugin_action_links']);
    }

    public function add_admin_menu()
    {
        add_submenu_page(
            'woocommerce',
            __('Inecobank Logs', 'woo-inecobank-payment-gateway'),
            __('Inecobank Logs', 'woo-inecobank-payment-gateway'),
            'manage_woocommerce',
            'inecobank-logs',
            array($this, 'logs_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings()
    {
        register_setting('inecobank_settings', 'inecobank_clear_logs');
    }

    /**
     * Display admin notices
     */
    public function admin_notices()
    {
        $gateway = WC()->payment_gateways()->payment_gateways()['inecobank'] ?? null;

        if (!$gateway) {
            return;
        }

        // Check if gateway is enabled but not configured
        if ($gateway->enabled === 'yes') {
            $username = $gateway->get_option('username');
            $password = $gateway->get_option('password');

            if (empty($username) || empty($password)) {
                ?>
                <div class="notice notice-error">
                    <p>
                        <?php _e('Inecobank Payment Gateway is enabled but not configured. Please enter your API credentials.', 'woo-inecobank-payment-gateway'); ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=inecobank')); ?>">
                            <?php _e('Configure', 'woo-inecobank-payment-gateway'); ?>
                        </a>
                    </p>
                </div>
                <?php
            }
        }
    }

    /**
     * Plugin action links
     *
     * @param array $links
     *
     * @return array
     */
    public function plugin_action_links($links)
    {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=inecobank') . '">' . __('Settings', 'woo-inecobank-payment-gateway') . '</a>',
            '<a href="https://code-craft.am/support" target="_blank">' . __('Support', 'woo-inecobank-payment-gateway') . '</a>',
        ];

        return array_merge($plugin_links, $links);
    }

    /**
     * Logs page output
     */
    public function logs_page()
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        // Handle log clearing
        if (isset($_POST['clear_logs']) && check_admin_referer('inecobank_clear_logs')) {
            $this->clear_logs();
            echo '<div class="notice notice-success"><p>' . __('Logs cleared successfully.', 'woo-inecobank-payment-gateway') . '</p></div>';
        }

        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/inecobank-logs';
        $log_files = glob($log_dir . '/*.log');

        // Sort by date (newest first)
        usort($log_files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });


        include WOO_INECOBANK_PLUGIN_DIR . 'admin/views/admin-logs.php';
    }

    /**
     * Clear all logs
     */
    private function clear_logs()
    {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/inecobank-logs';
        $log_files = glob($log_dir . '/*.log');

        foreach ($log_files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
