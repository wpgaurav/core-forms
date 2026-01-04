<?php
/**
 * Core Forms 2.0.0 Migration
 * 
 * This migration handles the transition from HTML Forms to Core Forms:
 * 1. Rename database table from hf_submissions to cf_submissions
 * 2. Update post type from html-form to core-form
 * 3. Update meta keys from _hf_ to _cf_
 * 4. Update option names from hf_ to cf_
 */

global $wpdb;

// Rename submissions table from hf_submissions to cf_submissions
$old_table = $wpdb->prefix . 'hf_submissions';
$new_table = $wpdb->prefix . 'cf_submissions';

// Check if old table exists and new table doesn't
$old_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $old_table ) ) === $old_table;
$new_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $new_table ) ) === $new_table;

if ( $old_exists && ! $new_exists ) {
    $wpdb->query( "RENAME TABLE `{$old_table}` TO `{$new_table}`" );
} elseif ( ! $new_exists ) {
    // Create new table if it doesn't exist (fresh install)
    $charset_collate = $wpdb->get_charset_collate();
    $wpdb->query(
        "CREATE TABLE IF NOT EXISTS {$new_table}(
        `id` INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
        `form_id` INT UNSIGNED NOT NULL,
        `data` TEXT NOT NULL,
        `user_agent` TEXT NULL,
        `ip_address` VARCHAR(255) NULL,
        `referer_url` TEXT NULL,
        `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) {$charset_collate};"
    );
}

// Update post type from html-form to core-form
$wpdb->query(
    $wpdb->prepare(
        "UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s",
        'core-form',
        'html-form'
    )
);

// Update settings meta key from _hf_settings to _cf_settings
$wpdb->query(
    $wpdb->prepare(
        "UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
        '_cf_settings',
        '_hf_settings'
    )
);

// Update message meta keys from hf_message_ to cf_message_
$wpdb->query(
    "UPDATE {$wpdb->postmeta} SET meta_key = REPLACE(meta_key, 'hf_message_', 'cf_message_') WHERE meta_key LIKE 'hf_message_%'"
);

// Migrate hf_settings option to cf_settings
$old_settings = get_option( 'hf_settings', null );
if ( $old_settings !== null ) {
    $new_settings = get_option( 'cf_settings', array() );
    
    // Merge old settings with any new settings
    if ( is_array( $old_settings ) ) {
        $merged_settings = array_merge( $old_settings, $new_settings );
        update_option( 'cf_settings', $merged_settings );
    }
    
    // Delete old option
    delete_option( 'hf_settings' );
}

// Migrate version option
$old_version = get_option( 'hf_version', null );
if ( $old_version !== null ) {
    update_option( 'cf_version', $old_version );
    delete_option( 'hf_version' );
}
