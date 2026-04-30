<p>
  <a href="https://wordpress.org/plugins/billplz-for-contact-form-7" target="_blank">
    <img src="./.wordpress-org/banner-772x250.png" alt="Billplz for Contact Form 7" width="772" height="250">
  </a>
</p>

# Accept payment in Contact Form 7 by using Billplz.

### Description
This is a gateway extension for Contact Form 7 plugin to use Billplz Payment Gateway.

Payments are processed offsite at [Billplz](https://billplz.com) and the customer will be redirected back to your site after completing the payment.

### Changelog

#### 1.3.0 - April 29, 2026
* New: Failed payments now appear in the payments admin tab and have their own Failed view filter.
* New: The example payment form created on activation is automatically selected as the active payment form.
* Security: Verified Billplz signature inside the payment confirmation shortcode so crafted URLs can no longer expose another payer's details.
* Security: Required a capability check and bulk-action nonce on the payments admin table before deleting or marking entries completed.
* Security: Escaped the transaction ID link in the payments admin table.
* Security: Verified the paid amount reported by Billplz against the recorded bill before marking a payment completed.
* Security: Sanitized API, general, and email settings on save, including X-Signature key and email body input.
* Security: Hardened the credentials notice to escape its admin URL and run a capability check.
* Improvement: Payment redirect now works with Contact Form 7's Ajax submission flow.
* Improvement: Billplz callback completion is now idempotent, so repeated callbacks cannot reprocess the same payment.
* Improvement: Confirmation email now sends as HTML with the correct Content-Type header and escapes transaction placeholders.
* Improvement: Payments admin table now uses the site timezone for the Submitted and Paid columns.
* Fix: Stopped writing the 0000-00-00 zero datetime to paid_at, which failed under MySQL strict mode.
* Compatibility: Tested up to WordPress 6.9.

#### 1.2.1 - July 14, 2025
* Security: Fixed XSS vulnerability in admin area payment table links.

#### 1.2 - March 30, 2023
* New: Added option to send email confirmation on payment success.
* New: Added ability to select multiple forms as payment forms.
* Improvement: Codebase refactoring for better organization.

#### 1.0.2 - December 24, 2022
* New: Display current mode status (Live / Test) on the dashboard's admin bar.
* New: A payment redirect page will be automatically created and selected by default upon plugin activation.
* Improvement on settings page UI

#### 1.0.1 - December 16, 2022
* Fix: Fatal error upon activation when Contact Form 7 is not active.

#### 1.0.0 - December 14, 2022
* Stable release

#### 0.1.0 - November 29, 2022
* Release Candidate-1

### DISCLAIMER
*This project is not affiliated, associated, authorized, endorsed by, or in any way officially connected with Billplz or Contact Form 7 or any of its subsidiaries or its affiliates. The official Billplz and Contact Form 7 website can be found at https://billplz.com and https://contactform7.com respectively. "Billplz" or "Contact Form 7" as well as related names, marks, emblems and images are registered trademarks of their respective owners.*