<?php

if( is_admin() || (  defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
	require __DIR__ . '/class-admin.php';
	require __DIR__ . '/class-api-client.php';
	require __DIR__ . '/class-api-exception.php';

	$api_url = 'https://my.htmlformsplugin.com/api/v2';
	$plugin_slug = 'html-forms-premium';
	$plugin_file = HF_PREMIUM_PLUGIN_FILE;
	$plugin_version = HF_PREMIUM_VERSION;

	$admin = new HTML_Forms\Premium\Licensing\Admin( $plugin_slug, $plugin_file, $plugin_version, $api_url );
	$admin->add_hooks();
}


