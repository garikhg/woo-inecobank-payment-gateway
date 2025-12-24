<?php
/**
 * Inecobank Order Actions
 *
 * @package WooCommerce Inecobank Payment Gateway
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Inecobank_Order_Actions class
 */
class Woo_Inecobank_Order_Actions {
    /**
     * Constructor
     */
    public function __construct() {
        add_filter( 'woocommerce_order_actions', [ $this, 'add_order_actions' ] );
        add_action( 'woocommerce_order_action_inecobank_complete_payment', [ $this, 'complete_payment' ] );
        add_action( 'woocommerce_order_action_inecobank_reverse_payment', [ $this, 'reverse_payment' ] );
        add_action( 'woocommerce_order_details_after_order_table', [ $this, 'display_payment_info' ] );
    }

    /**
     * Add order actions
     */
    public function add_order_actions( $actions ) {
        global $theorder;

        if ( ! $theorder || $theorder->get_payment_method() !== 'inecobank' ) {
            return $actions;
        }

        $payment_type = get_post_meta( $theorder->get_id(), '_payment_type', true );

        // Add complete payment action for two-phase payments
        if ( $payment_type === 'two_phase' && $theorder->get_status() === 'on-hold' ) {
            $actions['inecobank_complete_payment'] = __( 'Complete Inecobank Payment', 'woo-inecobank-payment-gateway' );
        }

        // Add reverse payment action
        if ( in_array( $theorder->get_status(), [ 'on-hold', 'pending' ] ) ) {
            $actions['inecobank_reverse_payment'] = __( 'Reverse Inecobank Payment', 'woo-inecobank-payment-gateway' );
        }

        return $actions;
    }

    /**
     * Complete two-phase payment
     */
    public function complete_payment( $order ) {
        $inecobank_order_id = get_post_meta( $_GET['order_id'], '_inecobank_order_id', true );

        if ( ! $inecobank_order_id ) {
            $order->add_order_note( __( 'Failed to complete payment: Inecobank order ID not found.', 'woo-inecobank-payment-gateway' ) );

            return;
        }

        // Get gateway settings
        $api = $this->getGatewaySettings();

        $result = $api->complete_payment( $inecobank_order_id, $order->get_total() );

        if ( $result['success'] ) {
            $order->payment_complete( $inecobank_order_id );
            $order->add_order_note( __( 'Two-phase payment completed via Inecobank.', 'woo-inecobank-payment-gateway' ) );

            // Show admin notice
            add_action( 'admin_notices', function () {
                echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Two-phase payment completed successfully.', 'woo-inecobank-payment-gateway' ) . '</p></div>';
            } );
        } else {
            $order->add_order_note( sprintf( __( 'Two-phase payment failed: %s', 'woo-inecobank-payment-gateway' ), $result['message'] ) );

            // Show admin notice
            add_action( 'admin_notices', function () use ( $result ) {
                echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( __( 'Two-phase payment failed: %s', 'woo-inecobank-payment-gateway' ), $result['message'] ) . '</p></div>';
            } );
        }
    }

    /**
     * Reverse payment
     */
    public function reverse_payment( $order ) {
        $inecobank_order_id = get_post_meta( $_GET['order_id'], '_inecobank_order_id', true );

        if ( ! $inecobank_order_id ) {
            $order->add_order_note( __( 'Failed to reverse payment: Inecobank order ID not found.', 'woo-inecobank-payment-gateway' ) );

            return;
        }

        // Get gateway settings
        $api = $this->getGatewaySettings();

        $result = $api->register_order( $inecobank_order_id );

        if ( $result['success'] ) {
            $order->update_status( 'cancelled', __( 'Payment reversed via Inecobank.', 'woo-inecobank-payment-gateway' ) );

            // Show admin notice
            add_action( 'admin_notices', function () {
                echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Payment reversed successfully.', 'woo-inecobank-payment-gateway' ) . '</p></div>';
            } );
        } else {
            $order->add_order_note( sprintf( __( 'Payment reverse failed: %s', 'woo-inecobank-payment-gateway' ), $result['message'] ) );

            // Show admin notice
            add_action( 'admin_notices', function () use ( $result ) {
                echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( __( 'Payment reverse failed: %s', 'woo-inecobank-payment-gateway' ), $result['message'] ) . '</p></div>';
            } );
        }
    }

    /**
     * Display payment information in order admin
     */
    public function display_payment_info( $order ) {
        if ( $order->get_payment_method() !== 'inecobank' ) {
            return;
        }

        $inecobank_order_id = get_post_meta( $order->get_id(), '_inecobank_order_id', true );
        $payment_type       = get_post_meta( $order->get_id(), '_payment_type', true );
        $auth_ref_num       = get_post_meta( $order->get_id(), '_inecobank_auth_ref_num', true );
        $approval_code      = get_post_meta( $order->get_id(), '_inecobank_approval_code', true );
        $card_pan           = get_post_meta( $order->get_id(), '_inecobank_card_pan', true );

        ?>
        <div class="inecobank-payment-info">
            <h3><?php _e( 'Inecobank Payment Details', 'woo-inecobank-payment-gateway' ) ?></h3>
            <table class="widefat">
                <?php if ( $inecobank_order_id ): ?>
                    <tr>
                        <th><strong><?php _e( 'Inecobank Order ID', 'woo-inecobank-payment-gateway' ) ?></strong></th>
                        <td><?php echo esc_html( $inecobank_order_id ) ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ( $payment_type ): ?>
                    <tr>
                        <th><strong><?php _e( 'Payment Type', 'woo-inecobank-payment-gateway' ) ?></strong></th>
                        <td><?php echo $payment_type === 'two_phase' ? __( 'Two-Phase', 'woo-inecobank-payment-gateway' ) : __( 'One-Phase', 'woo-inecobank-payment-gateway' ) ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ( $auth_ref_num ): ?>
                    <tr>
                        <th>
                            <strong><?php _e( 'Auth Reference Number', 'woo-inecobank-payment-gateway' ) ?></strong>
                        </th>
                        <td><?php echo esc_html( $auth_ref_num ) ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ( $approval_code ): ?>
                    <tr>
                        <th><strong><?php _e( 'Approval Code', 'woo-inecobank-payment-gateway' ) ?></strong></th>
                        <td><?php echo esc_html( $approval_code ) ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ( $card_pan ): ?>
                    <tr>
                        <th><strong><?php _e( 'Card PAN', 'woo-inecobank-payment-gateway' ) ?></strong></th>
                        <td><?php echo esc_html( $card_pan ) ?></td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
        <?php
    }

    /**
     * Get gateway settings
     *
     * @return Woo_Inecobank_API
     */
    public function getGatewaySettings(): Woo_Inecobank_API {
        $gateway  = WC()->payment_gateways()->payment_gateways()['inecobank'];
        $settings = $gateway->get_settings();

        $credentials = [
                'username' => $gateway->testmode ? $settings['test_username'] : $settings['username'],
                'password' => $gateway->testmode ? $settings['test_password'] : $settings['password'],
                'language' => $settings['language'],
        ];

        $logger = new Woo_Inecobank_Logger( $gateway->debug_mode );

        return new Woo_Inecobank_API( $credentials, $gateway->testmode, $logger );
    }
}
