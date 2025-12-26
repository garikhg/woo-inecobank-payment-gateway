# Inecobank Payment Gateway for WooCommerce

Accept credit and debit card payments on your WooCommerce store using Inecobank Payment Gateway.

## Description

The Inecobank Payment Gateway plugin allows you to accept payments directly on your WooCommerce store via Inecobank's secure payment platform. This plugin supports both one-phase (immediate) and two-phase (preauthorization) payment flows.

### Features

- ✅ **One-Phase Payments** - Immediate payment processing
- ✅ **Two-Phase Payments** - Preauthorization with manual completion
- ✅ **Full & Partial Refunds** - Process refunds directly from WooCommerce
- ✅ **Test Mode** - Test your integration before going live
- ✅ **Multi-Language Support** - Armenian, English, and Russian
- ✅ **Multi-Currency Support** - AMD, USD, EUR, RUB
- ✅ **Secure Payment Processing** - PCI-compliant through Inecobank
- ✅ **Order Status Synchronization** - Automatic status updates
- ✅ **Mobile Responsive** - Works on all devices
- ✅ **Auto-Repair System** - Automatic metadata repair for order tracking

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.2 or higher
- SSL Certificate (HTTPS)
- Inecobank merchant account

## Installation

### Automatic Installation

1. Log in to your WordPress admin panel
2. Go to **Plugins > Add New**
3. Search for "Inecobank Payment Gateway"
4. Click **Install Now** and then **Activate**

### Manual Installation

1. Download the plugin zip file
2. Extract the zip file
3. Upload the `inecobank-payment-gateway` folder to `/wp-content/plugins/`
4. Activate the plugin through the **Plugins** menu in WordPress

## Configuration

### 1. Get Your API Credentials

Contact Inecobank to obtain your merchant credentials:
- API Username
- API Password
- Test credentials (for testing)

### 2. Configure the Plugin

1. Go to **WooCommerce > Settings > Payments**
2. Click on **Inecobank Payment Gateway**
3. Configure the following settings:

#### Basic Settings

| Setting | Description |
|---------|-------------|
| **Enable/Disable** | Enable or disable the payment gateway |
| **Title** | Payment method title shown to customers (e.g., "Credit/Debit Card") |
| **Description** | Payment method description shown during checkout |

#### API Settings

| Setting | Description |
|---------|-------------|
| **Test Mode** | Enable to use test environment |
| **Payment Type** | Choose between One-Phase (immediate) or Two-Phase (preauth) |
| **Live API Username** | Your production API username from Inecobank |
| **Live API Password** | Your production API password from Inecobank |
| **Test API Username** | Your test API username from Inecobank |
| **Test API Password** | Your test API password from Inecobank |
| **Payment Page Language** | Language for the payment page (hy/en/ru) |

4. Click **Save changes**

## Payment Types

### One-Phase Payment (Immediate)

- Payment is processed and captured immediately
- Funds are transferred directly to your account
- Best for digital products and services

### Two-Phase Payment (Preauthorization)

- Payment is authorized but not captured
- Funds are held on customer's card
- Manual completion required from admin panel
- Best for physical products (capture after shipping)

#### Completing Two-Phase Payments

1. Go to **WooCommerce > Orders**
2. Open the order with "On Hold" status
3. In the **Order Actions** dropdown, select "Complete Inecobank Payment"
4. Click **Update** to process the completion

## Supported Currencies

The plugin automatically converts WooCommerce currencies to Inecobank currency codes:

| Currency | Code | ISO 4217 |
|----------|------|----------|
| Armenian Dram | AMD | 051 |
| US Dollar | USD | 840 |
| Euro | EUR | 978 |
| Russian Ruble | RUB | 643 |

## Refunds

### Processing Refunds

1. Go to **WooCommerce > Orders**
2. Open the completed order
3. Click **Refund** button
4. Enter the refund amount
5. Enter a reason (optional)
6. Click **Refund via Inecobank**

**Note:**
- Full and partial refunds are supported
- Total refund amount cannot exceed the original payment
- Multiple refunds can be processed for the same order

## Order Statuses

| Status | Description |
|--------|-------------|
| **Pending Payment** | Order created, awaiting payment |
| **On Hold** | Payment preauthorized (two-phase) |
| **Processing** | Payment completed successfully |
| **Failed** | Payment failed or declined |
| **Refunded** | Payment refunded |

## Testing

### Test Mode Setup

1. Enable **Test Mode** in plugin settings
2. Enter your test API credentials
3. Use test card numbers provided by Inecobank

### Test Workflow

1. Add products to cart
2. Proceed to checkout
3. Select Inecobank payment method
4. Complete payment on Inecobank test page
5. Verify order status updates correctly

## Technical Information

### API Endpoints

The plugin integrates with the following Inecobank API endpoints:

- `register.do` - One-phase payment registration
- `registerPreAuth.do` - Two-phase payment registration
- `deposit.do` - Complete two-phase payment
- `refund.do` - Process refunds
- `reverse.do` - Reverse payments
- `getOrderStatusExtended.do` - Check order status

### Webhook System

The plugin uses a smart webhook handler with auto-repair functionality:

- **Multi-tier Order Lookup**: Finds orders using meta key, direct ID, or iteration
- **Auto-Repair**: Automatically repairs missing metadata when orders are found
- **Graceful Degradation**: Handles missing UUIDs without breaking checkout flow
- **Comprehensive Logging**: Detailed logs for debugging (when debug mode enabled)

#### Webhook URL

```
https://your-site.com/?wc-api=inecobank-gateway
```

This URL must be accessible for order status updates to work properly.

### Order Metadata Structure

Orders store the following metadata:

- `_inecobank_order_id` - WooCommerce order number sent to Inecobank
- `_inecobank_uuid` - Inecobank transaction UUID
- `_inecobank_payment_type` - Payment type (one_phase/two_phase)
- `_inecobank_auth_ref_num` - Bank terminal/reference number
- `_inecobank_approval_code` - Payment approval code
- `_inecobank_card_pan` - Masked card number
- `_inecobank_transaction_date` - Transaction timestamp

## Security

- All API credentials are securely stored
- SSL/TLS encryption for API communication
- No sensitive card data stored on your server
- PCI-DSS compliant payment processing

## Troubleshooting

### Enable Debug Logging

1. Go to **WooCommerce > Inecobank Logs**
2. Enable **Debug Mode** in plugin settings
3. Review logs for detailed information

### Common Issues

#### Payment Failed

**Solutions:**
- Verify API credentials are correct
- Check if test mode is properly configured
- Ensure SSL certificate is valid
- Review Inecobank logs

#### Order Status Not Updating

**Solutions:**
- Verify webhook URL is accessible
- Check that metadata is being saved correctly
- Review WordPress/WooCommerce logs
- Ensure proper API credentials

#### Refund Failed

**Solutions:**
- Verify order was successfully paid
- Check refund amount doesn't exceed original
- Ensure sufficient time has passed since payment
- Verify API credentials have refund permissions

### Viewing Logs

1. Go to **WooCommerce > Inecobank Logs**
2. Select the log file to view
3. Look for error messages and API responses
4. Use log patterns to diagnose issues

## Frequently Asked Questions

### Do I need an SSL certificate?

Yes, SSL (HTTPS) is required for secure payment processing.

### Which payment cards are accepted?

All major credit and debit cards supported by Inecobank, including:
- Visa
- Mastercard
- ArCa (Armenian Card)

### Can I use this with subscription products?

Currently, the plugin supports one-time payments. Recurring payment support may be added in future versions.

### How long does it take to receive funds?

Settlement times depend on your agreement with Inecobank. Contact them for specific details.

### Is there a transaction fee?

Transaction fees are determined by your merchant agreement with Inecobank.

### Can I customize the payment page?

The payment page is hosted by Inecobank and follows their branding. Language can be customized in plugin settings.

## Support

For technical support and questions:

- **Plugin Issues:** Create an issue in the GitHub repository
- **Inecobank Account:** Contact Inecobank support
- **Email:** info@inecobank.am
- **Phone:** +374 10 123456

## Changelog

### Version 1.0.0 - 2024-12-26
- Initial release
- One-phase payment support
- Two-phase payment support
- Refund functionality
- Multi-language support
- Multi-currency support
- Test mode
- Auto-repair webhook system
- Comprehensive logging
- Enhanced order tracking

## Developer Documentation

### Hooks and Filters

#### Actions

```php
// Before payment processing
do_action('woo_inecobank_before_payment_process', $order_id);

// After successful payment
do_action('woo_inecobank_payment_complete', $order_id, $inecobank_order_id);

// After refund
do_action('woo_inecobank_refund_complete', $order_id, $refund_amount);
```

#### Filters

```php
// Modify API request data
apply_filters('woo_inecobank_register_order_data', $data, $order);

// Modify payment description
apply_filters('woo_inecobank_payment_description', $description, $order);

// Modify return URL
apply_filters('woo_inecobank_redirect_url', $url, $order);

// Modify success URL
apply_filters('woo_inecobank_success_url', $url, $order);
```

### Custom Integration Example

```php
// Add custom data to payment request
add_filter('woo_inecobank_register_order_data', function($data, $order) {
    $data['customField'] = 'custom_value';
    return $data;
}, 10, 2);

// Custom handling after successful payment
add_action('woo_inecobank_payment_complete', function($order_id, $inecobank_order_id) {
    // Your custom code here
    error_log("Payment completed for order #$order_id");
}, 10, 2);
```

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed for integration with Inecobank CJSC E-commerce Payment Gateway.

## Disclaimer

This plugin is provided "as is" without warranty of any kind. Use at your own risk. Always test thoroughly in a staging environment before deploying to production.

---

**Need Help?** Contact Inecobank support for merchant account setup and API access.
