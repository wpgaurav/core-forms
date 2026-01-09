<?php

namespace Core_Forms\Polls;

class PollAdmin {

    private $plugin_file;

    public function __construct( $plugin_file ) {
        $this->plugin_file = $plugin_file;
    }

    public function hook() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_core-poll', array( $this, 'save_poll' ), 10, 2 );
        add_filter( 'manage_core-poll_posts_columns', array( $this, 'add_columns' ) );
        add_action( 'manage_core-poll_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function register_post_type() {
        $labels = array(
            'name'               => __( 'Polls', 'core-forms' ),
            'singular_name'      => __( 'Poll', 'core-forms' ),
            'add_new'            => __( 'Add New', 'core-forms' ),
            'add_new_item'       => __( 'Add New Poll', 'core-forms' ),
            'edit_item'          => __( 'Edit Poll', 'core-forms' ),
            'new_item'           => __( 'New Poll', 'core-forms' ),
            'view_item'          => __( 'View Poll', 'core-forms' ),
            'search_items'       => __( 'Search Polls', 'core-forms' ),
            'not_found'          => __( 'No polls found', 'core-forms' ),
            'not_found_in_trash' => __( 'No polls found in Trash', 'core-forms' ),
            'menu_name'          => __( 'Core Polls', 'core-forms' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'supports'           => array( 'title' ),
            'menu_icon'          => 'dashicons-chart-bar',
        );

        register_post_type( 'core-poll', $args );
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'Core Polls', 'core-forms' ),
            __( 'Core Polls', 'core-forms' ),
            'edit_posts',
            'edit.php?post_type=core-poll',
            '',
            'dashicons-chart-bar',
            27
        );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'cf_poll_options',
            __( 'Poll Options', 'core-forms' ),
            array( $this, 'render_options_meta_box' ),
            'core-poll',
            'normal',
            'high'
        );

        add_meta_box(
            'cf_poll_settings',
            __( 'Poll Settings', 'core-forms' ),
            array( $this, 'render_settings_meta_box' ),
            'core-poll',
            'side',
            'default'
        );

        add_meta_box(
            'cf_poll_shortcode',
            __( 'Shortcode', 'core-forms' ),
            array( $this, 'render_shortcode_meta_box' ),
            'core-poll',
            'side',
            'default'
        );

        add_meta_box(
            'cf_poll_results',
            __( 'Results', 'core-forms' ),
            array( $this, 'render_results_meta_box' ),
            'core-poll',
            'normal',
            'default'
        );
    }

    public function render_options_meta_box( $post ) {
        $poll = cf_get_poll_by_post_id( $post->ID );
        $question = $poll ? $poll->question : '';
        $options = $poll ? $poll->options : array( '', '' );

        if ( count( $options ) < 2 ) {
            $options = array( '', '' );
        }

        wp_nonce_field( 'cf_save_poll', 'cf_poll_nonce' );
        ?>
        <div class="cf-poll-editor">
            <p>
                <label for="cf_poll_question"><strong><?php _e( 'Question', 'core-forms' ); ?></strong></label>
                <textarea name="cf_poll_question" id="cf_poll_question" class="large-text" rows="2"><?php echo esc_textarea( $question ); ?></textarea>
            </p>

            <div class="cf-poll-options-list">
                <p><strong><?php _e( 'Answer Options', 'core-forms' ); ?></strong></p>
                <div id="cf-poll-options">
                    <?php foreach ( $options as $index => $option ) : ?>
                        <div class="cf-poll-option">
                            <span class="cf-option-number"><?php echo $index + 1; ?>.</span>
                            <input type="text" name="cf_poll_options[]" value="<?php echo esc_attr( $option ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Option text', 'core-forms' ); ?>" />
                            <button type="button" class="button cf-remove-option" title="<?php esc_attr_e( 'Remove', 'core-forms' ); ?>">&times;</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p>
                    <button type="button" class="button" id="cf-add-option">
                        <span class="dashicons dashicons-plus-alt2" style="vertical-align: middle;"></span>
                        <?php _e( 'Add Option', 'core-forms' ); ?>
                    </button>
                </p>
            </div>
        </div>
        <?php
    }

    public function render_settings_meta_box( $post ) {
        $poll = cf_get_poll_by_post_id( $post->ID );
        $settings = $poll ? $poll->settings : array();

        $defaults = array(
            'allow_multiple'           => false,
            'show_results_before_vote' => false,
            'vote_limit'               => 'ip',
        );
        $settings = array_merge( $defaults, $settings );

        $ends_at = $poll && ! empty( $poll->ends_at ) ? date( 'Y-m-d\TH:i', strtotime( $poll->ends_at ) ) : '';
        $status = $poll ? $poll->status : 'active';
        ?>
        <p>
            <label for="cf_poll_status"><strong><?php _e( 'Status', 'core-forms' ); ?></strong></label><br>
            <select name="cf_poll_status" id="cf_poll_status">
                <option value="active" <?php selected( $status, 'active' ); ?>><?php _e( 'Active', 'core-forms' ); ?></option>
                <option value="closed" <?php selected( $status, 'closed' ); ?>><?php _e( 'Closed', 'core-forms' ); ?></option>
            </select>
        </p>

        <p>
            <label for="cf_poll_ends_at"><strong><?php _e( 'End Date (optional)', 'core-forms' ); ?></strong></label><br>
            <input type="datetime-local" name="cf_poll_ends_at" id="cf_poll_ends_at" value="<?php echo esc_attr( $ends_at ); ?>" />
        </p>

        <p>
            <label for="cf_poll_vote_limit"><strong><?php _e( 'Vote Limiting', 'core-forms' ); ?></strong></label><br>
            <select name="cf_poll_settings[vote_limit]" id="cf_poll_vote_limit">
                <option value="ip" <?php selected( $settings['vote_limit'], 'ip' ); ?>><?php _e( 'By IP Address', 'core-forms' ); ?></option>
                <option value="user" <?php selected( $settings['vote_limit'], 'user' ); ?>><?php _e( 'By User (logged in only)', 'core-forms' ); ?></option>
                <option value="cookie" <?php selected( $settings['vote_limit'], 'cookie' ); ?>><?php _e( 'By Cookie', 'core-forms' ); ?></option>
                <option value="none" <?php selected( $settings['vote_limit'], 'none' ); ?>><?php _e( 'No Limit', 'core-forms' ); ?></option>
            </select>
        </p>

        <p>
            <label>
                <input type="checkbox" name="cf_poll_settings[allow_multiple]" value="1" <?php checked( $settings['allow_multiple'] ); ?> />
                <?php _e( 'Allow multiple choices', 'core-forms' ); ?>
            </label>
        </p>

        <p>
            <label>
                <input type="checkbox" name="cf_poll_settings[show_results_before_vote]" value="1" <?php checked( $settings['show_results_before_vote'] ); ?> />
                <?php _e( 'Show results before voting', 'core-forms' ); ?>
            </label>
        </p>
        <?php
    }

    public function render_shortcode_meta_box( $post ) {
        if ( $post->post_status !== 'publish' ) {
            echo '<p>' . __( 'Publish the poll to get the shortcode.', 'core-forms' ) . '</p>';
            return;
        }
        ?>
        <p><?php _e( 'Use this shortcode to embed the poll:', 'core-forms' ); ?></p>
        <input type="text" readonly="readonly" class="large-text" value="[cf_poll slug=&quot;<?php echo esc_attr( $post->post_name ); ?>&quot;]" onfocus="this.select();" />
        <?php
    }

    public function render_results_meta_box( $post ) {
        $poll = cf_get_poll_by_post_id( $post->ID );
        if ( ! $poll ) {
            echo '<p>' . __( 'Save the poll to see results.', 'core-forms' ) . '</p>';
            return;
        }

        $results = cf_get_poll_results( $poll->id );
        $total_votes = array_sum( $results );

        if ( $total_votes === 0 ) {
            echo '<p>' . __( 'No votes yet.', 'core-forms' ) . '</p>';
            return;
        }
        ?>
        <div class="cf-poll-results-admin">
            <p><strong><?php printf( __( 'Total Votes: %d', 'core-forms' ), $total_votes ); ?></strong></p>
            <?php foreach ( $poll->options as $index => $option ) :
                $votes = isset( $results[ $index ] ) ? $results[ $index ] : 0;
                $percentage = $total_votes > 0 ? round( ( $votes / $total_votes ) * 100 ) : 0;
            ?>
                <div class="cf-result-row">
                    <div class="cf-result-label"><?php echo esc_html( $option ); ?></div>
                    <div class="cf-result-bar-wrap">
                        <div class="cf-result-bar" style="width: <?php echo $percentage; ?>%;"></div>
                        <span class="cf-result-text"><?php echo $votes; ?> (<?php echo $percentage; ?>%)</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    public function save_poll( $post_id, $post ) {
        if ( ! isset( $_POST['cf_poll_nonce'] ) || ! wp_verify_nonce( $_POST['cf_poll_nonce'], 'cf_save_poll' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $question = isset( $_POST['cf_poll_question'] ) ? sanitize_textarea_field( $_POST['cf_poll_question'] ) : '';
        $options = isset( $_POST['cf_poll_options'] ) ? array_map( 'sanitize_text_field', $_POST['cf_poll_options'] ) : array();
        $options = array_filter( $options );
        $options = array_values( $options );

        $settings = isset( $_POST['cf_poll_settings'] ) ? $_POST['cf_poll_settings'] : array();
        $sanitized_settings = array(
            'allow_multiple'           => ! empty( $settings['allow_multiple'] ),
            'show_results_before_vote' => ! empty( $settings['show_results_before_vote'] ),
            'vote_limit'               => isset( $settings['vote_limit'] ) ? sanitize_text_field( $settings['vote_limit'] ) : 'ip',
        );

        $status = isset( $_POST['cf_poll_status'] ) ? sanitize_text_field( $_POST['cf_poll_status'] ) : 'active';
        $ends_at = isset( $_POST['cf_poll_ends_at'] ) && ! empty( $_POST['cf_poll_ends_at'] )
            ? date( 'Y-m-d H:i:s', strtotime( $_POST['cf_poll_ends_at'] ) )
            : null;

        global $wpdb;
        $table = $wpdb->prefix . 'cf_polls';

        $existing = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$table} WHERE post_id = %d", $post_id ) );

        $data = array(
            'post_id'  => $post_id,
            'question' => $question,
            'options'  => wp_json_encode( $options ),
            'settings' => wp_json_encode( $sanitized_settings ),
            'status'   => $status,
            'ends_at'  => $ends_at,
        );

        if ( $existing ) {
            $wpdb->update( $table, $data, array( 'id' => $existing->id ) );
        } else {
            $wpdb->insert( $table, $data );
        }
    }

    public function add_columns( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;
            if ( $key === 'title' ) {
                $new_columns['shortcode'] = __( 'Shortcode', 'core-forms' );
                $new_columns['votes'] = __( 'Votes', 'core-forms' );
                $new_columns['status'] = __( 'Status', 'core-forms' );
            }
        }
        return $new_columns;
    }

    public function render_column( $column, $post_id ) {
        $poll = cf_get_poll_by_post_id( $post_id );

        switch ( $column ) {
            case 'shortcode':
                $post = get_post( $post_id );
                echo '<code>[cf_poll slug="' . esc_html( $post->post_name ) . '"]</code>';
                break;

            case 'votes':
                if ( $poll ) {
                    $results = cf_get_poll_results( $poll->id );
                    echo array_sum( $results );
                } else {
                    echo '0';
                }
                break;

            case 'status':
                if ( $poll ) {
                    if ( $poll->is_active() ) {
                        echo '<span class="cf-status-badge cf-status-valid">' . __( 'Active', 'core-forms' ) . '</span>';
                    } else {
                        echo '<span class="cf-status-badge cf-status-spam">' . __( 'Closed', 'core-forms' ) . '</span>';
                    }
                }
                break;
        }
    }

    public function enqueue_assets( $hook ) {
        global $post_type;

        if ( $post_type !== 'core-poll' ) {
            return;
        }

        wp_enqueue_style( 'core-forms-admin', plugins_url( 'assets/css/admin.css', $this->plugin_file ), array(), CORE_FORMS_VERSION );

        wp_add_inline_script( 'jquery', "
            jQuery(document).ready(function($) {
                $('#cf-add-option').on('click', function() {
                    var count = $('#cf-poll-options .cf-poll-option').length + 1;
                    var html = '<div class=\"cf-poll-option\">' +
                        '<span class=\"cf-option-number\">' + count + '.</span>' +
                        '<input type=\"text\" name=\"cf_poll_options[]\" class=\"regular-text\" placeholder=\"" . esc_js( __( 'Option text', 'core-forms' ) ) . "\" />' +
                        '<button type=\"button\" class=\"button cf-remove-option\" title=\"" . esc_js( __( 'Remove', 'core-forms' ) ) . "\">&times;</button>' +
                        '</div>';
                    $('#cf-poll-options').append(html);
                });

                $(document).on('click', '.cf-remove-option', function() {
                    if ($('#cf-poll-options .cf-poll-option').length > 2) {
                        $(this).closest('.cf-poll-option').remove();
                        $('#cf-poll-options .cf-poll-option').each(function(i) {
                            $(this).find('.cf-option-number').text((i + 1) + '.');
                        });
                    } else {
                        alert('" . esc_js( __( 'A poll must have at least 2 options.', 'core-forms' ) ) . "');
                    }
                });
            });
        " );
    }

}
