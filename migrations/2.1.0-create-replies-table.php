<?php
/**
 * Core Forms 2.1.0 Migration
 *
 * Create submission replies table for storing email replies sent from dashboard
 */

global $wpdb;

$table = $wpdb->prefix . 'cf_submission_replies';
$charset_collate = $wpdb->get_charset_collate();

$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) === $table;

if ( ! $table_exists ) {
    $wpdb->query(
        "CREATE TABLE {$table} (
            `id` INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
            `submission_id` INT UNSIGNED NOT NULL,
            `from_email` VARCHAR(255) NOT NULL,
            `to_email` VARCHAR(255) NOT NULL,
            `subject` VARCHAR(500) NOT NULL,
            `message` LONGTEXT NOT NULL,
            `user_id` BIGINT UNSIGNED NULL,
            `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `submission_idx` (`submission_id`)
        ) {$charset_collate};"
    );
}
