<?php
/**
 * Core Forms 2.0.18 Migration
 *
 * Add is_spam column to submissions table for spam management
 */

global $wpdb;

$table = $wpdb->prefix . 'cf_submissions';

// Check if column already exists
$column_exists = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = %s
        AND TABLE_NAME = %s
        AND COLUMN_NAME = %s",
        DB_NAME,
        $table,
        'is_spam'
    )
);

if ( empty( $column_exists ) ) {
    $wpdb->query(
        "ALTER TABLE `{$table}`
        ADD COLUMN `is_spam` TINYINT(1) NOT NULL DEFAULT 0 AFTER `referer_url`,
        ADD INDEX `is_spam_idx` (`is_spam`)"
    );
}
