<?php
/**
 * Core Forms 2.2.0 Migration
 *
 * Create poll tables for the Polls feature
 */

global $wpdb;

$charset_collate = $wpdb->get_charset_collate();

$polls_table = $wpdb->prefix . 'cf_polls';
$votes_table = $wpdb->prefix . 'cf_poll_votes';

$polls_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $polls_table ) ) === $polls_table;

if ( ! $polls_exists ) {
    $wpdb->query(
        "CREATE TABLE {$polls_table} (
            `id` INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
            `post_id` INT UNSIGNED NOT NULL,
            `question` TEXT NOT NULL,
            `options` LONGTEXT NOT NULL,
            `settings` LONGTEXT NOT NULL,
            `status` VARCHAR(20) DEFAULT 'active',
            `ends_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `post_idx` (`post_id`),
            INDEX `status_idx` (`status`)
        ) {$charset_collate};"
    );
}

$votes_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $votes_table ) ) === $votes_table;

if ( ! $votes_exists ) {
    $wpdb->query(
        "CREATE TABLE {$votes_table} (
            `id` INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
            `poll_id` INT UNSIGNED NOT NULL,
            `option_index` TINYINT UNSIGNED NOT NULL,
            `ip_address` VARCHAR(45) NULL,
            `user_id` BIGINT UNSIGNED NULL,
            `cookie_hash` VARCHAR(64) NULL,
            `voted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `poll_idx` (`poll_id`),
            INDEX `ip_idx` (`ip_address`),
            INDEX `user_idx` (`user_id`)
        ) {$charset_collate};"
    );
}
