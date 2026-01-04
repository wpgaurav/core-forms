<?php

namespace Core_Forms;

class Forms {
    /**
     * @var string
     */
    private $plugin_file;

    /**
     * @var array
     */
    private $settings;

    /**
    * @var string
    */
    private $assets_url;

    /**
     * Forms constructor.
     *
     * @param string $plugin_file
     * @param array $settings
     */
    public function __construct( $plugin_file, array $settings = array() ) {
        $this->plugin_file = $plugin_file;
        $this->settings    = $settings;
    }

    public function hook() {
        add_action( 'wp_ajax_cf_form_submit', array( $this, 'process' ) );
        add_action( 'wp_ajax_nopriv_cf_form_submit', array( $this, 'process' ) );
        add_action( 'init', array( $this, 'register' ) );

        add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
        add_action( 'init', array( $this, 'block_init' ) );
        add_action( 'init', array( $this, 'register_shortcode' ) );
        add_action( 'init', array( $this, 'register_assets' ) );
        add_action( 'template_redirect', array( $this, 'listen_for_preview' ) );
    }

    public function register() {
        register_post_type(
            'core-form',
            array(
                'labels'       => array(
                    'name'          => __( 'Core Forms', 'core-forms' ),
                    'singular_name' => __( 'Core Form', 'core-forms' ),
                ),
                'public'       => false,
                'show_in_rest' => true,
                'map_meta_cap' => true,
            )
        );
    }

    public function block_init() {
        // Register Core Forms block
        register_block_type(
            'core-forms/form',
            array(
                'render_callback' => array( $this, 'shortcode' ),
                'attributes'      => array(
                    'slug' => array( 'type' => 'string' ),
                    'id'   => array( 'type' => 'number' ),
                ),
            )
        );
    }

    public function register_shortcode() {
        add_shortcode( 'cf_form', array( $this, 'shortcode' ) );
    }

    public function register_assets() {
        $this->assets_url = plugins_url( '/assets/', $this->plugin_file );

        wp_register_script( 'core-forms', $this->assets_url . 'js/forms.js', array(), CORE_FORMS_VERSION, true );
        wp_localize_script(
            'core-forms',
            'cf_js_vars',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
            )
        );
        wp_register_style( 'core-forms', $this->assets_url . 'css/forms.css', array(), CORE_FORMS_VERSION );
        add_filter( 'wp_enqueue_scripts', array( $this, 'maybe_enqueue_stylesheet' ) );
    }

    public function maybe_enqueue_stylesheet() {
        if ( (int) $this->settings['load_stylesheet'] ) {
            wp_enqueue_style( 'core-forms' );
        }
    }

    /**
     * @param array $attributes
     *
     * @return string
     */
    public function shortcode( $attributes = array() ) {
        $attributes = shortcode_atts(
            array(
                'slug' => '',
                'id'   => '',
            ),
            $attributes,
            'cf_form'
        );

        // Determine if we're looking up by slug or ID
        $use_slug = ! empty( $attributes['slug'] );
        $form_id_or_slug = $use_slug ? $attributes['slug'] : $attributes['id'];

        // Allow filtering of form ID
        $form_id_or_slug = apply_filters( 'cf_shortcode_form_identifier', $form_id_or_slug, $attributes );

        // Bail early if no slug or ID is given
        if ( empty( $form_id_or_slug ) ) {
            return '';
        }

        try {
            // If slug attribute was used, always do slug lookup (even for numeric slugs like "001")
            if ( $use_slug ) {
                $form = cf_get_form_by_slug( $form_id_or_slug );
            } else {
                $form = cf_get_form( $form_id_or_slug );
            }
        } catch ( \Exception $e ) {
            // swallow exception
            return '';
        }

        return $form->get_html();
    }

    public function rest_api_init() {
        $route = '/forms';
        register_rest_route(
            'cf',
            $route,
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'rest_get_forms' ),
                'permission_callback' => array( $this, 'rest_check_admin_permissions' ),
            )
        );
    }

    /**
    * @return bool
    */
    public function rest_check_admin_permissions() {
        return current_user_can( 'edit_forms' );
    }

    /**
    * @return array
    */
    public function rest_get_forms() {
        $forms = cf_get_forms();
        return array_values(
            array_map(
                function ( Form $f ) {
                    return array(
                        'ID'    => $f->ID,
                        'title' => $f->title,
                        'slug'  => $f->slug,
                    );
                },
                $forms
            )
        );
    }

    public function process() {
        // validate honeypot field
        $honeypot_key = null;
        foreach ( $_POST as $key => $_value ) {
            if ( strpos( $key, '_cf_h' ) === 0 ) {
                $honeypot_key = $key;
                break;
            }
        }

        // if honeypot field not found, just ignore request
        if ( $honeypot_key === null || '' !== $_POST[ $honeypot_key ] ) {
            return;
        }

        // if nonces are enabled, make sure we have a valid one
        $settings = cf_get_settings();
        if ( $settings['enable_nonce'] && ! check_ajax_referer( 'core_forms_submit', '_wpnonce', false ) ) {
            wp_send_json(
                array(
                    'message' => array(
                        'type' => 'error',
                        'text' => __( 'Your session has expired.', 'core-forms' ),
                    ),
                ),
                403
            );
        }

        try {
            $form = cf_get_form( (int) $_POST['_cf_form_id'] );
        } catch ( \Exception $e ) {
            return;
        }

        // unset ignored field names (those starting with underscore or in ignored array)
        $ignored_field_names = apply_filters( 'cf_ignored_field_names', array() );
        $data                = $_POST;
        foreach ( $data as $key => $value ) {
            if ( $key[0] === '_' || in_array( $key, $ignored_field_names, true ) ) {
                unset( $data[ $key ] );
            }
        }

        // process data: trim all values & strip slashes
        $data = array_combine(
            array_keys( $data ),
            array_map(
                function( $value ) {
                    if ( is_array( $value ) ) {
                        return array_map( 'trim', array_map( 'stripslashes', $value ) );
                    }
                    return trim( stripslashes( $value ) );
                },
                $data
            )
        );
        $data = cf_template( $data );

        // detect referer & user agent from server variables
        $referer_url = sanitize_text_field( isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '' );
        $user_agent  = sanitize_text_field( isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '' );
        $ip_address  = sanitize_url( isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '' );

        // build submission object
        $submission               = new Submission();
        $submission->form_id      = $form->ID;
        $submission->data         = $data;
        $submission->user_agent   = $user_agent;
        $submission->ip_address   = $ip_address;
        $submission->referer_url  = $referer_url;
        $submission->submitted_at = gmdate( 'Y-m-d H:i:s' );

        // validate: check for required fields
        $error_code = $this->validate_form( $form, $data );

        do_action( 'cf_process_form', $form, $submission, $data, $error_code );

        if ( $error_code === '' ) {

            // save submission
            if ( $form->settings['save_submissions'] ) {
                global $wpdb;

                $wpdb->insert(
                    $wpdb->prefix . 'cf_submissions',
                    array(
                        'form_id'      => $submission->form_id,
                        'data'         => json_encode( $submission->data ),
                        'ip_address'   => $submission->ip_address,
                        'user_agent'   => $submission->user_agent,
                        'referer_url'  => $submission->referer_url,
                        'submitted_at' => $submission->submitted_at,
                    )
                );
                $submission->id = $wpdb->insert_id;

                do_action( 'cf_submission_inserted', $submission, $form );
            }

            do_action( 'cf_form_success', $submission, $form );

            // run actions
            if ( ! empty( $form->settings['actions'] ) ) {
                foreach ( $form->settings['actions'] as $action_settings ) {
                    do_action( sprintf( 'cf_process_form_action_%s', $action_settings['type'] ), $action_settings, $submission, $form );
                }
            }
        }

        // signal to others that form was processed, even when there was an error
        do_action( 'cf_form_response', $form, $submission, $error_code );

        $response = $this->get_response_for_error_code( $error_code, $form, $data, $submission );
        wp_send_json( $response );
    }

    public function listen_for_preview() {
        if ( empty( $_GET['cf_preview_form'] ) || ! current_user_can( 'edit_forms' ) ) {
            return;
        }

        try {
            $form = cf_get_form( (int) $_GET['cf_preview_form'] );
        } catch ( \Exception $e ) {
            return;
        }

        show_admin_bar( false );
        add_filter( 'pre_handle_404', '__return_true' );
        remove_all_actions( 'template_redirect' );
        add_action(
            'wp_head',
            function () {
                wp_enqueue_style( 'core-forms' );
                echo '<style type="text/css">body{ background: #f1f1f1; margin-top: 40px; } .cf-form{ max-width: 600px; padding: 12px 24px 24px; margin: 0 auto; background: white; }</style>';
            }
        );
        add_action(
            'wp_body_open',
            function () use ( $form ) {
                status_header( 200 );
                echo '<div class="cf-form-preview">';
                echo $form->get_html();
                echo '</div>';
                exit;
            }
        );
    }

    private function get_response_for_error_code( $error_code, Form $form, $data = array(), ?Submission $submission = null ) {
        // return success response for empty error code string or spam (to trick bots)
        if ( $error_code === '' || $error_code === 'spam' ) {
            $response = array(
                'message'   => array(
                    'type' => 'success',
                    'text' => $form->get_message( 'success' ),
                ),
                'hide_form' => (bool) $form->settings['hide_after_success'],
            );

            // maybe add redirect_url to response
            if ( ! empty( $form->settings['redirect_url'] ) ) {
                $redirect_url = cf_replace_data_variables( $form->settings['redirect_url'], $submission );
                $redirect_url = apply_filters( 'cf_form_redirect_url', $redirect_url, $form, $data );
                if ( ! empty( $redirect_url ) ) {
                    $response['redirect_url'] = $redirect_url;
                }
            }

            return $response;
        }

        // error response
        return array(
            'message' => array(
                'type' => 'error',
                'text' => $form->get_message( $error_code ),
            ),
        );
    }

    private function validate_form( Form $form, array $data ) {
        // validate required fields
        $required_fields = $form->get_required_fields();
        foreach ( $required_fields as $field_name ) {
            $field_name = trim( $field_name );
            $value      = isset( $data[ $field_name ] ) ? $data[ $field_name ] : '';

            if ( empty( $value ) || ( is_array( $value ) && ! array_filter( $value ) ) ) {
                $error_code = 'required_field_missing';
                return $error_code;
            }
        }

        // validate email fields
        $email_fields = $form->get_email_fields();
        foreach ( $email_fields as $field_name ) {
            $field_name = trim( $field_name );
            $value      = isset( $data[ $field_name ] ) ? $data[ $field_name ] : '';

            // if value empty, no need to validate (means field was optional)
            if ( ! empty( $value ) && ! is_email( $value ) ) {
                $error_code = 'invalid_email';
                return $error_code;
            }
        }

        /**
         * This filter allows you to perform your own form validation. The dynamic portion of the hook refers to the form slug.
         *
         * Return a non-empty string to indicate an error (this will be used as the form message key).
         *
         * @param string $error_code
         * @param Form $form
         * @param array $data
         */
        $error_code = apply_filters( 'cf_validate_form', '', $form, $data );
        return $error_code;
    }

    public function get_preview_url( Form $form ) {
        $preview_url = add_query_arg(
            array(
                'cf_preview_form' => $form->ID,
            ),
            site_url()
        );
        return $preview_url;
    }

    /**
    *
    * @param Form $form
    * @return string
    */
    public function get_available_field_types() {
        $types = array(
            'text'           => array( 'text' => 'Text', 'type' => 'input', 'placeholder' => 'Your text here' ),
            'email'          => array( 'text' => 'Email', 'type' => 'input', 'placeholder' => 'your@email.com' ),
            'textarea'       => array( 'text' => 'Textarea', 'placeholder' => 'Your text here' ),
            'tel'            => array( 'text' => 'Telephone', 'type' => 'input', 'placeholder' => '' ),
            'number'         => array( 'text' => 'Number', 'type' => 'input', 'placeholder' => '' ),
            'date'           => array( 'text' => 'Date', 'type' => 'input' ),
            'checkbox'       => array( 'text' => 'Checkbox' ),
            'dropdown'       => array( 'text' => 'Select' ),
            'radio'          => array( 'text' => 'Radio', 'accepts_options' => true ),
            'checkboxes'     => array( 'text' => 'Checkboxes', 'accepts_options' => true ),
            'hidden'         => array( 'text' => 'Hidden', 'type' => 'input' ),
            'submit'         => array( 'text' => 'Submit', 'type' => 'button' ),
        );
        $types = apply_filters( 'cf_available_field_types', $types );
        return $types;
    }

    /**
    * @param Form $form
    * @return string
    */
    public function get_visible_field_names( Form $form ) {
        $html         = $form->get_markup();
        $hidden       = get_hidden_columns( get_current_screen() );
        $field_names  = array();
        $regex        = '/<(?:input|select|textarea|button)[^>]*name=[\'|"]([^\'|"]+)[\'|"][^>]*>/i';

        preg_match_all( $regex, $html, $matches );

        if ( ! empty( $matches[1] ) ) {
            do_action( 'cf_output_table_data_columns', $form );

            foreach ( $matches[1] as $field_name ) {
                // strip [] from names
                $field_name = str_replace( '[]', '', $field_name );

                // skip hidden columns & duplicates
                if ( in_array( $field_name, $hidden ) || in_array( $field_name, $field_names ) ) {
                    continue;
                }

                $field_names[] = $field_name;
            }
        }
        return $field_names;
    }
}
