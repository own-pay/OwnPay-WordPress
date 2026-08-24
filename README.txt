=== OwnPay Payment Gateway ===
Contributors: ownpay
Donate link: https://ownpay.org/donate
Tags: ownpay, payment gateway, woocommerce, checkout, cards payment
Requires at least: 5.1
Tested up to: 7.1
Requires PHP: 8.0
Requires Plugins: woocommerce
Stable tag: 1.3.0
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

= 1.3.0 =
* Fixed cancel/failed payment redirect to use guest-compatible pages (Pay for Order for failed, Cart for cancelled).
* Replaced transient-based customer notice with WooCommerce session for reliable delivery across redirects.
* Fixed empty Gateway Transaction ID in redirect success order note by checking multiple API field name variants.
* Removed dead binary dashboard-menu-icon.jpg that was still in the repo.
* Added server-side payment status verification and customer-facing notices for failed and cancelled payment redirects from OwnPay.
* Extracted verify_payment_by_id() reusable method from sync_payment_status() to avoid code duplication.
* Added wp_unslash() to $_SERVER header fallback values in webhook handler.
* Wrapped method_title and method_description in __() for translation support.
* Changed admin menu capability from manage_options to manage_woocommerce for consistency with WooCommerce extension guidelines.
* Added floatval() cast to fee percentage in cart fee label.
* Fixed WordPress Plugin Checker warnings: ordered i18n placeholders, nonce verification comments for redirect parameters.
* Added automated GitHub Actions release workflow with version-tagged releases and plugin zip asset generation.
* Made admin payment list page responsive on mobile devices.
* Renamed settings labels: "OwnPay API Endpoint URL" to "OwnPay Base URL", "Custom Gateway Logo" to "OwnPay Gateway Logo".
* Added info tooltip on Webhook Secret setting.

= 1.2.0 =
* Removed stale binary dashboard menu icon (196KB JPG) in favor of the existing SVG icon.
* Added deactivation hook to clean up the runtime cache version option.
* Fixed version constant mismatch: `OPWC_VERSION` now correctly reflects `1.2.0` instead of the stale `1.0.0` value.
* Updated Plugin URI to point to the WordPress.org plugin directory listing instead of the GitHub repository.
* Improved `getallheaders()` fallback to only iterate `HTTP_*` server variables along with `CONTENT_TYPE` and `CONTENT_LENGTH`, avoiding unnecessary iteration over all `$_SERVER` keys.
* Removed redundant `strval()` wrappers around `sanitize_text_field()` calls where applicable.
* Refactored self-hooking in class constructors: moved `add_action`/`add_filter` registrations from `__construct()` into dedicated `init()` methods called externally, following WordPress.org coding best practices. Affected classes: `OPWC_Payment`, `OPWC_Hooks`, `OPWC_Menu_Settings`, and `OPWC_Admin`.
* Added `index.php` silence files to all subdirectories (`admin/css/`, `admin/js/`, `admin/partials/`, `admin/partials/views/`, `admin/partials/views/payment-list/`, `assets/`, `assets/js/`, `assets/logo/`, `languages/`) to prevent directory listing on servers.
* Fixed fallback version string in `OPWC` class constructor to match the current release.
* Expanded changelog entries for this release with specific technical details.

= 1.1.0 =
* Fixed some bugs

= 1.0.0 =
* Initial release of the OwnPay Payment Gateway plugin.

