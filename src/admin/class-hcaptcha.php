<?php

namespace Core_Forms\Admin;

// Create backward compatible alias for old namespace


class Hcaptcha {
    public function hook() {
        add_filter( 'cf_ignored_field_names', array( $this, 'ignored_fields' ) );
        add_filter( 'cf_ignored_field_names', array( $this, 'ignored_fields' ) ); // Legacy
    }

    public function ignored_fields() {
        return array(
            'hcaptcha-widget-id',
            'hcap_fst_token',
            'g-recaptcha-response',
            'h-captcha-response',
            'core_forms_form_nonce',
        );
    }
}
