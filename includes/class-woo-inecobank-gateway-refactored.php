<?php
/**
 * Inecobank Payment Gateway Class
 *
 * @package WooCommerce Inecobank Payment Gateway
 * @version 1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Inecobank Payment Gateway Class
 *
 * @since 1.0.0
 */
class Woo_Inecobank_Gateway extends WC_Payment_Gateway
{
    /**
     * API handler instance
     *
     * @var Woo_Inecobank_API
     */
    private $api;

    /**
     * Logger instance
     *
     * @var Woo_Inecobank_Logger
     */
    private $logger;

    /**
     * Webhook handler instance
     *
     * @var Woo_Inecobank_Webhook
     */
    private $webhook;

    /**
     * Refund handler instance
     *
     * @var Woo_Inecobank_Refund
     */
    private $refund_handler;

    /**
     * Test mode flag
     *
     * @var bool
     */
    private $testmode;

    /**
     * Payment type
     *
     * @var string
     */
    private $payment_type;

    /**
     * Debug mode flag
     *
     * @var bool
     */
    private $debug_mode;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->id = 'inecobank';
        $this->icon = apply_filters('woocommerce_inecobank_icon', WOO_INECOBANK_PLUGIN_URL . 'assets/images/inecobank.svg');
        $this->has_fields = false;
        $this->method_title = __('Inecobank Payment Gateway', 'woo-inecobank-payment-gateway');
        $this->method_description = __('Accept payments via Inecobank Payment Gateway', 'woo-inecobank-payment-gateway');

        // Supported features
        $this->supports = array('products', 'refunds');

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Get settings
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->testmode = 'yes' === $this->get_option('testmode');
        $this->payment_type = $this->get_option('payment_type', 'one_phase');
        $this->debug_mode = 'yes' === $this->get_option('debug_mode');

        // Initialize classes
        $this->logger = new Woo_Inecobank_Logger($this->debug_mode);
        $this->api = new Woo_Inecobank_API($this->get_api_credentials(), $this->testmode, $this->logger);
        $this->webhook = new Woo_Inecobank_Webhook($this->api, $this->logger);
        $this->refund_handler = new Woo_Inecobank_Refund($this->api, $this->logger);

        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_woo_inecobank_gateway', array($this->webhook, 'handle_webhook'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

        // Admin settings validation
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'validate_admin_options'), 20);
    }

    /**
     * Get API credentials
     *
     * @return array
     */
    private function get_api_credentials(): array
    {
        return array(
            'username' => $this->testmode ? $this->get_option('test_username') : $this->get_option('username'),
            'password' => $this->testmode ? $this->get_option('test_password') : $this->get_option('password'),
            'language' => $this->get_option('language', 'hy'),
        );
    }

    /**
     * Initialize gateway settings form fields
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'woo-inecobank-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Inecobank Payment Gateway', 'woo-inecobank-payment-gateway'),
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'woo-inecobank-payment-gateway'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woo-inecobank-payment-gateway'),
                'default' => __('Credit/Debit Card', 'woo-inecobank-payment-gateway'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'woo-inecobank-payment-gateway'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'woo-inecobank-payment-gateway'),
                'default' => __('Pay securely via Inecobank Payment Gateway.', 'woo-inecobank-payment-gateway'),
                'desc_tip' => true,
            ),
            'testmode' => array(
                'title' => __('Test Mode', 'woo-inecobank-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Test Mode', 'woo-inecobank-payment-gateway'),
                'default' => 'yes',
                'description' => __('Enable this to use the test environment. Disable for production.', 'woo-inecobank-payment-gateway'),
                'desc_tip' => true,
            ),
            'debug_mode' => array(
                'title' => __('Debug Mode', 'woo-inecobank-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Debug Mode', 'woo-inecobank-payment-gateway'),
                'description' => __('Save detailed debug messages to log file.', 'woo-inecobank-payment-gateway'),
                'default' => 'no',
                'desc_tip' => true,
            ),
            'payment_type' => array(
                'title' => __('Payment Type', 'woo-inecobank-payment-gateway'),
                'type' => 'select',
                'description' => __('Choose between one-phase (immediate) or two-phase (preauth) payment.', 'woo-inecobank-payment-gateway'),
                'default' => 'one_phase',
                'desc_tip' => true,
                'options' => array(
                    'one_phase' => __('One Phase (Immediate Payment)', 'woo-inecobank-payment-gateway'),
                    'two_phase' => __('Two Phase (Preauthorization)', 'woo-inecobank-payment-gateway'),
                ),
            ),
            'credentials_section' => array(
                'title' => __('API Credentials', 'woo-inecobank-payment-gateway'),
                'type' => 'title',
                'description' => __('Enter your Inecobank API credentials below.', 'woo-inecobank-payment-gateway'),
            ),
            'username' => array(
                'title' => __('Live API Username', 'woo-inecobank-payment-gateway'),
                'type' => 'text',
                'description' => __('Get your API credentials from Inecobank.', 'woo-inecobank-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'password' => array(
                'title' => __('Live API Password', 'woo-inecobank-payment-gateway'),
                'type' => 'password',
                'description' => __('Get your API credentials from Inecobank.', 'woo-inecobank-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'test_username' => array(
                'title' => __('Test API Username', 'woo-inecobank-payment-gateway'),
                'type' => 'text',
                'description' => __('Get your test API credentials from Inecobank.', 'woo-inecobank-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'test_password' => array(
                'title' => __('Test API Password', 'woo-inecobank-payment-gateway'),
                'type' => 'password',
                'description' => __('Get your test API credentials from Inecobank.', 'woo-inecobank-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ),
            'language' => array(
                'title' => __('Payment Page Language', 'woo-inecobank-payment-gateway'),
                'type' => 'select',
                'description' => __('Language for the payment page.', 'woo-inecobank-payment-gateway'),
                'default' => 'hy',
                'desc_tip' => true,
                'options' => array(
                    'hy' => __('Armenian', 'woo-inecobank-payment-gateway'),
                    'en' => __('English', 'woo-inecobank-payment-gateway'),
                    'ru' => __('Russian', 'woo-inecobank-payment-gateway'),
                ),
            ),
        );
    }

    /**
     * Validate admin options
     */
    public function validate_admin_options()
    {
        $credentials = $this->get_api_credentials();

        if ('yes' === $this->enabled && (empty($credentials['username']) || empty($credentials['password']))) {
            $mode = $this->testmode ? __('test', 'woo-inecobank-payment-gateway') : __('live', 'woo-inecobank-payment-gateway');
            WC_Admin_Settings::add_error(
                sprintf(
                    /* translators: %s: test or live */
                    __('Inecobank Payment Gateway: Please enter your %s API credentials.', 'woo-inecobank-payment-gateway'),
                    $mode
                )
            );
        }
    }

    /**
     * Process the payment
     *
     * @param int $order_id Order ID.
     *
     * @return array
     */
    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);

        if (!$order) {
            $this->logger->error('Invalid order ID: ' . $order_id);
            wc_add_notice(__('Invalid order. Please try again.', 'woo-inecobank-payment-gateway'), 'error');
            return array(
                'result' => 'fail',
                'redirect' => '',
            );
        }

        $this->logger->log('Processing payment for order #' . $order_id);

        // Register order with Inecobank
        $result = $this->api->register_order($order, $this->payment_type);

        if ($result['success']) {
            // Save Inecobank order ID
            $order->update_meta_data('_inecobank_order_id', $result['order_id']);
            $order->update_meta_data('_inecobank_payment_type', $this->payment_type);
            $order->save();

            // Mark as pending payment
            $order->update_status('pending', __('Awaiting Inecobank payment', 'woo-inecobank-payment-gateway'));

            // Reduce stock levels
            wc_reduce_stock_levels($order_id);

            // Remove cart
            WC()->cart->empty_cart();

            $this->logger->log('Order registered successfully. Inecobank Order ID: ' . $result['order_id']);

            // Return redirect to payment page
            return array(
                'result' => 'success',
                'redirect' => $result['form_url'],
            );
        } else {
            $error_message = $result['error_message'] ?? __('Payment error occurred. Please try again.', 'woo-inecobank-payment-gateway');
            $this->logger->error('Payment registration failed for order #' . $order_id . ': ' . $error_message);

            wc_add_notice($error_message, 'error');

            return array(
                'result' => 'fail',
                'redirect' => '',
            );
        }
    }

    /**
     * Process refund
     *
     * @param int    $order_id Order ID.
     * @param float  $amount Refund amount.
     * @param string $reason Refund reason.
     *
     * @return bool|WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        return $this->refund_handler->process_refund($order_id, $amount, $reason);
    }

    /**
     * Output for the order received page
     *
     * @param int $order_id Order ID.
     */
    public function receipt_page($order_id)
    {
        echo '<p>' . esc_html__('Thank you for your order, please click the button below to pay with Inecobank.', 'woo-inecobank-payment-gateway') . '</p>';
    }

    /**
     * Display admin options
     */
    public function admin_options()
    {
        ?>
        <h2><?php echo esc_html($this->method_title); ?></h2>
        <p><?php echo esc_html($this->method_description); ?></p>

        <?php if ($this->testmode): ?>
            <div class="notice notice-warning inline">
                <p><?php esc_html_e('Test mode is enabled. Use test credentials for testing. Remember to disable test mode when going live.', 'woo-inecobank-payment-gateway'); ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if (!$this->is_valid_for_use()): ?>
            <div class="notice notice-error inline">
                <p><?php esc_html_e('Inecobank Payment Gateway is not available. Your store currency is not supported.', 'woo-inecobank-payment-gateway'); ?>
                </p>
            </div>
        <?php endif; ?>

        <table class="form-table">
            <?php $this->generate_settings_html(); ?>
        </table>
        <?php
    }

    /**
     * Check if gateway is available
     *
     * @return bool
     */
    public function is_available(): bool
    {
        if ('yes' !== $this->enabled) {
            return false;
        }

        // Check if credentials are set
        $credentials = $this->get_api_credentials();
        if (empty($credentials['username']) || empty($credentials['password'])) {
            return false;
        }

        // Check if currency is supported
        if (!$this->is_valid_for_use()) {
            return false;
        }

        return true;
    }

    /**
     * Check if this gateway is valid for use
     *
     * @return bool
     */
    private function is_valid_for_use(): bool
    {
        $supported_currencies = array('AMD', 'USD', 'EUR', 'RUB');
        return in_array(get_woocommerce_currency(), $supported_currencies, true);
    }

    /**
     * Get gateway icon
     *
     * @return string
     */
    public function get_icon(): string
    {
        $icon_html = '';

        if ($this->icon) {
            $icon_html = '<img src="' . esc_url($this->icon) . '" alt="' . esc_attr($this->title) . '" style="height:30px;width:auto;" />';
        }

        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }
}
