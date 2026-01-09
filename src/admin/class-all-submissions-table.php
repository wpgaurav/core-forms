<?php

namespace Core_Forms\Admin;

use Core_Forms\Submission;
use WP_List_Table;

if ( class_exists( 'WP_List_Table' ) ) {

    class AllSubmissionsTable extends WP_List_Table {

        private $forms_cache = array();

        public function __construct() {
            parent::__construct(
                array(
                    'singular' => 'submission',
                    'plural'   => 'submissions',
                    'ajax'     => false,
                )
            );
        }

        public function prepare_items() {
            $this->process_bulk_action();

            $columns = $this->get_columns();
            $sortable = $this->get_sortable_columns();
            $hidden = array();
            $this->_column_headers = array( $columns, $hidden, $sortable );

            $per_page = 50;
            $current_page = $this->get_pagenum();
            $offset = ( $current_page - 1 ) * $per_page;

            $args = $this->get_query_args();
            $args['offset'] = $offset;
            $args['limit'] = $per_page;

            $this->items = cf_get_all_submissions( $args );

            $count_args = $this->get_query_args();
            $total_items = cf_count_all_submissions( $count_args );

            $this->set_pagination_args(
                array(
                    'per_page'    => $per_page,
                    'total_items' => $total_items,
                    'total_pages' => ceil( $total_items / $per_page ),
                )
            );
        }

        private function get_query_args() {
            $args = array();

            if ( ! empty( $_GET['form_id'] ) ) {
                $args['form_id'] = (int) $_GET['form_id'];
            }

            if ( ! empty( $_GET['s'] ) ) {
                $args['search'] = sanitize_text_field( $_GET['s'] );
            }

            if ( isset( $_GET['spam_status'] ) && $_GET['spam_status'] !== '' ) {
                $args['is_spam'] = $_GET['spam_status'] === '1';
            }

            if ( ! empty( $_GET['date_from'] ) ) {
                $args['date_from'] = sanitize_text_field( $_GET['date_from'] );
            }

            if ( ! empty( $_GET['date_to'] ) ) {
                $args['date_to'] = sanitize_text_field( $_GET['date_to'] );
            }

            if ( ! empty( $_GET['orderby'] ) ) {
                $args['orderby'] = sanitize_text_field( $_GET['orderby'] );
            }

            if ( ! empty( $_GET['order'] ) ) {
                $args['order'] = sanitize_text_field( $_GET['order'] );
            }

            return $args;
        }

        public function get_views() {
            $current = isset( $_GET['spam_status'] ) ? $_GET['spam_status'] : '';
            $all_count = cf_count_all_submissions();
            $spam_count = cf_count_all_submissions( array( 'is_spam' => true ) );
            $non_spam_count = cf_count_all_submissions( array( 'is_spam' => false ) );

            $base_url = admin_url( 'admin.php?page=core-forms-submissions' );

            return array(
                ''  => sprintf( '<a href="%s" class="%s">%s</a> (%d)', esc_url( $base_url ), $current === '' ? 'current' : '', __( 'All', 'core-forms' ), $all_count ),
                '0' => sprintf( '<a href="%s" class="%s">%s</a> (%d)', esc_url( add_query_arg( 'spam_status', '0', $base_url ) ), $current === '0' ? 'current' : '', __( 'Valid', 'core-forms' ), $non_spam_count ),
                '1' => sprintf( '<a href="%s" class="%s">%s</a> (%d)', esc_url( add_query_arg( 'spam_status', '1', $base_url ) ), $current === '1' ? 'current' : '', __( 'Spam', 'core-forms' ), $spam_count ),
            );
        }

        public function get_bulk_actions() {
            return array(
                'bulk_delete'      => __( 'Delete Permanently', 'core-forms' ),
                'bulk_mark_spam'   => __( 'Mark as Spam', 'core-forms' ),
                'bulk_unmark_spam' => __( 'Not Spam', 'core-forms' ),
                'bulk_export_csv'  => __( 'Export to CSV', 'core-forms' ),
            );
        }

        public function get_columns() {
            return array(
                'cb'           => '<input type="checkbox" />',
                'form'         => __( 'Form', 'core-forms' ),
                'data_preview' => __( 'Data', 'core-forms' ),
                'submitted_at' => __( 'Date', 'core-forms' ),
                'status'       => __( 'Status', 'core-forms' ),
            );
        }

        public function get_sortable_columns() {
            return array(
                'submitted_at' => array( 'submitted_at', true ),
                'form'         => array( 'form_id', false ),
            );
        }

        public function get_table_classes() {
            return array( 'widefat', 'fixed', 'striped', 'cf-submissions-table' );
        }

        public function column_cb( $submission ) {
            return sprintf( '<input type="checkbox" name="submissions[]" value="%d" />', $submission->id );
        }

        public function column_form( Submission $submission ) {
            if ( ! isset( $this->forms_cache[ $submission->form_id ] ) ) {
                try {
                    $this->forms_cache[ $submission->form_id ] = cf_get_form( $submission->form_id );
                } catch ( \Exception $e ) {
                    $this->forms_cache[ $submission->form_id ] = null;
                }
            }

            $form = $this->forms_cache[ $submission->form_id ];
            if ( ! $form ) {
                return sprintf( '<em>%s</em>', __( 'Deleted form', 'core-forms' ) );
            }

            $form_link = admin_url( 'admin.php?page=core-forms&view=edit&form_id=' . $form->ID . '&tab=submissions' );
            return sprintf( '<a href="%s">%s</a>', esc_url( $form_link ), esc_html( $form->title ) );
        }

        public function column_data_preview( Submission $submission ) {
            $preview = '';
            $count = 0;
            foreach ( $submission->data as $key => $value ) {
                if ( $count >= 2 ) {
                    $preview .= '...';
                    break;
                }
                if ( is_array( $value ) ) {
                    $value = implode( ', ', $value );
                }
                $value = wp_trim_words( $value, 8, '...' );
                $preview .= sprintf( '<strong>%s:</strong> %s<br>', esc_html( $key ), esc_html( $value ) );
                $count++;
            }

            $detail_link = admin_url( 'admin.php?page=core-forms&view=edit&form_id=' . $submission->form_id . '&tab=submissions&view_submission=' . $submission->id );
            $preview .= sprintf( '<a href="%s" class="cf-view-submission">%s &rarr;</a>', esc_url( $detail_link ), __( 'View', 'core-forms' ) );

            return $preview;
        }

        public function column_submitted_at( Submission $submission ) {
            $date_format = get_option( 'date_format' );
            $time_format = get_option( 'time_format' );
            return sprintf(
                '<span title="%s">%s</span>',
                esc_attr( gmdate( $date_format . ' ' . $time_format, strtotime( $submission->submitted_at ) ) ),
                esc_html( human_time_diff( strtotime( $submission->submitted_at ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'core-forms' ) )
            );
        }

        public function column_status( Submission $submission ) {
            if ( $submission->is_spam ) {
                return '<span class="cf-status-badge cf-status-spam">' . __( 'Spam', 'core-forms' ) . '</span>';
            }
            return '<span class="cf-status-badge cf-status-valid">' . __( 'Valid', 'core-forms' ) . '</span>';
        }

        public function no_items() {
            _e( 'No submissions found.', 'core-forms' );
        }

        public function single_row( $submission ) {
            $class = $submission->is_spam ? 'cf-spam-row' : '';
            echo sprintf( '<tr id="cf-submission-%d" class="%s">', $submission->id, esc_attr( $class ) );
            $this->single_row_columns( $submission );
            echo '</tr>';
        }

        public function extra_tablenav( $which ) {
            if ( $which !== 'top' ) {
                return;
            }
            ?>
            <div class="alignleft actions cf-filters">
                <?php $this->forms_dropdown(); ?>
                <?php $this->date_filters(); ?>
                <?php submit_button( __( 'Filter', 'core-forms' ), '', 'filter_action', false ); ?>
            </div>
            <?php
        }

        private function forms_dropdown() {
            $forms = cf_get_forms();
            $selected = isset( $_GET['form_id'] ) ? (int) $_GET['form_id'] : 0;
            ?>
            <select name="form_id">
                <option value=""><?php _e( 'All Forms', 'core-forms' ); ?></option>
                <?php foreach ( $forms as $form ) : ?>
                    <option value="<?php echo esc_attr( $form->ID ); ?>" <?php selected( $selected, $form->ID ); ?>>
                        <?php echo esc_html( $form->title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
        }

        private function date_filters() {
            $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( $_GET['date_from'] ) : '';
            $date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( $_GET['date_to'] ) : '';
            ?>
            <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" placeholder="<?php esc_attr_e( 'From', 'core-forms' ); ?>" />
            <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" placeholder="<?php esc_attr_e( 'To', 'core-forms' ); ?>" />
            <?php
        }

        public function process_bulk_action() {
            $action = $this->current_action();
            if ( empty( $action ) ) {
                return false;
            }

            if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-' . $this->_args['plural'] ) ) {
                return false;
            }

            $submissions = isset( $_REQUEST['submissions'] ) ? array_map( 'intval', (array) $_REQUEST['submissions'] ) : array();
            if ( empty( $submissions ) ) {
                return false;
            }

            switch ( $action ) {
                case 'bulk_delete':
                    $this->process_bulk_delete( $submissions );
                    break;
                case 'bulk_mark_spam':
                    $this->process_bulk_spam( $submissions, true );
                    break;
                case 'bulk_unmark_spam':
                    $this->process_bulk_spam( $submissions, false );
                    break;
                case 'bulk_export_csv':
                    $this->process_bulk_export_csv( $submissions );
                    break;
            }

            return true;
        }

        private function process_bulk_delete( $submission_ids ) {
            global $wpdb;
            $table = $wpdb->prefix . 'cf_submissions';

            foreach ( $submission_ids as $id ) {
                $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
            }
        }

        private function process_bulk_spam( $submission_ids, $is_spam ) {
            global $wpdb;
            $table = $wpdb->prefix . 'cf_submissions';

            foreach ( $submission_ids as $id ) {
                $wpdb->update(
                    $table,
                    array( 'is_spam' => $is_spam ? 1 : 0 ),
                    array( 'id' => $id ),
                    array( '%d' ),
                    array( '%d' )
                );
            }
        }

        private function process_bulk_export_csv( $submission_ids ) {
            $submissions = array();
            foreach ( $submission_ids as $id ) {
                $submissions[] = cf_get_form_submission( $id );
            }

            if ( empty( $submissions ) ) {
                return;
            }

            $filename = 'core-forms-submissions-' . gmdate( 'Y-m-d-His' ) . '.csv';

            header( 'Content-Type: text/csv; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename=' . $filename );
            header( 'Pragma: no-cache' );
            header( 'Expires: 0' );

            $output = fopen( 'php://output', 'w' );

            $all_keys = array( 'id', 'form_id', 'submitted_at', 'is_spam', 'ip_address' );
            foreach ( $submissions as $submission ) {
                foreach ( array_keys( $submission->data ) as $key ) {
                    if ( ! in_array( $key, $all_keys, true ) ) {
                        $all_keys[] = $key;
                    }
                }
            }

            fputcsv( $output, $all_keys );

            foreach ( $submissions as $submission ) {
                $row = array(
                    $submission->id,
                    $submission->form_id,
                    $submission->submitted_at,
                    $submission->is_spam ? 'spam' : 'valid',
                    $submission->ip_address,
                );
                foreach ( array_slice( $all_keys, 5 ) as $key ) {
                    $value = isset( $submission->data[ $key ] ) ? $submission->data[ $key ] : '';
                    if ( is_array( $value ) ) {
                        $value = implode( ', ', $value );
                    }
                    $row[] = $value;
                }
                fputcsv( $output, $row );
            }

            fclose( $output );
            exit;
        }

    }

}
