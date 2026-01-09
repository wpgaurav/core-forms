<?php
/**
 * Core Forms 2.1.0 Migration
 *
 * Add modified_at column to submissions table for tracking edits
 */

global $wpdb;

$table = $wpdb->prefix . 'cf_submissions';

$column_exists = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = %s
        AND TABLE_NAME = %s
        AND COLUMN_NAME = %s",
        DB_NAME,
        $table,
        'modified_at'
    )
);

if ( empty( $column_exists ) ) {
    $wpdb->query(
        "ALTER TABLE `{$table}`
        ADD COLUMN `modified_at` TIMESTAMP NULL AFTER `submitted_at`"
    );
}
