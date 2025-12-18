=== Inecobank Payment Gateway for WooCommerce ===
Contributors: inecobank
Tags: payment, gateway, inecobank, armenia, woocommerce, credit card, ecommerce
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Accept credit and debit card payments on your WooCommerce store using Inecobank Payment Gateway.

== Description ==

The Inecobank Payment Gateway plugin allows you to accept payments directly on your WooCommerce store via Inecobank's secure payment platform. This plugin supports both one-phase (immediate) and two-phase (preauthorization) payment flows.

= Key Features =

* **One-Phase Payments** - Immediate payment processing
* **Two-Phase Payments** - Preauthorization with manual completion
* **Full & Partial Refunds** - Process refunds directly from WooCommerce
* **Test Mode** - Test your integration before going live
* **Multi-Language Support** - Armenian, English, and Russian
* **Multi-Currency Support** - AMD, USD, EUR, RUB
* **Secure Payment Processing** - PCI-compliant through Inecobank
* **Order Status Synchronization** - Automatic status updates
* **Mobile Responsive** - Works on all devices

= Supported Payment Cards =

* Visa
* Mastercard
* ArCa (Armenian Card)
* Other cards supported by Inecobank

= Supported Currencies =

* AMD - Armenian Dram (051)
* USD - US Dollar (840)
* EUR - Euro (978)
* RUB - Russian Ruble (643)

= Requirements =

* Active Inecobank merchant account
* SSL Certificate (HTTPS required)
* WooCommerce 3.0 or higher

= Documentation =

For detailed setup instructions and API documentation, please visit the plugin's GitHub repository or contact Inecobank support.

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Go to Plugins > Add New
3. Search for "Inecobank Payment Gateway"
4. Click Install Now and then Activate

= Manual Installation =

1. Download the plugin zip file
2. Go to WordPress admin panel > Plugins > Add New > Upload Plugin
3. Choose the downloaded zip file and click Install Now
4. Activate the plugin

= Configuration =

1. Go to WooCommerce > Settings > Payments
2. Click on "Inecobank Payment Gateway"
3. Enable the payment method
4. Enter your API credentials (provided by Inecobank)
5. Choose payment type (One-Phase or Two-Phase)
6. Select payment page language
7. Save changes

= Getting API Credentials =

Contact Inecobank to obtain your merchant credentials:
* API Username
* API Password
* Test credentials for development

== Frequently Asked Questions ==

= Do I need an SSL certificate? =

Yes, SSL (HTTPS) is mandatory for secure payment processing. Most hosting providers offer free SSL certificates through Let's Encrypt.

= Which payment cards are accepted? =

All major credit and debit cards supported by Inecobank, including Visa, Mastercard, and ArCa (Armenian Card).

= What is the difference between One-Phase and Two-Phase payments? =

**One-Phase Payment:** Payment is processed and captured immediately. Best for digital products and services.

**Two-Phase Payment:** Payment is authorized but not captured immediately. Funds are held on customer's card and you must manually complete the payment from the admin panel. Best for physical products where you want to capture payment after shipping.

= How do I complete a Two-Phase payment? =

1. Go to WooCommerce > Orders
2. Open the order with "On Hold" status
3. In the Order Actions dropdown, select "Complete Inecobank Payment"
4. Click Update to process the completion

= Can I process refunds? =

Yes, both full and partial refunds are supported. You can process refunds directly from the order page in WooCommerce admin.

= How do I process a refund? =

1. Go to WooCommerce > Orders
2. Open the completed order
3. Click the Refund button
4. Enter the refund amount and reason
5. Click "Refund via Inecobank"

= Does this plugin support recurring payments? =

Currently, the plugin supports one-time payments only. Recurring payment support may be added in future versions.

= How do I test the plugin before going live? =

Enable Test Mode in the plugin settings and use the test API credentials provided by Inecobank. You can then use test card numbers to simulate transactions.

= What should I do if payment fails? =

Common solutions:
* Verify your API credentials are correct
* Ensure Test Mode is properly configured if testing
* Check that your SSL certificate is valid
* Review WooCommerce error logs under WooCommerce > Status > Logs

= How long does it take to receive funds? =

Settlement times depend on your merchant agreement with Inecobank. Contact Inecobank support for specific details.

= Are there transaction fees? =

Transaction fees are determined by your merchant agreement with Inecobank. Contact them for pricing details.

= Can I customize the payment page? =

The payment page is hosted and secured by Inecobank. You can customize the language (Armenian, English, or Russian) in the plugin settings.

= Is customer card data stored on my website? =

No, all sensitive payment data is handled securely by Inecobank. Your website never stores or processes card details, ensuring PCI-DSS compliance.

= What happens if a customer doesn't complete the payment? =

The order will remain in "Pending Payment" status. You can set up automatic cancellation for unpaid orders in WooCommerce settings.

== Screenshots ==

1. Plugin settings page - Configure API credentials and payment options
2. Checkout page - Inecobank payment method displayed to customers
3. Payment page - Secure Inecobank payment form
4. Order management - Complete two-phase payments from admin panel
5. Refund interface - Process refunds directly from order page

== Changelog ==

= 1.0.0 - 2024-12-18 =
* Initial release
* One-phase payment support
* Two-phase payment (preauthorization) support
* Full and partial refund functionality
* Multi-language support (Armenian, English, Russian)
* Multi-currency support (AMD, USD, EUR, RUB)
* Test mode for development
* Secure API integration with Inecobank
* Order status synchronization
* WooCommerce 8.0 compatibility

== Upgrade Notice ==

= 1.0.0 =
Initial release of Inecobank Payment Gateway for WooCommerce.

== Additional Information ==

= Support =

For technical support:
* Plugin Issues: Create an issue in the GitHub repository
* Inecobank Account: Contact Inecobank support
* Email: info@inecobank.am

= API Documentation =

This plugin integrates with Inecobank E-commerce Payment Gateway 1.0 API using the following endpoints:
* register.do - One-phase payment registration
* registerPreAuth.do - Two-phase payment registration
* deposit.do - Complete two-phase payment
* refund.do - Process refunds
* reverse.do - Reverse payments
* getOrderStatusExtended.do - Check order status

= Security =

* All API credentials are securely stored
* SSL/TLS encryption for all API communication
* No sensitive card data stored on your server
* PCI-DSS compliant payment processing through Inecobank
* Regular security updates

= Developer Information =

For developers integrating custom functionality:

**Available Actions:**
* `inecobank_before_payment_process` - Before payment processing
* `inecobank_payment_complete` - After successful payment
* `inecobank_refund_complete` - After refund

**Available Filters:**
* `inecobank_payment_request_data` - Modify API request data
* `inecobank_payment_description` - Modify payment description
* `inecobank_return_url` - Modify return URL

Example:
`
add_filter('inecobank_payment_request_data', function($data, $order) {
    $data['customField'] = 'custom_value';
    return $data;
}, 10, 2);
`

= Privacy Policy =

This plugin does not collect or store any personal data. All payment data is processed securely by Inecobank Payment Gateway. Please refer to Inecobank's privacy policy for information about how they handle payment data.

= Translations =

The plugin is translation-ready and includes:
* English (default)
* Armenian (hy)
* Russian (ru)

You can contribute translations through WordPress.org translation system.

== Credits ==

Developed for integration with Inecobank CJSC E-commerce Payment Gateway.

== License ==

This plugin is licensed under the GPL v2 or later.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
