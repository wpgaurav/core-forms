<?php

namespace Core_Forms\Admin;

/**
 * Math Captcha
 *
 * Simple math-based spam protection that asks users to solve a basic arithmetic problem.
 * Less intrusive than reCAPTCHA and doesn't require external services.
 */
class MathCaptcha {

    /**
     * Hook into WordPress
     */
    public function hook() {
        add_filter( 'cf_ignored_field_names', array( $this, 'ignored_fields' ) );
        add_filter( 'cf_validate_form', array( $this, 'validate_math_captcha' ), 15, 3 );
        add_filter( 'cf_form_markup', array( $this, 'add_math_captcha_to_form' ), 20, 2 );
        add_action( 'cf_admin_output_form_messages', array( $this, 'output_math_captcha_message_fields' ) );
        add_action( 'cf_admin_output_form_settings', array( $this, 'output_math_captcha_setting' ) );
    }

    /**
     * Add math captcha fields to ignored fields list
     *
     * @param array $ignored_fields
     * @return array
     */
    public function ignored_fields( $ignored_fields ) {
        $ignored_fields[] = 'cf_math_captcha_answer';
        $ignored_fields[] = 'cf_math_captcha_hash';
        return $ignored_fields;
    }

    /**
     * Check if math captcha is enabled for this form
     *
     * @param \Core_Forms\Form $form
     * @return bool
     */
    private function is_enabled( $form ) {
        return ! empty( $form->settings['enable_math_captcha'] );
    }

    /**
     * Generate a simple math problem
     *
     * @return array
     */
    private function generate_math_problem() {
        $operators = array( '+', '-', '*' );
        $operator = $operators[ array_rand( $operators ) ];

        switch ( $operator ) {
            case '+':
                $num1 = wp_rand( 1, 20 );
                $num2 = wp_rand( 1, 20 );
                $answer = $num1 + $num2;
                break;
            case '-':
                $num1 = wp_rand( 10, 30 );
                $num2 = wp_rand( 1, $num1 - 1 );
                $answer = $num1 - $num2;
                break;
            case '*':
                $num1 = wp_rand( 2, 10 );
                $num2 = wp_rand( 2, 10 );
                $answer = $num1 * $num2;
                break;
        }

        $question = sprintf( '%d %s %d', $num1, $operator, $num2 );

        return array(
            'question' => $question,
            'answer'   => $answer,
        );
    }

    /**
     * Create a hash for the answer
     *
     * @param int $answer
     * @return string
     */
    private function hash_answer( $answer ) {
        return wp_hash( $answer . wp_salt() );
    }

    /**
     * Add math captcha field to form markup
     *
     * @param string $markup
     * @param \Core_Forms\Form $form
     * @return string
     */
    public function add_math_captcha_to_form( $markup, $form ) {
        if ( ! $this->is_enabled( $form ) ) {
            return $markup;
        }

        $math = $this->generate_math_problem();
        $hash = $this->hash_answer( $math['answer'] );

        $captcha_html = sprintf(
            '<div class="cf-math-captcha" role="group" aria-labelledby="cf_math_captcha_label_%d">
                <label id="cf_math_captcha_label_%d" for="cf_math_captcha_answer_%d">
                    %s <strong>%s</strong> = ?
                </label>
                <input type="number" id="cf_math_captcha_answer_%d" name="cf_math_captcha_answer" required aria-required="true" aria-describedby="cf_math_captcha_desc_%d" inputmode="numeric" />
                <span id="cf_math_captcha_desc_%d" class="cf-field-description" style="display:block;font-size:0.9em;margin-top:0.25em;">%s</span>
                <input type="hidden" name="cf_math_captcha_hash" value="%s" />
            </div>',
            $form->ID,
            $form->ID,
            $form->ID,
            esc_html__( 'Security question:', 'core-forms' ),
            esc_html( $math['question'] ),
            $form->ID,
            $form->ID,
            $form->ID,
            esc_html__( 'This question helps us prevent spam. Please solve the math problem above.', 'core-forms' ),
            esc_attr( $hash )
        );

        // Try to add before submit button, otherwise append
        if ( stripos( $markup, '<button' ) !== false || stripos( $markup, 'type="submit"' ) !== false ) {
            // Find last occurrence of button or submit input
            $pattern = '/(<button[^>]*>|<input[^>]*type=["\']submit["\'][^>]*>)/i';
            $markup = preg_replace( $pattern, $captcha_html . "\n$1", $markup, 1 );
        } else {
            $markup .= "\n" . $captcha_html;
        }

        return $markup;
    }

    /**
     * Validate math captcha answer
     *
     * @param string $error_code
     * @param \Core_Forms\Form $form
     * @param array $data
     * @return string
     */
    public function validate_math_captcha( $error_code, $form, $data ) {
        // If there's already an error, don't proceed
        if ( ! empty( $error_code ) ) {
            return $error_code;
        }

        // Only validate if math captcha is enabled
        if ( ! $this->is_enabled( $form ) ) {
            return $error_code;
        }

        // Skip for logged-in users with edit capability
        if ( current_user_can( 'edit_posts' ) ) {
            return $error_code;
        }

        // Check if answer and hash are present
        if ( empty( $data['cf_math_captcha_answer'] ) || empty( $data['cf_math_captcha_hash'] ) ) {
            $this->log_debug( 'Math captcha answer or hash missing', $form );
            return 'math_captcha_failed';
        }

        $answer = (int) $data['cf_math_captcha_answer'];
        $hash = $data['cf_math_captcha_hash'];

        // Verify the answer
        $expected_hash = $this->hash_answer( $answer );

        if ( $hash !== $expected_hash ) {
            $this->log_debug( sprintf( 'Math captcha incorrect: user answered %d', $answer ), $form );
            return 'math_captcha_failed';
        }

        return $error_code;
    }

    /**
     * Log debug information
     *
     * @param string $message
     * @param \Core_Forms\Form $form
     */
    private function log_debug( $message, $form = null ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $log_message = '[Core Forms Math Captcha] ' . $message;
            if ( $form ) {
                $log_message .= sprintf( ' (Form: "%s", ID: %d)', $form->title, $form->ID );
            }
            error_log( $log_message );
        }
    }

    /**
     * Output math captcha message fields in the Messages tab
     *
     * @param \Core_Forms\Form $form
     */
    public function output_math_captcha_message_fields( $form ) {
        $math_captcha_message = isset( $form->messages['math_captcha_failed'] ) ? $form->messages['math_captcha_failed'] : __( 'Incorrect answer to the math problem. Please try again.', 'core-forms' );
        ?>
        <tr valign="top">
            <th scope="row" colspan="2" class="cf-settings-header"><?php echo __( 'Math Captcha', 'core-forms' ); ?></th>
        </tr>

        <tr valign="top">
            <th scope="row"><label for="cf_form_math_captcha_failed"><?php _e( 'Incorrect Answer', 'core-forms' ); ?></label></th>
            <td>
                <input type="text" class="widefat" id="cf_form_math_captcha_failed" name="form[messages][math_captcha_failed]" value="<?php echo esc_attr( $math_captcha_message ); ?>" />
                <p class="description"><?php _e( 'The text shown when the user provides an incorrect answer to the math problem.', 'core-forms' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Output math captcha setting in the Settings tab
     *
     * @param \Core_Forms\Form $form
     */
    public function output_math_captcha_setting( $form ) {
        $enabled = $this->is_enabled( $form );
        ?>
        <tr valign="top">
            <th scope="row" colspan="2" class="cf-settings-header"><?php echo __( 'Spam Protection', 'core-forms' ); ?></th>
        </tr>

        <tr valign="top">
            <th scope="row"><label for="cf_enable_math_captcha"><?php _e( 'Math Captcha', 'core-forms' ); ?></label></th>
            <td>
                <label>
                    <input type="checkbox" id="cf_enable_math_captcha" name="form[settings][enable_math_captcha]" value="1" <?php checked( $enabled ); ?> />
                    <?php _e( 'Enable simple math captcha to prevent spam', 'core-forms' ); ?>
                </label>
                <p class="description"><?php _e( 'Adds a simple math problem that users must solve before submitting. Effective against basic spam bots without requiring external services.', 'core-forms' ); ?></p>
            </td>
        </tr>
        <?php
    }
}
