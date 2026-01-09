<?php

namespace Core_Forms;

class Reply {
    public $id;
    public $submission_id;
    public $from_email;
    public $to_email;
    public $subject;
    public $message;
    public $user_id;
    public $sent_at;

    public static function from_object( $object ) {
        $reply                = new Reply();
        $reply->id            = isset( $object->id ) ? (int) $object->id : 0;
        $reply->submission_id = isset( $object->submission_id ) ? (int) $object->submission_id : 0;
        $reply->from_email    = isset( $object->from_email ) ? $object->from_email : '';
        $reply->to_email      = isset( $object->to_email ) ? $object->to_email : '';
        $reply->subject       = isset( $object->subject ) ? $object->subject : '';
        $reply->message       = isset( $object->message ) ? $object->message : '';
        $reply->user_id       = isset( $object->user_id ) ? (int) $object->user_id : 0;
        $reply->sent_at       = isset( $object->sent_at ) ? $object->sent_at : '';
        return $reply;
    }

    public function get_user() {
        if ( ! $this->user_id ) {
            return null;
        }
        return get_user_by( 'id', $this->user_id );
    }

    public function get_user_display_name() {
        $user = $this->get_user();
        if ( $user ) {
            return $user->display_name;
        }
        return __( 'Unknown', 'core-forms' );
    }
}
