=== OwnPay Payment Gateway ===
Contributors: OwnPay
Donate link: https://ownpay.org/donate
Tags: ownpay, payment gateway, woocommerce, checkout, cards payment
Requires at least: 5.1
Tested up to: 7.0
Requires PHP: 8.0
Requires Plugins: woocommerce
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept card, bank transfer, and mobile banking payments in WooCommerce via OwnPay.

== Description ==

OwnPay Payment Gateway is the official WooCommerce integration for OwnPay payment gateways. It provides your customers with a secure, responsive, and seamless checkout experience. Offer credit/debit cards, bank transfers, and local mobile banking under your own custom white-labeled brand.

### Key Features:
* Quick integration of OwnPay payments into WooCommerce checkout.
* Secure, encrypted transaction processing.
* Configure custom checkout gateway logos directly from the admin dashboard with automatic sizing constraints.
* Built-in server-to-server webhook confirmation with HMAC-SHA256 signature verification.
* Synchronous check on thank-you page to verify payments instantly.
* Optional customer checkout processing fees (flat or percentage).
* Easy to read payment logs with raw API response payload view.

### Benefits:
- **Unified Checkout**: Pay using multiple payment methods on a single white-labeled endpoint.
- **Secure Transactions**: Protect your store and customers with HMAC signed verification callbacks.
- **Responsive & Fast**: Designed to load efficiently on both desktop and mobile screens.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory, or install it directly through the WordPress plugins dashboard.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to WooCommerce > Settings > Payments, and click on **OwnPay**.
4. Configure the API endpoint, Bearer API Key, and Webhook Secret.
5. Copy the displayed Webhook callback URL (`https://your-site.com/?wc-api=ownpay`) and configure it in your OwnPay Merchant dashboard.

== Frequently Asked Questions ==

= How do I configure webhooks? =
Copy the Webhook URL from the OwnPay settings in WooCommerce, paste it into your OwnPay Developer dashboard webhooks list, and copy the generated secret key back to the WooCommerce settings.

= Can I customize the gateway logo? =
Yes! Input any image URL under the "Custom Gateway Logo URL" option in the payment settings. The logo will automatically be rendered with a fixed size constraint to fit the layout.

== Screenshots ==
1. Settings panel showing API credentials and webhook helper.
2. Customer checkout payment method selection.
3. OwnPay admin dashboard showing recent WooCommerce transaction logs.

== External Services ==

This plugin sends payment data to your OwnPay gateway installation - a self-hosted or
managed payment server whose URL you configure in WooCommerce > Settings > Payments > OwnPay.

Data sent to your configured endpoint when a customer initiates a checkout:

* Order amount and currency
* Customer email address, name, and phone number
* WooCommerce order ID (as a payment reference)

No data is sent to any OwnPay-operated server by default. The API endpoint is entirely
controlled by the site administrator. For OwnPay's privacy policy, visit https://ownpay.org/privacy.

== Privacy ==

This plugin communicates with the OwnPay payment API (configurable endpoint) to process transactions. No customer data is sent to OwnPay without the customer initiating a payment. Transaction details are stored in WooCommerce order meta. For OwnPay's privacy policy, visit https://ownpay.org/privacy.

== Changelog ==

= 1.0.0 =
* Initial release of the OwnPay Payment Gateway plugin.

== Upgrade Notice ==

= 1.0.0 =
Initial release.