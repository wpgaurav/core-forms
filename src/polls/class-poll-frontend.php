<?php

namespace Core_Forms\Polls;

class PollFrontend {

    private $plugin_file;
    private static $scripts_enqueued = false;

    public function __construct( $plugin_file ) {
        $this->plugin_file = $plugin_file;
    }

    public function hook() {
        add_shortcode( 'cf_poll', array( $this, 'render_poll_shortcode' ) );
        add_action( 'wp_ajax_cf_poll_vote', array( $this, 'handle_vote' ) );
        add_action( 'wp_ajax_nopriv_cf_poll_vote', array( $this, 'handle_vote' ) );
    }

    public function render_poll_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'slug' => '',
            'id'   => 0,
        ), $atts, 'cf_poll' );

        $poll = null;

        if ( ! empty( $atts['slug'] ) ) {
            $poll = cf_get_poll_by_slug( $atts['slug'] );
        } elseif ( ! empty( $atts['id'] ) ) {
            $poll = cf_get_poll( (int) $atts['id'] );
        }

        if ( ! $poll ) {
            return '<p class="cf-poll-error">' . __( 'Poll not found.', 'core-forms' ) . '</p>';
        }

        if ( ! $poll->is_active() ) {
            return $this->render_results( $poll, true );
        }

        $this->enqueue_assets();

        $has_voted = $this->has_user_voted( $poll );

        if ( $has_voted ) {
            return $this->render_results( $poll );
        }

        if ( $poll->shows_results_before_vote() ) {
            return $this->render_poll_with_results( $poll );
        }

        return $this->render_poll_form( $poll );
    }

    private function render_poll_form( Poll $poll ) {
        $input_type = $poll->allows_multiple() ? 'checkbox' : 'radio';

        ob_start();
        ?>
        <div class="cf-poll" data-poll-id="<?php echo esc_attr( $poll->id ); ?>">
            <div class="cf-poll-question"><?php echo esc_html( $poll->question ); ?></div>

            <form class="cf-poll-form" method="post">
                <input type="hidden" name="poll_id" value="<?php echo esc_attr( $poll->id ); ?>" />
                <?php wp_nonce_field( 'cf_poll_vote_' . $poll->id, 'cf_poll_nonce' ); ?>

                <div class="cf-poll-options">
                    <?php foreach ( $poll->options as $index => $option ) : ?>
                        <label class="cf-poll-option">
                            <input type="<?php echo $input_type; ?>" name="cf_poll_option<?php echo $poll->allows_multiple() ? '[]' : ''; ?>" value="<?php echo esc_attr( $index ); ?>" />
                            <span class="cf-option-text"><?php echo esc_html( $option ); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="cf-poll-actions">
                    <button type="submit" class="cf-poll-submit">
                        <?php _e( 'Vote', 'core-forms' ); ?>
                    </button>
                </div>

                <div class="cf-poll-message" style="display: none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_poll_with_results( Poll $poll ) {
        $results = cf_get_poll_results( $poll->id );
        $total = array_sum( $results );
        $input_type = $poll->allows_multiple() ? 'checkbox' : 'radio';

        ob_start();
        ?>
        <div class="cf-poll cf-poll-with-results" data-poll-id="<?php echo esc_attr( $poll->id ); ?>">
            <div class="cf-poll-question"><?php echo esc_html( $poll->question ); ?></div>

            <form class="cf-poll-form" method="post">
                <input type="hidden" name="poll_id" value="<?php echo esc_attr( $poll->id ); ?>" />
                <?php wp_nonce_field( 'cf_poll_vote_' . $poll->id, 'cf_poll_nonce' ); ?>

                <div class="cf-poll-options">
                    <?php foreach ( $poll->options as $index => $option ) :
                        $votes = isset( $results[ $index ] ) ? $results[ $index ] : 0;
                        $percentage = $total > 0 ? round( ( $votes / $total ) * 100 ) : 0;
                    ?>
                        <label class="cf-poll-option cf-poll-option-with-bar">
                            <input type="<?php echo $input_type; ?>" name="cf_poll_option<?php echo $poll->allows_multiple() ? '[]' : ''; ?>" value="<?php echo esc_attr( $index ); ?>" />
                            <span class="cf-option-content">
                                <span class="cf-option-text"><?php echo esc_html( $option ); ?></span>
                                <span class="cf-option-stats"><?php echo $percentage; ?>% (<?php echo $votes; ?>)</span>
                            </span>
                            <span class="cf-option-bar" style="width: <?php echo $percentage; ?>%;"></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="cf-poll-footer">
                    <span class="cf-poll-total"><?php printf( __( '%d votes', 'core-forms' ), $total ); ?></span>
                    <button type="submit" class="cf-poll-submit">
                        <?php _e( 'Vote', 'core-forms' ); ?>
                    </button>
                </div>

                <div class="cf-poll-message" style="display: none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_results( Poll $poll, $is_closed = false ) {
        $results = cf_get_poll_results( $poll->id );
        $total = array_sum( $results );

        ob_start();
        ?>
        <div class="cf-poll cf-poll-results" data-poll-id="<?php echo esc_attr( $poll->id ); ?>">
            <div class="cf-poll-question"><?php echo esc_html( $poll->question ); ?></div>

            <?php if ( $is_closed ) : ?>
                <div class="cf-poll-closed-notice"><?php _e( 'This poll is closed.', 'core-forms' ); ?></div>
            <?php endif; ?>

            <div class="cf-poll-results-list">
                <?php foreach ( $poll->options as $index => $option ) :
                    $votes = isset( $results[ $index ] ) ? $results[ $index ] : 0;
                    $percentage = $total > 0 ? round( ( $votes / $total ) * 100 ) : 0;
                ?>
                    <div class="cf-result-item">
                        <div class="cf-result-header">
                            <span class="cf-result-option"><?php echo esc_html( $option ); ?></span>
                            <span class="cf-result-percentage"><?php echo $percentage; ?>%</span>
                        </div>
                        <div class="cf-result-bar-container">
                            <div class="cf-result-bar" style="width: <?php echo $percentage; ?>%;"></div>
                        </div>
                        <div class="cf-result-votes"><?php printf( __( '%d votes', 'core-forms' ), $votes ); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cf-poll-total-votes">
                <?php printf( __( 'Total: %d votes', 'core-forms' ), $total ); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_vote() {
        $poll_id = isset( $_POST['poll_id'] ) ? (int) $_POST['poll_id'] : 0;

        if ( ! $poll_id || ! isset( $_POST['cf_poll_nonce'] ) || ! wp_verify_nonce( $_POST['cf_poll_nonce'], 'cf_poll_vote_' . $poll_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid request.', 'core-forms' ) ) );
        }

        $poll = cf_get_poll( $poll_id );

        if ( ! $poll || ! $poll->is_active() ) {
            wp_send_json_error( array( 'message' => __( 'This poll is no longer active.', 'core-forms' ) ) );
        }

        if ( $this->has_user_voted( $poll ) ) {
            wp_send_json_error( array( 'message' => __( 'You have already voted in this poll.', 'core-forms' ) ) );
        }

        $options = isset( $_POST['cf_poll_option'] ) ? $_POST['cf_poll_option'] : array();

        if ( ! is_array( $options ) ) {
            $options = array( $options );
        }

        $options = array_map( 'intval', $options );
        $options = array_filter( $options, function( $opt ) use ( $poll ) {
            return $opt >= 0 && $opt < count( $poll->options );
        } );

        if ( empty( $options ) ) {
            wp_send_json_error( array( 'message' => __( 'Please select an option.', 'core-forms' ) ) );
        }

        if ( ! $poll->allows_multiple() && count( $options ) > 1 ) {
            $options = array( $options[0] );
        }

        foreach ( $options as $option_index ) {
            $this->record_vote( $poll, $option_index );
        }

        $this->set_voted_cookie( $poll );

        $results_html = $this->render_results( $poll );

        wp_send_json_success( array(
            'message' => __( 'Thank you for voting!', 'core-forms' ),
            'html'    => $results_html,
        ) );
    }

    private function record_vote( Poll $poll, $option_index ) {
        global $wpdb;
        $table = $wpdb->prefix . 'cf_poll_votes';

        $data = array(
            'poll_id'      => $poll->id,
            'option_index' => $option_index,
            'ip_address'   => $this->get_ip_address(),
            'user_id'      => get_current_user_id() ?: null,
            'cookie_hash'  => $this->get_cookie_hash(),
        );

        $wpdb->insert( $table, $data );
    }

    private function has_user_voted( Poll $poll ) {
        $limit_method = $poll->get_vote_limit_method();

        if ( $limit_method === 'none' ) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'cf_poll_votes';

        switch ( $limit_method ) {
            case 'ip':
                $ip = $this->get_ip_address();
                return (bool) $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table} WHERE poll_id = %d AND ip_address = %s",
                    $poll->id,
                    $ip
                ) );

            case 'user':
                $user_id = get_current_user_id();
                if ( ! $user_id ) {
                    return false;
                }
                return (bool) $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table} WHERE poll_id = %d AND user_id = %d",
                    $poll->id,
                    $user_id
                ) );

            case 'cookie':
                $cookie_hash = $this->get_cookie_hash();
                if ( empty( $cookie_hash ) ) {
                    return false;
                }
                return (bool) $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$table} WHERE poll_id = %d AND cookie_hash = %s",
                    $poll->id,
                    $cookie_hash
                ) );
        }

        return false;
    }

    private function get_ip_address() {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            return sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
        }
        if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
            return sanitize_text_field( trim( $ips[0] ) );
        }
        return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '';
    }

    private function get_cookie_hash() {
        $cookie_name = 'cf_poll_voter';

        if ( isset( $_COOKIE[ $cookie_name ] ) ) {
            return sanitize_text_field( $_COOKIE[ $cookie_name ] );
        }

        return '';
    }

    private function set_voted_cookie( Poll $poll ) {
        $cookie_name = 'cf_poll_voter';

        if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
            $hash = wp_generate_password( 32, false );
            setcookie( $cookie_name, $hash, time() + ( 365 * DAY_IN_SECONDS ), COOKIEPATH, COOKIE_DOMAIN );
        }
    }

    private function enqueue_assets() {
        if ( self::$scripts_enqueued ) {
            return;
        }

        wp_enqueue_style(
            'core-forms-poll',
            plugins_url( 'assets/css/poll-frontend.css', $this->plugin_file ),
            array(),
            CORE_FORMS_VERSION
        );

        wp_enqueue_script(
            'core-forms-poll',
            plugins_url( 'assets/js/poll-frontend.js', $this->plugin_file ),
            array( 'jquery' ),
            CORE_FORMS_VERSION,
            true
        );

        wp_localize_script( 'core-forms-poll', 'cf_poll', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'strings'  => array(
                'error'   => __( 'An error occurred. Please try again.', 'core-forms' ),
                'loading' => __( 'Submitting...', 'core-forms' ),
            ),
        ) );

        self::$scripts_enqueued = true;
    }

}
