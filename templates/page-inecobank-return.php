<!-- 
  Inecobank Payment - Processing Page Template
  
  INSTALLATION:
  1. Copy this file to your theme: wp-content/themes/your-theme/page-inecobank-return.php
  
  OR let the plugin create the page automatically
-->

<?php
/**
 * Template Name: Inecobank Payment Return
 * 
 * This page handles the return from Inecobank payment gateway
 *
 * @package WooCommerce Inecobank Payment Gateway
 * @version 1.0.0
 */

// Block direct access
if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Get parameters from URL
$order_id_param = isset($_GET['orderId']) ? sanitize_text_field(wp_unslash($_GET['orderId'])) : '';
$payment_id = isset($_GET['paymentID']) ? sanitize_text_field(wp_unslash($_GET['paymentID'])) : '';
$response_code = isset($_GET['responseCode']) ? sanitize_text_field(wp_unslash($_GET['responseCode'])) : '';
$description = isset($_GET['description']) ? sanitize_text_field(wp_unslash($_GET['description'])) : '';

// Log the return
if (function_exists('wc_get_logger')) {
    $logger = wc_get_logger();
    $logger->info('Inecobank return page accessed', array(
        'source' => 'inecobank-gateway',
        'orderId' => $order_id_param,
        'paymentID' => $payment_id,
        'responseCode' => $response_code,
    ));
}

// Determine if payment was successful
$is_success = '0' === $response_code || 'success' === strtolower($description);
?>

<div class="inecobank-return-page">
    <div class="inecobank-processing-container">

        <?php if ($is_success): ?>
        <!-- Success Processing -->
        <div class="inecobank-processing-success">
            <div class="inecobank-spinner">
                <svg class="inecobank-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                    <circle class="inecobank-checkmark-circle" cx="26" cy="26" r="25" fill="none" />
                    <path class="inecobank-checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" />
                </svg>
            </div>

            <h1 class="inecobank-processing-title">
                <?php esc_html_e('Payment Successful!', 'woo-inecobank-payment-gateway'); ?>
            </h1>

            <p class="inecobank-processing-message">
                <?php esc_html_e('Thank you! Your payment has been processed successfully.', 'woo-inecobank-payment-gateway'); ?>
            </p>

            <p class="inecobank-processing-submessage">
                <?php esc_html_e('Please wait while we confirm your order...', 'woo-inecobank-payment-gateway'); ?>
            </p>
        </div>

        <?php
else: ?>
        <!-- Failed/Processing -->
        <div class="inecobank-processing-pending">
            <div class="inecobank-spinner">
                <div class="inecobank-loader"></div>
            </div>

            <h1 class="inecobank-processing-title">
                <?php esc_html_e('Processing Payment...', 'woo-inecobank-payment-gateway'); ?>
            </h1>

            <p class="inecobank-processing-message">
                <?php esc_html_e('Please wait while we verify your payment.', 'woo-inecobank-payment-gateway'); ?>
            </p>

            <p class="inecobank-processing-submessage">
                <?php esc_html_e('Do not close this window or press the back button.', 'woo-inecobank-payment-gateway'); ?>
            </p>
        </div>
        <?php
endif; ?>

        <!-- Transaction Details -->
        <?php if ($order_id_param): ?>
        <div class="inecobank-transaction-details">
            <p class="inecobank-reference">
                <?php esc_html_e('Reference:', 'woo-inecobank-payment-gateway'); ?>
                <strong>
                    <?php echo esc_html($payment_id ? $payment_id : $order_id_param); ?>
                </strong>
            </p>
        </div>
        <?php
endif; ?>

    </div>
</div>

<!-- Auto-process webhook and redirect -->
<script>
    (function () {
        // Trigger webhook processing via AJAX
        var webhookUrl = '<?php echo esc_js(WC()->api_request_url('woo_inecobank_gateway')); ?>';
        var params = new URLSearchParams(window.location.search);

        // Add all URL parameters to webhook request
        var fullWebhookUrl = webhookUrl;
        if (params.toString()) {
            fullWebhookUrl += (webhookUrl.indexOf('?') === -1 ? '?' : '&') + params.toString();
        }

        console.log('Processing payment via webhook:', fullWebhookUrl);

        // Give user time to see the success message (2 seconds)
        setTimeout(function () {
            // Redirect to webhook which will then redirect to thank you page
            window.location.href = fullWebhookUrl;
        }, <?php echo $is_success ? 2000 : 1000; ?>);

        // Fallback: if redirect doesn't work after 10 seconds, show error
        setTimeout(function () {
            document.querySelector('.inecobank-processing-message').innerHTML = '<?php esc_html_e('Taking longer than expected...', 'woo - inecobank - payment - gateway'); ?>';
    }, 10000);
    }) ();
</script>

<style>
    .inecobank-return-page {
        min-height: 60vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .inecobank-processing-container {
        max-width: 500px;
        width: 100%;
        background: white;
        border-radius: 12px;
        padding: 60px 40px;
        text-align: center;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }

    .inecobank-processing-title {
        font-size: 28px;
        font-weight: 700;
        color: #2c3e50;
        margin: 20px 0 10px;
    }

    .inecobank-processing-message {
        font-size: 16px;
        color: #5a6c7d;
        margin: 10px 0;
        line-height: 1.6;
    }

    .inecobank-processing-submessage {
        font-size: 14px;
        color: #95a5a6;
        margin: 10px 0 30px;
    }

    /* Spinner Container */
    .inecobank-spinner {
        width: 80px;
        height: 80px;
        margin: 0 auto 20px;
        position: relative;
    }

    /* Loading Spinner */
    .inecobank-loader {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        width: 80px;
        height: 80px;
        animation: inecobank-spin 1s linear infinite;
    }

    @keyframes inecobank-spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Success Checkmark */
    .inecobank-checkmark {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: block;
        stroke-width: 3;
        stroke: #4caf50;
        stroke-miterlimit: 10;
        box-shadow: inset 0px 0px 0px #4caf50;
        animation: inecobank-fill 0.4s ease-in-out 0.4s forwards, inecobank-scale 0.3s ease-in-out 0.9s both;
    }

    .inecobank-checkmark-circle {
        stroke-dasharray: 166;
        stroke-dashoffset: 166;
        stroke-width: 3;
        stroke-miterlimit: 10;
        stroke: #4caf50;
        fill: none;
        animation: inecobank-stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }

    .inecobank-checkmark-check {
        transform-origin: 50% 50%;
        stroke-dasharray: 48;
        stroke-dashoffset: 48;
        animation: inecobank-stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }

    @keyframes inecobank-stroke {
        100% {
            stroke-dashoffset: 0;
        }
    }

    @keyframes inecobank-scale {

        0%,
        100% {
            transform: none;
        }

        50% {
            transform: scale3d(1.1, 1.1, 1);
        }
    }

    @keyframes inecobank-fill {
        100% {
            box-shadow: inset 0px 0px 0px 30px #4caf50;
        }
    }

    /* Transaction Details */
    .inecobank-transaction-details {
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px solid #ecf0f1;
    }

    .inecobank-reference {
        font-size: 13px;
        color: #7f8c8d;
        margin: 0;
        word-break: break-all;
    }

    .inecobank-reference strong {
        color: #34495e;
        display: block;
        margin-top: 5px;
        font-family: monospace;
    }

    /* Success styling */
    .inecobank-processing-success .inecobank-processing-title {
        color: #27ae60;
    }

    /* Mobile Responsive */
    @media (max-width: 600px) {
        .inecobank-processing-container {
            padding: 40px 20px;
        }

        .inecobank-processing-title {
            font-size: 24px;
        }

        .inecobank-processing-message {
            font-size: 15px;
        }
    }
</style>

<?php
get_footer();
?>