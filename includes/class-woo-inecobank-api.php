<?php
/**
 * Inecobank API Handler
 *
 * @package WooCommerce Inecobank Payment Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Inecobank_API class
 */
class Woo_Inecobank_API {

	/**
	 * API Base URL
	 */
	const API_URL = 'https://pg.inecoecom.am/payment/rest/';

	/**
	 * API credentials
	 * @var array
	 */
	private $credentials;

	/**
	 * Test mode flag
	 *
	 * @var bool
	 */
	private $testmode;

	/**
	 * Logger instance
	 *
	 * @var Inecobank_Logger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @param array $credentials
	 * @param bool $testmode
	 * @param Inecobank_Logger $logger
	 */
	public function __construct( array $credentials, $testmode, $logger ) {
		$this->credentials = $credentials;
		$this->testmode    = $testmode;
		$this->logger      = $logger;
	}

	/**
	 * Register Order
	 *
	 * @param WC_Order $order
	 * @param string $payment_type
	 *
	 * @return array
	 */
	public function register_order( $order, $payment_type = 'one_phase' ) {
		$endpoint = $payment_type === 'two_phase' ? 'registerPreAuth.do' : 'register.do';

		$request_data = [
			'userName'    => $this->credentials['username'],
			'password'    => $this->credentials['password'],
			'orderNumber' => $this->get_order_number( $order ),
			'amount'      => $this->get_amount( $order->get_total() ),
			'currency'    => $this->get_currency_code( $order->get_currency() ),
			'returnUrl'   => WC()->api_request_url( 'wc_inecobank_payment_gateway' ),
			'description' => $this->get_order_description( $order ),
			'language'    => $this->credentials['language'],
			'pageView'    => 'DESKTOP',
			'clientId'    => $this->get_client_id( $order ),
			'phone'       => $order->get_billing_phone(),
			'email'       => $order->get_billing_email(),
		];

		$request_data = apply_filters( 'woo_inecobank_register_order_data', $request_data, $order );

		$this->logger->log( 'Register order request: ' . print_r( $request_data, true ) );

		$response = $this->send_request( $endpoint, $request_data );

		if ( isset( $response['errorCode'] ) && $response['errorCode'] == 0 && isset( $response['formUrl'] ) ) {
			return [
				'success'  => true,
				'order_id' => $response['orderId'],
				'form_url' => $response['formUrl'],
			];
		} else {
			$error_message = $response['errorMessage'] ?? __( 'Payment error occurred.', 'woo-inecobank-payment-gateway' );

			return [
				'success'       => false,
				'error_message' => $error_message,
			];
		}
	}

	/**
	 * Get Order Status
	 *
	 * @param string $inecobank_order_id
	 *
	 * @return array|bool
	 */
	public function get_order_status( $inecobank_order_id ) {
		$request_data = [
			'userName' => $this->credentials['username'],
			'password' => $this->credentials['password'],
			'orderId'  => $inecobank_order_id,
			'language' => $this->credentials['language'],
		];

		$this->logger->log( 'Getting order status for: ' . $inecobank_order_id );

		$response = $this->send_request( 'getOrderStatusExtended.do', $request_data );

		if ( isset( $response['errorCode'] ) && $response['errorCode'] == 0 ) {
			return $response;
		}

		$this->logger->log( 'Failed to get order status: ' . json_encode( $response ), 'error' );

		return false;
	}

	/**
	 * Complete two-phase payment
	 *
	 * @param string $inecobank_order_id
	 * @param float $amount
	 */
	public function complete_payment( $inecobank_order_id, $amount ) {
		$request_data = [
			'userName' => $this->credentials['username'],
			'password' => $this->credentials['password'],
			'orderId'  => $inecobank_order_id,
			'amount'   => $this->get_amount( $amount ),
		];

		$this->logger->log( 'Completing payment for order: ' . $inecobank_order_id );

		$response = $this->send_request( 'deposit.do', $request_data );

		if ( isset( $response['errorCode'] ) && $response['errorCode'] == 0 ) {
			return [ 'success' => true ];
		} else {
			$error_message = $response['errorMessage'] ?? __( 'Payment completion failed.', 'woo-inecobank-payment-gateway' );

			return [
				'success'       => false,
				'error_message' => $error_message
			];
		}
	}

	/**
	 * Process refund
	 *
	 * @param string $inecobank_order_id
	 * @param float|null $amount
	 * @param string $reason
	 */
	public function process_refund( $inecobank_order_id, $amount = null, $reason = '' ) {
		$request_data = [
			'userName' => $this->credentials['username'],
			'password' => $this->credentials['password'],
			'orderId'  => $inecobank_order_id,
			'amount'   => $this->get_amount( $amount ),
		];

		$this->logger->log( 'Processing refund for order: ' . $inecobank_order_id, 'amount: ' . $amount );

		$response = $this->send_request( 'refund.do', $request_data );

		if ( isset( $response['errorCode'] ) && $response['errorCode'] == 0 ) {
			return [ 'success' => true ];
		} else {
			$error_message = $response['errorMessage'] ?? __( 'Refund failed.', 'woo-inecobank-payment-gateway' );

			return [
				'success'       => false,
				'error_message' => $error_message
			];
		}
	}

	/**
	 * Reverse order
	 *
	 * @param string $inecobank_order_id
	 *
	 * @return array
	 */
	public function reverse_order( $inecobank_order_id ) {
		$request_data = [
			'userName' => $this->credentials['username'],
			'password' => $this->credentials['password'],
			'orderId'  => $inecobank_order_id,
		];

		$this->logger->log( 'Reversing order: ' . $inecobank_order_id );

		$response = $this->send_request( 'reverse.do', $request_data );

		if ( isset( $response['errorCode'] ) && $response['errorCode'] == 0 ) {
			return [ 'success' => true ];
		} else {
			$error_message = $response['errorMessage'] ?? __( 'Order reversal failed.', 'woo-inecobank-payment-gateway' );

			return [
				'success'       => false,
				'error_message' => $error_message
			];
		}
	}

	/**
	 * Send API request
	 */
	private function send_request( $endpoint, $request_data ) {
		$url = self::API_URL . $endpoint;
		$this->logger->log( 'Sending request to: ' . $url );

		$response = wp_remote_post( $url, [
			'method'    => 'POST',
			'headers'   => [ 'Content-Type' => 'application/x-www-form-urlencoded' ],
			'body'      => $request_data,
			'timeout'   => 70,
			'sslverify' => true
		] );

		if ( is_wp_error( $response ) ) {
			$this->logger->log( 'API request failed: ' . $response->get_error_message(), 'error' );

			return [
				'errorCode'    => '999',
				'errorMessage' => $response->get_error_message(),
			];
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		$this->logger->log( 'API response: ' . $body );

		return ! $result ? [ 'errorCode' => '999', 'errorMessage' => 'Invalid response from API.' ] : $result;
	}

	/**
	 * Generate a unique order number
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	private function get_order_number( $order ) {
		return $order->get_order_number() . '_' . time();
	}

	/**
	 * Get order description
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	private function get_order_description( $order ) {
		$description = sprintf( __( 'Order %s', 'woo-inecobank-payment-gateway' ), $order->get_order_number() );

		return apply_filters( 'woo_inecobank_payment_description', $description, $order );
	}

	/**
	 * Get client ID
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	private function get_client_id( $order ) {
		$client_id = $order->get_customer_id();
		if ( ! $client_id ) {
			$client_id = $order->get_billing_email();
		}

		return (string) $client_id;
	}

	/**
	 * Convert amount to minor units
	 *
	 * @param float $amount
	 *
	 * @return int
	 */
	private function get_amount( $amount ) {
		// Convert to minor units (cents)
		return intval( $amount * 100 );
	}

	/**
	 * Get currency code in ISO 4217 format
	 *
	 * @param string $currency
	 *
	 * @return string
	 */
	private function get_currency_code( $currency ) {
		$codes = [
			'AMD' => '051',
			'USD' => '840',
			'EUR' => '978',
			'RUB' => '643',
		];

		return $codes[ $currency ] ?? '051';
	}
}
