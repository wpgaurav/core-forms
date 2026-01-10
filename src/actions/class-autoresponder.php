<?php

namespace Core_Forms\Actions;

use Core_Forms\Form;
use Core_Forms\Submission;

class AutoResponder extends Action {

    public $type  = 'autoresponder';
    public $label = 'Auto-Responder';

    public function __construct() {
        $this->label = __( 'Auto-Responder', 'core-forms' );
    }

    private function get_default_settings() {
        return array(
            'email_field'  => '',
            'from_name'    => get_bloginfo( 'name' ),
            'from_email'   => get_option( 'admin_email' ),
            'reply_to'     => get_option( 'admin_email' ),
            'subject'      => __( 'Thank you for your submission', 'core-forms' ),
            'message'      => $this->get_default_message(),
            'content_type' => 'text/html',
        );
    }

    private function get_default_message() {
        $message = __( 'Hi there,', 'core-forms' ) . "\n\n";
        $message .= __( 'Thank you for contacting us. We have received your submission and will get back to you soon.', 'core-forms' ) . "\n\n";
        $message .= __( 'Here is a copy of your submission:', 'core-forms' ) . "\n\n";
        $message .= "[all]\n\n";
        $message .= __( 'Best regards,', 'core-forms' ) . "\n";
        $message .= get_bloginfo( 'name' );
        return $message;
    }

    public function page_settings( $settings, $index ) {
        $settings = array_merge( $this->get_default_settings(), $settings );
        ?>
        <span class="cf-action-summary"><?php printf( __( 'Send confirmation to [%s]', 'core-forms' ), esc_html( $settings['email_field'] ?: 'email field' ) ); ?></span>
        <input type="hidden" name="form[settings][actions][<?php echo $index; ?>][type]" value="<?php echo $this->type; ?>" />

        <p class="description">
            <?php _e( 'Send an automatic confirmation email to the person who submitted the form.', 'core-forms' ); ?>
        </p>

        <table class="form-table">
            <tr>
                <th><label><?php _e( 'Recipient Email Field', 'core-forms' ); ?> <span class="cf-required">*</span></label></th>
                <td>
                    <input name="form[settings][actions][<?php echo $index; ?>][email_field]" value="<?php echo esc_attr( $settings['email_field'] ); ?>" type="text" class="regular-text" placeholder="email" required />
                    <p class="description"><?php _e( 'Enter the name of the form field that contains the recipient\'s email address (e.g., "email", "your_email").', 'core-forms' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label><?php _e( 'From Name', 'core-forms' ); ?></label></th>
                <td>
                    <input name="form[settings][actions][<?php echo $index; ?>][from_name]" value="<?php echo esc_attr( $settings['from_name'] ); ?>" type="text" class="regular-text" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" />
                </td>
            </tr>
            <tr>
                <th><label><?php _e( 'From Email', 'core-forms' ); ?> <span class="cf-required">*</span></label></th>
                <td>
                    <input name="form[settings][actions][<?php echo $index; ?>][from_email]" value="<?php echo esc_attr( $settings['from_email'] ); ?>" type="email" class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" required />
                </td>
            </tr>
            <tr>
                <th><label><?php _e( 'Reply-To Email', 'core-forms' ); ?></label></th>
                <td>
                    <input name="form[settings][actions][<?php echo $index; ?>][reply_to]" value="<?php echo esc_attr( $settings['reply_to'] ); ?>" type="email" class="regular-text" placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" />
                    <p class="description"><?php _e( 'When the recipient replies to this email, it will go to this address.', 'core-forms' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label><?php _e( 'Subject', 'core-forms' ); ?> <span class="cf-required">*</span></label></th>
                <td>
                    <input name="form[settings][actions][<?php echo $index; ?>][subject]" value="<?php echo esc_attr( $settings['subject'] ); ?>" type="text" class="widefat" placeholder="<?php esc_attr_e( 'Thank you for your submission', 'core-forms' ); ?>" required />
                </td>
            </tr>
            <tr>
                <th><label><?php _e( 'Message', 'core-forms' ); ?> <span class="cf-required">*</span></label></th>
                <td>
                    <textarea name="form[settings][actions][<?php echo $index; ?>][message]" rows="10" class="widefat" required><?php echo esc_textarea( $settings['message'] ); ?></textarea>
                    <p class="help">
                        <?php _e( 'Available variables:', 'core-forms' ); ?><br />
                        <span class="cf-field-names"></span>
                        <code>[all]</code> <code>[all:label]</code> <code>[CF_FORM_ID]</code> <code>[CF_TIMESTAMP]</code> <code>[CF_IP_ADDRESS]</code>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label><?php _e( 'Content Type', 'core-forms' ); ?></label></th>
                <td>
                    <select name="form[settings][actions][<?php echo $index; ?>][content_type]">
                        <option value="text/html" <?php selected( $settings['content_type'], 'text/html' ); ?>><?php _e( 'HTML', 'core-forms' ); ?></option>
                        <option value="text/plain" <?php selected( $settings['content_type'], 'text/plain' ); ?>><?php _e( 'Plain Text', 'core-forms' ); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    public function process( array $settings, Submission $submission, Form $form ) {
        $settings = array_merge( $this->get_default_settings(), $settings );

        if ( empty( $settings['email_field'] ) || empty( $settings['message'] ) ) {
            return false;
        }

        $data = $submission->data;
        $email_field = $settings['email_field'];

        if ( empty( $data[ $email_field ] ) || ! is_email( $data[ $email_field ] ) ) {
            return false;
        }

        $to = sanitize_email( $data[ $email_field ] );
        $to = apply_filters( 'cf_action_autoresponder_to', $to, $submission );

        $html_email = $settings['content_type'] === 'text/html';

        $subject = cf_replace_data_variables( $settings['subject'], $submission, 'strip_tags' );
        $subject = apply_filters( 'cf_action_autoresponder_subject', $subject, $submission );

        $message = cf_replace_data_variables( $settings['message'], $submission, $html_email ? 'esc_html' : null );
        if ( ! $html_email ) {
            $message = str_replace( '<br />', "\n", $message );
        }
        $message = apply_filters( 'cf_action_autoresponder_message', $message, $submission );

        $headers = array();

        $content_type = $html_email ? 'text/html' : 'text/plain';
        $charset = get_bloginfo( 'charset' );
        $headers[] = sprintf( 'Content-Type: %s; charset=%s', $content_type, $charset );

        $from_name = ! empty( $settings['from_name'] ) ? $settings['from_name'] : get_bloginfo( 'name' );
        $from_email = ! empty( $settings['from_email'] ) ? $settings['from_email'] : get_option( 'admin_email' );
        $headers[] = sprintf( 'From: %s <%s>', $from_name, $from_email );

        if ( ! empty( $settings['reply_to'] ) ) {
            $headers[] = sprintf( 'Reply-To: %s', $settings['reply_to'] );
        }

        $headers = apply_filters( 'cf_action_autoresponder_headers', $headers, $submission );

        // Log the email attempt
        $log_id = cf_log_email( array(
            'form_id'       => $form->ID,
            'submission_id' => $submission->id,
            'to_email'      => $to,
            'from_email'    => $from_email,
            'subject'       => $subject,
            'message'       => $message,
            'headers'       => $headers,
            'status'        => 'pending',
            'action_type'   => 'autoresponder',
        ) );

        // Send the email
        $result = wp_mail( $to, $subject, $message, $headers );

        // Update log with result
        if ( $log_id ) {
            if ( $result ) {
                cf_update_email_log_status( $log_id, 'sent' );
            } else {
                global $phpmailer;
                $error_message = '';
                if ( isset( $phpmailer ) && $phpmailer instanceof \PHPMailer\PHPMailer\PHPMailer ) {
                    $error_message = $phpmailer->ErrorInfo;
                }
                cf_update_email_log_status( $log_id, 'failed', $error_message ?: __( 'Unknown error', 'core-forms' ) );
            }
        }

        return $result;
    }
}
