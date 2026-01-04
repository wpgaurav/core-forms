<?php

namespace Core_Forms\Actions;

use Core_Forms\Form;
use Core_Forms\Submission;

class Email extends Action {

    public $type  = 'email';
    public $label = 'Send Email';

    public function __construct() {
        $this->label = __( 'Send Email', 'core-forms' );
    }

    /**
    * @return array
    */
    private function get_default_settings() {
        return array(
            'from'         => get_option( 'admin_email' ),
            'to'           => get_option( 'admin_email' ),
            'subject'      => '',
            'message'      => '',
            'headers'      => '',
            'content_type' => 'text/html',
        );
    }

    /**
    * @param array $settings
    * @param string|int $index
    */
    public function page_settings( $settings, $index ) {
        $settings = array_merge( $this->get_default_settings(), $settings );
        ?>
       <span class="cf-action-summary"><?php printf( 'From %s. To %s.', esc_html($settings['from']), esc_html($settings['to']) ); ?></span>
       <input type="hidden" name="form[settings][actions][<?php echo $index; ?>][type]" value="<?php echo $this->type; ?>" />

       <p class="description">
       <?php _e( 'Send out an email notification whenever this form is successfully submitted.', 'core-forms' ); ?>
       <a target="_blank" tabindex="-1" class="core-forms-help" href="https://developer.developer/core-forms/sending-email-notifications/"><span class="dashicons dashicons-editor-help"></span></a>
       </p>

       <table class="form-table">
           <tr>
               <th><label><?php echo __( 'From', 'core-forms' ); ?> <span class="cf-required">*</span></label></th>
               <td>
                   <input name="form[settings][actions][<?php echo $index; ?>][from]" value="<?php echo esc_attr( $settings['from'] ); ?>" type="text" class="regular-text" placeholder="jane@email.com" required />
               </td>
           </tr>
           <tr>
               <th><label><?php echo __( 'To', 'core-forms' ); ?> <span class="cf-required">*</span></label></th>
               <td>
                   <input name="form[settings][actions][<?php echo $index; ?>][to]" value="<?php echo esc_attr( $settings['to'] ); ?>" type="text" class="regular-text" placeholder="john@email.com" required />
               </td>
           </tr>
           <tr>
               <th><label><?php echo __( 'Subject', 'core-forms' ); ?></label></th>
               <td>
                   <input name="form[settings][actions][<?php echo $index; ?>][subject]" value="<?php echo esc_attr( $settings['subject'] ); ?>" type="text" class="regular-text" placeholder="<?php echo esc_attr( __( 'Your email subject', 'core-forms' ) ); ?>" />
               </td>
           </tr>

           <tr>
               <th><label><?php echo __( 'Message', 'core-forms' ); ?> <span class="cf-required">*</span></label></th>
               <td>
                   <textarea name="form[settings][actions][<?php echo $index; ?>][message]" rows="8" class="widefat" placeholder="<?php echo esc_attr( __( 'Your email message', 'core-forms' ) ); ?>" required><?php echo esc_textarea( $settings['message'] ); ?></textarea>
                   <p class="help"><?php _e( 'You can use the following variables (in all fields): ', 'core-forms' ); ?><br /><span class="cf-field-names"></span></p>
               </td>
           </tr>

           <tr>
               <th><label><?php echo __( 'Content Type', 'core-forms' ); ?></label></th>
               <td>
                   <select name="form[settings][actions][<?php echo $index; ?>][content_type]" required>
                      <option <?php selected( $settings['content_type'], 'text/plain' ); ?>>text/plain</option>
                      <option <?php selected( $settings['content_type'], 'text/html' ); ?>>text/html</option>
                   </select>
               </td>
           </tr>

           <tr>
               <th><label><?php echo __( 'Additional Headers', 'core-forms' ); ?></label></th>
               <td>
                   <textarea name="form[settings][actions][<?php echo $index; ?>][headers]" rows="4" class="widefat" placeholder="<?php echo esc_attr( 'Reply-To: [NAME] <[EMAIL]>' ); ?>"><?php echo esc_textarea( $settings['headers'] ); ?></textarea>
               </td>
           </tr>
       </table>
        <?php
    }

    /**
     * Processes this action
     *
     * @param array $settings
     * @param Submission $submission
     * @param Form $form
     */
    public function process( array $settings, Submission $submission, Form $form ) {
        if ( empty( $settings['to'] ) || empty( $settings['message'] ) ) {
            return false;
        }

        $settings   = array_merge( $this->get_default_settings(), $settings );
        $html_email = $settings['content_type'] === 'text/html';

        $to = cf_replace_data_variables( $settings['to'], $submission, 'strip_tags' );
        $to = apply_filters( 'cf_action_email_to', $to, $submission );

        $subject = ! empty( $settings['subject'] ) ? cf_replace_data_variables( $settings['subject'], $submission, 'strip_tags' ) : '';
        $subject = apply_filters( 'cf_action_email_subject', $subject, $submission );

        $message = cf_replace_data_variables( $settings['message'], $submission, $html_email ? 'esc_html' : null );
        $message = ! $html_email ? str_replace( '<br />', '', $message ) : $message;
        $message = apply_filters( 'cf_action_email_message', $message, $submission );

        // parse additional email headers from settings
        $headers = array();
        if ( ! empty( $settings['headers'] ) ) {
            $headers = explode( PHP_EOL, cf_replace_data_variables( $settings['headers'], $submission, 'strip_tags' ) );
        }

        $content_type = $html_email ? 'text/html' : 'text/plain';
        $charset      = get_bloginfo( 'charset' );
        $headers[]    = sprintf( 'Content-Type: %s; charset=%s', $content_type, $charset );

        if ( ! empty( $settings['from'] ) ) {
            $from      = cf_replace_data_variables( $settings['from'], $submission, 'strip_tags' );
            $from      = apply_filters( 'cf_action_email_from', $from, $submission );
            $headers[] = sprintf( 'From: %s', $from );
        }

        return wp_mail( $to, $subject, $message, $headers );
    }
}
