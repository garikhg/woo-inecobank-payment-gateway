<?php
/**
 * Inecobank Webhook Handler
 *
 * @package WooCommerce Inecobank Payment Gateway
 */

/**
 * Inecobank_Webhook class
 */
class Woo_Inecobank_Webhook {
	/**
	 * API handler instance
	 * @var Inecobank_API
	 */
	private $api;

	/**
	 * Logger instance
	 * @var Inecobank_Logger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @param Inecobank_API $api
	 * @param Inecobank_Logger $logger
	 */
	public function __construct( $api, $logger ) {
		$this->api    = $api;
		$this->logger = $logger;
	}

	/**
	 * Handle webhook callback
	 */
	public function handle_webhook() {
		$this->logger->log( 'Webhook received: ' . json_encode( $_GET ) );

		$order_id = isset( $_GET['orderId'] ) ? sanitize_text_field( $_GET['orderId'] ) : '';

		if ( empty( $order_id ) ) {
			$this->logger->log( 'Invalid webhook request: No orderId', 'error' );
			wp_die( 'Invalid request', 'Inecobank Payment', array( 'response' => 400 ) );
		}

		// Find order by Inecobank order ID
		$order = $this->find_order_by_inecobank_id( $order_id );

		if ( ! $order ) {
			$this->logger->log( 'Order not found for Inecobank ID: ' . $order_id, 'error' );
			wp_redirect( wc_get_page_permalink( 'cart' ) );
			exit;
		}

		// Verify payment status
		$status = $this->api->get_order_status( $order_id );
		if ( $status && isset( $status['orderStatus'] ) ) {
			$this->process_order_status( $order, $status );
		}

		// Redirect to order received page
		wp_redirect( $this->get_return_url( $order ) );
		exit();
	}

	/**
	 * Find order by Inecobank order ID
	 *
	 * @param string $inecobank_order_id
	 *
	 * @return WC_Order|false
	 */
	private function find_order_by_inecobank_id( $inecobank_order_id ) {
		$orders = wc_get_orders(
			[
				'meta_key'   => '_inecobank_order_id',
				'meta_value' => $inecobank_order_id,
				'limit'      => 1
			]
		);

		return ! empty( $orders[0] ) ? $orders[0] : false;
	}

	/**
	 * Process order status
	 *
	 * @param WC_Order $order
	 * @param array $status
	 */
	private function process_order_status( $order, $status ) {
		$order_status       = $status['orderStatus'];
		$inecobank_order_id = get_post_meta( $order->get_id(), '_inecobank_order_id', true );

		$this->logger->log( 'Processing order status ' . $order_status . ' for order #' . $order->get_id() );

		switch ( $order_status ) {
			case 0:
				// Order registered, but not paid
				$this->logger->log( 'Order registered, but not paid: #' . $order->get_id() );
				break;
			case 1:
				// Preauthorized (two-phase payment)
				if ( $order->get_status() !== 'on-hold' ) {
					$order->update_status( 'on-hold', __( 'Payment preauthorized. Complete the payment from admin panel.', 'woo-inecobank-payment-gateway' ) );
					$this->save_payment_details( $order, $status );
				}
				break;
			case 2:
				// Deposited (payment completed)
				if ( ! $order->is_paid() ) {
					$order->payment_complete( $inecobank_order_id );
					$order->add_order_note( __( 'Payment completed via Inecobank', 'woo-inecobank-payment-gateway' ) );
					$this->save_payment_details( $order, $status );

					do_action( 'woo_inecobank_payment_complete', $order->get_id(), $inecobank_order_id );
				}
				break;
			case 3:
				// Reversed
				if ( $order->get_status() !== 'failed' ) {
					$order->update_status( 'failed', __( 'Payment was reversed.', 'woo-inecobank-payment-gateway' ) );
				}
				break;
			case 4:
				// Refunded
				if ( $order->get_status() !== 'refunded' ) {
					$order->update_status( 'refunded', __( 'Payment refunded.', 'woo-inecobank-payment-gateway' ) );
				}
				break;
			case 5:
				// Authorization initiated
				$this->logger->log( 'Authorization initiated: #' . $order->get_id() );
				break;
			case 6:
				// Declined
				if ( $order->get_status() !== 'failed' ) {
					$order->update_status( 'failed', __( 'Payment was declined.', 'woo-inecobank-payment-gateway' ) );
				}
			default:
				$this->logger->log( 'Unknown order status: #' . $order->get_id() );
				break;
		}
	}

	/**
	 * Save payment details to order
	 */
	private function save_payment_details( $order, $status ) {
		// todo: save payment details
		// Save transaction details
		if ( isset( $status['authRefNum'] ) ) {
			update_post_meta( $order->get_id(), '_inecobank_auth_ref_num', $status['authRefNum'] );
		}

		if ( isset( $status['actionCode'] ) ) {
			update_post_meta( $order->get_id(), '_inecobank_action_code', $status['actionCode'] );
		}

		if ( isset( $status['cardAuthInfo']['pan'] ) ) {
			update_post_meta( $order->get_id(), '_inecobank_pan', $status['cardAuthInfo']['pan'] );
		}

		if ( isset( $status['cardAuthInfo']['approvalCode'] ) ) {
			update_post_meta( $order->get_id(), '_inecobank_approval_code', $status['cardAuthInfo']['approvalCode'] );
		}

		// Save transaction date
		if (isset( $status['date'] ) ) {
			update_post_meta( $order->get_id(), '_inecobank_transaction_date', $status['date'] );
		}
	}

	/**
	 * Get return URL
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	private function get_return_url( $order ) {
		$return_url = $order->get_checkout_order_received_url();

		return apply_filters( 'woo_inecobank_return_url', $return_url, $order );
	}

}
