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

class EmailLogsTable extends \WP_List_Table {

    public function __construct() {
        parent::__construct( array(
            'singular' => 'email_log',
            'plural'   => 'email_logs',
            'ajax'     => false,
        ) );
    }

    public function get_columns() {
        return array(
            'cb'         => '<input type="checkbox" />',
            'status'     => __( 'Status', 'core-forms' ),
            'to_email'   => __( 'To', 'core-forms' ),
            'subject'    => __( 'Subject', 'core-forms' ),
            'form'       => __( 'Form', 'core-forms' ),
            'type'       => __( 'Type', 'core-forms' ),
            'sent_at'    => __( 'Date', 'core-forms' ),
        );
    }

    public function get_sortable_columns() {
        return array(
            'sent_at' => array( 'sent_at', true ),
            'status'  => array( 'status', false ),
        );
    }

    public function get_bulk_actions() {
        return array(
            'delete' => __( 'Delete', 'core-forms' ),
        );
    }

    public function prepare_items() {
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;

        $args = array(
            'offset'  => $offset,
            'limit'   => $per_page,
            'orderby' => isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'sent_at',
            'order'   => isset( $_GET['order'] ) ? strtoupper( sanitize_key( $_GET['order'] ) ) : 'DESC',
        );

        if ( ! empty( $_GET['status'] ) ) {
            $args['status'] = sanitize_key( $_GET['status'] );
        }

        if ( ! empty( $_GET['form_id'] ) ) {
            $args['form_id'] = (int) $_GET['form_id'];
        }

        if ( ! empty( $_GET['s'] ) ) {
            $args['search'] = sanitize_text_field( $_GET['s'] );
        }

        $this->items = cf_get_email_logs( $args );
        $total_items = cf_count_email_logs( $args );

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ) );

        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns(),
        );
    }

    public function column_cb( $item ) {
        return sprintf( '<input type="checkbox" name="log_ids[]" value="%d" />', $item->id );
    }

    public function column_status( $item ) {
        $status_labels = array(
            'sent'    => '<span class="cf-status-badge cf-status-sent">' . __( 'Sent', 'core-forms' ) . '</span>',
            'failed'  => '<span class="cf-status-badge cf-status-failed">' . __( 'Failed', 'core-forms' ) . '</span>',
            'pending' => '<span class="cf-status-badge cf-status-pending">' . __( 'Pending', 'core-forms' ) . '</span>',
        );

        $output = isset( $status_labels[ $item->status ] ) ? $status_labels[ $item->status ] : esc_html( $item->status );

        if ( $item->status === 'failed' && ! empty( $item->error_message ) ) {
            $output .= '<br><small class="cf-error-message">' . esc_html( $item->error_message ) . '</small>';
        }

        return $output;
    }

    public function column_to_email( $item ) {
        $output = esc_html( $item->to_email );

        $actions = array(
            'view' => sprintf(
                '<a href="#" class="cf-view-email-details" data-log-id="%d">%s</a>',
                $item->id,
                __( 'View Details', 'core-forms' )
            ),
        );

        if ( $item->status === 'failed' ) {
            $resend_url = wp_nonce_url(
                admin_url( 'admin.php?page=core-forms-email-logs&_cf_admin_action=resend_email&log_id=' . $item->id ),
                'cf_admin_action'
            );
            $actions['resend'] = sprintf( '<a href="%s">%s</a>', esc_url( $resend_url ), __( 'Resend', 'core-forms' ) );
        }

        $delete_url = wp_nonce_url(
            admin_url( 'admin.php?page=core-forms-email-logs&_cf_admin_action=delete_email_log&log_id=' . $item->id ),
            'cf_admin_action'
        );
        $actions['delete'] = sprintf( '<a href="%s" class="submitdelete">%s</a>', esc_url( $delete_url ), __( 'Delete', 'core-forms' ) );

        return $output . $this->row_actions( $actions );
    }

    public function column_subject( $item ) {
        return esc_html( $item->subject ?: '(' . __( 'no subject', 'core-forms' ) . ')' );
    }

    public function column_form( $item ) {
        if ( empty( $item->form_id ) ) {
            return '—';
        }

        try {
            $form = cf_get_form( $item->form_id );
            return sprintf(
                '<a href="%s">%s</a>',
                esc_url( admin_url( 'admin.php?page=core-forms&view=edit&form_id=' . $form->ID ) ),
                esc_html( $form->title )
            );
        } catch ( \Exception $e ) {
            return sprintf( '#%d', $item->form_id );
        }
    }

    public function column_type( $item ) {
        $types = array(
            'email'         => __( 'Email', 'core-forms' ),
            'autoresponder' => __( 'Auto-Responder', 'core-forms' ),
        );
        return isset( $types[ $item->action_type ] ) ? $types[ $item->action_type ] : esc_html( $item->action_type );
    }

    public function column_sent_at( $item ) {
        $timestamp = strtotime( $item->sent_at );
        return sprintf(
            '<span title="%s">%s</span>',
            esc_attr( date_i18n( 'Y-m-d H:i:s', $timestamp ) ),
            human_time_diff( $timestamp, current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'core-forms' )
        );
    }

    public function column_default( $item, $column_name ) {
        return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
    }

    public function extra_tablenav( $which ) {
        if ( $which !== 'top' ) {
            return;
        }

        $forms = cf_get_forms();
        $current_form = isset( $_GET['form_id'] ) ? (int) $_GET['form_id'] : 0;
        $current_status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
        ?>
        <div class="alignleft actions">
            <select name="form_id">
                <option value=""><?php _e( 'All Forms', 'core-forms' ); ?></option>
                <?php foreach ( $forms as $form ) : ?>
                    <option value="<?php echo esc_attr( $form->ID ); ?>" <?php selected( $current_form, $form->ID ); ?>>
                        <?php echo esc_html( $form->title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value=""><?php _e( 'All Statuses', 'core-forms' ); ?></option>
                <option value="sent" <?php selected( $current_status, 'sent' ); ?>><?php _e( 'Sent', 'core-forms' ); ?></option>
                <option value="failed" <?php selected( $current_status, 'failed' ); ?>><?php _e( 'Failed', 'core-forms' ); ?></option>
                <option value="pending" <?php selected( $current_status, 'pending' ); ?>><?php _e( 'Pending', 'core-forms' ); ?></option>
            </select>
            <?php submit_button( __( 'Filter', 'core-forms' ), '', 'filter_action', false ); ?>
        </div>
        <?php
    }

    public function no_items() {
        _e( 'No email logs found.', 'core-forms' );
    }
}
