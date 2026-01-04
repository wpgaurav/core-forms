<?php

namespace Core_Forms;

class TagReplacers {

    public function user( $field ) {
        $user = wp_get_current_user();
        return isset( $user->{$field} ) ? $user->{$field} : '';
    }

    public function post( $field ) {
        $post = get_post();
        return ( $post && isset( $post->{$field} ) ) ? $post->{$field} : '';
    }

    public function url_params( $field ) {
        return isset( $_GET[ $field ] ) ? sanitize_text_field( $_GET[ $field ] ) : '';
    }
}
