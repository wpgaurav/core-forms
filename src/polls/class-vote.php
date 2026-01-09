<?php

namespace Core_Forms\Polls;

class Vote {
    public $id;
    public $poll_id;
    public $option_index;
    public $ip_address;
    public $user_id;
    public $cookie_hash;
    public $voted_at;

    public static function from_object( $object ) {
        $vote               = new Vote();
        $vote->id           = isset( $object->id ) ? (int) $object->id : 0;
        $vote->poll_id      = isset( $object->poll_id ) ? (int) $object->poll_id : 0;
        $vote->option_index = isset( $object->option_index ) ? (int) $object->option_index : 0;
        $vote->ip_address   = isset( $object->ip_address ) ? $object->ip_address : '';
        $vote->user_id      = isset( $object->user_id ) ? (int) $object->user_id : 0;
        $vote->cookie_hash  = isset( $object->cookie_hash ) ? $object->cookie_hash : '';
        $vote->voted_at     = isset( $object->voted_at ) ? $object->voted_at : '';
        return $vote;
    }
}
