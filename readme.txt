=== Core Forms ===
Contributors: gauravtiwari
Donate link: https://gauravtiwari.org
Tags: forms, contact form, custom forms, form builder, html form
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 3.2.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A simpler, faster, and smarter WordPress forms plugin with all premium features included.

== Description ==

**Core Forms** is a lightweight, performance-focused form plugin for WordPress. Built for developers and site owners who want complete control over their forms without the bloat.

All premium features are now included in the core plugin - no add-ons required!

= Features =

* **Lightweight & Fast** - Minimal JavaScript and CSS footprint
* **Full HTML Control** - Write your own form HTML with complete flexibility
* **Gutenberg Block** - Easy form insertion via the block editor
* **Shortcode Support** - Use `[cf_form slug="your-form-slug"]` anywhere
* **AJAX Submissions** - Smooth, no-refresh form submissions
* **Email Notifications** - Send customizable email notifications on submission
* **Mailchimp Integration** - Connect forms to your Mailchimp lists (requires MC4WP)
* **Data Variables** - Dynamic content replacement in forms and emails
* **Submission Storage** - Save and manage form submissions in the database
* **Export Submissions** - Export form submissions to CSV
* **reCAPTCHA v3** - Google reCAPTCHA v3 integration for spam protection
* **hCaptcha** - hCaptcha integration as an alternative to reCAPTCHA

= Premium Features (Now Included!) =

* **File Uploads** - Allow users to upload files through your forms
* **Webhooks** - Send form data to external services and APIs
* **Submission Limits** - Limit the number of form submissions
* **User Login Required** - Restrict forms to logged-in users only
* **Data Management** - Advanced submission data organization
* **Admin Notifications** - Visual notification badges in admin

= Developer Friendly =

* Clean, well-documented codebase
* Extensive filter and action hooks for customization
* `Core_Forms` namespace for all classes
* `cf_*` function prefix for all public functions
* PSR-4 compatible autoloading
* Forked from HTML Forms - a performance packed contact form plugin.

= Requirements =

* WordPress 6.0 or higher
* PHP 7.4 or higher

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/core-forms/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to **Core Forms** in the admin menu to create your first form

== Frequently Asked Questions ==

= How do I create a form? =

1. Navigate to **Core Forms > Add New**
2. Enter your form HTML in the editor
3. Configure actions and settings
4. Use the shortcode `[cf_form slug="your-form"]` to display the form

= How do I access form submissions? =

Go to **Core Forms** and click on your form, then navigate to the **Submissions** tab.

= Can I export submissions? =

Yes! Go to **Core Forms > Settings** and use the Export functionality to download submissions as CSV.

= Does this work with Gutenberg? =

Yes! Core Forms includes a Gutenberg block for easy form insertion.

== Changelog ==

= 3.0.0 =
* Complete rebrand from HTML Forms to Core Forms
* All premium features now included in core plugin
* Updated namespace to `Core_Forms`
* Updated function prefix to `cf_`
* Updated shortcode to `[cf_form]`
* Updated block to `core-forms/form`
* New Features
* Performance improvements and code cleanup
