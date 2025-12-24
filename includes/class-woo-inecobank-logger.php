<?php
/**
 * Inecobank Logger
 *
 * @package WooCommerce Inecobank Payment Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Woo_Inecobank_Logger {

	/**
	 * Debug mode flag
	 *
	 * @var bool
	 */
	private $debug_mode;

	/**
	 * Log file path
	 *
	 * @var string
	 */
	private $log_file;


	/**
	 * Constructor
	 *
	 * @param bool $debug_mode
	 */
	public function __construct( $debug_mode = false ) {
		$this->debug_mode = $debug_mode;

		$upload_dir = wp_upload_dir();
		$log_dir    = $upload_dir['basedir'] . '/inecobank-logs';

		// Create log directory if it doesn't exist
		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );

			// Create .htaccess to protect logs
			$htaccess_content = 'deny from all';
			file_put_contents( $log_dir . '/.htaccess', $htaccess_content );
		}

		$this->log_file = $log_dir . '/inecobank-gateway-' . date( 'Y-m-d' ) . '.log';
	}

	/**
	 * Log message
	 *
	 * @param string $message
	 * @param string $level
	 */
	public function log( $message, $level = 'info' ) {
		if ( ! $this->debug_mode && $level === 'info' ) {
			return;
		}

		$timestamp = date( 'Y-m-d H:i:s' );
		$log_entry = sprintf( "[%s] [%s]: %s\n", $timestamp, strtoupper( $level ), $message );

		// Write to file
		file_put_contents( $this->log_file, $log_entry, FILE_APPEND );

		// Also log to WooCommerce logger if available
		if ( function_exists( 'wc_get_logger' ) ) {
			$wc_logger = wc_get_logger();
			$wc_logger->log( $message, $message, [ 'source' => 'inecobank-gateway' ] );
		}
	}

	/**
	 * Log error
	 *
	 * @param string $message
	 */
	public function error( $message ) {
		$this->log( $message, 'error' );
	}

	/**
	 * Log warning
	 *
	 * @param string $message
	 */
	public function warning( $message ) {
		$this->log( $message, 'warning' );
	}

	/**
	 * Log info
	 *
	 * @param string $message
	 */
	public function info( $message ) {
		$this->log( $message, 'info' );
	}

	/**
	 * Clear old logs
	 *
	 * @param int $days
	 */
	public function clear_old_logs( $days = 30 ) {
		$upload_dir = wp_upload_dir();
		$log_dir    = $upload_dir['basedir'] . '/inecobank-logs';

		if ( ! is_dir( $log_dir ) ) {
			return;
		}

		$files = glob( $log_dir . '/*.log' );
		$now   = time();
		foreach ( $files as $file ) {
			if ( $now - filemtime( $file ) >= 60 * 60 * 24 * $days ) {
				unlink( $file );
			}
		}
	}
}
