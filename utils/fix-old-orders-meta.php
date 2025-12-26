<?php
/**
 * Utility Script: Fix Old Orders Meta
 * 
 * This script fixes orders created before the meta structure change.
 * Old: _inecobank_order_id = UUID
 * New: _inecobank_order_id = Order Number, _inecobank_uuid = UUID
 * 
 * HOW TO USE:
 * 1. Access: https://your-site.com/wp-content/plugins/woo-inecobank-payment-gateway/utils/fix-old-orders-meta.php
 * 2. Or run from command line: php fix-old-orders-meta.php
 */

// Load WordPress
require_once __DIR__ . '/../../../../wp-load.php';

// Security check
if (!current_user_can('manage_woocommerce') && !defined('WP_CLI')) {
    die('Access denied. You need WooCommerce management permissions.');
}

echo "<h1>Fix Inecobank Orders Meta Data</h1>\n";
echo "<p>This script will update old orders to have the correct meta structure.</p>\n";
echo "<hr>\n";

// Get all orders with Inecobank payment method
$args = array(
    'limit' => -1,
    'payment_method' => 'inecobank',
    'return' => 'ids',
);

$order_ids = wc_get_orders($args);

echo "<p>Found <strong>" . count($order_ids) . "</strong> Inecobank orders to check.</p>\n";
echo "<hr>\n";

$fixed_count = 0;
$skipped_count = 0;

foreach ($order_ids as $order_id) {
    $order = wc_get_order($order_id);

    if (!$order) {
        continue;
    }

    $inecobank_order_id = $order->get_meta('_inecobank_order_id');
    $inecobank_uuid = $order->get_meta('_inecobank_uuid');

    // Check if this is an old order (has UUID in _inecobank_order_id and no _inecobank_uuid)
    if (!empty($inecobank_order_id) && empty($inecobank_uuid)) {
        // Check if it looks like a UUID (contains dashes and is long)
        if (strlen($inecobank_order_id) > 30 && strpos($inecobank_order_id, '-') !== false) {
            // This is an old order with UUID in _inecobank_order_id
            $order_number = $order->get_order_number();

            echo "<div style='padding:10px; margin:10px 0; background:#fff3cd; border-left:4px solid #ffc107;'>\n";
            echo "<strong>Order #{$order_id}</strong><br>\n";
            echo "Old: _inecobank_order_id = <code>{$inecobank_order_id}</code><br>\n";
            echo "New: _inecobank_order_id = <code>{$order_number}</code><br>\n";
            echo "New: _inecobank_uuid = <code>{$inecobank_order_id}</code><br>\n";

            // Update the meta
            $order->update_meta_data('_inecobank_order_id', $order_number);
            $order->update_meta_data('_inecobank_uuid', $inecobank_order_id);
            $order->save();

            echo "<span style='color:green;'>✅ FIXED!</span>\n";
            echo "</div>\n";

            $fixed_count++;
        } else {
            echo "<div style='padding:10px; margin:10px 0; background:#d4edda; border-left:4px solid #28a745;'>\n";
            echo "<strong>Order #{$order_id}</strong><br>\n";
            echo "_inecobank_order_id = <code>{$inecobank_order_id}</code><br>\n";
            echo "<span style='color:green;'>✅ Already correct format (order number)</span>\n";
            echo "</div>\n";

            $skipped_count++;
        }
    } else if (!empty($inecobank_uuid)) {
        echo "<div style='padding:10px; margin:10px 0; background:#d4edda; border-left:4px solid #28a745;'>\n";
        echo "<strong>Order #{$order_id}</strong><br>\n";
        echo "_inecobank_order_id = <code>{$inecobank_order_id}</code><br>\n";
        echo "_inecobank_uuid = <code>{$inecobank_uuid}</code><br>\n";
        echo "<span style='color:green;'>✅ Already has both values</span>\n";
        echo "</div>\n";

        $skipped_count++;
    } else {
        echo "<div style='padding:10px; margin:10px 0; background:#f8d7da; border-left:4px solid #dc3545;'>\n";
        echo "<strong>Order #{$order_id}</strong><br>\n";
        echo "<span style='color:red;'>⚠️ No Inecobank meta found</span>\n";
        echo "</div>\n";

        $skipped_count++;
    }
}

echo "<hr>\n";
echo "<h2>Summary</h2>\n";
echo "<p>Total orders checked: <strong>" . count($order_ids) . "</strong></p>\n";
echo "<p>Orders fixed: <strong style='color:green;'>{$fixed_count}</strong></p>\n";
echo "<p>Orders skipped (already correct): <strong style='color:blue;'>{$skipped_count}</strong></p>\n";
echo "<hr>\n";
echo "<p><strong>Done!</strong> You can now test the webhook with the old orders.</p>\n";