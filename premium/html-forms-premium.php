<?php
/*
Plugin Name: HTML Forms Premium
Plugin URI: https://www.htmlformsplugin.com/premium/
Description: Contains all premium functionality for HTML Forms.
Version: 1.2.0
Author: HTML Forms
Author URI: https://htmlformsplugin.com/
License: GPL v3
Text Domain: html-forms

HTML Forms
Copyright (C) 2017-2024, Link Software LLC, support@linksoftwarellc.com

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


// Prevent direct file access
defined( 'ABSPATH' ) or exit;


/**
 * Loads the various premium add-on plugins
 *
 * @ignore
 */
function _hf_premium_bootstrap() {
    // don't run if HTML Forms itself is not activated
    if ( ! defined( 'HTML_FORMS_VERSION' ) ) {
        add_action( 'admin_notices', function() {
           echo '<div class="notice notice-error"><p>'. sprintf(__( 'You need to install and activate <a href="%s">HTML Forms</a> in order to use HTML Forms Premium.', 'html-forms-premium'), admin_url('plugin-install.php?s=html+forms+ibericode&tab=search&type=term')) .'</p></div>';
        });
        return;
    }

	// Define some useful constants
	define('HF_PREMIUM_VERSION', '1.2.0');
	define( 'HF_PREMIUM_PLUGIN_FILE', __FILE__ );

    require __DIR__ . '/data-exporter/data-exporter.php';
	require __DIR__ . '/data-management/data-management.php';
	require __DIR__ . '/webhooks/webhooks.php';
	require __DIR__ . '/file-upload/file-upload.php';
	require __DIR__ . '/notifications/notifications.php';
	require __DIR__ . '/submission-limit/submission-limit.php';
	require __DIR__ . '/require-user-logged-in/require-user-logged-in.php';
	require __DIR__ . '/licensing/licensing.php';
}

// Only bootstrap on PHP 5.3 and later
if ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
	add_action( 'plugins_loaded', '_hf_premium_bootstrap', 30 );
}

