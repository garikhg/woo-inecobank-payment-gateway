<?php
/**
 * Inecobank Webhook Handler
 *
 * @package WooCommerce Inecobank Payment Gateway
 */

/**
 * Inecobank_Webhook class
 */
class Woo_Inecobank_Webhook
{
	/**
	 * API handler instance
	 * @var Woo_Inecobank_API
	 */
	private $api;

	/**
	 * Logger instance
	 * @var Woo_Inecobank_Logger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @param Woo_Inecobank_API $api
	 * @param Woo_Inecobank_Logger $logger
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
		$this->logger->log('Webhook received: ' . json_encode($_GET));

		// Inecobank sends 'orderID' with capital D, but handle both cases
		$order_id = '';
		if (isset($_GET['orderID'])) {
			$order_id = sanitize_text_field($_GET['orderID']);
		} elseif (isset($_GET['orderId'])) {
			$order_id = sanitize_text_field($_GET['orderId']);
		}

		if (empty($order_id)) {
			$this->logger->log('Invalid webhook request: No orderID/orderId parameter', 'error');
			wp_die('Invalid request', 'Inecobank Payment', array('response' => 400));
		}

		// Find WooCommerce order by the order number sent to Inecobank
		$order = $this->find_order_by_inecobank_id($order_id);

		if (!$order) {
			$this->logger->log('Order not found for Inecobank ID: ' . $order_id, 'error');
			wp_die('Invalid request', 'Inecobank Payment', array('response' => 400));
		}

		$this->logger->log('Processing webhook for WC Order #' . $order->get_id() . ', Current Status: ' . $order->get_status());

		// Get the UUID saved when order was registered
		$inecobank_uuid = $order->get_meta('_inecobank_uuid');

		if (empty($inecobank_uuid)) {
			$this->logger->log('No Inecobank UUID found for order #' . $order->get_id(), 'error');
			wp_die('Invalid order data', 'Inecobank Payment', array('response' => 400));
		}

		// Verify payment status with Inecobank API using the UUID
		$status = $this->api->get_order_status($inecobank_uuid);

		if ($status && isset($status['orderStatus'])) {
			$this->logger->log('Inecobank Order Status: ' . $status['orderStatus'] . ' for Order #' . $order->get_id());

			// Process the status update
			$this->process_order_status($order, $status);

			// Log the final order status
			$order->reload(); // Reload order to get updated status
			$this->logger->log('Order #' . $order->get_id() . ' status after processing: ' . $order->get_status());
		} else {
			$this->logger->log('Failed to get order status from Inecobank API for Order #' . $order->get_id(), 'error');
		}

		// Redirect to appropriate page based on order status
		$redirect_url = $this->get_redirect_url($order);
		$this->logger->log('Redirecting to: ' . $redirect_url);

		wp_redirect($redirect_url);
		exit();
	}

	/**
	 * Find order by Inecobank order ID
	 *
	 * @param string $inecobank_order_id
	 *
	 * @return WC_Order|false
	 */
	private function find_order_by_inecobank_id($inecobank_order_id)
	{
		$orders = wc_get_orders(
			[
				'meta_key' => '_inecobank_order_id',
				'meta_value' => $inecobank_order_id,
				'limit' => 1
			]
		);

		return !empty($orders[0]) ? $orders[0] : false;
	}

	/**
	 * Process order status
	 *
	 * @param WC_Order $order
	 * @param array $status
	 */
	private function process_order_status($order, $status)
	{
		$order_status = $status['orderStatus'];
		$inecobank_order_id = get_post_meta($order->get_id(), '_inecobank_order_id', true);

		$this->logger->log('Processing order status ' . $order_status . ' for order #' . $order->get_id());

		switch ($order_status) {
			case 0:
				// Order registered, but not paid
				$this->logger->log('Order registered, but not paid: #' . $order->get_id());
				break;
			case 1:
				// Preauthorized (two-phase payment)
				if ($order->get_status() !== 'on-hold') {
					$order->update_status('on-hold', __('Payment preauthorized. Complete the payment from admin panel.', 'woo-inecobank-payment-gateway'));
					$this->save_payment_details($order, $status);
				}
				break;
			case 2:
				// Deposited (payment completed)
				if (!$order->is_paid()) {
					// Save payment details first to set transaction ID
					$this->save_payment_details($order, $status);

					// Then mark as paid - use terminal ID if available, otherwise use Inecobank order ID
					$transaction_id = isset($status['authRefNum']) && !empty($status['authRefNum'])
						? $status['authRefNum']
						: $inecobank_order_id;

					$order->payment_complete($transaction_id);
					$order->add_order_note(
						sprintf(
							__('Payment completed via Inecobank. Terminal ID: %s', 'woo-inecobank-payment-gateway'),
							$transaction_id
						)
					);

					do_action('woo_inecobank_payment_complete', $order->get_id(), $inecobank_order_id);
				}
				break;
			case 3:
				// Reversed
				if ($order->get_status() !== 'failed') {
					$order->update_status('failed', __('Payment was reversed.', 'woo-inecobank-payment-gateway'));
				}
				break;
			case 4:
				// Refunded
				if ($order->get_status() !== 'refunded') {
					$order->update_status('refunded', __('Payment refunded.', 'woo-inecobank-payment-gateway'));
				}
				break;
			case 5:
				// Authorization initiated
				$this->logger->log('Authorization initiated: #' . $order->get_id());
				break;
			case 6:
				// Declined
				if ($order->get_status() !== 'failed') {
					$order->update_status('failed', __('Payment was declined.', 'woo-inecobank-payment-gateway'));
				}
			default:
				$this->logger->log('Unknown order status: #' . $order->get_id());
				break;
		}
	}

	/**
	 * Save payment details to order
	 */
	private function save_payment_details($order, $status)
	{
		// Set WooCommerce transaction ID to bank terminal/reference number
		if (isset($status['authRefNum']) && !empty($status['authRefNum'])) {
			$order->set_transaction_id($status['authRefNum']);
			update_post_meta($order->get_id(), '_inecobank_auth_ref_num', $status['authRefNum']);
		}

		if (isset($status['actionCode'])) {
			update_post_meta($order->get_id(), '_inecobank_action_code', $status['actionCode']);
		}

		if (isset($status['cardAuthInfo']['pan'])) {
			update_post_meta($order->get_id(), '_inecobank_card_pan', $status['cardAuthInfo']['pan']);
		}

		if (isset($status['cardAuthInfo']['approvalCode'])) {
			update_post_meta($order->get_id(), '_inecobank_approval_code', $status['cardAuthInfo']['approvalCode']);
		}

		// Save transaction date
		if (isset($status['date'])) {
			update_post_meta($order->get_id(), '_inecobank_transaction_date', $status['date']);
		}

		// Save the order to persist transaction ID
		$order->save();
	}

	/**
	 * Get redirect URL based on order status
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	private function get_redirect_url($order)
	{
		$order_status = $order->get_status();

		// For successful payments, redirect to order received page
		if (in_array($order_status, array('processing', 'completed', 'on-hold'))) {
			$redirect_url = $this->get_success_url($order);
		} // For failed payments, redirect to checkout with error
		elseif (in_array($order_status, array('failed', 'cancelled'))) {
			wc_add_notice(__('Payment failed. Please try again.', 'woo-inecobank-payment-gateway'), 'error');
			$redirect_url = $order->get_checkout_payment_url();
		} // For pending or other statuses, redirect to order pay page
		else {
			$redirect_url = $order->get_checkout_payment_url();
		}

		return apply_filters('woo_inecobank_redirect_url', $redirect_url, $order);
	}

	/**
	 * Get success/thank you page URL
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	private function get_success_url($order)
	{
		// Use WooCommerce standard order received URL
		$return_url = $order->get_checkout_order_received_url();

		return apply_filters('woo_inecobank_success_url', $return_url, $order);
	}

	/**
	 * Get return URL (backward compatibility)
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	private function get_return_url($order)
	{
		return $this->get_redirect_url($order);
	}


}
