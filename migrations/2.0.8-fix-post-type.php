<?php
/**
 * Core Forms 2.0.8 Migration
 *
 * Fix any forms that were created with wrong post_type after initial migration
 */

global $wpdb;

// Update any remaining html-form posts to core-form
$wpdb->query(
    $wpdb->prepare(
        "UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s",
        'core-form',
        'html-form'
    )
);
