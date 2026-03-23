=== Inecobank Payment Gateway for WooCommerce ===
Contributors: garikhg
Donate link: https://inecobank.am
Tags: payment gateway, inecobank, armenia, woocommerce, payment, credit card, debit card, arca, visa, mastercard
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 1.1.16
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept credit and debit card payments on your WooCommerce store using Inecobank Payment Gateway - Armenia's trusted payment solution.

== Description ==

**Inecobank Payment Gateway for WooCommerce** enables you to accept secure online payments directly on your store through Inecobank's reliable payment platform. Perfect for Armenian businesses and international merchants accepting AMD, USD, EUR, and RUB.

= Why Choose Inecobank Payment Gateway? =

* **Trusted by Thousands** - Inecobank is one of Armenia's leading banks
* **Secure & PCI Compliant** - Industry-standard security for all transactions
* **Multiple Payment Options** - Support for Visa, Mastercard, and ArCa cards
* **Fast Settlement** - Quick fund transfers to your merchant account
* **Local Support** - Armenian customer support team

= Key Features =

* **One-Phase Payments** - Instant payment processing for digital products
* **Two-Phase Payments** - Authorization with manual capture for physical goods
* **Full & Partial Refunds** - Easy refund management from WooCommerce admin
* **Test Mode** - Sandbox environment for testing without real transactions
* **Multi-Language** - Interface in Armenian, English, and Russian
* **Multi-Currency** - Accept AMD, USD, EUR, and RUB
* **Auto Status Updates** - Orders automatically updated based on payment status
* **Mobile Optimized** - Seamless payment experience on all devices
* **Detailed Logging** - Comprehensive logs for troubleshooting
* **Auto-Repair System** - Smart metadata recovery for order tracking

= Supported Payment Cards =

* Visa
* Mastercard
* ArCa (Armenian Card System)
* All cards supported by Inecobank

= Supported Currencies =

* AMD - Armenian Dram
* USD - US Dollar
* EUR - Euro
* RUB - Russian Ruble

= Security & Compliance =

* **PCI-DSS Compliant** - Inecobank handles all sensitive card data
* **SSL Encrypted** - Secure communication between your site and Inecobank
* **No Card Storage** - Customer card details never touch your server
* **Secure Webhooks** - Order status updates via secure callbacks
* **Regular Updates** - Ongoing security patches and improvements

= Perfect For =

* E-commerce stores in Armenia
* Businesses accepting Armenian Dram (AMD)
* International merchants targeting Armenian market
* Shops selling digital and physical products
* Subscription-based services (coming soon)

= Requirements =

* Active Inecobank merchant account (contact Inecobank to apply)
* WooCommerce 3.0 or higher
* WordPress 5.0 or higher
* PHP 7.2 or higher
* SSL Certificate (required for production)

= Getting Started =

1. Install and activate the plugin
2. Get API credentials from Inecobank
3. Configure settings in WooCommerce > Settings > Payments
4. Test with Test Mode enabled
5. Go live and start accepting payments!

= Developer Friendly =

Includes hooks and filters for custom integrations:

* `woo_inecobank_payment_complete` - After successful payment
* `woo_inecobank_refund_complete` - After refund processed
* `woo_inecobank_register_order_data` - Modify payment request data
* And more...

= Support & Documentation =

* **Plugin Support:** [GitHub Issues](https://github.com/garikhg/woo-inecobank-payment-gateway)
* **Inecobank Support:** info@inecobank.am
* **Phone:** +374 10 123456

= Privacy & Data =

This plugin does not collect, store, or share any personal data. All payment processing is handled securely by Inecobank. Customer payment details are never stored on your WordPress site.

== Installation ==

= Automatic Installation (Recommended) =

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins > Add New**
3. Search for "Inecobank Payment Gateway"
4. Click **Install Now**
5. Click **Activate Plugin**

= Manual Installation =

1. Download the plugin ZIP file
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the downloaded file and click **Install Now**
4. Click **Activate Plugin**

= Initial Setup =

1. Go to **WooCommerce > Settings > Payments**
2. Click on **Inecobank Payment Gateway**
3. Click the toggle to **Enable** the payment method
4. Configure your settings:

**Basic Configuration:**
* Enter a **Title** (e.g., "Credit/Debit Card")
* Add a **Description** for customers
* Select **Payment Type** (One-Phase or Two-Phase)
* Choose **Language** (Armenian, English, or Russian)

**API Credentials:**
* Enable **Test Mode** for testing
* Enter **Test API Username** and **Test API Password** (provided by Inecobank)
* Enter **Live API Username** and **Live API Password** (for production)

5. Click **Save changes**
6. Test a payment with Test Mode enabled
7. When ready, disable Test Mode to accept live payments

= Getting API Credentials =

Contact Inecobank to set up your merchant account:

* **Email:** info@inecobank.am
* **Phone:** +374 10 123456
* **Website:** https://inecobank.am

They will provide:
* Test API credentials for development
* Live API credentials for production
* Test card numbers for testing

== Frequently Asked Questions ==

= Do I need an Inecobank merchant account? =

Yes, you must have an active Inecobank merchant account to use this plugin. Contact Inecobank to apply for a merchant account.

= Is SSL required? =

Yes, an SSL certificate (HTTPS) is mandatory for processing live payments. Most hosting providers offer free SSL certificates through Let's Encrypt.

= Which payment cards are accepted? =

The plugin accepts all major credit and debit cards supported by Inecobank:
* Visa
* Mastercard  
* ArCa (Armenian Card System)

= What currencies can I accept? =

You can accept payments in:
* AMD (Armenian Dram) - 051
* USD (US Dollar) - 840
* EUR (Euro) - 978
* RUB (Russian Ruble) - 643

= What's the difference between One-Phase and Two-Phase payments? =

**One-Phase (Immediate Capture):**
* Payment is captured immediately
* Best for digital products, downloads, and services
* Funds transferred to your account right away

**Two-Phase (Preauthorization):**
* Payment is authorized but not captured
* Funds held on customer's card
* Manual capture required from admin panel
* Best for physical products (capture after shipping)

= How do I complete a Two-Phase payment? =

1. Go to **WooCommerce > Orders**
2. Open the order with **On Hold** status
3. Scroll to **Order Actions** dropdown
4. Select "Complete Inecobank Payment"
5. Click **Update**

The payment will be captured and order status updated to Processing.

= Can I process refunds? =

Yes! Both full and partial refunds are supported:

1. Open the completed order
2. Click the **Refund** button
3. Enter refund amount
4. Add refund reason (optional)
5. Click **Refund via Inecobank**

= How long does it take to receive funds? =

Settlement times depend on your merchant agreement with Inecobank. Typically:
* One-Phase payments: 1-2 business days
* Two-Phase payments: After manual capture

Contact Inecobank for your specific settlement terms.

= Are there transaction fees? =

Transaction fees are set in your merchant agreement with Inecobank. The plugin itself is free. Contact Inecobank for pricing details.

= Can I test before going live? =

Absolutely! Enable **Test Mode** in the plugin settings and use test API credentials provided by Inecobank. You can test the entire payment flow without processing real transactions.

= Is customer card data stored on my website? =

No. All sensitive payment data is processed and secured by Inecobank. Your WordPress site never stores or sees card details, ensuring PCI-DSS compliance.

= What happens if payment fails? =

If a payment fails:
* Order remains in **Pending Payment** status
* Customer sees an error message
* Customer can retry payment
* Order is automatically cancelled if unpaid (based on WooCommerce settings)

= Can I customize the payment page? =

The payment page is hosted by Inecobank for security. You can customize:
* Payment method title and description
* Language (Armenian, English, Russian)
* Return URL (automatically configured)

= Does this work with subscriptions? =

Recurring payments are not currently supported. This feature may be added in a future version. The plugin currently supports one-time payments only.

= How do I enable logging? =

1. Go to **WooCommerce > Settings > Payments > Inecobank**
2. Enable **Debug Mode**
3. View logs at **WooCommerce > Inecobank Logs**

Logs help diagnose issues with payment processing, API communication, and order status updates.

= What if my webhook URL is not working? =

Your webhook URL is:
`https://yoursite.com/?wc-api=inecobank-gateway`

Ensure:
* URL is publicly accessible (not behind firewall)
* SSL certificate is valid
* WordPress permalinks are enabled
* No security plugins blocking the webhook

= Can I use this plugin in other countries? =

Yes, but Inecobank primarily serves Armenian businesses. If you're outside Armenia, contact Inecobank to discuss international merchant accounts.

== Screenshots ==

1. Plugin settings page - Configure API credentials and payment options
2. Checkout page - Customers see Inecobank as a payment method
3. Secure payment page - Inecobank's hosted payment form (customer view)
4. Order details - Payment information displayed in admin
5. Two-phase completion - Admin action to capture preauthorized payment
6. Refund interface - Process refunds directly from order page
7. Payment logs - Detailed logging for troubleshooting

== Changelog ==

= 1.1.16 - 2026-01-12 =
* **New Features**
* ✅ Added automatic stock restoration when orders fail or are not paid
* ✅ Stock is restored for unpaid pending orders after 20 minutes
* ✅ Stock is restored when payments are declined or reversed
* ✅ Prevents duplicate stock restoration with meta tracking

* **Improvements**
* ✅ Enhanced order status checking with stock management
* ✅ Added detailed logging for stock restoration operations
* ✅ Improved webhook handling for failed payment statuses

= 1.1.15 - 2025-12-29 =
* **Improvements**
* ✅ Added detailed logging for API error responses
* ✅ Enhanced error handling in payment process

= 1.1.14 - 2025-12-29 =
* **Improvements**
* ✅ Increased API timeout to 60 seconds
* ✅ Increased HTTP Request timeout to 90 seconds
* ✅ Code cleanup

= 1.1.13 - 2025-12-29 =
* **Improvements**
* ✅ Aligned cURL request parameters with server-compatible configuration
* ✅ Removed custom User-Agent and IPv4 forcing to fix timeout issues

= 1.1.12 - 2025-12-29 =
* **Improvements**
* ✅ Reverted custom cURL configuration to ensure better compatibility
* ✅ Minor code cleanup and formatting improvements

= 1.1.11 - 2025-12-29 =
* **Improvements**
* ✅ Code style and formatting improvements
* ✅ Updated timeout settings

= 1.1.10 - 2025-12-29 =
* **Improvements**
* ✅ Increased default connection timeout to 60s for better reliability on slow servers
* ✅ Updated diagnostics tool with higher timeout thresholds
* ✅ Added cCurl diagnostics file

= 1.1.9 - 2025-12-29 =
* **Improvements**
* ✅ Added `custom_http_request_timeout` filter to set default HTTP timeout to 15s
* ✅ Added npm script for easier plugin distribution

= 1.1.8 - 2025-12-27 =
* **Diagnostics**
* ✅ Exposed diagnostics tool via `wp-admin/admin.php?woo_inecobank_diagnostics=true`
* ✅ Use this link to test direct server-to-server connectivity and identify firewall issues

= 1.1.7 - 2025-12-27 =
* **Connectivity**
* ✅ Added `User-Agent` header simulation to bypass strict firewalls
* ✅ Enforced IPv4 resolution (`CURLOPT_IPRESOLVE_V4`) to prevent IPv6 timeout issues

= 1.1.6 - 2025-12-27 =
* **Performance**
* ✅ Reverted to standard WordPress timeout handling (15s) to prevent 504 Gateway Timeouts
* ✅ Removed custom cURL hook injection for better server compatibility

= 1.1.5 - 2025-12-27 =
* **Tweaks**
* ✅ Adjusted default API timeout to 30 seconds for better balance between reliability and speed

= 1.1.4 - 2025-12-27 =
* **Critical Fixes**
* ✅ Fixed API request parameters: Set `jsonParams` to `FORCE_3DS2` for compliance
* ✅ Removed `clientId` from standard requests to prevent "binding logic" errors

= 1.1.3 - 2025-12-27 =
* **Improvements**
* ✅ Aligned API request parameters with legacy system for maximum compatibility
* ✅ Disabled SSL verification by default (matching upstream behavior)
* ✅ Simplified request headers to prevent server-side rejection

= 1.1.2 - 2025-12-27 =
* **Bug Fixes**
* ✅ Enforced cURL connection timeout to prevent 10s server drops
* ✅ Increased default API timeout to 60 seconds

= 1.1.1 - 2025-12-26 =
* **Bug Fixes**
* ✅ Fixed PHP timeout override - now sets `ini_set('default_socket_timeout')` dynamically
* ✅ Added filter hooks `woo_inecobank_api_timeout` and `woo_inecobank_request_args` for customization
* ✅ Enhanced timeout logging - now shows actual timeout value being used
* ✅ Improved timeout restoration to prevent affecting other plugins

* **New Tools**
* ✅ Added comprehensive connection diagnostic tool (`admin/diagnostics.php`)
* ✅ Diagnostics check: DNS resolution, cURL, PHP settings, socket connections, SSL/TLS, firewall
* ✅ Provides actionable recommendations for connection issues

= 1.1.0 - 2025-12-26 =
* **New Features**
* ✅ Added "Keep Cart Contents" option to prevent cart clearing when user clicks back from payment page
* ✅ Implemented automated order status checking every 20 minutes via WP-Cron
* ✅ Added unique order number generation with timestamp suffix to prevent duplicate order errors
* ✅ Enhanced logs page with WordPress-native styling (postbox components)
* ✅ Added collapsible log entries with expand/collapse functionality
* ✅ Added individual delete buttons for each log file
* ✅ Created complete translation files (Armenian and Russian)

* **Improvements**
* ✅ Increased API timeout from 10s to 45s (60s for local environments)
* ✅ Added automatic retry logic (2 attempts) for timeout errors
* ✅ Improved connection handling with "Connection: close" header
* ✅ Enhanced error logging with attempt tracking
* ✅ Better SSL/TLS connection handling

* **Bug Fixes**
* ✅ Fixed refund API to use Inecobank UUID instead of order number
* ✅ Fixed duplicate order number errors on payment retry
* ✅ Fixed 403 errors on logs page collapse/expand state saving
* ✅ Fixed testmode property usage for API initialization
* ✅ Fixed nonce mismatch in log deletion
* ✅ Resolved type hint errors in Gateway and Refund classes
* ✅ Fixed cart emptying logic for webhook returns after successful payment

* **Technical Updates**
* ✅ Orders now automatically marked as "Failed" if unpaid after 20 minutes
* ✅ API automatically retries on connection timeout
* ✅ Improved webhook handling for orders without UUID
* ✅ Enhanced logging system with better categorization

= 1.0.0 - 2024-12-26 =
* **Initial Release**
* ✅ One-phase (immediate) payment processing
* ✅ Two-phase (preauthorization) payment support
* ✅ Full and partial refund functionality
* ✅ Multi-language support (Armenian, English, Russian)
* ✅ Multi-currency support (AMD, USD, EUR, RUB)
* ✅ Test mode for development and testing
* ✅ Comprehensive logging system
* ✅ Auto-repair webhook system for order tracking
* ✅ Detailed payment information in admin
* ✅ WooCommerce 8.0+ compatibility
* ✅ WordPress 6.4+ compatibility
* ✅ PHP 7.2+ support

== Upgrade Notice ==

= 1.1.16 =
Stock restoration feature. Automatically restores product quantities when orders fail or are unpaid.

= 1.1.15 =
Logging update. Added detailed API error logging.

= 1.1.14 =
Timeout update. Increased connection timeouts.

= 1.1.13 =
Compatibility update. Fixed API timeout issues.

= 1.1.12 =
Compatibility update. Reverted cURL configurations.

= 1.1.11 =
Maintenance update. Code style improvements.

= 1.1.10 =
Performance update. Increased timeouts to 60s.

= 1.1.9 =
Timeout fix. Added custom filter to ensure connection reliability.

= 1.1.8 =
Diagnostics update. Run `wp-admin/admin.php?woo_inecobank_diagnostics=true` to test your server's connection to Inecobank.

= 1.1.7 =
Network fix. Adds User-Agent spoofing and IPv4 enforcement to bypass server-side firewalls/timeouts.

= 1.1.6 =
Stability fix. Addressed 504 Gateway errors by aligning timeout logic with server constraints.

= 1.1.5 =
Adjustment. Reduced default API timeout to 30s.

= 1.1.4 =
Critical API fix. Updates request parameters to strictly match Inecobank requirements (3DS2 enforcement).

= 1.1.3 =
Compatibility update. Matches connection parameters with legacy systems to resolve "Timeout was reached" errors.

= 1.1.2 =
Critical connection fix. Enforces cURL connection timeouts to resolve "Failed to connect ... after 10002 ms" errors on strict servers.

= 1.1.1 =
Critical patch for connection timeout issues. Adds diagnostic tool to troubleshoot Inecobank API connectivity. Recommended for users experiencing timeout errors.

= 1.1.0 =
Major update with automated order management, improved connection reliability, and enhanced user experience. Recommended for all users.

= 1.0.0 =
Initial release. Install to start accepting payments through Inecobank Payment Gateway.

== Additional Information ==

= API Integration =

This plugin integrates with Inecobank E-commerce Payment Gateway API 1.0 using these endpoints:

* `register.do` - One-phase payment registration
* `registerPreAuth.do` - Two-phase payment registration  
* `deposit.do` - Capture preauthorized payment
* `refund.do` - Process full/partial refunds
* `reverse.do` - Reverse/cancel payments
* `getOrderStatusExtended.do` - Check payment status

= Webhook System =

The plugin includes an intelligent webhook system:

* **Multi-tier order lookup** - Finds orders even if metadata is missing
* **Auto-repair functionality** - Repairs missing order metadata automatically
* **Graceful degradation** - Handles edge cases without breaking checkout
* **Comprehensive logging** - Detailed logs for debugging

= Order Metadata =

Each order stores detailed payment information:

* Order number sent to Inecobank
* Inecobank transaction UUID
* Payment type (one-phase/two-phase)
* Bank terminal reference number
* Payment approval code
* Masked card number (last 4 digits)
* Transaction timestamp

= Developer Hooks =

**Actions:**
`do_action('woo_inecobank_before_payment_process', $order_id);`
`do_action('woo_inecobank_payment_complete', $order_id, $inecobank_order_id);`
`do_action('woo_inecobank_refund_complete', $order_id, $amount);`

**Filters:**
`apply_filters('woo_inecobank_register_order_data', $data, $order);`
`apply_filters('woo_inecobank_payment_description', $description, $order);`
`apply_filters('woo_inecobank_redirect_url', $url, $order);`

Example usage:

`
add_filter('woo_inecobank_register_order_data', function($data, $order) {
    $data['custom_field'] = get_post_meta($order->get_id(), '_custom_meta', true);
    return $data;
}, 10, 2);
`

= Translations =

The plugin is translation-ready and includes:

* English (default)
* Armenian (hy)
* Russian (ru)

Contribute translations via WordPress.org translation system.

= Minimum Requirements =

* WordPress 5.0 or greater
* WooCommerce 3.0 or greater
* PHP version 7.2 or greater
* MySQL version 5.6 or greater OR MariaDB version 10.0 or greater
* SSL Certificate (for production use)

= Recommended Requirements =

* WordPress 6.0 or greater
* WooCommerce 7.0 or greater
* PHP version 7.4 or greater
* MySQL version 5.7 or greater OR MariaDB version 10.2 or greater

= Browser Support =

The payment page works on all modern browsers:
* Chrome (latest)
* Firefox (latest)
* Safari (latest)
* Edge (latest)
* Mobile browsers (iOS Safari, Chrome Mobile)

= Contributing =

Development happens on GitHub. Pull requests and bug reports are welcome:
https://github.com/garikhg/woo-inecobank-payment-gateway

= Support =

**For Plugin Issues:**
* GitHub: https://github.com/garikhg/woo-inecobank-payment-gateway/issues
* Email: support@example.com

**For Inecobank Account:**
* Email: info@inecobank.am
* Phone: +374 10 123456
* Website: https://inecobank.am

**For WooCommerce Issues:**
* WooCommerce Documentation: https://docs.woocommerce.com
* WooCommerce Support: https://woocommerce.com/support

= Credits =

Developed by Garegin Hakobyan for integration with Inecobank CJSC E-commerce Payment Gateway.

= License =

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

== Copyright ==

* The Inecobank logo and branding materials are trademarks of Inecobank CJSC.
* Visa, Mastercard, and ArCa logos are property of their respective owners.
* All plugin icons, banners, and screenshots are licensed under GPLv2 or later.
