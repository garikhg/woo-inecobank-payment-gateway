# Stock Rollback Enhancement - Explicit Quantity Restoration

## Summary
Enhanced the stock restoration functionality to **explicitly rollback product quantities** with detailed logging showing exact quantities being restored for each product.

## Key Changes

### Previous Implementation
- Used WooCommerce's `wc_maybe_increase_stock_levels()` function
- Simple, but lacked visibility into what was being restored

### New Implementation  
- **Explicitly iterates through each order item**
- **Gets the exact quantity ordered for each product**
- **Retrieves OLD stock quantity before restoration**
- **Calculates NEW stock quantity after restoration**
- **Logs detailed before/after stock levels**
- **Adds comprehensive order notes with per-item details**

## Technical Details

### Stock Restoration Process

```php
foreach ( $order->get_items() as $item_id => $item ) {
    $product = $item->get_product();
    
    // Skip if product doesn't exist or doesn't manage stock
    if ( !$product || !$product->managing_stock() ) {
        continue;
    }
    
    $quantity = $item->get_quantity();        // Quantity from order
    $old_stock = $product->get_stock_quantity();  // Current stock
    
    // Rollback: Add quantity back to stock
    $new_stock = wc_update_product_stock( $product, $quantity, 'increase' );
    
    // Log: Product Name (ID: X) - Quantity: Y, Stock: old → new
}
```

### Example Output

**Order Note:**
```
Product stock quantities restored to inventory (3 items):
Red T-Shirt (ID: 123) - Quantity: 2, Stock: 5 → 7
Blue Jeans (ID: 456) - Quantity: 1, Stock: 10 → 11
Black Hat (ID: 789) - Quantity: 3, Stock: 0 → 3
```

**Log Entry:**
```
Restored stock for product Red T-Shirt (ID: 123): Added 2 units, Stock changed from 5 to 7
Restored stock for product Blue Jeans (ID: 456): Added 1 units, Stock changed from 10 to 11
Restored stock for product Black Hat (ID: 789): Added 3 units, Stock changed from 0 to 3
Stock restoration completed for order #12345 - 3 items restored
```

## Benefits

### 1. **Transparency**
✅ See exactly which products had stock restored  
✅ Know the exact quantities added back  
✅ Track before/after stock levels

### 2. **Auditability**
✅ Complete audit trail in logs  
✅ Detailed order notes for admin review  
✅ Easy to verify stock restoration accuracy

### 3. **Debugging**
✅ Identify stock management issues quickly  
✅ Verify correct quantities are being restored  
✅ Track stock discrepancies

### 4. **Product Type Support**
✅ Simple products  
✅ Variable products (variations)  
✅ Only restores stock for products that manage inventory

## Implementation Location

### Files Updated
1. **`class-woo-inecobank-gateway.php`**
   - `restore_order_stock()` method (lines 548-623)
   
2. **`class-woo-inecobank-webhook.php`**
   - `restore_order_stock()` method (lines 261-342)

Both implementations are **identical** to ensure consistent behavior whether stock is restored via:
- ✅ Scheduled cron job (gateway class)
- ✅ Webhook callback (webhook class)

## When Stock is Restored

### Automated Cron Job (Every 20 Minutes)
- Checks pending orders older than 20 minutes
- Verifies payment status with Inecobank API
- Restores stock if order is unpaid

### Webhook Callbacks  
- Payment Declined (Status 6) → Restore stock immediately
- Payment Reversed (Status 3) → Restore stock immediately

## Safety Features

### 1. Duplicate Prevention
```php
if ( $order->get_meta( '_inecobank_stock_restored' ) === 'yes' ) {
    // Already restored, skip
    return;
}
```

### 2. Stock Management Check
```php
if ( ! $product->managing_stock() ) {
    // Product doesn't track inventory, skip
    continue;
}
```

### 3. Product Validation
```php
if ( ! $product ) {
    // Invalid product, skip
    continue;
}
```

## Testing Scenarios

### Test 1: Simple Product - Unpaid Order
1. Add simple product (stock: 10) to cart
2. Create order (quantity: 2)
3. **Stock reduces to 8**
4. Don't pay, wait 20 minutes
5. Cron runs → Stock restored to 10
6. Order note shows: "Product restored, Stock: 8 → 10"

### Test 2: Variable Product - Declined Payment
1. Add product variation (stock: 5) to cart
2. Complete checkout with test decline card
3. **Stock reduced to 4**
4. Payment declined → Webhook triggered
5. Stock immediately restored to 5
6. Order note shows: "Product restored, Stock: 4 → 5"

### Test 3: Multiple Products
1. Add 3 different products to cart
2. Create order but don't pay
3. Wait for cron or trigger failure
4. All 3 products get stock restored
5. Order note lists all 3 products with quantities

### Test 4: Product Without Stock Management
1. Product set to "Don't track inventory"
2. Order created and fails
3. No stock restoration attempted
4. Order note: "No stock to restore (products do not manage stock)"

## Version
**Plugin Version**: 1.1.16  
**Date**: 2026-01-12  
**Enhancement**: Explicit Stock Rollback with Detailed Logging

---

This enhancement ensures **complete transparency** in inventory management, making it easy to verify that stock quantities are correctly rolled back when orders are not paid.
