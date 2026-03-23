# Automatic Stock Rollback for Failed/Cancelled Orders

## Overview
Added **automatic stock restoration** that triggers whenever an order status changes to "Failed" or "Cancelled", regardless of how the status change occurs.

## Implementation

### Hook Registration
```php
// In __construct() method
add_action('woocommerce_order_status_changed', array($this, 'handle_order_status_change'), 10, 4);
```

### Handler Method
```php
public function handle_order_status_change($order_id, $old_status, $new_status, $order)
{
    // Only process Inecobank orders
    if ($order->get_payment_method() !== 'inecobank') {
        return;
    }

    // Only restore stock when transitioning TO failed or cancelled
    if (!in_array($new_status, array('failed', 'cancelled'), true)) {
        return;
    }

    // Don't restore if transitioning FROM failed or cancelled (already restored)
    if (in_array($old_status, array('failed', 'cancelled'), true)) {
        return;
    }

    $this->logger->log(sprintf(
        'Order #%d status changed from %s to %s - triggering automatic stock restoration',
        $order_id,
        $old_status,
        $new_status
    ));

    // Restore stock
    $this->restore_order_stock($order);
}
```

## How It Works

### Trigger Conditions
Stock is **automatically restored** when ALL of the following are true:

1. ✅ Order payment method is "Inecobank"
2. ✅ Order status changes **TO** "Failed" or "Cancelled"
3. ✅ Order status is **NOT changing FROM** "Failed" or "Cancelled"

### Scenarios That Trigger Stock Restoration

| Scenario | Old Status | New Status | Stock Restored? |
|----------|-----------|-----------|-----------------|
| Admin manually marks as failed | Pending | Failed | ✅ Yes |
| Admin manually cancels order | Processing | Cancelled | ✅ Yes |
| Webhook marks payment declined | Pending | Failed | ✅ Yes |
| Cron marks unpaid order | Pending | Failed | ✅ Yes |
| Customer cancels unpaid order | Pending | Cancelled | ✅ Yes |
| Payment verification fails | On-Hold | Failed | ✅ Yes |

### Scenarios That DON'T Trigger Stock Restoration

| Scenario | Old Status | New Status | Stock Restored? |
|----------|-----------|-----------|-----------------|
| Already failed order updated | Failed | Failed | ❌ No (already restored) |
| Failed to cancelled transition | Failed | Cancelled | ❌ No (already restored) |
| Successful payment | Pending | Processing | ❌ No (payment completed) |
| Non-Inecobank order fails | Pending | Failed | ❌ No (different gateway) |

## Benefits

### 1. **Universal Coverage** 
✅ Works for **any** way an order can be marked as Failed/Cancelled:
- Manual admin action
- Webhook callback
- Automated cron job
- Customer cancellation
- Other plugins/integrations

### 2. **No Manual Intervention**
✅ Admins don't need to remember to restore stock manually  
✅ Stock is **always** restored automatically  
✅ Prevents inventory discrepancies

### 3. **Duplicate Prevention**
✅ Built-in logic prevents double restoration:
- Checks if already in Failed/Cancelled state
- Uses `_inecobank_stock_restored` meta flag
- Won't restore twice for the same order

### 4. **Gateway-Specific**
✅ Only affects Inecobank orders  
✅ Doesn't interfere with other payment gateways  
✅ Clean separation of concerns

## Complete Stock Restoration Flow

```
┌─────────────────────────────────────────────────────────────┐
│                 Order Status Changes                         │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│          woocommerce_order_status_changed Hook              │
│                    (Fires Automatically)                     │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│              handle_order_status_change()                    │
│                                                               │
│  1. Check if Inecobank order                                 │
│  2. Check if changing TO Failed/Cancelled                    │
│  3. Check if NOT already Failed/Cancelled                    │
│  4. Log the status change                                    │
│  5. Call restore_order_stock()                               │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│               restore_order_stock()                          │
│                                                               │
│  1. Check if already restored (_inecobank_stock_restored)    │
│  2. Loop through each product in order                       │
│  3. Get old stock quantity                                   │
│  4. Add order quantity back to stock                         │
│  5. Get new stock quantity                                   │
│  6. Log: Product X - Stock: old → new                        │
│  7. Add detailed order note                                  │
│  8. Mark as restored                                         │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                  Stock Successfully Restored                 │
│                                                               │
│  Order Note:                                                 │
│  "Product stock quantities restored to inventory (X items):  │
│   Product Name (ID: 123) - Quantity: 2, Stock: 5 → 7"       │
│                                                               │
│  Log Entry:                                                  │
│  "Order #12345 status changed from pending to failed -      │
│   triggering automatic stock restoration"                    │
│  "Restored stock for product... Stock changed from 5 to 7"  │
└─────────────────────────────────────────────────────────────┘
```

## Testing Guide

### Test 1: Manual Admin Failure
1. Create order (don't pay) - Stock reduced
2. Admin: Change status to "Failed" manually
3. **Expected**: Stock automatically restored
4. **Verify**: Order note shows stock restoration details
5. **Verify**: Product stock increased back

### Test 2: Manual Admin Cancellation
1. Create order (don't pay) - Stock reduced
2. Customer or Admin: Cancel order
3. **Expected**: Stock automatically restored
4. **Verify**: Order note shows restoration
5. **Verify**: Stock levels correct

### Test 3: Webhook Declined Payment
1. Use test card that triggers decline
2. Webhook changes status to "Failed"
3. **Expected**: Stock automatically restored
4. **Verify**: Both webhook AND status change handlers work

### Test 4: Cron Unpaid Order
1. Create order, wait 20 minutes
2. Cron marks as "Failed" (unpaid)
3. **Expected**: Stock automatically restored
4. **Verify**: Only restored once (not doubled)

### Test 5: Duplicate Prevention
1. Order marked as "Failed" (stock restored)
2. Manually change from "Failed" to "Cancelled"
3. **Expected**: Stock NOT restored again
4. **Verify**: Order note shows "already restored"

### Test 6: Other Payment Gateway
1. Create order with different gateway
2. Mark as "Failed"
3. **Expected**: Hook runs but returns early
4. **Expected**: No Inecobank stock restoration triggered

## Log Examples

### Successful Restoration
```
[2026-01-12 12:50:30] Order #12345 status changed from pending to failed - triggering automatic stock restoration
[2026-01-12 12:50:30] Starting stock restoration for order #12345
[2026-01-12 12:50:30] Restored stock for product Red T-Shirt (ID: 123): Added 2 units, Stock changed from 5 to 7
[2026-01-12 12:50:30] Stock restoration completed for order #12345 - 1 items restored
```

### Already Restored
```
[2026-01-12 12:51:00] Order #12345 status changed from failed to cancelled - triggering automatic stock restoration
[2026-01-12 12:51:00] Stock already restored for order #12345
```

### Different Gateway (Skipped)
```
[No log entry - hook returns early for non-Inecobank orders]
```

## Technical Details

### File Modified
- `/includes/class-woo-inecobank-gateway.php`

### Changes Made
1. Added hook in `__construct()`: `woocommerce_order_status_changed`
2. Added new method: `handle_order_status_change()`
3. Reuses existing: `restore_order_stock()` method

### Code Lines
- Hook registration: Line ~129
- Handler method: Lines 548-583
- Stock restoration: Lines 585-662

## Safety Features

### 1. Payment Method Check
```php
if ($order->get_payment_method() !== 'inecobank') {
    return; // Skip non-Inecobank orders
}
```

### 2. Status Direction Check
```php
if (!in_array($new_status, array('failed', 'cancelled'), true)) {
    return; // Only restore when moving TO failed/cancelled
}
```

### 3. Prevent Double Restoration
```php
if (in_array($old_status, array('failed', 'cancelled'), true)) {
    return; // Don't restore if already failed/cancelled
}
```

### 4. Meta Flag Protection
```php
if ($order->get_meta('_inecobank_stock_restored') === 'yes') {
    return; // Already restored, skip
}
```

## Version
**Plugin Version**: 1.1.16  
**Feature**: Automatic Stock Rollback  
**Date**: 2026-01-12

---

This enhancement ensures that product stock is **always automatically restored** whenever an Inecobank order is marked as Failed or Cancelled, providing complete inventory accuracy with zero manual intervention required! 🎉
