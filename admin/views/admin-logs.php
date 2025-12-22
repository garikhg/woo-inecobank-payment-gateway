<?php
/**
 * Admin Logs View
 *
 * @package WooCommerce Inecobank Payment Gateway
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<div class="wrap">
    <h1><?php _e( 'Inecobank Payment Gateway Logs', 'woo-inecobank-payment-gateway' ); ?></h1>

    <div class="inecobank-logs-header" style="margin-bottom: 20px;">
        <form method="post" style="display: inline-block">
            <?php wp_nonce_field( 'woo-inecobank-logs-clear' ); ?>
            <button type="submit" name="clear_logs"
                    class="button button-primary"
                    onclick="return confirm('<?php _e( 'Are you sure you want to clear all logs?', 'woo-inecobank-payment-gateway' ); ?>')"
            >
                <?php _e( 'Clear All Logs', 'woo-inecobank-payment-gateway' ); ?>
            </button>
        </form>
    </div>

    <?php if ( empty( $log_files ) ) : ?>
        <div class="notice notice-info">
            <p><?php _e( 'No logs found.', 'woo-inecobank-payment-gateway' ); ?></p>
        </div>
    <?php else : ?>
        <div class="inecobank-logs-list">
            <?php foreach ( $log_files as $log_file ) : ?>
                <?php
                $file_name = basename( $log_file );
                $file_size = filesize( $log_file );
                $file_date = date( 'Y-m-d H:i:s', filemtime( $log_file ) );
                ?>
                <div class="inecobank-log-file">
                    <h3><?php echo esc_html( $file_name ); ?></h3>
                    <p>
                        <strong><?php _e( 'Size:', 'woo-inecobank-payment-gateway' ); ?></strong> <?php echo esc_html( $file_size ); ?>
                        <strong><?php _e( 'Last Modified:', 'woo-inecobank-payment-gateway' ); ?></strong> <?php echo esc_html( $file_date ); ?>
                    </p>
                    <details>
                        <summary style="cursor: pointer; font-weight: bold;">
                            <?php _e( 'View Log', 'woo-inecobank-payment-gateway' ); ?>
                        </summary>
                        <pre style="background: #EFEFEF; padding: 15px; overflow-x: auto; max-height: 400px; margin-top: 10px;">
                                <?php echo esc_html( file_get_contents( $log_file ) ); ?>
                            </pre>
                    </details>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
