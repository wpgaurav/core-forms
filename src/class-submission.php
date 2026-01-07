<?php

namespace Core_Forms;

class Submission {
    public $id;
    public $form_id;
    public $data;
    public $user_agent;
    public $ip_address;
    public $referer_url;
    public $is_spam;
    public $submitted_at;

    public static function from_object( $object ) {
        $submission               = new Submission();
        $submission->id           = isset( $object->id ) ? (int) $object->id : 0;
        $submission->form_id      = isset( $object->form_id ) ? (int) $object->form_id : 0;
        $submission->data         = isset( $object->data ) ? (array) json_decode( $object->data, true ) : array();
        $submission->ip_address   = isset( $object->ip_address ) ? $object->ip_address : '';
        $submission->user_agent   = isset( $object->user_agent ) ? $object->user_agent : '';
        $submission->referer_url  = isset( $object->referer_url ) ? $object->referer_url : '';
        $submission->is_spam      = isset( $object->is_spam ) ? (bool) $object->is_spam : false;
        $submission->submitted_at = isset( $object->submitted_at ) ? $object->submitted_at : '';
        return $submission;
    }
}
