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
        'post_status'         => array( 'publish', 'draft', 'pending', 'future' ),
        'posts_per_page'      => -1,
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    );
    $args         = array_merge( $default_args, $args );
    $query        = new WP_Query;
    $posts        = $query->query( $args );
    $forms        = array_map( 'cf_get_form', $posts );
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
 * Plugin activation handler
 */
function _cf_on_plugin_activation() {
    if ( is_multisite() ) {
        _cf_on_plugin_activation_multisite();
        return;
    }

    // install table for regular wp install
    _cf_create_submissions_table();

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

        // install table for current blog
        _cf_create_submissions_table();

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
        `submitted_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) {$charset_collate};"
    );
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
    restore_current_blog();
}
