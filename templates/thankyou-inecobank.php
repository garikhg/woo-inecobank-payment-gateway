<!--
  Inecobank Payment - Custom Thank You Page Template

  INSTALLATION:
  1. Copy this file to your theme folder: wp-content/themes/your-theme/woocommerce/checkout/thankyou-inecobank.php
  2. This will automatically override the default thank you page for Inecobank payments

  OR use the shortcode [inecobank_thank_you] on any page
-->

<?php
/**
 * Inecobank Payment Gateway - Thank You Page Template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/thankyou-inecobank.php
 *
 * @package WooCommerce Inecobank Payment Gateway
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get the order
$order_id = isset($_GET['key']) ? wc_get_order_id_by_order_key(sanitize_text_field($_GET['key'])) : 0;
$order = $order_id ? wc_get_order($order_id) : null;

if (!$order || $order->get_payment_method() !== 'inecobank') {
    // Fallback to standard WooCommerce thank you page
    wc_get_template('checkout/thankyou.php', array('order' => $order));
    return;
}

// Get payment details
$transaction_id = $order->get_transaction_id();
$payment_date = $order->get_date_paid() ? $order->get_date_paid() : $order->get_date_created();
$terminal_id = get_post_meta($order->get_id(), '_inecobank_auth_ref_num', true);
$card_pan = get_post_meta($order->get_id(), '_inecobank_card_pan', true);
$approval_code = get_post_meta($order->get_id(), '_inecobank_approval_code', true);
?>

<div class="woocommerce-order-inecobank-thank-you">

    <?php if ($order->has_status('failed')): ?>

        <div class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed">
            <p><?php esc_html_e('Unfortunately your payment was not successful. Please try again or contact us for assistance.', 'woo-inecobank-payment-gateway'); ?>
            </p>
        </div>

        <p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
            <a href="<?php echo esc_url($order->get_checkout_payment_url()); ?>"
                class="button pay"><?php esc_html_e('Try Again', 'woo-inecobank-payment-gateway'); ?></a>
            <?php if (is_user_logged_in()): ?>
                <a href="<?php echo esc_url(wc_get_page_permalink('myaccount')); ?>"
                    class="button"><?php esc_html_e('My Account', 'woo-inecobank-payment-gateway'); ?></a>
            <?php endif; ?>
        </p>

    <?php else: ?>

        <div class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received">
            <?php echo apply_filters('woocommerce_thankyou_order_received_text', esc_html__('Thank you. Your order has been received.', 'woo-inecobank-payment-gateway'), $order); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>

        <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
            <li class="woocommerce-order-overview__order order">
                <?php esc_html_e('Order number:', 'woo-inecobank-payment-gateway'); ?>
                <strong><?php echo $order->get_order_number(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
            </li>

            <li class="woocommerce-order-overview__date date">
                <?php esc_html_e('Date:', 'woo-inecobank-payment-gateway'); ?>
                <strong><?php echo wc_format_datetime($order->get_date_created()); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
            </li>

            <?php if (is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email()): ?>
                <li class="woocommerce-order-overview__email email">
                    <?php esc_html_e('Email:', 'woo-inecobank-payment-gateway'); ?>
                    <strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
                </li>
            <?php endif; ?>

            <li class="woocommerce-order-overview__total total">
                <?php esc_html_e('Total:', 'woo-inecobank-payment-gateway'); ?>
                <strong><?php echo $order->get_formatted_order_total(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
            </li>

            <?php if ($order->get_payment_method_title()): ?>
                <li class="woocommerce-order-overview__payment-method method">
                    <?php esc_html_e('Payment method:', 'woo-inecobank-payment-gateway'); ?>
                    <strong><?php echo wp_kses_post($order->get_payment_method_title()); ?></strong>
                </li>
            <?php endif; ?>
        </ul>

        <?php // Inecobank Payment Details Section ?>
        <?php if ($transaction_id || $terminal_id || $card_pan): ?>
            <section class="woocommerce-inecobank-payment-details">
                <h2 class="woocommerce-order-details__title">
                    <?php esc_html_e('Payment Details', 'woo-inecobank-payment-gateway'); ?></h2>
                <table
                    class="woocommerce-table woocommerce-table--inecobank-payment-details shop_table inecobank_payment_details">
                    <tbody>
                        <?php if ($transaction_id): ?>
                            <tr>
                                <th><?php esc_html_e('Transaction ID:', 'woo-inecobank-payment-gateway'); ?></th>
                                <td><strong><?php echo esc_html($transaction_id); ?></strong></td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($terminal_id && $terminal_id !== $transaction_id): ?>
                            <tr>
                                <th><?php esc_html_e('Terminal Reference:', 'woo-inecobank-payment-gateway'); ?></th>
                                <td><?php echo esc_html($terminal_id); ?></td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($card_pan): ?>
                            <tr>
                                <th><?php esc_html_e('Card Number:', 'woo-inecobank-payment-gateway'); ?></th>
                                <td><?php echo esc_html($card_pan); ?></td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($approval_code): ?>
                            <tr>
                                <th><?php esc_html_e('Approval Code:', 'woo-inecobank-payment-gateway'); ?></th>
                                <td><?php echo esc_html($approval_code); ?></td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($payment_date): ?>
                            <tr>
                                <th><?php esc_html_e('Payment Date:', 'woo-inecobank-payment-gateway'); ?></th>
                                <td><?php echo wc_format_datetime($payment_date); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </td>
                            </tr>
                        <?php endif; ?>

                        <tr>
                            <th><?php esc_html_e('Payment Status:', 'woo-inecobank-payment-gateway'); ?></th>
                            <td>
                                <span class="woocommerce-order-status status-<?php echo esc_attr($order->get_status()); ?>">
                                    <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>

    <?php endif; ?>

    <?php do_action('woocommerce_thankyou_' . $order->get_payment_method(), $order->get_id()); ?>
    <?php do_action('woocommerce_thankyou', $order->get_id()); ?>

</div>

<style>
    .woocommerce-inecobank-payment-details {
        margin: 2em 0;
        padding: 1.5em;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
    }

    .woocommerce-table--inecobank-payment-details {
        margin: 1em 0 0;
        width: 100%;
    }

    .woocommerce-table--inecobank-payment-details th {
        font-weight: 600;
        padding: 0.75em 1em;
        text-align: left;
        width: 40%;
    }

    .woocommerce-table--inecobank-payment-details td {
        padding: 0.75em 1em;
    }

    .woocommerce-order-status {
        display: inline-block;
        padding: 0.25em 0.75em;
        border-radius: 3px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85em;
    }

    .woocommerce-order-status.status-processing {
        background: #c6e1c6;
        color: #0f5132;
    }

    .woocommerce-order-status.status-completed {
        background: #d1e7dd;
        color: #0a3622;
    }

    .woocommerce-order-status.status-on-hold {
        background: #fff3cd;
        color: #664d03;
    }

    .woocommerce-order-status.status-failed {
        background: #f8d7da;
        color: #842029;
    }
</style>
