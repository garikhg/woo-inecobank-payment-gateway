<?php
/**
 * Inecobank API Handler
 *
 * @package WooCommerce Inecobank Payment Gateway
 * @version 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Inecobank API Class
 *
 * @since 1.0.0
 */
class Woo_Inecobank_API {

	/**
	 * API Base URL
	 */
	const API_URL = 'https://pg.inecoecom.am/payment/rest/';

	/**
	 * API Timeout in seconds
	 */
	const API_TIMEOUT = 45;

	/**
	 * API credentials
	 *
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
	 * @var Woo_Inecobank_Logger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @param array $credentials API credentials.
	 * @param bool $testmode Test mode flag.
	 * @param Woo_Inecobank_Logger $logger Logger instance.
	 */
	public function __construct( array $credentials, bool $testmode, $logger ) {
		$this->credentials = $credentials;
		$this->testmode    = $testmode;
		$this->logger      = $logger;
	}

	/**
	 * Register Order
	 *
	 * @param WC_Order $order Order object.
	 * @param string $payment_type Payment type (one_phase or two_phase).
	 *
	 * @return array
	 */
	public function register_order( WC_Order $order, string $payment_type = 'one_phase' ): array {
		$endpoint = 'two_phase' === $payment_type ? 'registerPreAuth.do' : 'register.do';

		$request_data = array(
			'userName'    => $this->credentials['username'],
			'password'    => $this->credentials['password'],
			'orderNumber' => $this->get_order_number( $order ),
			'amount'      => $this->get_amount( $order->get_total() ),
			'currency'    => $this->get_currency_code( $order->get_currency() ),
			'returnUrl'   => $this->get_return_url(),
			'description' => $this->get_order_description( $order ),
			'language'    => $this->credentials['language'],
			'pageView'    => 'DESKTOP',
			'clientId'    => $this->get_client_id( $order ),
			'jsonParams'  => json_encode( array(
				'orderNumber' => $order->get_order_number(),
				'orderId'     => $order->get_id(),
			) ),
		);

		// Add optional fields
		if ( $order->get_billing_phone() ) {
			$request_data['phone'] = $this->sanitize_phone( $order->get_billing_phone() );
		}

		if ( $order->get_billing_email() ) {
			$request_data['email'] = $order->get_billing_email();
		}

		$request_data = apply_filters( 'woo_inecobank_register_order_data', $request_data, $order );

		$this->logger->log( 'Register order request for order #' . $order->get_id() . ' using ' . $payment_type );

		$response = $this->send_request( $endpoint, $request_data );

		// Check for successful registration
		if ( isset( $response['errorCode'] ) && 0 === (int) $response['errorCode'] && isset( $response['formUrl'] ) ) {
			return array(
				'success'  => true,
				'order_id' => $response['orderId'],
				'form_url' => $response['formUrl'],
			);
		} else {
			$error_message = $this->get_error_message( $response );
			$this->logger->error( 'Order registration failed: ' . $error_message );

			return array(
				'success'       => false,
				'error_message' => $error_message,
				'error_code'    => $response['errorCode'] ?? 'unknown',
			);
		}
	}

	/**
	 * Get Order Status
	 *
	 * @param string $inecobank_order_id Inecobank order ID.
	 *
	 * @return array|false
	 */
	public function get_order_status( string $inecobank_order_id ) {
		$request_data = array(
			'userName' => $this->credentials['username'],
			'password' => $this->credentials['password'],
			'orderId'  => $inecobank_order_id,
			'language' => $this->credentials['language'],
		);

		$this->logger->log( 'Getting order status for: ' . $inecobank_order_id );

		$response = $this->send_request( 'getOrderStatusExtended.do', $request_data );

		if ( isset( $response['errorCode'] ) && 0 === (int) $response['errorCode'] ) {
			return $response;
		}

		$this->logger->error( 'Failed to get order status: ' . wp_json_encode( $response ) );

		return false;
	}

	/**
	 * Complete two-phase payment
	 *
	 * @param string $inecobank_order_id Inecobank order ID.
	 * @param float $amount Amount to complete.
	 *
	 * @return array
	 */
	public function complete_payment( string $inecobank_order_id, float $amount ): array {
		$request_data = array(
			'userName' => $this->credentials['username'],
			'password' => $this->credentials['password'],
			'orderId'  => $inecobank_order_id,
			'amount'   => $this->get_amount( $amount ),
		);

		$this->logger->log( 'Completing payment for order: ' . $inecobank_order_id );

		$response = $this->send_request( 'deposit.do', $request_data );

		if ( isset( $response['errorCode'] ) && 0 === (int) $response['errorCode'] ) {
			return array( 'success' => true );
		} else {
			$error_message = $this->get_error_message( $response, __( 'Payment completion failed.', 'woo-inecobank-payment-gateway' ) );

			return array(
				'success' => false,
				'message' => $error_message,
			);
		}
	}

	/**
	 * Process refund
	 *
	 * @param string $inecobank_order_id Inecobank order ID.
	 * @param float|null $amount Refund amount.
	 * @param string $reason Refund reason.
	 *
	 * @return array
	 */
	public function process_refund( string $inecobank_order_id, $amount = null, string $reason = '' ): array {
		$request_data = array(
			'userName' => $this->credentials['username'],
			'password' => $this->credentials['password'],
			'orderId'  => $inecobank_order_id,
			'amount'   => $this->get_amount( $amount ),
		);

		$this->logger->log( 'Processing refund for order: ' . $inecobank_order_id . ', amount: ' . $amount );

		$response = $this->send_request( 'refund.do', $request_data );

		if ( isset( $response['errorCode'] ) && 0 === (int) $response['errorCode'] ) {
			return array( 'success' => true );
		} else {
			$error_message = $this->get_error_message( $response, __( 'Refund failed.', 'woo-inecobank-payment-gateway' ) );

			return array(
				'success' => false,
				'message' => $error_message,
			);
		}
	}

	/**
	 * Reverse order
	 *
	 * @param string $inecobank_order_id Inecobank order ID.
	 *
	 * @return array
	 */
	public function reverse_order( string $inecobank_order_id ): array {
		$request_data = array(
			'userName' => $this->credentials['username'],
			'password' => $this->credentials['password'],
			'orderId'  => $inecobank_order_id,
		);

		$this->logger->log( 'Reversing order: ' . $inecobank_order_id );

		$response = $this->send_request( 'reverse.do', $request_data );

		if ( isset( $response['errorCode'] ) && 0 === (int) $response['errorCode'] ) {
			return array( 'success' => true );
		} else {
			$error_message = $this->get_error_message( $response, __( 'Order reversal failed.', 'woo-inecobank-payment-gateway' ) );

			return array(
				'success' => false,
				'message' => $error_message,
			);
		}
	}

	/**
	 * Send API request
	 *
	 * @param string $endpoint API endpoint.
	 * @param array $request_data Request data.
	 *
	 * @return array
	 */
	private function send_request( string $endpoint, array $request_data ): array {
		$url = self::API_URL . $endpoint;

		$this->logger->log( 'Sending request to: ' . $endpoint );

		// Check if we're in local environment
		if ( $this->is_local_environment() ) {
			$this->logger->log( 'Local environment detected - attempting connection with extended timeout' );
		}

		$response = wp_remote_post(
			$url,
			array(
				'method'      => 'POST',
				'headers'     => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
				'body'        => $request_data,
				'timeout'     => self::API_TIMEOUT,
				'sslverify'   => ! $this->testmode, // Disable SSL verification in test mode
				'httpversion' => '1.1',
			)
		);

		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->logger->error( 'API request failed: ' . $error_message );

			return array(
				'errorCode'    => '999',
				'errorMessage' => $error_message,
			);
		}

		$body        = wp_remote_retrieve_body( $response );
		$status_code = wp_remote_retrieve_response_code( $response );
		$result      = json_decode( $body, true );

		$this->logger->log( 'API response (HTTP ' . $status_code . '): ' . $body );

		if ( ! $result || ! is_array( $result ) ) {
			return array(
				'errorCode'    => '999',
				'errorMessage' => __( 'Invalid response from payment gateway.', 'woo-inecobank-payment-gateway' ),
			);
		}

		return $result;
	}

	/**
	 * Check if running in local environment
	 *
	 * @return bool
	 */
	private function is_local_environment(): bool {
		$local_indicators = array( 'localhost', '127.0.0.1', '.local', '.test', 'flywheel', 'vagrant', 'docker' );
		$server_name      = $_SERVER['SERVER_NAME'] ?? '';

		foreach ( $local_indicators as $indicator ) {
			if ( false !== stripos( $server_name, $indicator ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get error message from API response
	 *
	 * @param array $response API response.
	 * @param string $default_message Default message.
	 *
	 * @return string
	 */
	private function get_error_message( array $response, string $default_message = '' ): string {
		if ( empty( $default_message ) ) {
			$default_message = __( 'Payment error occurred. Please try again.', 'woo-inecobank-payment-gateway' );
		}

		if ( isset( $response['errorMessage'] ) && ! empty( $response['errorMessage'] ) ) {
			return sanitize_text_field( $response['errorMessage'] );
		}

		return $default_message;
	}

	/**
	 * Get return URL for payment gateway callback
	 *
	 * @return string
	 */
	private function get_return_url(): string {
		return WC()->api_request_url( 'woo_inecobank_gateway' );
	}

	/**
	 * Generate a unique order number
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return string
	 */
	private function get_order_number( WC_Order $order ): string {
		return $order->get_order_number() . '_' . time();
	}

	/**
	 * Get order description
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return string
	 */
	private function get_order_description( WC_Order $order ): string {
		$description = sprintf(
		/* translators: %s: order number */
			__( 'Order #%s', 'woo-inecobank-payment-gateway' ),
			$order->get_order_number()
		);

		return apply_filters( 'woo_inecobank_payment_description', $description, $order );
	}

	/**
	 * Get client ID
	 *
	 * @param WC_Order $order Order object.
	 *
	 * @return string
	 */
	private function get_client_id( WC_Order $order ): string {
		$client_id = $order->get_customer_id();

		if ( ! $client_id ) {
			$client_id = $order->get_billing_email();
		}

		return (string) $client_id;
	}

	/**
	 * Sanitize phone number
	 *
	 * @param string $phone Phone number.
	 *
	 * @return string
	 */
	private function sanitize_phone( string $phone ): string {
		// Remove all non-numeric characters except +
		$phone = preg_replace( '/[^0-9+]/', '', $phone );

		return substr( $phone, 0, 20 ); // Limit to 20 characters
	}

	/**
	 * Convert amount to minor units
	 *
	 * @param float $amount Amount in major units.
	 *
	 * @return int
	 */
	private function get_amount( float $amount ): int {
		// Convert to minor units (cents)
		return (int) round( $amount * 100 );
	}

	/**
	 * Get currency code in ISO 4217 numeric format
	 *
	 * @param string $currency Currency code.
	 *
	 * @return string
	 */
	private function get_currency_code( string $currency ): string {
		$codes = array(
			'AMD' => '051',
			'USD' => '840',
			'EUR' => '978',
			'RUB' => '643',
		);

		return $codes[ $currency ] ?? '051'; // Default to AMD
	}
}
