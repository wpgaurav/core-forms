<?php

namespace Core_Forms\Admin;

use Core_Forms\Reply;

class SubmissionReply {

    private $settings;

    public function __construct() {
        $this->settings = cf_get_settings();
    }

    public function hook() {
        add_action( 'admin_action_cf_send_reply', array( $this, 'handle_send_reply' ) );
        add_action( 'cf_admin_output_settings', array( $this, 'output_global_settings' ) );
    }

    public function handle_send_reply() {
        if ( ! current_user_can( 'edit_forms' ) ) {
            wp_die( __( 'You do not have permission to send replies.', 'core-forms' ) );
        }

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'cf_send_reply' ) ) {
            wp_die( __( 'Security check failed.', 'core-forms' ) );
        }

        $submission_id = isset( $_POST['submission_id'] ) ? (int) $_POST['submission_id'] : 0;
        $form_id = isset( $_POST['form_id'] ) ? (int) $_POST['form_id'] : 0;

        if ( ! $submission_id || ! $form_id ) {
            wp_die( __( 'Invalid submission.', 'core-forms' ) );
        }

        $to_email = isset( $_POST['reply_to'] ) ? sanitize_email( $_POST['reply_to'] ) : '';
        $from_email = isset( $_POST['reply_from'] ) ? sanitize_email( $_POST['reply_from'] ) : '';
        $subject = isset( $_POST['reply_subject'] ) ? sanitize_text_field( $_POST['reply_subject'] ) : '';
        $message = isset( $_POST['reply_message'] ) ? wp_kses_post( $_POST['reply_message'] ) : '';

        if ( ! is_email( $to_email ) ) {
            wp_die( __( 'Invalid recipient email address.', 'core-forms' ) );
        }

        if ( ! is_email( $from_email ) ) {
            $from_email = get_option( 'admin_email' );
        }

        if ( empty( $subject ) || empty( $message ) ) {
            wp_die( __( 'Subject and message are required.', 'core-forms' ) );
        }

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo( 'name' ) . ' <' . $from_email . '>',
            'Reply-To: ' . $from_email,
        );

        $html_message = wpautop( $message );
        $sent = wp_mail( $to_email, $subject, $html_message, $headers );

        if ( $sent ) {
            $this->save_reply( $submission_id, $from_email, $to_email, $subject, $message );
        }

        $redirect_url = admin_url( sprintf(
            'admin.php?page=core-forms&view=edit&form_id=%d&tab=submissions&view_submission=%d',
            $form_id,
            $submission_id
        ) );

        if ( $sent ) {
            $redirect_url = add_query_arg( 'reply_sent', '1', $redirect_url );
        } else {
            $redirect_url = add_query_arg( 'reply_error', '1', $redirect_url );
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    private function save_reply( $submission_id, $from_email, $to_email, $subject, $message ) {
        global $wpdb;
        $table = $wpdb->prefix . 'cf_submission_replies';

        $wpdb->insert(
            $table,
            array(
                'submission_id' => $submission_id,
                'from_email'    => $from_email,
                'to_email'      => $to_email,
                'subject'       => $subject,
                'message'       => $message,
                'user_id'       => get_current_user_id(),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%d' )
        );

        return $wpdb->insert_id;
    }

    public function output_global_settings() {
        $reply_from_email = isset( $this->settings['reply_from_email'] ) ? $this->settings['reply_from_email'] : '';
        ?>
        <h2 class="title"><?php _e( 'Reply Settings', 'core-forms' ); ?></h2>

        <p class="description">
            <?php _e( 'Configure default settings for replying to form submissions.', 'core-forms' ); ?>
        </p>

        <table class="form-table" role="presentation">
            <tbody>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Default Sender Email', 'core-forms' ); ?></th>
                    <td>
                        <input type="email" class="regular-text" name="cf_settings[reply_from_email]" value="<?php echo esc_attr( $reply_from_email ); ?>" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
                        <p class="description"><?php _e( 'The default email address to use when sending replies. Leave blank to use the site admin email.', 'core-forms' ); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    public static function get_recipient_email_from_submission( $submission ) {
        $email_fields = array( 'email', 'e-mail', 'your-email', 'user_email', 'contact_email', 'mail' );

        foreach ( $submission->data as $key => $value ) {
            $key_lower = strtolower( $key );
            if ( in_array( $key_lower, $email_fields, true ) || strpos( $key_lower, 'email' ) !== false ) {
                if ( is_email( $value ) ) {
                    return $value;
                }
            }
        }

        foreach ( $submission->data as $key => $value ) {
            if ( is_email( $value ) ) {
                return $value;
            }
        }

        return '';
    }

    public static function render_reply_form( $submission, $form ) {
        $settings = cf_get_settings();
        $default_from = ! empty( $settings['reply_from_email'] ) ? $settings['reply_from_email'] : get_option( 'admin_email' );
        $to_email = self::get_recipient_email_from_submission( $submission );
        ?>
        <div class="cf-reply-section">
            <h3><?php _e( 'Send Reply', 'core-forms' ); ?></h3>

            <?php if ( empty( $to_email ) ) : ?>
                <div class="notice notice-warning inline">
                    <p><?php _e( 'No email address found in the submission data. Please enter the recipient email manually.', 'core-forms' ); ?></p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo admin_url( 'admin.php?action=cf_send_reply' ); ?>" class="cf-reply-form">
                <?php wp_nonce_field( 'cf_send_reply' ); ?>
                <input type="hidden" name="submission_id" value="<?php echo esc_attr( $submission->id ); ?>" />
                <input type="hidden" name="form_id" value="<?php echo esc_attr( $form->ID ); ?>" />

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="reply_from"><?php _e( 'From', 'core-forms' ); ?></label></th>
                        <td>
                            <input type="email" name="reply_from" id="reply_from" class="regular-text" value="<?php echo esc_attr( $default_from ); ?>" required />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="reply_to"><?php _e( 'To', 'core-forms' ); ?></label></th>
                        <td>
                            <input type="email" name="reply_to" id="reply_to" class="regular-text" value="<?php echo esc_attr( $to_email ); ?>" required />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="reply_subject"><?php _e( 'Subject', 'core-forms' ); ?></label></th>
                        <td>
                            <input type="text" name="reply_subject" id="reply_subject" class="large-text" value="<?php echo esc_attr( sprintf( __( 'Re: %s submission', 'core-forms' ), $form->title ) ); ?>" required />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="reply_message"><?php _e( 'Message', 'core-forms' ); ?></label></th>
                        <td>
                            <textarea name="reply_message" id="reply_message" class="large-text" rows="8" required></textarea>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-email" style="vertical-align: middle; margin-right: 4px;"></span>
                        <?php _e( 'Send Reply', 'core-forms' ); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    public static function render_replies_list( $submission_id ) {
        $replies = cf_get_submission_replies( $submission_id );

        if ( empty( $replies ) ) {
            return;
        }
        ?>
        <div class="cf-replies-section">
            <h3><?php _e( 'Reply History', 'core-forms' ); ?></h3>

            <div class="cf-reply-thread">
                <?php foreach ( $replies as $reply ) : ?>
                    <div class="cf-reply-item">
                        <div class="cf-reply-header">
                            <span class="cf-reply-from">
                                <strong><?php echo esc_html( $reply->get_user_display_name() ); ?></strong>
                                &lt;<?php echo esc_html( $reply->from_email ); ?>&gt;
                            </span>
                            <span class="cf-reply-to">
                                &rarr; <?php echo esc_html( $reply->to_email ); ?>
                            </span>
                            <span class="cf-reply-date">
                                <?php echo esc_html( human_time_diff( strtotime( $reply->sent_at ), current_time( 'timestamp' ) ) ); ?> <?php _e( 'ago', 'core-forms' ); ?>
                            </span>
                        </div>
                        <div class="cf-reply-subject">
                            <strong><?php _e( 'Subject:', 'core-forms' ); ?></strong> <?php echo esc_html( $reply->subject ); ?>
                        </div>
                        <div class="cf-reply-body">
                            <?php echo wpautop( esc_html( $reply->message ) ); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

}
