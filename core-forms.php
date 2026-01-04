<?php
/*
Plugin Name: Core Forms
Plugin URI: https://developer.developer/plugins/core-forms
Description: A simpler, faster, and smarter WordPress forms plugin with premium features included.
Version: 2.0.12
Author: developer developer
Author URI: https://developer.developer
License: GPL v3
Text Domain: core-forms
Domain Path: /languages
Requires at least: 6.0
Requires PHP: 7.4

Core Forms
Copyright (C) 2017-2026, developer developer <developer@developer.developer>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace Core_Forms;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'CORE_FORMS_VERSION', '2.0.12' );
define( 'CORE_FORMS_PLUGIN_FILE', __FILE__ );
define( 'CORE_FORMS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'CORE_FORMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load autoloader early (but don't use translations)
if ( ! function_exists( 'cf_get_form' ) ) {
    require __DIR__ . '/vendor/autoload.php';
}

/**
 * Bootstrap the plugin - runs at init to ensure translations are loaded
 */
function _bootstrap() {
    $settings = cf_get_settings();

    $forms = new Forms( __FILE__, $settings );
    $forms->hook();

    if ( is_admin() ) {
        if ( ! \defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
            $admin = new Admin\Admin( __FILE__ );
            $admin->hook();
        }

        $gdpr = new Admin\GDPR();
        $gdpr->hook();

        if ( function_exists( 'hcaptcha' ) ) {
            $hcaptcha = new Admin\Hcaptcha();
            $hcaptcha->hook();
        }
    }

    // Initialize Google reCAPTCHA v3 (needs to run on both frontend and backend)
    if ( \class_exists( 'Core_Forms\\Admin\\Recaptcha' ) ) {
        $recaptcha = new Admin\Recaptcha();
        $recaptcha->hook();
    }

    // Load premium features (now integrated)
    _load_premium_features();
    
    // Initialize form actions
    _cf_actions();
}

/**
 * Load premium features - now integrated into core
 */
function _load_premium_features() {
    $premium_path = CORE_FORMS_PLUGIN_PATH . 'premium/';
    
    // Data Exporter
    if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
        require $premium_path . 'data-exporter/data-exporter.php';
    }
    
    // Data Management
    if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
        require $premium_path . 'data-management/data-management.php';
    }
    
    // Webhooks
    require $premium_path . 'webhooks/webhooks.php';
    
    // File Upload
    require $premium_path . 'file-upload/file-upload.php';
    
    // Notifications
    require $premium_path . 'notifications/notifications.php';
    
    // Submission Limit
    require $premium_path . 'submission-limit/submission-limit.php';
    
    // Require User Logged In
    require $premium_path . 'require-user-logged-in/require-user-logged-in.php';
}

/**
 * Initialize form actions
 */
function _cf_actions() {
    $email_action = new Actions\Email();
    $email_action->hook();

    if ( \class_exists( 'MC4WP_MailChimp' ) ) {
        $mailchimp_action = new Actions\MailChimp();
        $mailchimp_action->hook();
    }
}

// Hook into WordPress at init (priority 1 to run early but after translations are available)
add_action( 'init', 'Core_Forms\\_bootstrap', 1 );

// Activation hook
register_activation_hook( __FILE__, '_cf_on_plugin_activation' );

// Add cap to site admin after being added to blog
add_action( 'add_user_to_blog', '_cf_on_add_user_to_blog', 10, 3 );

// Install db table for newly added sites
add_action( 'wp_insert_site', '_cf_on_wp_insert_site' );
