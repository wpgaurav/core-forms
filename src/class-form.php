<?php

namespace Core_Forms;

class Form {

    public $ID       = 0;
    public $title    = '';
    public $slug     = '';
    public $markup   = '';
    public $messages = array();
    public $settings = array();

    /**
     * Form constructor.
     *
     * @param $id
     */
    public function __construct( $id ) {
        $this->ID = $id;
    }

    /**
    * Magic method for accessing unexisting properties, eg lowercase "id".
    * @param string $property
    * @return mixed
    */
    public function __get( $property ) {
        if ( $property === 'id' ) {
            return $this->ID;
        }
    }

    public function get_html() {
        $form = $this;

        /**
         * Filters the CSS classes to be added to this form's class attribute.
         *
         * @param array $form_classes
         * @param Form $form
         */
        $form_classes_attr = apply_filters( 'cf_form_element_class_attr', '', $form );

        /**
         * Filters the action attribute for this form.
         *
         * @param string $form_action
         * @param Form $form
         */
        $form_action      = apply_filters( 'cf_form_element_action_attr', null, $form );
        $form_action_attr = is_null( $form_action ) ? '' : sprintf( 'action="%s"', $form_action );

        $data_attributes = $this->get_data_attributes();
        $settings = cf_get_settings();

        $html  = '';
        $html .= sprintf( '<form method="post" %s class="cf-form cf-form-%d %s" %s novalidate aria-label="%s">', $form_action_attr, $this->ID, esc_attr( $form_classes_attr ), $data_attributes, esc_attr( $this->title ) );

        if ( $settings['enable_nonce'] ) {
            $html .= wp_nonce_field( 'core_forms_submit', '_wpnonce', true, false );
        }

        $html .= '<input type="hidden" name="action" value="cf_form_submit" />';
        $html .= sprintf( '<input type="hidden" name="_cf_form_id" value="%d" />', $this->ID );
        $html .= sprintf( '<div style="display: none;" aria-hidden="true"><input type="text" name="_cf_h%d" value="" tabindex="-1" autocomplete="off" /></div>', $this->ID );
        $html .= '<div class="cf-messages" role="status" aria-live="polite" aria-atomic="true"></div>';
        $html .= '<div class="cf-fields-wrap">';
        $html .= $this->get_markup();
        $html .= '<noscript><p role="alert">' . __( 'Please enable JavaScript for this form to work.', 'core-forms' ) . '</p></noscript>';
        $html .= '</div>'; // end field wrap
        $html .= '</form>';

        // ensure JS scripts are enqueued whenever this function is called
        if ( function_exists( 'wp_enqueue_script' ) ) {
            wp_enqueue_script( 'core-forms' );
            wp_enqueue_script( 'core-forms-a11y' );
        }

        /**
         * Filters the resulting HTML for this form.
         *
         * @param string $html
         * @param Form $form
         */
        $html = apply_filters( 'cf_form_html', $html, $form );
        return $html;
    }

    public function get_data_attributes() {
        $form       = $this;
        $attributes = array(
            'id'    => $this->ID,
            'title' => $this->title,
            'slug'  => $this->slug,
        );

        // add messages
        foreach ( $this->messages as $key => $message ) {
            $key                             = str_replace( '_', '-', $key );
            $attributes[ 'message-' . $key ] = $message;
        }

        /**
         * Filters the data attributes to be added to the form attribute.
         *
         * @param array $attributes
         * @param Form $form
         */
        $attributes = apply_filters( 'cf_form_element_data_attributes', $attributes, $form );

        // create string of attribute key-value pairs
        $string = '';
        foreach ( $attributes as $attr => $value ) {
            // prefix all attributes with data-
            if ( substr( $attr, 0, 5 ) !== 'data-' ) {
                $attr = 'data-' . $attr;
            }

            $string .= sprintf( '%s="%s" ', $attr, esc_attr( $value ) );
        }
        $string = rtrim( $string, ' ' );

        return $string;
    }


    /**
     * @return string
     */
    public function __toString() {
        return $this->get_html();
    }

    /**
    * @return string
    */
    public function get_markup() {
        /**
         * @param string $markup
         * @param Form $form
         */
        return apply_filters( 'cf_form_markup', $this->markup, $this );
    }

    /**
     * @return array
     */
    public function get_required_fields() {
        if ( empty( $this->settings['required_fields'] ) ) {
            return array();
        }

        $required_fields = explode( ',', $this->settings['required_fields'] );
        return $required_fields;
    }

    /**
     * @return array
     */
    public function get_email_fields() {
        if ( empty( $this->settings['email_fields'] ) ) {
            return array();
        }

        $email_fields = explode( ',', $this->settings['email_fields'] );
        return $email_fields;
    }

    /**
    * @param string $code
    * @return string
    */
    public function get_message( $code ) {
        $form    = $this;
        $message = isset( $this->messages[ $code ] ) ? $this->messages[ $code ] : '';

        /**
        * @param string $message
        * @param Form $form
        */
        $message = apply_filters( 'cf_form_message_' . $code, $message, $form );
        return $message;
    }

    /**
    * @return int The number of named fields in the form
    *
    * Note: this includes all default fields and an additional field for the "was-required" element we include in every request.
    */
    public function get_field_count() {
        $pattern = '/\bname\s*=\s*["\'/i';
        preg_match_all( $pattern, $this->get_html(), $matches );
        $count = ! empty( $matches ) ? count( $matches[0] ) : 0;
        $count++; // Add one for 'was-required'
        return $count;
    }
}
