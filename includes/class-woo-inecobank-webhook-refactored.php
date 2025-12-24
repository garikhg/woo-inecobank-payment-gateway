<?php
/**
 * Inecobank Webhook Handler
 *
 * @package WooCommerce Inecobank Payment Gateway
 * @version 1.1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Inecobank Webhook Class
 *
 * @since 1.0.0
 */
class Woo_Inecobank_Webhook
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
     * Constructor
     *
     * @param Woo_Inecobank_API    $api API handler.
     * @param Woo_Inecobank_Logger $logger Logger instance.
     */
    public function __construct($api, $logger)
    {
        $this->api = $api;
        $this->logger = $logger;
    }

    /**
     * Handle webhook callback
     */
    public function handle_webhook()
    {
        // Log incoming request
        $this->logger->log('Webhook received: ' . wp_json_encode($_GET));

        // Get orderId from query parameters
        $order_id = isset($_GET['orderId']) ? sanitize_text_field(wp_unslash($_GET['orderId'])) : '';

        if (empty($order_id)) {
            $this->logger->error('Invalid webhook request: No orderId');
            $this->send_error_response('Invalid request - missing orderId', 400);
        }

        // Find order by Inecobank order ID
        $order = $this->find_order_by_inecobank_id($order_id);

        if (!$order) {
            $this->logger->error('Order not found for Inecobank ID: ' . $order_id);
            $this->redirect_to_cart();
        }

        // Check if order is already completed
        if ($order->is_paid()) {
            $this->logger->log('Order #' . $order->get_id() . ' already paid. Redirecting to thank you page.');
            $this->redirect_to_order_received($order);
        }

        // Verify payment status with Inecobank API
        $status = $this->api->get_order_status($order_id);

        if ($status && isset($status['orderStatus'])) {
            $this->process_order_status($order, $status);
        } else {
            $this->logger->error('Failed to get order status from API for order: ' . $order_id);
            // Still redirect to thank you page - status will be updated by admin or cron
        }

        // Redirect to order received page
        $this->redirect_to_order_received($order);
    }

    /**
     * Find order by Inecobank order ID
     *
     * @param string $inecobank_order_id Inecobank order ID.
     *
     * @return WC_Order|false
     */
    private function find_order_by_inecobank_id(string $inecobank_order_id)
    {
        $orders = wc_get_orders(
            array(
                'meta_key' => '_inecobank_order_id',
                'meta_value' => $inecobank_order_id,
                'limit' => 1,
                'status' => array('pending', 'on-hold', 'processing'),
            )
        );

        return !empty($orders[0]) ? $orders[0] : false;
    }

    /**
     * Process order status based on API response
     *
     * @param WC_Order $order Order object.
     * @param array    $status Status from API.
     */
    private function process_order_status(WC_Order $order, array $status)
    {
        $order_status = isset($status['orderStatus']) ? (int) $status['orderStatus'] : -1;
        $inecobank_order_id = $order->get_meta('_inecobank_order_id', true);

        $this->logger->log('Processing order status ' . $order_status . ' for order #' . $order->get_id());

        switch ($order_status) {
            case 0:
                // Order registered, but not paid
                $this->logger->log('Order registered, but not paid: #' . $order->get_id());
                break;

            case 1:
                // Preauthorized (two-phase payment)
                if ('on-hold' !== $order->get_status()) {
                    $order->update_status('on-hold', __('Payment preauthorized. Complete the payment from admin panel.', 'woo-inecobank-payment-gateway'));
                    $this->save_payment_details($order, $status);
                    $this->logger->log('Order #' . $order->get_id() . ' preauthorized successfully');
                }
                break;

            case 2:
                // Deposited (payment completed)
                if (!$order->is_paid()) {
                    $order->payment_complete($inecobank_order_id);
                    $order->add_order_note(__('Payment completed via Inecobank', 'woo-inecobank-payment-gateway'));
                    $this->save_payment_details($order, $status);
                    $this->logger->log('Order #' . $order->get_id() . ' payment completed successfully');

                    do_action('woo_inecobank_payment_complete', $order->get_id(), $inecobank_order_id);
                }
                break;

            case 3:
                // Reversed
                if ('failed' !== $order->get_status()) {
                    $order->update_status('failed', __('Payment was reversed by the bank.', 'woo-inecobank-payment-gateway'));
                    $this->logger->log('Order #' . $order->get_id() . ' payment reversed');
                }
                break;

            case 4:
                // Refunded
                if ('refunded' !== $order->get_status()) {
                    $order->update_status('refunded', __('Payment refunded.', 'woo-inecobank-payment-gateway'));
                    $this->logger->log('Order #' . $order->get_id() . ' payment refunded');
                }
                break;

            case 5:
                // Authorization initiated
                $this->logger->log('Authorization initiated for order #' . $order->get_id());
                break;

            case 6:
                // Declined
                if ('failed' !== $order->get_status()) {
                    $order->update_status('failed', __('Payment was declined by the bank.', 'woo-inecobank-payment-gateway'));
                    $this->logger->log('Order #' . $order->get_id() . ' payment declined');
                }
                break;

            default:
                $this->logger->warning('Unknown order status (' . $order_status . ') for order #' . $order->get_id());
                break;
        }
    }

    /**
     * Save payment details to order metadata
     *
     * @param WC_Order $order Order object.
     * @param array    $status Payment status data.
     */
    private function save_payment_details(WC_Order $order, array $status)
    {
        // Save transaction reference number
        if (isset($status['authRefNum'])) {
            $order->update_meta_data('_inecobank_auth_ref_num', sanitize_text_field($status['authRefNum']));
        }

        // Save action code
        if (isset($status['actionCode'])) {
            $order->update_meta_data('_inecobank_action_code', sanitize_text_field($status['actionCode']));
        }

        // Save card details (masked PAN)
        if (isset($status['cardAuthInfo']['pan'])) {
            $order->update_meta_data('_inecobank_card_pan', sanitize_text_field($status['cardAuthInfo']['pan']));
        }

        // Save approval code
        if (isset($status['cardAuthInfo']['approvalCode'])) {
            $order->update_meta_data('_inecobank_approval_code', sanitize_text_field($status['cardAuthInfo']['approvalCode']));
        }

        // Save transaction date
        if (isset($status['date'])) {
            $order->update_meta_data('_inecobank_transaction_date', sanitize_text_field($status['date']));
        }

        // Save IP address
        if (isset($status['ip'])) {
            $order->update_meta_data('_inecobank_transaction_ip', sanitize_text_field($status['ip']));
        }

        $order->save();

        $this->logger->log('Payment details saved for order #' . $order->get_id());
    }

    /**
     * Get return URL for order
     *
     * @param WC_Order $order Order object.
     *
     * @return string
     */
    private function get_return_url(WC_Order $order): string
    {
        $return_url = $order->get_checkout_order_received_url();
        return apply_filters('woo_inecobank_return_url', $return_url, $order);
    }

    /**
     * Redirect to order received page
     *
     * @param WC_Order $order Order object.
     */
    private function redirect_to_order_received(WC_Order $order)
    {
        wp_safe_redirect($this->get_return_url($order));
        exit;
    }

    /**
     * Redirect to cart page
     */
    private function redirect_to_cart()
    {
        wp_safe_redirect(wc_get_cart_url());
        exit;
    }

    /**
     * Send error response and exit
     *
     * @param string $message Error message.
     * @param int    $code HTTP status code.
     */
    private function send_error_response(string $message, int $code = 400)
    {
        wp_die(
            esc_html($message),
            esc_html__('Inecobank Payment Error', 'woo-inecobank-payment-gateway'),
            array('response' => $code)
        );
    }
}
