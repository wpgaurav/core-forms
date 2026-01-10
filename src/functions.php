<?php

use Core_Forms\Form;
use Core_Forms\Submission;

/**
 * Get multiple forms
 * 
 * @param array $args
 * @return array
 */
function cf_get_forms( array $args = array() ) {
    $default_args = array(
        'post_type'           => 'core-form',
        'post_status'         => 'any',
        'posts_per_page'      => -1,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    );
    $args  = array_merge( $default_args, $args );
    $query = new WP_Query;
    $posts = $query->query( $args );
    $forms = array();
    foreach ( $posts as $post ) {
        try {
            $forms[] = cf_get_form( $post );
        } catch ( Exception $e ) {
            // Skip invalid forms
            continue;
        }
    }
    return $forms;
}

/**
 * Get a single form by ID or slug
 * 
 * @param $form_id_or_slug int|string|WP_Post
 * @return Form
 * @throws Exception
 */
function cf_get_form( $form_id_or_slug ) {

    if ( is_numeric( $form_id_or_slug ) || $form_id_or_slug instanceof WP_Post ) {
        $post = get_post( $form_id_or_slug );

        if ( ! $post instanceof WP_Post || $post->post_type !== 'core-form' ) {
            throw new Exception( 'Invalid form ID' );
        }
    } else {

        $query = new WP_Query;
        $posts = $query->query(
            array(
                'post_type'           => 'core-form',
                'name'                => $form_id_or_slug,
                'post_status'         => 'publish',
                'posts_per_page'      => 1,
                'ignore_sticky_posts' => true,
                'no_found_rows'       => true,
            )
        );
        if ( empty( $posts ) ) {
            throw new Exception( 'Invalid form slug' );
        }
        $post = $posts[0];
    }

    // get all post meta in a single call for performance
    $post_meta = get_post_meta( $post->ID );

    // grab & merge form settings
    $default_settings = array(
        'save_submissions'   => 1,
        'hide_after_success' => 0,
        'redirect_url'       => '',
        'required_fields'    => '',
        'email_fields'       => '',
        'custom_css'         => '',
        'custom_js'          => '',
    );
    $default_settings = apply_filters( 'cf_form_default_settings', $default_settings );
    $settings         = array();
    if ( ! empty( $post_meta['_cf_settings'][0] ) ) {
        $settings = (array) maybe_unserialize( $post_meta['_cf_settings'][0] );
    }
    $settings = array_merge( $default_settings, $settings );

    // grab & merge form messages
    $default_messages = array(
        'success'                => __( 'Thank you! We will be in touch soon.', 'core-forms' ),
        'invalid_email'          => __( 'Sorry, that email address looks invalid.', 'core-forms' ),
        'required_field_missing' => __( 'Please fill in the required fields.', 'core-forms' ),
        'error'                  => __( 'Oops. An error occurred.', 'core-forms' ),
        'recaptcha_failed'       => __( 'reCAPTCHA verification failed. Please try again.', 'core-forms' ),
        'recaptcha_low_score'    => __( 'Your submission appears to be spam. Please try again.', 'core-forms' ),
        'spam'                   => __( 'Your submission was flagged as spam.', 'core-forms' ),
        'math_captcha_failed'    => __( 'Incorrect answer to the math problem. Please try again.', 'core-forms' ),
    );
    $default_messages = apply_filters( 'cf_form_default_messages', $default_messages );
    $messages         = array();
    foreach ( $post_meta as $meta_key => $meta_values ) {
        if ( strpos( $meta_key, 'cf_message_' ) === 0 ) {
            $message_key              = substr( $meta_key, strlen( 'cf_message_' ) );
            $messages[ $message_key ] = (string) $meta_values[0];
        }
    }
    $messages = array_merge( $default_messages, $messages );

    // finally, create form instance
    $form           = new Form( $post->ID );
    $form->title    = $post->post_title;
    $form->slug     = $post->post_name;
    $form->markup   = $post->post_content;
    $form->settings = $settings;
    $form->messages = $messages;
    return $form;
}

/**
 * Get a form by slug only (ignores numeric check)
 *
 * @param string $slug
 * @return Form
 * @throws Exception
 */
function cf_get_form_by_slug( $slug ) {
    $query = new WP_Query;
    $posts = $query->query(
        array(
            'post_type'           => 'core-form',
            'name'                => $slug,
            'post_status'         => 'publish',
            'posts_per_page'      => 1,
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        )
    );
    if ( empty( $posts ) ) {
        throw new Exception( 'Invalid form slug' );
    }

    return cf_get_form( $posts[0] );
}

/**
 * Count form submissions
 * 
 * @param $form_id
 * @return int
 */
function cf_count_form_submissions( $form_id ) {
    global $wpdb;
    $table  = $wpdb->prefix . 'cf_submissions';
    $result = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} s WHERE s.form_id = %d;", $form_id ) );
    return (int) $result;
}

/**
 * Get form submissions
 * 
 * @param $form_id
 * @param array $args
 * @return Submission[]
 */
function cf_get_form_submissions( $form_id, array $args = array() ) {
    $default_args = array(
        'offset' => 0,
        'limit'  => 1000,
    );
    $args         = array_merge( $default_args, $args );

    global $wpdb;
    $table       = $wpdb->prefix . 'cf_submissions';
    $results     = $wpdb->get_results( $wpdb->prepare( "SELECT s.* FROM {$table} s WHERE s.form_id = %d ORDER BY s.submitted_at DESC LIMIT %d, %d;", $form_id, $args['offset'], $args['limit'] ), OBJECT_K );
    $submissions = array();
    foreach ( $results as $key => $object ) {
        $submission          = Submission::from_object( $object );
        $submissions[ $key ] = $submission;
    }
    return $submissions;
}

/**
 * Get a single form submission
 * 
 * @param int $submission_id
 * @return Submission
 */
function cf_get_form_submission( $submission_id ) {
    global $wpdb;
    $table      = $wpdb->prefix . 'cf_submissions';
    $object     = $wpdb->get_row( $wpdb->prepare( "SELECT s.* FROM {$table} s WHERE s.id = %d;", $submission_id ), OBJECT );
    $submission = Submission::from_object( $object );
    return $submission;
}

/**
 * Get plugin settings
 * 
 * @return array
 */
function cf_get_settings() {
    $default_settings = array(
        'enable_nonce'     => 0,
        'load_stylesheet'  => 0,
        'wrapper_tag'      => 'p',
        'google_recaptcha' => array(
            'site_key'   => '',
            'secret_key' => '',
        ),
        'cloudflare_turnstile' => array(
            'site_key'   => '',
            'secret_key' => '',
        ),
        'reply_from_email' => '',
    );

    $settings = get_option( 'cf_settings', null );

    // prevent a SQL query when option does not yet exist
    if ( $settings === null ) {
        update_option( 'cf_settings', array(), true );
        $settings = array();
    }

    // merge with default settings
    $settings = array_merge( $default_settings, $settings );
    
    // Ensure nested arrays are properly merged
    if ( isset( $default_settings['google_recaptcha'] ) ) {
        $settings['google_recaptcha'] = array_merge(
            $default_settings['google_recaptcha'],
            isset( $settings['google_recaptcha'] ) ? $settings['google_recaptcha'] : array()
        );
    }

    if ( isset( $default_settings['cloudflare_turnstile'] ) ) {
        $settings['cloudflare_turnstile'] = array_merge(
            $default_settings['cloudflare_turnstile'],
            isset( $settings['cloudflare_turnstile'] ) ? $settings['cloudflare_turnstile'] : array()
        );
    }

    /**
    * Filters the global Core Forms settings
    *
    * @param array $settings
    */
    $settings = apply_filters( 'cf_settings', $settings );

    return $settings;
}

/**
* Get element from array, allows for dot notation eg: "foo.bar"
*
* @param array $array
* @param string $key
* @param mixed $default
* @return mixed
*/
function cf_array_get( $array, $key, $default = null ) {
    if ( is_null( $key ) ) {
        return $array;
    }

    if ( isset( $array[ $key ] ) ) {
        return $array[ $key ];
    }

    foreach ( explode( '.', $key ) as $segment ) {
        if ( ! is_array( $array ) || ! array_key_exists( $segment, $array ) ) {
            return $default;
        }

        $array = $array[ $segment ];
    }

    return $array;
}

/**
 * Processes template tags like {{user.user_email}}
 *
 * @param string $template
 *
 * @return string
 */
function cf_template( $template ) {
    $replacers = new Core_Forms\TagReplacers();
    $tags      = array(
        'user'       => array( $replacers, 'user' ),
        'post'       => array( $replacers, 'post' ),
        'url_params' => array( $replacers, 'url_params' ),
    );

    /**
    * Filters the available tags in Core Forms templates, like {{user.user_email}}.
    *
    * Can be used to add simple scalar replacements or more advanced replacement functions that accept a parameter.
    *
    * @param array $tags
    */
    $tags = apply_filters( 'cf_template_tags', $tags );

    $template = preg_replace_callback(
        '/\{\{ *(\w+)(?:\.([.\w]+))? *(?:\|\| *(\w+))? *\}\}/',
        function( $matches ) use ( $tags ) {
            $tag     = $matches[1];
            $param   = ! isset( $matches[2] ) ? '' : $matches[2];
            $default = ! isset( $matches[3] ) ? '' : $matches[3];

            // do not change anything if we have no replacer with that key, could be custom user logic or another plugin.
            if ( ! isset( $tags[ $tag ] ) ) {
                return $matches[0];
            }

            $replacement = $tags[ $tag ];
            $value       = is_callable( $replacement ) ? call_user_func_array( $replacement, array( $param ) ) : $replacement;
            return ! empty( $value ) ? $value : $default;
        },
        $template
    );

    return $template;
}

/**
 * Replace data variables in string
 * 
 * @param string $string
 * @param Submission $submission
 * @param Closure|string $escape_function
 * @return string
 */
function cf_replace_data_variables( $string, Submission $submission, $escape_function = null ) {
    $data = ( !empty( $submission->data ) ? $submission->data : array() );
    $submission_fields = array( 'CF_TIMESTAMP', 'CF_USER_AGENT', 'CF_IP_ADDRESS', 'CF_REFERRER_URL' );

    return preg_replace_callback(
        '/\[(.+?)\]/',
        function( $matches ) use ( $submission, $submission_fields, $escape_function  ) {
            $key = $matches[1];

            if ( in_array( $key, $submission_fields ) ) {
                $replacement = '';

                switch ( $key ) {
                    case 'CF_TIMESTAMP' :
                        $replacement = $submission->submitted_at;
                        break;
                    case 'CF_USER_AGENT' :
                        $replacement = $submission->user_agent;
                        break;
                    case 'CF_IP_ADDRESS' :
                        $replacement = $submission->ip_address;
                        break;
                    case 'CF_REFERRER_URL' :
                        $replacement = $submission->referer_url;
                        break;
                    default :
                        $replacement = '';
                        break;
                }
            } else {
                // replace spaces in name with underscores to match PHP requirement for keys in $_POST superglobal
                $key         = str_replace( ' ', '_', $key );
                $replacement = cf_array_get( $submission->data, $key, '' );
                $replacement = cf_field_value( $replacement, 0, $escape_function );
            }

            return $replacement;
        },
        $string
    );
}

/**
* Returns a formatted & HTML-escaped field value. Detects file-, array- and date-types.
*
* Caveat: if value is a file, an HTML string is returned (which means email action should use "Content-Type: html" when it includes a file field).
*
* @param string|array $value
* @param int $limit
* @param Closure|string $escape_function
* @return string
*/
function cf_field_value( $value, $limit = 0, $escape_function = 'esc_html' ) {
    if ( $value === '' ) {
        return $value;
    }

    if ( cf_is_file( $value ) ) {
        if ( ! is_array( $value )
            || ! isset( $value['name'] )
            || ! isset( $value['size'] )
            || ! isset( $value['type'] )
            || ! isset( $value['attachment_id'] ) ) {
            return false;
        }
        
        // Verify attachment exists
        if ( get_post( $value['attachment_id'] ) == null ) {
            return __( 'File not found', 'core-forms' );
        }

        $file_url = isset( $value['url'] ) ? $value['url'] : '';
        if ( isset( $value['attachment_id'] ) && apply_filters( 'cf_file_upload_use_direct_links', false ) === false ) {
            $file_url = admin_url( sprintf( 'post.php?action=edit&post=%d', $value['attachment_id'] ) );
        }
        
        $short_name = substr( $value['name'], 0, 20 );
        $suffix     = strlen( $value['name'] ) > 20 ? '...' : '';
        return sprintf( '<a href="%s">%s%s</a> (%s)', esc_url( $file_url ), esc_html( $short_name ), esc_html( $suffix ), cf_human_filesize( $value['size'] ) );
    }

    if ( cf_is_date( $value ) ) {
        $date_format = get_option( 'date_format' );
        return gmdate( $date_format, strtotime( str_replace( '/', '-', $value ) ) );
    }

    // join array-values with comma
    if ( is_array( $value ) ) {
        $value = join( ', ', $value );
    }

    // limit string to certain length
    if ( $limit > 0 ) {
        $limited = strlen( $value ) > $limit;
        $value   = substr( $value, 0, $limit );

        if ( $limited ) {
            $value .= '...';
        }
    }

    // escape value
    if ( $escape_function !== null && is_callable( $escape_function ) ) {
        $value = $escape_function( $value );
    }

    // add line breaks, if not string limited to certain length
    if ( $limit === 0 ) {
        $value = nl2br( $value );
    }

    return $value;
}

/**
* Returns true if value is a "file"
*
* @param mixed $value
* @return bool
*/
function cf_is_file( $value ) {
    return is_array( $value )
        && isset( $value['name'] )
        && isset( $value['size'] )
        && isset( $value['type'] );
}

/**
* Returns true if value looks like a date-string submitted from a <input type="date">
* @param mixed $value
* @return bool
*/
function cf_is_date( $value ) {
    if ( ! is_string( $value )
            || strlen( $value ) !== 10
            || (int) preg_match( '/\d{2,4}[-\/]\d{2}[-\/]\d{2,4}/', $value ) === 0 ) {
        return false;
    }

    $timestamp = strtotime( $value );
    return $timestamp != false;
}

/**
 * Convert file size to human readable format
 * 
 * @param int $size
 * @param int $precision
 * @return string
*/
function cf_human_filesize( $size, $precision = 2 ) {
    for ( $i = 0; ( $size / 1024 ) > 0.9; $i++, $size /= 1024 ) {
        // nothing, loop logic contains everything
    }
    $steps = array( 'B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );
    return round( $size, $precision ) . $steps[ $i ];
}

/**
 * Gets all the form tabs to show in the admin.
 * @param Form $form
 * @return array
 */
function cf_get_admin_tabs( Form $form ) {
    $tabs = array(
        'fields'   => __( 'Fields', 'core-forms' ),
        'messages' => __( 'Messages', 'core-forms' ),
        'settings' => __( 'Settings', 'core-forms' ),
        'actions'  => __( 'Actions', 'core-forms' ),
    );

    if ( $form->settings['save_submissions'] ) {
        $tabs['submissions'] = __( 'Submissions', 'core-forms' );
    }
    return apply_filters( 'cf_admin_tabs', $tabs, $form );
}

/**
 * Run database migrations if needed
 */
function _cf_maybe_run_migrations() {
    $db_version = get_option( 'cf_db_version', '1.0' );

    // Migration 3.1.2: Add is_spam column
    if ( version_compare( $db_version, '3.1.2', '<' ) ) {
        _cf_create_submissions_table();
        update_option( 'cf_db_version', '3.1.2' );
    }
}

/**
 * Plugin activation handler
 */
function _cf_on_plugin_activation() {
    if ( is_multisite() ) {
        _cf_on_plugin_activation_multisite();
        return;
    }

    // install tables for regular wp install
    _cf_create_submissions_table();
    _cf_create_email_logs_table();

    // add "edit_forms" cap to user that activated the plugin
    $user = wp_get_current_user();
    $user->add_cap( 'edit_forms', true );
}

/**
 * Multisite activation handler
 */
function _cf_on_plugin_activation_multisite() {
    $added_caps = array();

    foreach ( get_sites( array( 'number' => PHP_INT_MAX ) ) as $site ) {
        switch_to_blog( (int) $site->blog_id );

        // install tables for current blog
        _cf_create_submissions_table();
        _cf_create_email_logs_table();

        // iterate through current blog admins
        foreach ( get_users(
            array(
                'blog_id' => (int) $site->blog_id,
                'role'    => 'administrator',
                'fields'  => 'ID',
            )
        ) as $admin_id ) {
            if ( ! (int) $admin_id || in_array( $admin_id, $added_caps ) ) {
                continue;
            }

            // add "edit_forms" cap to site admin
            $user = new \WP_User( (int) $admin_id );
            $user->add_cap( 'edit_forms', true );

            $added_caps[] = $admin_id;
        }

        restore_current_blog();
    }
}

/**
 * Create submissions table
 */
function _cf_create_submissions_table() {
    /** @var wpdb */
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // create table for storing submissions
    $table = $wpdb->prefix . 'cf_submissions';
    $wpdb->query(
        "CREATE TABLE IF NOT EXISTS {$table}(
        `id` INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
        `form_id` INT UNSIGNED NOT NULL,
        `data` TEXT NOT NULL,
        `user_agent` TEXT NULL,
        `ip_address` VARCHAR(255) NULL,
        `referer_url` TEXT NULL,
        `is_spam` TINYINT(1) NOT NULL DEFAULT 0,
        `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) {$charset_collate};"
    );

    // Add is_spam column if it doesn't exist (for existing installations)
    $column_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'is_spam'" );
    if ( empty( $column_exists ) ) {
        $wpdb->query( "ALTER TABLE {$table} ADD COLUMN `is_spam` TINYINT(1) NOT NULL DEFAULT 0 AFTER `referer_url`" );
    }
}

/**
 * Add user to blog handler
 */
function _cf_on_add_user_to_blog( $user_id, $role, $blog_id ) {
    if ( 'administrator' !== $role ) {
        return;
    }

    // add "edit_forms" cap to site admin
    $user = new \WP_User( (int) $user_id );
    $user->add_cap( 'edit_forms', true );
}

/**
 * New site handler
 */
function _cf_on_wp_insert_site( \WP_Site $site ) {
    switch_to_blog( (int) $site->blog_id );
    _cf_create_submissions_table();
    _cf_create_email_logs_table();
    restore_current_blog();
}

/**
 * Get all submissions across all forms
 *
 * @param array $args Query arguments
 * @return Submission[]
 */
function cf_get_all_submissions( array $args = array() ) {
    $defaults = array(
        'form_id'   => 0,
        'search'    => '',
        'is_spam'   => null,
        'date_from' => '',
        'date_to'   => '',
        'offset'    => 0,
        'limit'     => 50,
        'orderby'   => 'submitted_at',
        'order'     => 'DESC',
    );
    $args = array_merge( $defaults, $args );

    global $wpdb;
    $table = $wpdb->prefix . 'cf_submissions';

    $where_clauses = array( '1=1' );
    $prepare_values = array();

    if ( ! empty( $args['form_id'] ) ) {
        $where_clauses[] = 's.form_id = %d';
        $prepare_values[] = (int) $args['form_id'];
    }

    if ( $args['is_spam'] !== null ) {
        $where_clauses[] = 's.is_spam = %d';
        $prepare_values[] = $args['is_spam'] ? 1 : 0;
    }

    if ( ! empty( $args['search'] ) ) {
        $where_clauses[] = 's.data LIKE %s';
        $prepare_values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
    }

    if ( ! empty( $args['date_from'] ) ) {
        $where_clauses[] = 's.submitted_at >= %s';
        $prepare_values[] = $args['date_from'] . ' 00:00:00';
    }

    if ( ! empty( $args['date_to'] ) ) {
        $where_clauses[] = 's.submitted_at <= %s';
        $prepare_values[] = $args['date_to'] . ' 23:59:59';
    }

    $allowed_orderby = array( 'submitted_at', 'form_id', 'id' );
    $orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'submitted_at';
    $order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

    $where = implode( ' AND ', $where_clauses );
    $prepare_values[] = (int) $args['offset'];
    $prepare_values[] = (int) $args['limit'];

    $sql = "SELECT s.* FROM {$table} s WHERE {$where} ORDER BY s.{$orderby} {$order} LIMIT %d, %d";
    $results = $wpdb->get_results( $wpdb->prepare( $sql, $prepare_values ), OBJECT_K );

    $submissions = array();
    foreach ( $results as $key => $object ) {
        $submissions[ $key ] = Submission::from_object( $object );
    }

    return $submissions;
}

/**
 * Count all submissions across all forms
 *
 * @param array $args Query arguments
 * @return int
 */
function cf_count_all_submissions( array $args = array() ) {
    $defaults = array(
        'form_id'   => 0,
        'search'    => '',
        'is_spam'   => null,
        'date_from' => '',
        'date_to'   => '',
    );
    $args = array_merge( $defaults, $args );

    global $wpdb;
    $table = $wpdb->prefix . 'cf_submissions';

    $where_clauses = array( '1=1' );
    $prepare_values = array();

    if ( ! empty( $args['form_id'] ) ) {
        $where_clauses[] = 's.form_id = %d';
        $prepare_values[] = (int) $args['form_id'];
    }

    if ( $args['is_spam'] !== null ) {
        $where_clauses[] = 's.is_spam = %d';
        $prepare_values[] = $args['is_spam'] ? 1 : 0;
    }

    if ( ! empty( $args['search'] ) ) {
        $where_clauses[] = 's.data LIKE %s';
        $prepare_values[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
    }

    if ( ! empty( $args['date_from'] ) ) {
        $where_clauses[] = 's.submitted_at >= %s';
        $prepare_values[] = $args['date_from'] . ' 00:00:00';
    }

    if ( ! empty( $args['date_to'] ) ) {
        $where_clauses[] = 's.submitted_at <= %s';
        $prepare_values[] = $args['date_to'] . ' 23:59:59';
    }

    $where = implode( ' AND ', $where_clauses );

    if ( ! empty( $prepare_values ) ) {
        $result = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} s WHERE {$where}", $prepare_values ) );
    } else {
        $result = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} s WHERE {$where}" );
    }

    return (int) $result;
}

/**
 * Search submissions by data content
 *
 * @param string $search Search term
 * @param int $form_id Optional form ID to limit search
 * @param array $args Additional query arguments
 * @return Submission[]
 */
function cf_search_submissions( $search, $form_id = 0, array $args = array() ) {
    $args['search'] = $search;
    if ( $form_id > 0 ) {
        $args['form_id'] = $form_id;
    }
    return cf_get_all_submissions( $args );
}

/**
 * Get replies for a submission
 *
 * @param int $submission_id Submission ID
 * @return Reply[]
 */
function cf_get_submission_replies( $submission_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'cf_submission_replies';

    $results = $wpdb->get_results(
        $wpdb->prepare( "SELECT * FROM {$table} WHERE submission_id = %d ORDER BY sent_at DESC", $submission_id )
    );

    $replies = array();
    foreach ( $results as $object ) {
        $replies[] = Reply::from_object( $object );
    }

    return $replies;
}

/**
 * Get count of replies for a submission
 *
 * @param int $submission_id Submission ID
 * @return int
 */
function cf_count_submission_replies( $submission_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'cf_submission_replies';

    return (int) $wpdb->get_var(
        $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE submission_id = %d", $submission_id )
    );
}

/**
 * Get default reply sender email
 *
 * @return string
 */
function cf_get_default_reply_email() {
    $settings = cf_get_settings();
    if ( ! empty( $settings['reply_from_email'] ) ) {
        return $settings['reply_from_email'];
    }
    return get_option( 'admin_email' );
}

/**
 * Get a poll by ID
 *
 * @param int $poll_id Poll ID
 * @return Polls\Poll|null
 */
function cf_get_poll( $poll_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'cf_polls';

    $object = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $poll_id ) );

    if ( ! $object ) {
        return null;
    }

    return Polls\Poll::from_object( $object );
}

/**
 * Get a poll by post ID
 *
 * @param int $post_id Post ID
 * @return Polls\Poll|null
 */
function cf_get_poll_by_post_id( $post_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'cf_polls';

    $object = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE post_id = %d", $post_id ) );

    if ( ! $object ) {
        return null;
    }

    return Polls\Poll::from_object( $object );
}

/**
 * Get a poll by slug
 *
 * @param string $slug Post slug
 * @return Polls\Poll|null
 */
function cf_get_poll_by_slug( $slug ) {
    $post = get_page_by_path( $slug, OBJECT, 'core-poll' );

    if ( ! $post ) {
        return null;
    }

    return cf_get_poll_by_post_id( $post->ID );
}

/**
 * Get poll results
 *
 * @param int $poll_id Poll ID
 * @return array Associative array of option_index => vote_count
 */
function cf_get_poll_results( $poll_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'cf_poll_votes';

    $results = $wpdb->get_results( $wpdb->prepare(
        "SELECT option_index, COUNT(*) as votes FROM {$table} WHERE poll_id = %d GROUP BY option_index",
        $poll_id
    ) );

    $counts = array();
    foreach ( $results as $row ) {
        $counts[ (int) $row->option_index ] = (int) $row->votes;
    }

    return $counts;
}

/**
 * Get total votes for a poll
 *
 * @param int $poll_id Poll ID
 * @return int
 */
function cf_get_poll_total_votes( $poll_id ) {
    global $wpdb;
    $table = $wpdb->prefix . 'cf_poll_votes';

    return (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table} WHERE poll_id = %d",
        $poll_id
    ) );
}

/**
 * Enhance form markup with accessibility attributes
 * Automatically adds missing ARIA attributes, associates labels, etc.
 *
 * @param string $markup Form markup HTML
 * @param Form $form Form object
 * @return string Enhanced markup
 */
function cf_enhance_accessibility( $markup, $form = null ) {
    if ( empty( $markup ) ) {
        return $markup;
    }

    $dom = new \DOMDocument();
    libxml_use_internal_errors( true );
    $dom->loadHTML(
        '<?xml encoding="UTF-8"><div id="cf-a11y-wrapper">' . $markup . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();

    $xpath = new \DOMXPath( $dom );

    // Track IDs we've seen to avoid duplicates
    $seen_ids = array();
    $id_counter = 1;

    // 1. Add aria-required to required fields that don't have it
    $required_fields = $xpath->query( '//*[@required and not(@aria-required)]' );
    foreach ( $required_fields as $field ) {
        $field->setAttribute( 'aria-required', 'true' );
    }

    // 2. Ensure all form inputs have IDs (needed for label association)
    $inputs = $xpath->query( '//input[not(@type="hidden") and not(@type="submit")] | //textarea | //select' );
    foreach ( $inputs as $input ) {
        if ( ! $input->hasAttribute( 'id' ) ) {
            $name = $input->getAttribute( 'name' );
            $base_id = $name ? preg_replace( '/[^a-z0-9_-]/i', '_', $name ) : 'cf_field';
            $new_id = $base_id;
            while ( isset( $seen_ids[ $new_id ] ) ) {
                $new_id = $base_id . '_' . $id_counter++;
            }
            $input->setAttribute( 'id', $new_id );
            $seen_ids[ $new_id ] = true;
        } else {
            $seen_ids[ $input->getAttribute( 'id' ) ] = true;
        }
    }

    // 3. Associate orphan labels with their inputs (labels without "for" attribute)
    $labels = $xpath->query( '//label[not(@for)]' );
    foreach ( $labels as $label ) {
        // Check if label wraps an input
        $wrapped_input = $xpath->query( './/input | .//textarea | .//select', $label );
        if ( $wrapped_input->length > 0 ) {
            // Label wraps input - get or set ID
            $input = $wrapped_input->item( 0 );
            if ( $input->hasAttribute( 'id' ) ) {
                $label->setAttribute( 'for', $input->getAttribute( 'id' ) );
            }
        } else {
            // Look for adjacent input (next sibling or in same parent)
            $parent = $label->parentNode;
            $adjacent_input = $xpath->query( './/input[not(@type="hidden")] | .//textarea | .//select', $parent );
            if ( $adjacent_input->length > 0 ) {
                $input = $adjacent_input->item( 0 );
                if ( $input->hasAttribute( 'id' ) && ! $label->hasAttribute( 'for' ) ) {
                    $label->setAttribute( 'for', $input->getAttribute( 'id' ) );
                }
            }
        }
    }

    // 4. Add autocomplete attributes for common field types
    $autocomplete_map = array(
        'email'      => 'email',
        'name'       => 'name',
        'first_name' => 'given-name',
        'last_name'  => 'family-name',
        'phone'      => 'tel',
        'tel'        => 'tel',
        'address'    => 'street-address',
        'city'       => 'address-level2',
        'state'      => 'address-level1',
        'zip'        => 'postal-code',
        'postal'     => 'postal-code',
        'country'    => 'country-name',
        'url'        => 'url',
        'website'    => 'url',
    );

    $all_inputs = $xpath->query( '//input[not(@autocomplete) and not(@type="hidden") and not(@type="submit") and not(@type="checkbox") and not(@type="radio")] | //textarea[not(@autocomplete)] | //select[not(@autocomplete)]' );
    foreach ( $all_inputs as $input ) {
        $name = strtolower( $input->getAttribute( 'name' ) );
        $type = $input->getAttribute( 'type' );

        // Set autocomplete based on input type
        if ( $type === 'email' ) {
            $input->setAttribute( 'autocomplete', 'email' );
        } elseif ( $type === 'tel' ) {
            $input->setAttribute( 'autocomplete', 'tel' );
        } elseif ( $type === 'url' ) {
            $input->setAttribute( 'autocomplete', 'url' );
        } else {
            // Check name against map
            foreach ( $autocomplete_map as $key => $value ) {
                if ( strpos( $name, $key ) !== false ) {
                    $input->setAttribute( 'autocomplete', $value );
                    break;
                }
            }
        }
    }

    // 5. Ensure fieldsets have legends
    $fieldsets = $xpath->query( '//fieldset[not(legend)]' );
    foreach ( $fieldsets as $fieldset ) {
        $legend = $dom->createElement( 'legend', __( 'Field Group', 'core-forms' ) );
        $legend->setAttribute( 'class', 'screen-reader-text' );
        $fieldset->insertBefore( $legend, $fieldset->firstChild );
    }

    // 6. Add input type attributes for better mobile support
    $email_inputs = $xpath->query( '//input[@type="text" and contains(@name, "email")]' );
    foreach ( $email_inputs as $input ) {
        $input->setAttribute( 'type', 'email' );
        $input->setAttribute( 'inputmode', 'email' );
    }

    $tel_inputs = $xpath->query( '//input[@type="text" and (contains(@name, "phone") or contains(@name, "tel"))]' );
    foreach ( $tel_inputs as $input ) {
        $input->setAttribute( 'type', 'tel' );
        $input->setAttribute( 'inputmode', 'tel' );
    }

    $url_inputs = $xpath->query( '//input[@type="text" and (contains(@name, "url") or contains(@name, "website"))]' );
    foreach ( $url_inputs as $input ) {
        $input->setAttribute( 'type', 'url' );
        $input->setAttribute( 'inputmode', 'url' );
    }

    // Get the wrapper content
    $wrapper = $dom->getElementById( 'cf-a11y-wrapper' );
    if ( $wrapper ) {
        $html = '';
        foreach ( $wrapper->childNodes as $child ) {
            $html .= $dom->saveHTML( $child );
        }
        return $html;
    }

    return $markup;
}

// Hook accessibility enhancement to form markup
add_filter( 'cf_form_markup', 'cf_enhance_accessibility', 100, 2 );

/**
 * Create email logs table
 */
function _cf_create_email_logs_table() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table = $wpdb->prefix . 'cf_email_logs';

    $wpdb->query(
        "CREATE TABLE IF NOT EXISTS {$table}(
        `id` INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
        `form_id` INT UNSIGNED NOT NULL,
        `submission_id` INT UNSIGNED NULL,
        `to_email` VARCHAR(255) NOT NULL,
        `from_email` VARCHAR(255) NULL,
        `subject` VARCHAR(500) NULL,
        `message` LONGTEXT NULL,
        `headers` TEXT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
        `error_message` TEXT NULL,
        `action_type` VARCHAR(50) DEFAULT 'email',
        `sent_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX form_idx (form_id),
        INDEX submission_idx (submission_id),
        INDEX status_idx (status)
    ) {$charset_collate};"
    );
}

/**
 * Log an email attempt
 *
 * @param array $data Email data
 * @return int|false Log ID or false on failure
 */
function cf_log_email( array $data ) {
    global $wpdb;
    $table = $wpdb->prefix . 'cf_email_logs';

    $defaults = array(
        'form_id'       => 0,
        'submission_id' => null,
        'to_email'      => '',
        'from_email'    => '',
        'subject'       => '',
        'message'       => '',
        'headers'       => '',
        'status'        => 'pending',
        'error_message' => null,
        'action_type'   => 'email',
    );
    $data = array_merge( $defaults, $data );

    $result = $wpdb->insert(
        $table,
        array(
            'form_id'       => (int) $data['form_id'],
            'submission_id' => $data['submission_id'] ? (int) $data['submission_id'] : null,
            'to_email'      => sanitize_email( $data['to_email'] ),
            'from_email'    => sanitize_email( $data['from_email'] ),
            'subject'       => sanitize_text_field( $data['subject'] ),
            'message'       => $data['message'],
            'headers'       => is_array( $data['headers'] ) ? implode( "\n", $data['headers'] ) : $data['headers'],
            'status'        => sanitize_key( $data['status'] ),
            'error_message' => $data['error_message'],
            'action_type'   => sanitize_key( $data['action_type'] ),
        ),
        array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
    );

    return $result ? $wpdb->insert_id : false;
}

/**
 * Update email log status
 *
 * @param int $log_id Log ID
 * @param string $status Status (sent, failed, pending)
 * @param string|null $error_message Error message if failed
 * @return bool
 */
function cf_update_email_log_status( $log_id, $status, $error_message = null ) {
    global $wpdb;
    $table = $wpdb->prefix . 'cf_email_logs';

    $data = array( 'status' => sanitize_key( $status ) );
    $formats = array( '%s' );

    if ( $error_message !== null ) {
        $data['error_message'] = $error_message;
        $formats[] = '%s';
    }

    return (bool) $wpdb->update( $table, $data, array( 'id' => $log_id ), $formats, array( '%d' ) );
}

/**
 * Get email logs
 *
 * @param array $args Query arguments
 * @return array
 */
function cf_get_email_logs( array $args = array() ) {
    global $wpdb;
    $table = $wpdb->prefix . 'cf_email_logs';

    $defaults = array(
        'form_id'       => 0,
        'submission_id' => 0,
        'status'        => '',
        'search'        => '',
        'date_from'     => '',
        'date_to'       => '',
        'offset'        => 0,
        'limit'         => 50,
        'orderby'       => 'sent_at',
        'order'         => 'DESC',
    );
    $args = array_merge( $defaults, $args );

    $where_clauses = array( '1=1' );
    $prepare_values = array();

    if ( ! empty( $args['form_id'] ) ) {
        $where_clauses[] = 'form_id = %d';
        $prepare_values[] = (int) $args['form_id'];
    }

    if ( ! empty( $args['submission_id'] ) ) {
        $where_clauses[] = 'submission_id = %d';
        $prepare_values[] = (int) $args['submission_id'];
    }

    if ( ! empty( $args['status'] ) ) {
        $where_clauses[] = 'status = %s';
        $prepare_values[] = $args['status'];
    }

    if ( ! empty( $args['search'] ) ) {
        $where_clauses[] = '(to_email LIKE %s OR subject LIKE %s)';
        $search_like = '%' . $wpdb->esc_like( $args['search'] ) . '%';
        $prepare_values[] = $search_like;
        $prepare_values[] = $search_like;
    }

    if ( ! empty( $args['date_from'] ) ) {
        $where_clauses[] = 'sent_at >= %s';
        $prepare_values[] = $args['date_from'] . ' 00:00:00';
    }

    if ( ! empty( $args['date_to'] ) ) {
        $where_clauses[] = 'sent_at <= %s';
        $prepare_values[] = $args['date_to'] . ' 23:59:59';
    }

    $where = implode( ' AND ', $where_clauses );
    $orderby = in_array( $args['orderby'], array( 'sent_at', 'id', 'status' ), true ) ? $args['orderby'] : 'sent_at';
    $order = strtoupper( $args['order'] ) === 'ASC' ? 'ASC' : 'DESC';

    $sql = "SELECT * FROM {$table} WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
    $prepare_values[] = (int) $args['limit'];
    $prepare_values[] = (int) $args['offset'];

    if ( ! empty( $prepare_values ) ) {
        $results = $wpdb->get_results( $wpdb->prepare( $sql, $prepare_values ) );
    } else {
        $results = $wpdb->get_results( $sql );
    }

    return $results ?: array();
}

/**
 * Count email logs
 *
 * @param array $args Query arguments
 * @return int
 */
function cf_count_email_logs( array $args = array() ) {
    global $wpdb;
    $table = $wpdb->prefix . 'cf_email_logs';

    $defaults = array(
        'form_id'       => 0,
        'submission_id' => 0,
        'status'        => '',
        'search'        => '',
        'date_from'     => '',
        'date_to'       => '',
    );
    $args = array_merge( $defaults, $args );

    $where_clauses = array( '1=1' );
    $prepare_values = array();

    if ( ! empty( $args['form_id'] ) ) {
        $where_clauses[] = 'form_id = %d';
        $prepare_values[] = (int) $args['form_id'];
    }

    if ( ! empty( $args['submission_id'] ) ) {
        $where_clauses[] = 'submission_id = %d';
        $prepare_values[] = (int) $args['submission_id'];
    }

    if ( ! empty( $args['status'] ) ) {
        $where_clauses[] = 'status = %s';
        $prepare_values[] = $args['status'];
    }

    if ( ! empty( $args['search'] ) ) {
        $where_clauses[] = '(to_email LIKE %s OR subject LIKE %s)';
        $search_like = '%' . $wpdb->esc_like( $args['search'] ) . '%';
        $prepare_values[] = $search_like;
        $prepare_values[] = $search_like;
    }

    if ( ! empty( $args['date_from'] ) ) {
        $where_clauses[] = 'sent_at >= %s';
        $prepare_values[] = $args['date_from'] . ' 00:00:00';
    }

    if ( ! empty( $args['date_to'] ) ) {
        $where_clauses[] = 'sent_at <= %s';
        $prepare_values[] = $args['date_to'] . ' 23:59:59';
    }

    $where = implode( ' AND ', $where_clauses );

    if ( ! empty( $prepare_values ) ) {
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $prepare_values ) );
    }

    return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where}" );
}

/**
 * Delete old email logs (cleanup)
 *
 * @param int $days_old Delete logs older than this many days
 * @return int Number of rows deleted
 */
function cf_delete_old_email_logs( $days_old = 90 ) {
    global $wpdb;
    $table = $wpdb->prefix . 'cf_email_logs';

    return $wpdb->query( $wpdb->prepare(
        "DELETE FROM {$table} WHERE sent_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
        (int) $days_old
    ) );
}
