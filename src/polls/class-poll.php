<?php

namespace Core_Forms\Polls;

class Poll {
    public $id;
    public $post_id;
    public $question;
    public $options = array();
    public $settings = array();
    public $status;
    public $ends_at;
    public $created_at;

    private $post = null;

    public static function from_object( $object ) {
        $poll             = new Poll();
        $poll->id         = isset( $object->id ) ? (int) $object->id : 0;
        $poll->post_id    = isset( $object->post_id ) ? (int) $object->post_id : 0;
        $poll->question   = isset( $object->question ) ? $object->question : '';
        $poll->options    = isset( $object->options ) ? json_decode( $object->options, true ) : array();
        $poll->settings   = isset( $object->settings ) ? json_decode( $object->settings, true ) : array();
        $poll->status     = isset( $object->status ) ? $object->status : 'active';
        $poll->ends_at    = isset( $object->ends_at ) ? $object->ends_at : null;
        $poll->created_at = isset( $object->created_at ) ? $object->created_at : '';

        if ( ! is_array( $poll->options ) ) {
            $poll->options = array();
        }
        if ( ! is_array( $poll->settings ) ) {
            $poll->settings = array();
        }

        return $poll;
    }

    public function get_post() {
        if ( $this->post === null && $this->post_id > 0 ) {
            $this->post = get_post( $this->post_id );
        }
        return $this->post;
    }

    public function get_title() {
        $post = $this->get_post();
        return $post ? $post->post_title : '';
    }

    public function get_slug() {
        $post = $this->get_post();
        return $post ? $post->post_name : '';
    }

    public function is_active() {
        if ( $this->status !== 'active' ) {
            return false;
        }

        if ( ! empty( $this->ends_at ) && strtotime( $this->ends_at ) < time() ) {
            return false;
        }

        return true;
    }

    public function allows_multiple() {
        return ! empty( $this->settings['allow_multiple'] );
    }

    public function shows_results_before_vote() {
        return ! empty( $this->settings['show_results_before_vote'] );
    }

    public function get_vote_limit_method() {
        return isset( $this->settings['vote_limit'] ) ? $this->settings['vote_limit'] : 'ip';
    }

    public function get_default_settings() {
        return array(
            'allow_multiple'          => false,
            'show_results_before_vote' => false,
            'vote_limit'              => 'ip',
        );
    }
}
