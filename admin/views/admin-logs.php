<?php
/**
 * Admin Logs View
 *
 * @package WooCommerce Inecobank Payment Gateway
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Inecobank Payment Gateway Logs', 'woo-inecobank-payment-gateway'); ?></h1>

    <div class="inecobank-logs-header" style="margin-top: 10px; margin-bottom: 20px;">
        <form method="post">
            <?php wp_nonce_field('inecobank_clear_logs'); ?>
            <button type="submit" name="clear_logs" class="button button-secondary"
                onclick="return confirm('<?php _e('Are you sure you want to clear all logs?', 'woo-inecobank-payment-gateway'); ?>')">
                <?php _e('Clear All Logs', 'woo-inecobank-payment-gateway'); ?>
            </button>
        </form>
    </div>

    <hr class="wp-header-end">

    <?php if (empty($log_files)): ?>
        <div class="notice notice-info inline">
            <p><?php _e('No logs found.', 'woo-inecobank-payment-gateway'); ?></p>
        </div>
    <?php else: ?>
        <div id="poststuff">
            <style>
                .postbox-header {
                    display: flex;
                    align-items: center;
                    padding: 8px 12px !important;
                }

                .postbox-header .handlediv {
                    background: none;
                    border: none;
                    cursor: pointer;
                    padding: 4px;
                    margin-right: 8px;
                    display: flex;
                    align-items: center;
                }

                .postbox-header .handlediv .dashicons {
                    transition: transform 0.2s;
                }

                .postbox.closed .handlediv .dashicons {
                    transform: rotate(-90deg);
                }

                .postbox-header .hndle {
                    flex: 1;
                    margin: 0;
                    padding: 0;
                    cursor: pointer;
                }

                .postbox-header .handle-actions {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
            </style>
            <?php foreach ($log_files as $log_file): ?>
                <?php
                $file_name = basename($log_file);
                $file_size = size_format(filesize($log_file));
                $file_date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($log_file));

                // Generate delete URL with nonce
                $delete_url = wp_nonce_url(
                    add_query_arg(
                        array(
                            'page' => 'inecobank-logs',
                            'action' => 'delete_log',
                            'file' => $file_name,
                        ),
                        admin_url('admin.php')
                    ),
                    'inecobank_delete_log'
                );
                ?>
                <div class="postbox closed">
                    <div class="postbox-header">
                        <button type="button" class="handlediv" aria-expanded="false">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                        <h2 class="hndle ui-sortable-handle"><?php echo esc_html($file_name); ?></h2>
                        <div class="handle-actions">
                            <span class="description">
                                <?php echo esc_html($file_date); ?> &mdash; <?php echo esc_html($file_size); ?>
                            </span>
                            <a href="<?php echo esc_url($delete_url); ?>" class="button button-small button-link-delete"
                                onclick="return confirm('<?php _e('Are you sure you want to delete this log file?', 'woo-inecobank-payment-gateway'); ?>')">
                                <?php _e('Delete', 'woo-inecobank-payment-gateway'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="inside">
                        <textarea class="large-text code" rows="15" readonly
                            style="background: #f6f7f7;"><?php echo esc_textarea(file_get_contents($log_file)); ?></textarea>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>