<?php

namespace Core_Forms\Admin;

/**
 * Akismet Spam Filtering Integration
 *
 * Automatically checks form submissions against Akismet's spam detection service
 * when the Akismet plugin is active and configured.
 */
class Akismet {

    /**
     * Hook into WordPress
     */
    public function hook() {
        // Only proceed if Akismet is active and configured
        if ( ! $this->is_akismet_active() ) {
            return;
        }

        add_filter( 'cf_validate_form', array( $this, 'check_for_spam' ), 20, 3 );
        add_action( 'cf_admin_output_form_messages', array( $this, 'output_akismet_message_fields' ) );

        if ( is_admin() ) {
            add_action( 'admin_notices', array( $this, 'show_admin_notice' ) );
        }
    }

    /**
     * Check if Akismet is active and has a valid API key
     *
     * @return bool
     */
    private function is_akismet_active() {
        // Check if Akismet class exists
        if ( ! class_exists( 'Akismet' ) ) {
            return false;
        }

        // Check if API key is configured
        $api_key = \Akismet::get_api_key();
        if ( empty( $api_key ) ) {
            return false;
        }

        return true;
    }

    /**
     * Check form submission for spam using Akismet
     *
     * @param string $error_code Current error code
     * @param \Core_Forms\Form $form The form being submitted
     * @param array $data Form submission data
     * @return string Error code (empty string if not spam)
     */
    public function check_for_spam( $error_code, $form, $data ) {
        // If there's already an error, don't proceed
        if ( ! empty( $error_code ) ) {
            return $error_code;
        }

        // Skip spam check for logged-in users with edit capability
        if ( current_user_can( 'edit_posts' ) ) {
            return $error_code;
        }

        // Build comment data for Akismet
        $akismet_data = $this->build_akismet_data( $form, $data );

        // Check with Akismet
        $is_spam = $this->akismet_check( $akismet_data );

        if ( $is_spam ) {
            $this->log_debug( 'Submission flagged as spam by Akismet', $form );
            return 'spam';
        }

        return $error_code;
    }

    /**
     * Build the data array for Akismet API
     *
     * @param \Core_Forms\Form $form
     * @param array $data
     * @return array
     */
    private function build_akismet_data( $form, $data ) {
        $akismet_data = array(
            'blog'                 => get_option( 'home' ),
            'blog_lang'            => get_locale(),
            'blog_charset'         => get_option( 'blog_charset' ),
            'user_ip'              => $this->get_user_ip(),
            'user_agent'           => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '',
            'referrer'             => isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( $_SERVER['HTTP_REFERER'] ) : '',
            'permalink'            => isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( $_SERVER['HTTP_REFERER'] ) : get_site_url(),
            'comment_type'         => 'contact-form',
            'comment_author'       => '',
            'comment_author_email' => '',
            'comment_author_url'   => '',
            'comment_content'      => '',
        );

        // Try to find name, email, and message fields from form data
        foreach ( $data as $key => $value ) {
            if ( is_array( $value ) ) {
                $value = implode( ', ', $value );
            }

            $key_lower = strtolower( $key );

            // Detect name field
            if ( empty( $akismet_data['comment_author'] ) ) {
                if ( in_array( $key_lower, array( 'name', 'full_name', 'fullname', 'your_name', 'your-name', 'author', 'first_name', 'firstname' ), true ) ) {
                    $akismet_data['comment_author'] = $value;
                }
            }

            // Detect email field
            if ( empty( $akismet_data['comment_author_email'] ) ) {
                if ( in_array( $key_lower, array( 'email', 'e-mail', 'your_email', 'your-email', 'mail', 'email_address' ), true ) || is_email( $value ) ) {
                    $akismet_data['comment_author_email'] = $value;
                }
            }

            // Detect URL field
            if ( empty( $akismet_data['comment_author_url'] ) ) {
                if ( in_array( $key_lower, array( 'url', 'website', 'site', 'web', 'homepage' ), true ) ) {
                    $akismet_data['comment_author_url'] = $value;
                }
            }
        }

        // Build comment content from all form fields
        $content_parts = array();
        foreach ( $data as $key => $value ) {
            if ( is_array( $value ) ) {
                $value = implode( ', ', $value );
            }
            if ( ! empty( $value ) ) {
                $content_parts[] = sprintf( '%s: %s', $key, $value );
            }
        }
        $akismet_data['comment_content'] = implode( "\n", $content_parts );

        // Allow filtering of Akismet data
        $akismet_data = apply_filters( 'cf_akismet_data', $akismet_data, $form, $data );

        return $akismet_data;
    }

    /**
     * Send data to Akismet and check if it's spam
     *
     * @param array $data
     * @return bool True if spam, false if not
     */
    private function akismet_check( $data ) {
        $query_string = http_build_query( $data );

        $response = \Akismet::http_post( $query_string, 'comment-check' );

        if ( ! empty( $response[1] ) && 'true' === trim( $response[1] ) ) {
            return true;
        }

        return false;
    }

    /**
     * Get user IP address
     *
     * @return string
     */
    private function get_user_ip() {
        $ip = '';

        // Check for proxy headers
        $headers = array(
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        );

        foreach ( $headers as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                $ip = sanitize_text_field( $_SERVER[ $header ] );
                // Handle comma-separated IPs (X-Forwarded-For)
                if ( strpos( $ip, ',' ) !== false ) {
                    $ips = explode( ',', $ip );
                    $ip = trim( $ips[0] );
                }
                break;
            }
        }

        return $ip;
    }

    /**
     * Log debug information
     *
     * @param string $message
     * @param \Core_Forms\Form $form
     */
    private function log_debug( $message, $form = null ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $log_message = '[Core Forms Akismet] ' . $message;
            if ( $form ) {
                $log_message .= sprintf( ' (Form: "%s", ID: %d)', $form->title, $form->ID );
            }
            error_log( $log_message );
        }
    }

    /**
     * Output Akismet message fields in the Messages tab
     *
     * @param \Core_Forms\Form $form
     */
    public function output_akismet_message_fields( $form ) {
        if ( ! $this->is_akismet_active() ) {
            return;
        }

        $spam_message = isset( $form->messages['spam'] ) ? $form->messages['spam'] : __( 'Your submission was flagged as spam.', 'core-forms' );
        ?>
        <tr valign="top">
            <th scope="row" colspan="2" class="cf-settings-header"><?php echo __( 'Akismet Spam Protection', 'core-forms' ); ?></th>
        </tr>

        <tr valign="top">
            <th scope="row"><label for="cf_form_spam"><?php _e( 'Spam Detected', 'core-forms' ); ?></label></th>
            <td>
                <input type="text" class="widefat" id="cf_form_spam" name="form[messages][spam]" value="<?php echo esc_attr( $spam_message ); ?>" />
                <p class="description"><?php _e( 'The text shown when Akismet detects a submission as spam. Note: A success message is shown to prevent spam bots from knowing they were blocked.', 'core-forms' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Show admin notice when Akismet is protecting forms
     */
    public function show_admin_notice() {
        // Only show on Core Forms edit pages
        if ( empty( $_GET['page'] ) || $_GET['page'] !== 'core-forms' || empty( $_GET['form_id'] ) ) {
            return;
        }

        echo '<div class="notice notice-info" data-notice="cf-akismet">';
        echo '<p><span class="dashicons dashicons-shield-alt" style="color:#3858e9;"></span> <strong>' . __( 'Akismet spam protection is active.', 'core-forms' ) . '</strong> ';
        echo __( 'Form submissions are automatically checked for spam.', 'core-forms' );
        echo '</p></div>';
    }
}
