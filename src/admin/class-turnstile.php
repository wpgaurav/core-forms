<?php

namespace Core_Forms\Admin;

class Turnstile {
    private $settings;
    private static $scripts_enqueued = false;

    public function __construct() {
        if ( function_exists( 'cf_get_settings' ) ) {
            $this->settings = cf_get_settings();
        } else {
            $this->settings = array(
                'cloudflare_turnstile' => array(
                    'site_key' => '',
                    'secret_key' => '',
                )
            );
        }
    }

    public function hook() {
        if ( is_admin() ) {
            add_action( 'cf_admin_output_form_messages', array( $this, 'output_turnstile_message_fields' ) );
            add_action( 'cf_admin_output_form_settings', array( $this, 'output_form_settings' ) );
            add_action( 'cf_admin_output_settings', array( $this, 'output_global_settings' ) );
        }

        if ( $this->is_configured() ) {
            add_filter( 'cf_ignored_field_names', array( $this, 'ignored_fields' ) );
            add_filter( 'cf_validate_form', array( $this, 'validate_turnstile' ), 10, 3 );
            add_filter( 'cf_form_markup', array( $this, 'add_turnstile_to_form' ), 10, 2 );
            add_filter( 'cf_form_html', array( $this, 'enqueue_turnstile_on_form_render' ), 10, 2 );

            add_action( 'admin_notices', array( $this, 'show_admin_notice' ) );
        }
    }

    /**
     * Check if Turnstile is properly configured globally
     *
     * @return bool
     */
    private function is_configured() {
        $site_key = ! empty( $this->settings['cloudflare_turnstile']['site_key'] );
        $secret_key = ! empty( $this->settings['cloudflare_turnstile']['secret_key'] );

        return $site_key && $secret_key;
    }

    /**
     * Check if Turnstile is enabled for a specific form
     *
     * @param \Core_Forms\Form $form
     * @return bool
     */
    private function is_enabled_for_form( $form ) {
        return ! empty( $form->settings['enable_turnstile'] );
    }

    /**
     * Add Turnstile response fields to ignored fields list
     *
     * @param array $ignored_fields
     * @return array
     */
    public function ignored_fields( $ignored_fields ) {
        $ignored_fields[] = 'cf-turnstile-response';
        $ignored_fields[] = 'cf-turnstile-failed';

        return $ignored_fields;
    }

    /**
     * Enqueue Turnstile scripts when a form is being rendered
     *
     * @param string $html
     * @param \Core_Forms\Form $form
     * @return string
     */
    public function enqueue_turnstile_on_form_render( $html, $form ) {
        // Skip if Turnstile is not enabled for this form
        if ( ! $this->is_enabled_for_form( $form ) ) {
            return $html;
        }

        // Only enqueue scripts once per page load
        if ( self::$scripts_enqueued ) {
            return $html;
        }

        // Skip if we're in admin area
        if ( is_admin() ) {
            return $html;
        }

        $this->enqueue_turnstile_script();
        self::$scripts_enqueued = true;

        return $html;
    }

    /**
     * Enqueue Cloudflare Turnstile scripts
     */
    public function enqueue_turnstile_script() {
        $site_key = $this->settings['cloudflare_turnstile']['site_key'];

        // Enqueue Cloudflare Turnstile API
        wp_enqueue_script(
            'cloudflare-turnstile',
            'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit',
            array(),
            null,
            true
        );

        // Enqueue Turnstile integration script
        wp_enqueue_script(
            'core-forms-turnstile',
            plugins_url( 'assets/js/turnstile.js', $this->get_plugin_file() ),
            array( 'cloudflare-turnstile', 'core-forms' ),
            CORE_FORMS_VERSION,
            true
        );

        wp_localize_script( 'core-forms-turnstile', 'cf_turnstile', array(
            'site_key' => $site_key,
        ) );
    }

    /**
     * Get the plugin file path
     *
     * @return string
     */
    private function get_plugin_file() {
        return dirname( dirname( __DIR__ ) ) . '/core-forms.php';
    }

    /**
     * Add Turnstile widget container to form markup
     *
     * @param string $markup
     * @param \Core_Forms\Form $form
     * @return string
     */
    public function add_turnstile_to_form( $markup, $form ) {
        // Skip if Turnstile is not enabled for this form
        if ( ! $this->is_enabled_for_form( $form ) ) {
            return $markup;
        }

        $site_key = $this->settings['cloudflare_turnstile']['site_key'];
        $turnstile_widget = sprintf(
            '<div class="cf-turnstile-container" data-sitekey="%s" data-form-id="%d"></div>',
            esc_attr( $site_key ),
            $form->ID
        );

        // Try to insert before submit button, otherwise append
        if ( preg_match( '/<(input|button)[^>]*type=["\']submit["\']/i', $markup ) ) {
            $markup = preg_replace(
                '/(<(input|button)[^>]*type=["\']submit["\'][^>]*>)/i',
                $turnstile_widget . '$1',
                $markup,
                1
            );
        } else {
            $markup .= $turnstile_widget;
        }

        return $markup;
    }

    /**
     * Validate Turnstile response
     *
     * @param string $error_code
     * @param \Core_Forms\Form $form
     * @param array $data
     * @return string
     */
    public function validate_turnstile( $error_code, $form, $data ) {
        // If there's already an error, don't proceed
        if ( ! empty( $error_code ) ) {
            return $error_code;
        }

        // Skip validation if Turnstile is not enabled for this form
        if ( ! $this->is_enabled_for_form( $form ) ) {
            return $error_code;
        }

        // Get Turnstile response from form data
        $turnstile_response = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( $_POST['cf-turnstile-response'] ) : '';
        $turnstile_failed = isset( $_POST['cf-turnstile-failed'] ) ? sanitize_text_field( $_POST['cf-turnstile-failed'] ) : '';

        // Check if Turnstile execution failed on the client side
        if ( ! empty( $turnstile_failed ) ) {
            $this->log_debug( 'Turnstile execution failed on client side', $form );
            return 'turnstile_failed';
        }

        if ( empty( $turnstile_response ) ) {
            $this->log_debug( 'Turnstile token missing from form submission', $form );
            return 'turnstile_failed';
        }

        // Basic token format validation (should be a long string)
        if ( strlen( $turnstile_response ) < 20 ) {
            $this->log_debug( 'Turnstile token appears to be invalid (too short)', $form );
            return 'turnstile_failed';
        }

        // Verify Turnstile with Cloudflare
        $verification_result = $this->verify_turnstile( $turnstile_response );

        if ( ! $verification_result['success'] ) {
            $error_codes = isset( $verification_result['error-codes'] ) ? implode( ', ', $verification_result['error-codes'] ) : 'unknown';
            $this->log_debug( 'Turnstile verification failed with error codes: ' . $error_codes, $form );
            return 'turnstile_failed';
        }

        return $error_code;
    }

    /**
     * Verify Turnstile response with Cloudflare's API
     *
     * @param string $response
     * @return array
     */
    private function verify_turnstile( $response ) {
        $secret_key = $this->settings['cloudflare_turnstile']['secret_key'];
        $remote_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';

        $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $data = array(
            'secret'   => $secret_key,
            'response' => $response,
            'remoteip' => $remote_ip,
        );

        $response = wp_remote_post( $url, array(
            'body' => $data,
            'timeout' => 10,
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'error-codes' => array( 'network_error' ) );
        }

        $body = wp_remote_retrieve_body( $response );
        $result = json_decode( $body, true );

        if ( ! $result ) {
            return array( 'success' => false, 'error-codes' => array( 'invalid_response' ) );
        }

        return $result;
    }

    /**
     * Log debug information if WP_DEBUG is enabled
     *
     * @param string $message
     * @param \Core_Forms\Form $form
     */
    private function log_debug( $message, $form = null ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $log_message = '[Core Forms Turnstile] ' . $message;
            if ( $form ) {
                $log_message .= sprintf( ' (Form: "%s", ID: %d)', $form->title, $form->ID );
            }
            error_log( $log_message );
        }
    }

    /**
     * Output Turnstile message fields in the Messages tab
     *
     * @param \Core_Forms\Form $form
     */
    public function output_turnstile_message_fields( $form ) {
        // Only show these fields if Turnstile is configured
        if ( ! $this->is_configured() ) {
            return;
        }
        ?>
        <tr valign="top">
            <th scope="row" colspan="2" class="cf-settings-header"><?php echo __( 'Cloudflare Turnstile', 'core-forms' ); ?></th>
        </tr>

        <tr valign="top">
            <th scope="row"><label for="cf_form_turnstile_failed"><?php _e( 'Turnstile Failed', 'core-forms' ); ?></label></th>
            <td>
                <input type="text" class="widefat" id="cf_form_turnstile_failed" name="form[messages][turnstile_failed]" value="<?php echo esc_attr( isset( $form->messages['turnstile_failed'] ) ? $form->messages['turnstile_failed'] : __( 'Verification failed. Please try again.', 'core-forms' ) ); ?>" required />
                <p class="description"><?php _e( 'The text that shows when Turnstile verification fails.', 'core-forms' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Output Turnstile settings in the Settings tab
     *
     * @param \Core_Forms\Form $form
     */
    public function output_form_settings( $form ) {
        // Only show these fields if Turnstile is configured
        if ( ! $this->is_configured() ) {
            return;
        }

        $enabled = ! empty( $form->settings['enable_turnstile'] );
        ?>
        <tr valign="top">
            <th scope="row" colspan="2" class="cf-settings-header"><?php _e( 'Cloudflare Turnstile', 'core-forms' ); ?></th>
        </tr>

        <tr valign="top">
            <th scope="row"><label for="cf_enable_turnstile"><?php _e( 'Enable Turnstile', 'core-forms' ); ?></label></th>
            <td>
                <label>
                    <input type="checkbox" id="cf_enable_turnstile" name="form[settings][enable_turnstile]" value="1" <?php checked( $enabled ); ?> />
                    <?php _e( 'Enable Cloudflare Turnstile on this form', 'core-forms' ); ?>
                </label>
                <p class="description"><?php _e( 'Add a privacy-focused CAPTCHA widget to protect this form from spam.', 'core-forms' ); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Show admin notice when Turnstile is active on a form
     */
    public function show_admin_notice() {
        // Only show on Core Forms pages with a form selected
        if ( empty( $_GET['page'] ) || $_GET['page'] !== 'core-forms' || empty( $_GET['form_id'] ) ) {
            return;
        }

        try {
            $form = cf_get_form( (int) $_GET['form_id'] );
        } catch ( \Exception $e ) {
            return;
        }

        // Only show if Turnstile is enabled for this form
        if ( ! $this->is_enabled_for_form( $form ) ) {
            return;
        }

        echo '<div class="notice notice-success" data-notice="cf-turnstile">';
        echo '<p><span class="dashicons dashicons-shield" style="color:#46b450;"></span> <strong>' . __( 'Cloudflare Turnstile is enabled on this form.', 'core-forms' ) . '</strong> ';
        echo __( 'Submissions will be protected from bots and spam.', 'core-forms' );
        echo ' <a href="' . admin_url( 'admin.php?page=core-forms-settings' ) . '">' . __( 'View settings', 'core-forms' ) . '</a>.</p>';
        echo '</div>';
    }

    public function output_global_settings() {
        $site_key = isset( $this->settings['cloudflare_turnstile']['site_key'] ) ? $this->settings['cloudflare_turnstile']['site_key'] : '';
        $secret_key = isset( $this->settings['cloudflare_turnstile']['secret_key'] ) ? $this->settings['cloudflare_turnstile']['secret_key'] : '';
        ?>
        <h2 class="title"><?php _e( 'Cloudflare Turnstile', 'core-forms' ); ?></h2>

        <p class="description">
            <?php _e( 'Cloudflare Turnstile is a privacy-focused alternative to CAPTCHA. It runs in the background without user interaction.', 'core-forms' ); ?>
            <?php _e( 'To use this feature, register your site at', 'core-forms' ); ?>
            <a target="_blank" tabindex="-1" href="https://dash.cloudflare.com/?to=/:account/turnstile">https://dash.cloudflare.com/turnstile</a>.
            <?php _e( 'Once configured, you can enable Turnstile on individual forms in their settings.', 'core-forms' ); ?>
        </p>

        <table class="form-table" role="presentation">
            <tbody>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Site Key', 'core-forms' ); ?></th>
                    <td>
                        <input type="text" class="large-text" name="cf_settings[cloudflare_turnstile][site_key]" value="<?php echo esc_attr( $site_key ); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Secret Key', 'core-forms' ); ?></th>
                    <td>
                        <input type="text" class="large-text" name="cf_settings[cloudflare_turnstile][secret_key]" value="<?php echo esc_attr( $secret_key ); ?>" />
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }
}
