<?php
/**
 * Inecobank Payment Gateway Class
 *
 * @package WooCommerce Inecobank Payment Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Inecobank Payment Gateway Class
 */
class Woo_Inecobank_Gateway extends WC_Payment_Gateway {
    /**
     * API handler instance
     */
    private $api;

    /**
     * Logger instance
     * @var Inecobank_Logger
     */
    private $logger;

    /**
     * Webhook handler instance
     */
    private $webhook;

    /**
     * Refund handler instance
     */
    private $refund_handler;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id                 = 'inecobank';
        $this->icon               = apply_filters( 'woocommerce_inecobank_icon', WOO_INECOBANK_PLUGIN_URL . 'assets/images/inecobank.svg' );
        $this->has_fields         = false;
        $this->method_title       = __( 'Inecobank Payment Gateway', 'woo-inecobank-payment-gateway' );
        $this->method_description = __( 'Accept payments via Inecobank Payment Gateway', 'woo-inecobank-payment-gateway' );

        // Supported features
        $this->supports = [
                'products',
                'refunds',
        ];

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Get settings
        $this->title        = $this->get_option( 'title' );
        $this->description  = $this->get_option( 'description' );
        $this->enabled      = $this->get_option( 'enabled' );
        $this->testmode     = 'yes' === $this->get_option( 'testmode' );
        $this->payment_type = $this->get_option( 'payment_type', 'one_phase' );
        $this->debug_mode   = 'yes' === $this->get_option( 'debug_mode' );

        // Initialize classes
        $this->logger         = new Woo_Inecobank_Logger( $this->debug_mode );
        $this->api            = new Woo_Inecobank_Api( $this->get_api_credentials(), $this->testmode, $this->logger );
        $this->webhook        = new Woo_Inecobank_Webhook( $this->api, $this->logger );
        $this->refund_handler = new Woo_Inecobank_Refund( $this->api, $this->logger );

        // Hooks
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ] );
        add_action( 'woocommerce_api_woo_inecobank_gateway', [ $this->webhook, 'handle_webhook' ] );
        add_action( 'woocommerce_receipt_' . $this->id, [ $this, 'receipt_page' ] );
    }

    /**
     * Get API credentials
     *
     * @return array
     */
    private function get_api_credentials() {
        return [
                'username' => $this->testmode ? $this->get_option( 'test_username' ) : $this->get_option( 'username' ),
                'password' => $this->testmode ? $this->get_option( 'test_password' ) : $this->get_option( 'password' ),
                'language' => $this->get_option( 'language', 'hy' ),
        ];
    }

    /**
     * Initialize gateway settings form fields
     */
    public function init_form_fields() {
        $this->form_fields = [
                'enabled'       => [
                        'title'   => __( 'Enable/Disable', 'woo-inecobank-payment-gateway' ),
                        'type'    => 'checkbox',
                        'label'   => __( 'Enable Inecobank Payment Gateway', 'woo-inecobank-payment-gateway' ),
                        'default' => 'no',
                ],
                'title'         => [
                        'title'       => __( 'Title', 'woo-inecobank-payment-gateway' ),
                        'type'        => 'text',
                        'description' => __( 'This controls the title which the user sees during checkout.',
                                'woo-inecobank-payment-gateway' ),
                        'default'     => __( 'Credit/Debit Card', 'woo-inecobank-payment-gateway' ),
                        'desc_tip'    => true,
                ],
                'description'   => [
                        'title'       => __( 'Description', 'woo-inecobank-payment-gateway' ),
                        'type'        => 'textarea',
                        'description' => __( 'Payment method description that the customer will see on your checkout.', 'woo-inecobank-payment-gateway' ),
                        'default'     => __( 'Pay securely via Inecobank Payment Gateway.', 'woo-inecobank-payment-gateway' ),
                        'desc_tip'    => true,
                ],
                'testmode'      => [
                        'title'    => __( 'Test Mode', 'woo-inecobank-payment-gateway' ),
                        'type'     => 'checkbox',
                        'label'    => __( 'Enable Test Mode', 'woo-inecobank-payment-gateway' ),
                        'default'  => 'yes',
                        'desc_tip' => true,
                ],
                'debug_mode'    => [
                        'title'       => __( 'Debug Mode', 'woo-inecobank-payment-gateway' ),
                        'type'        => 'checkbox',
                        'label'       => __( 'Enable Debug Mode', 'woo-inecobank-payment-gateway' ),
                        'description' => __( 'Save debug messages to log file.', 'woo-inecobank-payment-gateway' ),
                        'default'     => 'no',
                        'desc_tip'    => true,
                ],
                'payment_type'  => [
                        'title'       => __( 'Payment Type', 'woo-inecobank-payment-gateway' ),
                        'type'        => 'select',
                        'description' => __( 'Choose between one-phase (immediate) or two-phase (preauth) payment.', 'woo-inecobank-payment-gateway' ),
                        'default'     => 'one_phase',
                        'desc_tip'    => true,
                        'options'     => [
                                'one_phase' => __( 'One Phase', 'woo-inecobank-payment-gateway' ),
                                'two_phase' => __( 'Two Phase', 'woo-inecobank-payment-gateway' ),
                        ],
                ],
                'username'      => [
                        'title'       => __( 'Live API Username', 'woo-inecobank-payment-gateway' ),
                        'type'        => 'text',
                        'description' => __( 'Get your API credentials from Inecobank.', 'woo-inecobank-payment-gateway' ),
                        'default'     => '',
                        'desc_tip'    => true,
                ],
                'password'      => [
                        'title'       => __( 'Live API Password', 'woo-inecobank-payment-gateway' ),
                        'type'        => 'password',
                        'description' => __( 'Get your API credentials from Inecobank.', 'woo-inecobank-payment-gateway' ),
                        'default'     => '',
                        'desc_tip'    => true,
                ],
                'test_username' => [
                        'title'       => __( 'Test API Username', 'woo-inecobank-payment-gateway' ),
                        'type'        => 'text',
                        'description' => __( 'Get your API credentials from Inecobank.', 'woo-inecobank-payment-gateway' ),
                        'default'     => '',
                        'desc_tip'    => true,
                ],
                'test_password' => [
                        'title'       => __( 'Test API Password', 'woo-inecobank-payment-gateway' ),
                        'type'        => 'password',
                        'description' => __( 'Get your API credentials from Inecobank.', 'woo-inecobank-payment-gateway' ),
                        'default'     => '',
                        'desc_tip'    => true,
                ],
                'language'      => [
                        'title'       => __( 'Language', 'woo-inecobank-payment-gateway' ),
                        'type'        => 'select',
                        'description' => __( 'Language for the payment page.', 'woo-inecobank-payment-gateway' ),
                        'default'     => 'hy',
                        'desc_tip'    => true,
                        'options'     => [
                                'hy' => __( 'Armenian', 'woo-inecobank-payment-gateway' ),
                                'en' => __( 'English', 'woo-inecobank-payment-gateway' ),
                                'ru' => __( 'Russian', 'woo-inecobank-payment-gateway' ),
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
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        $this->logger->log( 'Processing payment for order #' . $order_id );

        // Register order with Inecobank
        $result = $this->api->register_order( $order, $this->payment_type );

        if ( $result['success'] ) {
            // Save Inecobank order ID
            update_post_meta( $order_id, '_inecobank_order_id', $result['order_id'] );
            update_post_meta( $order_id, '_inecobank_payment_type', $this->payment_type );

            // Mark as pending payment
            $order->update_status( 'pending', __( 'Awaiting Inecobank payment', 'woo-inecobank-payment-gateway' ) );

            // Reduce stock levels
            wc_reduce_stock_levels( $order_id );

            // Remove cart
            WC()->cart->empty_cart();

            $this->logger->log( 'Order registered successfully. Inecobank Order ID: ' . $result['order_id'] );

            // Return redirect to payment page
            return [
                    'result'   => 'success',
                    'redirect' => $result['form_url'],
            ];
        } else {
            wc_add_notice( $result['error_message'], 'error' );

            return [
                    'result'   => 'fail',
                    'redirect' => '',
            ];
        }
    }

    /**
     * Process refund
     *
     * @param int $order_id
     * @param float $amount
     * @param string $reason
     */
    public function process_refund( $order_id, $amount = null, $reason = '' ) {
        return $this->refund_handler->process_refund( $order_id, $amount, $reason );
    }

    /**
     * Output for the order received page
     *
     * @param int $order_id
     */
    public function receipt_page( $order_id ) {
        echo '<p>' . __( 'Thank you for your order, please click the button below to pay with Inecobank.', 'woo-inecobank-payment-gateway' ) . '</p>';
    }

    /**
     * Display admin options
     */
    public function admin_options() {
        ?>
        <h2><?php echo esc_html( $this->method_title ) ?></h2>
        <p><?php echo esc_html( $this->method_description ) ?></p>

        <?php if ( $this->testmode ): ?>
            <div class="notice notice-warning inline">
                <p><?php _e( 'Test mode is enabled. Use test credentials for testing.', 'woo-inecobank-payment-gateway' ) ?></p>
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
    public function is_available() {
        if ( 'yes' !== $this->enabled ) {
            return false;
        }

        // Check if credentials are set
        $credentials = $this->get_api_credentials();
        if ( empty( $credentials['username'] ) || empty( $credentials['password'] ) ) {
            return false;
        }

        return true;
    }

    /**
     * Get gateway icon
     *
     * @return string
     */
    public function get_icon() {
        $icon_html = '';

        if ( $this->icon ) {
            $icon_html = '<img src="' . esc_url( $this->icon ) . '" alt="' . esc_attr( $this->title ) . '" style="height:30px;width:auto;" />';
        }

        return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
    }
}

