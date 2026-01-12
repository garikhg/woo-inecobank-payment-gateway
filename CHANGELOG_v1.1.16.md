# Inecobank Payment Gateway - Stock Restoration Feature (v1.1.16)

## Overview
Refactored the `woo-inecobank-payment-gateway` plugin to automatically restore product stock quantities when orders fail or are not paid. This ensures proper inventory management and prevents overselling.

## Changes Made

### 1. Enhanced Gateway Class (`class-woo-inecobank-gateway.php`)

#### Updated `check_pending_orders()` Method
- **Added stock restoration before marking orders as failed**
- Checks if payment is verified from bank account via API
- If not paid after 20 minutes, changes order status to "Failed" and restores stock
- Handles three scenarios for stock restoration:
  1. Orders without Inecobank UUID (not registered properly)
  2. Orders verified as unpaid via API status check
  3. Orders where API verification fails

#### Added `restore_order_stock()` Helper Method
- **Private method** to handle stock restoration logic
- Prevents duplicate stock restoration using meta tracking (`_inecobank_stock_restored`)
- Uses WooCommerce's built-in `wc_maybe_increase_stock_levels()` function
- Adds order note documenting stock restoration
- Logs all stock restoration operations for debugging

### 2. Enhanced Webhook Handler (`class-woo-inecobank-webhook.php`)

#### Updated `process_order_status()` Method
- **Added stock restoration for failed payment statuses**:
  - Case 3: Payment Reversed
  - Case 6: Payment Declined
- Stock is restored before changing order status to "Failed"

#### Added `restore_order_stock()` Helper Method
- Same implementation as in Gateway class
- Ensures stock is only restored once per order
- Maintains data consistency across webhook callbacks

### 3. Version Updates

Updated plugin version to **1.1.16** in:
- `woo-inecobank-payment-gateway.php` (Plugin header and constant)
- `package.json`
- `readme.txt` (Changelog and Upgrade Notice)

## Technical Implementation

### Stock Restoration Logic
```php
private function restore_order_stock( $order ) {
    // Check if stock has already been restored
    if ( $order->get_meta( '_inecobank_stock_restored' ) === 'yes' ) {
        $this->logger->log( 'Stock already restored for order #' . $order->get_id() );
        return;
    }

    // Use WooCommerce built-in function to restore stock
    wc_maybe_increase_stock_levels( $order->get_id() );

    // Mark stock as restored
    $order->update_meta_data( '_inecobank_stock_restored', 'yes' );
    $order->save();

    $this->logger->log( 'Stock restored for order #' . $order->get_id() );
    $order->add_order_note( __( 'Product stock quantities restored to inventory.', 'woo-inecobank-payment-gateway' ) );
}
```

### Key Features

1. **Automatic Detection**: Checks pending orders every 20 minutes via cron
2. **Bank Verification**: Verifies payment status with Inecobank API
3. **Stock Restoration**: Returns product quantities to inventory when order fails
4. **Duplicate Prevention**: Uses meta field to prevent multiple restorations
5. **Comprehensive Logging**: Logs all operations for audit trail
6. **Order Notes**: Adds admin notes documenting stock restoration

### Payment Status Codes Handled

- **Status 0**: Registered but not paid → Fails after 20 min → Stock restored
- **Status 3**: Reversed → Mark as failed → Stock restored
- **Status 6**: Declined → Mark as failed → Stock restored

### Meta Fields Used

- `_inecobank_stock_restored`: Tracks if stock has been restored (prevents duplicates)
- `_inecobank_uuid`: Payment UUID from Inecobank API
- `_inecobank_order_id`: Order number sent to Inecobank

## Testing Recommendations

1. **Test Unpaid Orders**:
   - Create an order but don't complete payment
   - Wait 20 minutes
   - Verify order status changes to "Failed"
   - Verify stock is restored to inventory

2. **Test Declined Payments**:
   - Use a test card that triggers decline
   - Verify order status changes to "Failed"
   - Verify stock is immediately restored

3. **Test Reversed Payments**:
   - Complete payment then reverse it via Inecobank dashboard
   - Verify webhook updates order status to "Failed"
   - Verify stock is restored

4. **Test Duplicate Prevention**:
   - Manually trigger cron multiple times
   - Verify stock is only restored once per order

## Files Modified

1. `/includes/class-woo-inecobank-gateway.php`
2. `/includes/class-woo-inecobank-webhook.php`
3. `/woo-inecobank-payment-gateway.php`
4. `/package.json`
5. `/readme.txt`

## Benefits

✅ **Inventory Accuracy**: Prevents inventory discrepancies from unpaid orders  
✅ **Customer Experience**: Ensures products show as available when orders fail  
✅ **Automation**: No manual intervention needed for stock restoration  
✅ **Reliability**: Uses WooCommerce's built-in stock management functions  
✅ **Auditability**: Complete logging and order notes for tracking  
✅ **Safety**: Prevents duplicate stock restoration with meta tracking

## Version History

- **v1.1.16** (2026-01-12): Added automatic stock restoration feature
- **v1.1.15** (2025-12-29): Enhanced logging
- **v1.1.14** (2025-12-29): Timeout improvements

---

**Developer**: Garegin Hakobyan  
**Date**: 2026-01-12  
**Plugin**: Inecobank Payment Gateway for WooCommerce
