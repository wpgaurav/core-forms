<?php

namespace Core_Forms\Admin;

class SubmissionEditor {

    public function __construct() {
    }

    public function hook() {
        add_action( 'admin_action_cf_save_submission', array( $this, 'handle_save_submission' ) );
    }

    public function handle_save_submission() {
        if ( ! current_user_can( 'edit_forms' ) ) {
            wp_die( __( 'You do not have permission to edit submissions.', 'core-forms' ) );
        }

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'cf_save_submission' ) ) {
            wp_die( __( 'Security check failed.', 'core-forms' ) );
        }

        $submission_id = isset( $_POST['submission_id'] ) ? (int) $_POST['submission_id'] : 0;
        $form_id = isset( $_POST['form_id'] ) ? (int) $_POST['form_id'] : 0;

        if ( ! $submission_id || ! $form_id ) {
            wp_die( __( 'Invalid submission.', 'core-forms' ) );
        }

        $submission = cf_get_form_submission( $submission_id );
        if ( ! $submission || $submission->form_id !== $form_id ) {
            wp_die( __( 'Submission not found.', 'core-forms' ) );
        }

        $new_data = isset( $_POST['submission_data'] ) ? $_POST['submission_data'] : array();
        $sanitized_data = array();

        foreach ( $new_data as $key => $value ) {
            $key = sanitize_text_field( $key );
            if ( is_array( $value ) ) {
                $sanitized_data[ $key ] = array_map( 'sanitize_textarea_field', $value );
            } else {
                $sanitized_data[ $key ] = sanitize_textarea_field( $value );
            }
        }

        global $wpdb;
        $table = $wpdb->prefix . 'cf_submissions';

        $result = $wpdb->update(
            $table,
            array(
                'data'        => wp_json_encode( $sanitized_data ),
                'modified_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $submission_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        $redirect_url = admin_url( sprintf(
            'admin.php?page=core-forms&view=edit&form_id=%d&tab=submissions&view_submission=%d',
            $form_id,
            $submission_id
        ) );

        if ( $result !== false ) {
            $redirect_url = add_query_arg( 'updated', '1', $redirect_url );
        } else {
            $redirect_url = add_query_arg( 'error', '1', $redirect_url );
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    public static function render_edit_form( $submission, $form ) {
        ?>
        <div class="cf-submission-edit-form">
            <form method="post" action="<?php echo admin_url( 'admin.php?action=cf_save_submission' ); ?>">
                <?php wp_nonce_field( 'cf_save_submission' ); ?>
                <input type="hidden" name="submission_id" value="<?php echo esc_attr( $submission->id ); ?>" />
                <input type="hidden" name="form_id" value="<?php echo esc_attr( $form->ID ); ?>" />

                <table class="form-table cf-edit-table">
                    <tbody>
                        <?php foreach ( $submission->data as $key => $value ) : ?>
                            <tr>
                                <th scope="row">
                                    <label for="submission_data_<?php echo esc_attr( $key ); ?>">
                                        <?php echo esc_html( $key ); ?>
                                    </label>
                                </th>
                                <td>
                                    <?php if ( is_array( $value ) ) : ?>
                                        <?php if ( cf_is_file( $value ) ) : ?>
                                            <p class="description">
                                                <?php _e( 'File attachments cannot be edited.', 'core-forms' ); ?>
                                                <?php echo cf_field_value( $value ); ?>
                                            </p>
                                            <input type="hidden" name="submission_data[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( wp_json_encode( $value ) ); ?>" />
                                        <?php else : ?>
                                            <textarea
                                                name="submission_data[<?php echo esc_attr( $key ); ?>]"
                                                id="submission_data_<?php echo esc_attr( $key ); ?>"
                                                class="large-text"
                                                rows="3"
                                            ><?php echo esc_textarea( implode( "\n", $value ) ); ?></textarea>
                                            <p class="description"><?php _e( 'Multiple values are separated by new lines.', 'core-forms' ); ?></p>
                                        <?php endif; ?>
                                    <?php elseif ( strlen( $value ) > 100 ) : ?>
                                        <textarea
                                            name="submission_data[<?php echo esc_attr( $key ); ?>]"
                                            id="submission_data_<?php echo esc_attr( $key ); ?>"
                                            class="large-text"
                                            rows="5"
                                        ><?php echo esc_textarea( $value ); ?></textarea>
                                    <?php else : ?>
                                        <input
                                            type="text"
                                            name="submission_data[<?php echo esc_attr( $key ); ?>]"
                                            id="submission_data_<?php echo esc_attr( $key ); ?>"
                                            class="regular-text"
                                            value="<?php echo esc_attr( $value ); ?>"
                                        />
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php _e( 'Save Changes', 'core-forms' ); ?>
                    </button>
                    <a href="<?php echo esc_url( remove_query_arg( 'edit' ) ); ?>" class="button">
                        <?php _e( 'Cancel', 'core-forms' ); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

}
