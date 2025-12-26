<?php
/**
 * Inecobank Refund Handler
 *
 * @package WooCommerce Inecobank Payment Gateway
 */

class Woo_Inecobank_Refund
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
	 * Process refund
	 */
	public function process_refund($order_id, $amount = null, $reason = '')
	{
		$order = wc_get_order($order_id);
		if (!$order) {
			$this->logger->log('Refund failed: Order not found #' . $order_id, 'error');

			return new WP_Error('error', __('Order not found.', 'woo-inecobank-payment-gateway'));
		}

		$inecobank_order_id = get_post_meta($order_id, '_inecobank_order_id', true);
		if (!$inecobank_order_id) {
			$this->logger->log('Refund failed: Inecobank order ID not found for order #' . $order_id, 'error');

			return new WP_Error('error', __('Inecobank order ID not found.', 'woo-inecobank-payment-gateway'));
		}

		// Validate refund amount
		if (is_null($amount) || $amount <= 0) {
			$this->logger->log('Refund failed: Invalid refund amount for order #' . $order_id, 'error');

			return new WP_Error('error', __('Invalid refund amount.', 'woo-inecobank-payment-gateway'));
		}

		// Check if order is paid
		if (!$order->is_paid()) {
			$this->logger->log('Refund failed: Order is not paid #' . $order_id, 'error');

			return new WP_Error('error', __('Order must be paid before refunding.', 'woo-inecobank-payment-gateway'));
		}

		// Check refund limit
		$total_refunded = $order->get_total_refunded();
		$order_total = $order->get_total();

		if (($total_refunded + $amount) > ($order_total)) {
			$this->logger->log('Refund failed: Amount exceeds order total', 'error');

			return new WP_Error('error', __('Refund amount exceeds order total.', 'woo-inecobank-payment-gateway'));
		}

		$this->logger->log('Processing refund for order #' . $order_id . ', amount: ' . $amount . ', reason: ' . $reason);

		// Process refund via API
		$result = $this->api->process_refund($inecobank_order_id, $amount);
		if ($result['success']) {
			// Add order note
			$order->add_order_note(
				sprintf(
					__('Refunded %s via Inecobank. Reason: %s', 'woo-inecobank-payment-gateway'),
					wc_price($amount),
					$reason
				)
			);

			// Save refund details
			$this->save_refund_details($order_id, $amount, $reason);

			$this->logger->log('Refund processed successfully for order #' . $order_id);

			do_action('woo_inecobank_refund_complete', $order_id, $amount);

			return true;
		} else {
			$this->logger->log('Refund failed: ' . $result['message'], 'error');

			return new WP_Error('error', $result['message']);
		}
	}

	/**
	 * Save refund details
	 *
	 * @param int $order_id
	 * @param int $amount
	 * @param string $reason
	 */
	public function save_refund_details($order_id, $amount, $reason)
	{
		$refunds = get_post_meta($order_id, '_inecobank_refunds', true);

		if (!is_array($refunds)) {
			$refunds = [];
		}

		$refunds[] = array(
			'amount' => $amount,
			'reason' => $reason,
			'date' => current_time('mysql')
		);

		update_post_meta($order_id, '_inecobank_refunds', $refunds);
	}

	/**
	 * Get refund history
	 *
	 * @param int $order_id
	 *
	 * @return array
	 */
	public function get_refund_history($order_id)
	{
		$refunds = get_post_meta($order_id, '_inecobank_refunds', true);

		return is_array($refunds) ? $refunds : [];
	}
}
