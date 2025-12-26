<?php
/**
 * Inecobank Payment Gateway Class
 *
 * @package WooCommerce Inecobank Payment Gateway
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Inecobank Payment Gateway Class
 */
class Woo_Inecobank_Gateway extends WC_Payment_Gateway
{
    /**
     * API handler instance
     * @var Woo_Inecobank_API
     */
    private Woo_Inecobank_API $api;

    /**
     * Logger instance
     * @var Woo_Inecobank_Logger
     */
    private Woo_Inecobank_Logger $logger;

    /**
     * Webhook handler instance
     *
     * @var Woo_Inecobank_Webhook
     */
    private Woo_Inecobank_Webhook $webhook;

    /**
     * Refund handler instance
     *
     * @var Woo_Inecobank_Refund
     */
    private Woo_Inecobank_Refund $refund_handler;

    /**
     * Payment type
     *
     * @var string
     */
    private string $payment_type;

    /**
     * Test mode flag
     *
     * @var bool
     */
    private bool $testmode;

    /**
     * Debug mode flag
     *
     * @var bool
     */
    private bool $debug_mode;

    /**
     * Keep cart enabled flag
     * 
     * @var bool
     */
    private bool $keep_cart;

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
        $this->keep_cart = 'yes' === $this->get_option('keep_cart');
        $this->payment_type = $this->get_option('payment_type', 'one_phase');
        $this->debug_mode = 'yes' === $this->get_option('debug_mode');

        // Initialize classes
        $this->logger = new Woo_Inecobank_Logger($this->debug_mode);
        $this->api = new Woo_Inecobank_API($this->get_api_credentials(), $this->testmode, $this->logger);
        $this->webhook = new Woo_Inecobank_Webhook($this->api, $this->logger);
        $this->refund_handler = new Woo_Inecobank_Refund($this->api, $this->logger);

        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(
            $this,
            'process_admin_options'
        ));
        add_action('woocommerce_api_inecobank-gateway', array($this->webhook, 'handle_webhook'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

        // Schedule automated order status check
        add_action('woo_inecobank_check_pending_orders', array($this, 'check_pending_orders'));

        // Register cron schedule on activation (if not already scheduled)
        if (!wp_next_scheduled('woo_inecobank_check_pending_orders')) {
            wp_schedule_event(time(), 'every_20_minutes', 'woo_inecobank_check_pending_orders');
        }
    }

    /**
     * Get API credentials
     *
     * @return array
     */
    private function get_api_credentials()
    {
        // Return test or live credentials based on test mode
        if ($this->testmode) {
            return array(
                'username' => $this->get_option('test_username'),
                'password' => $this->get_option('test_password'),
                'language' => $this->get_option('language', 'hy'),
            );
        }

        return array(
            'username' => $this->get_option('username'),
            'password' => $this->get_option('password'),
            'language' => $this->get_option('language', 'hy'),
        );
    }

    /**
     * Initialize gateway settings form fields
     */
    public function init_form_fields()
    {
        $this->form_fields = [
            'enabled' => [
                'title' => __('Enable/Disable', 'woo-inecobank-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Inecobank Payment Gateway', 'woo-inecobank-payment-gateway'),
                'default' => 'no',
            ],
            'title' => [
                'title' => __('Title', 'woo-inecobank-payment-gateway'),
                'type' => 'text',
                'description' => __(
                    'This controls the title which the user sees during checkout.',
                    'woo-inecobank-payment-gateway'
                ),
                'default' => __('Credit/Debit Card', 'woo-inecobank-payment-gateway'),
                'desc_tip' => true,
            ],
            'description' => [
                'title' => __('Description', 'woo-inecobank-payment-gateway'),
                'type' => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'woo-inecobank-payment-gateway'),
                'default' => __('Pay securely via Inecobank Payment Gateway.', 'woo-inecobank-payment-gateway'),
                'desc_tip' => true,
            ],
            'testmode' => [
                'title' => __('Test Mode', 'woo-inecobank-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Test Mode', 'woo-inecobank-payment-gateway'),
                'default' => 'yes',
                'desc_tip' => true,
            ],
            'keep_cart' => [
                'title' => __('Keep Cart Contents', 'woo-inecobank-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Do not clear cart on redirect', 'woo-inecobank-payment-gateway'),
                'description' => __('If enabled, items will remain in the cart when the user is redirected to the payment page. Recommended to prevent empty carts if user clicks Back.', 'woo-inecobank-payment-gateway'),
                'default' => 'no',
                'desc_tip' => true,
            ],
            'debug_mode' => [
                'title' => __('Debug Mode', 'woo-inecobank-payment-gateway'),
                'type' => 'checkbox',
                'label' => __('Enable Debug Mode', 'woo-inecobank-payment-gateway'),
                'description' => __('Save debug messages to log file.', 'woo-inecobank-payment-gateway'),
                'default' => 'no',
                'desc_tip' => true,
            ],
            'payment_type' => [
                'title' => __('Payment Type', 'woo-inecobank-payment-gateway'),
                'type' => 'select',
                'description' => __('Choose between one-phase (immediate) or two-phase (preauth) payment.', 'woo-inecobank-payment-gateway'),
                'default' => 'one_phase',
                'desc_tip' => true,
                'options' => [
                    'one_phase' => __('One Phase', 'woo-inecobank-payment-gateway'),
                    'two_phase' => __('Two Phase', 'woo-inecobank-payment-gateway'),
                ],
            ],
            'username' => [
                'title' => __('Live API Username', 'woo-inecobank-payment-gateway'),
                'type' => 'text',
                'description' => __('Get your API credentials from Inecobank.', 'woo-inecobank-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ],
            'password' => [
                'title' => __('Live API Password', 'woo-inecobank-payment-gateway'),
                'type' => 'password',
                'description' => __('Get your API credentials from Inecobank.', 'woo-inecobank-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ],
            'test_username' => [
                'title' => __('Test API Username', 'woo-inecobank-payment-gateway'),
                'type' => 'text',
                'description' => __('Get your API credentials from Inecobank.', 'woo-inecobank-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ],
            'test_password' => [
                'title' => __('Test API Password', 'woo-inecobank-payment-gateway'),
                'type' => 'password',
                'description' => __('Get your API credentials from Inecobank.', 'woo-inecobank-payment-gateway'),
                'default' => '',
                'desc_tip' => true,
            ],
            'language' => [
                'title' => __('Language', 'woo-inecobank-payment-gateway'),
                'type' => 'select',
                'description' => __('Language for the payment page.', 'woo-inecobank-payment-gateway'),
                'default' => 'hy',
                'desc_tip' => true,
                'options' => [
                    'hy' => __('Armenian', 'woo-inecobank-payment-gateway'),
                    'en' => __('English', 'woo-inecobank-payment-gateway'),
                    'ru' => __('Russian', 'woo-inecobank-payment-gateway'),
                ],
            ],
        ];
    }

    /**
     * Process the payment
     *
     * @param int $order_id
     *
     * @return array
     */
    public function process_payment($order_id)
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

        // Generate unique order number to avoid "Duplicate Order" errors on retry
        // Append timestamp to ensure uniqueness (e.g. 12345-1678901234)
        $unique_order_number = $order->get_order_number() . '-' . time();

        // Register order with Inecobank
        $result = $this->api->register_order($order, $this->payment_type, $unique_order_number);

        if ($result['success']) {
            // Save the unique order number we sent to Inecobank (for webhook lookup)
            $order->update_meta_data('_inecobank_order_id', $unique_order_number);
            // Save the UUID returned by Inecobank (for API status checks)
            $order->update_meta_data('_inecobank_uuid', $result['order_id']);
            $order->update_meta_data('_inecobank_payment_type', $this->payment_type);
            $order->save();

            // Mark as pending payment
            $order->update_status('pending', __('Awaiting Inecobank payment', 'woo-inecobank-payment-gateway'));

            // Reduce stock levels
            wc_reduce_stock_levels($order_id);

            $this->logger->log('Order registered successfully. Inecobank UUID: ' . $result['order_id'] . ', Order Number: ' . $order->get_order_number());

            // Check if we should keep the cart contents
            if ($this->keep_cart) {
                // If keep_cart is enabled, we manually send the JSON response and exit
                // to prevent WooCommerce from proceeding to empty the cart.
                wp_send_json(array(
                    'result' => 'success',
                    'redirect' => $result['form_url'],
                ));
                exit;
            }

            // Remove cart (standard behavior)
            WC()->cart->empty_cart();

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
     * @param int $order_id
     * @param float $amount
     * @param string $reason
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        return $this->refund_handler->process_refund($order_id, $amount, $reason);
    }

    /**
     * Output for the order received page
     *
     * @param int $order_id
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
    public function is_available()
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
    public function get_icon()
    {
        $icon_html = '';

        if ($this->icon) {
            $icon_html = '<img src="' . esc_url($this->icon) . '" alt="' . esc_attr($this->title) . '" style="height:30px;width:auto;" />';
        }

        return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }

    /**
     * Check pending orders and mark as failed if not paid after 20 minutes
     */
    public function check_pending_orders()
    {
        $this->logger->log('Running automated pending order status check');

        // Get orders with 'pending' status that use Inecobank gateway
        $pending_orders = wc_get_orders(array(
            'status' => 'pending',
            'payment_method' => 'inecobank',
            'limit' => 50, // Process in batches
            'orderby' => 'date',
            'order' => 'ASC',
        ));

        if (empty($pending_orders)) {
            $this->logger->log('No pending Inecobank orders found');
            return;
        }

        $checked_count = 0;
        $failed_count = 0;

        foreach ($pending_orders as $order) {
            // Check if order is older than 20 minutes
            $order_created = $order->get_date_created();
            $now = new WC_DateTime();
            $time_diff = $now->getTimestamp() - $order_created->getTimestamp();

            if ($time_diff < (20 * 60)) {
                // Order is less than 20 minutes old, skip
                continue;
            }

            $checked_count++;

            // Get Inecobank UUID
            $inecobank_uuid = $order->get_meta('_inecobank_uuid');

            if (empty($inecobank_uuid)) {
                // No UUID means order wasn't properly registered with Inecobank
                $this->logger->log('Order #' . $order->get_id() . ' has no Inecobank UUID, marking as failed');
                $order->update_status('failed', __('Payment not completed within 20 minutes. No payment UUID found.', 'woo-inecobank-payment-gateway'));
                $failed_count++;
                continue;
            }

            // Check status with Inecobank API
            $status = $this->api->get_order_status($inecobank_uuid);

            if ($status && isset($status['orderStatus'])) {
                $order_status = (int) $status['orderStatus'];

                // Status codes: 2 = Deposited (paid), 1 = Pre-authorized, 0 = Registered (not paid yet)
                if ($order_status === 2 || $order_status === 1) {
                    // Order is paid or pre-authorized, process it
                    $this->logger->log('Order #' . $order->get_id() . ' is paid (status: ' . $order_status . ')');

                    if ($order_status === 2) {
                        $order->payment_complete($inecobank_uuid);
                        $order->add_order_note(__('Payment verified via automated status check.', 'woo-inecobank-payment-gateway'));
                    } elseif ($order_status === 1) {
                        $order->update_status('on-hold', __('Payment pre-authorized via automated check.', 'woo-inecobank-payment-gateway'));
                    }
                } else {
                    // Order is still unpaid after 20 minutes, mark as failed
                    $this->logger->log('Order #' . $order->get_id() . ' not paid after 20 minutes (status: ' . $order_status . '), marking as failed');
                    $order->update_status('failed', __('Payment not completed within 20 minutes.', 'woo-inecobank-payment-gateway'));
                    $failed_count++;
                }
            } else {
                // API check failed, mark as failed to be safe
                $this->logger->log('API check failed for order #' . $order->get_id() . ', marking as failed', 'error');
                $order->update_status('failed', __('Payment verification failed. Could not confirm payment status.', 'woo-inecobank-payment-gateway'));
                $failed_count++;
            }
        }

        $this->logger->log('Pending order check completed. Checked: ' . $checked_count . ', Failed: ' . $failed_count);
    }
}
