<?php

namespace Core_Forms\Admin;

class EmailLogs {

    private $plugin_file;

    public function __construct( $plugin_file ) {
        $this->plugin_file = $plugin_file;
    }

    public function hook() {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_init', array( $this, 'maybe_create_table' ) );
        add_action( 'cf_admin_action_delete_email_log', array( $this, 'process_delete_log' ) );
        add_action( 'cf_admin_action_bulk_delete_email_logs', array( $this, 'process_bulk_delete_logs' ) );
        add_action( 'cf_admin_action_resend_email', array( $this, 'process_resend_email' ) );
    }

    public function maybe_create_table() {
        $option_key = 'cf_email_logs_table_created';
        if ( get_option( $option_key ) ) {
            return;
        }
        _cf_create_email_logs_table();
        update_option( $option_key, '1' );
    }

    public function menu() {
        add_submenu_page(
            'core-forms',
            __( 'Email Logs', 'core-forms' ),
            __( 'Email Logs', 'core-forms' ),
            'edit_forms',
            'core-forms-email-logs',
            array( $this, 'page_email_logs' )
        );
    }

    public function page_email_logs() {
        require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
        require_once dirname( $this->plugin_file ) . '/src/admin/class-email-logs-table.php';
        $table = new EmailLogsTable();
        require dirname( $this->plugin_file ) . '/views/page-email-logs.php';
    }

    public function process_delete_log() {
        if ( ! current_user_can( 'edit_forms' ) || ! check_admin_referer( 'cf_admin_action' ) ) {
            wp_die( __( 'You are not allowed to do this.', 'core-forms' ) );
        }

        $log_id = isset( $_GET['log_id'] ) ? (int) $_GET['log_id'] : 0;
        if ( $log_id > 0 ) {
            global $wpdb;
            $table = $wpdb->prefix . 'cf_email_logs';
            $wpdb->delete( $table, array( 'id' => $log_id ), array( '%d' ) );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=core-forms-email-logs&deleted=1' ) );
        exit;
    }

    public function process_bulk_delete_logs() {
        if ( ! current_user_can( 'edit_forms' ) || ! check_admin_referer( 'cf_admin_action' ) ) {
            wp_die( __( 'You are not allowed to do this.', 'core-forms' ) );
        }

        $log_ids = isset( $_POST['log_ids'] ) ? array_map( 'intval', (array) $_POST['log_ids'] ) : array();
        if ( ! empty( $log_ids ) ) {
            global $wpdb;
            $table = $wpdb->prefix . 'cf_email_logs';
            $placeholders = implode( ',', array_fill( 0, count( $log_ids ), '%d' ) );
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id IN ({$placeholders})", $log_ids ) );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=core-forms-email-logs&deleted=' . count( $log_ids ) ) );
        exit;
    }

    public function process_resend_email() {
        if ( ! current_user_can( 'edit_forms' ) || ! check_admin_referer( 'cf_admin_action' ) ) {
            wp_die( __( 'You are not allowed to do this.', 'core-forms' ) );
        }

        $log_id = isset( $_GET['log_id'] ) ? (int) $_GET['log_id'] : 0;
        if ( $log_id > 0 ) {
            global $wpdb;
            $table = $wpdb->prefix . 'cf_email_logs';
            $log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $log_id ) );

            if ( $log ) {
                $headers = ! empty( $log->headers ) ? explode( "\n", $log->headers ) : array();
                $result = wp_mail( $log->to_email, $log->subject, $log->message, $headers );

                if ( $result ) {
                    wp_safe_redirect( admin_url( 'admin.php?page=core-forms-email-logs&resent=1' ) );
                } else {
                    wp_safe_redirect( admin_url( 'admin.php?page=core-forms-email-logs&resend_failed=1' ) );
                }
                exit;
            }
        }

        wp_safe_redirect( admin_url( 'admin.php?page=core-forms-email-logs' ) );
        exit;
    }
}
