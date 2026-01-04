<?php

namespace Core_Forms\Admin;

use Core_Forms\Form;
use Core_Forms\Submission;

class Admin {

	/**
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Admin constructor.
	 *
	 * @param string $plugin_file
	 */
	public function __construct( $plugin_file ) {
		$this->plugin_file = $plugin_file;
	}

	public function hook() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( $this, 'run_migrations' ) );
		add_action( 'admin_init', array( $this, 'listen' ) );
		add_action( 'admin_print_styles', array( $this, 'assets' ) );
		add_action( 'admin_head', array( $this, 'add_screen_options' ) );
        add_action( 'cf_admin_action_create_form', array( $this, 'process_create_form' ) );
		add_action( 'wp_ajax_cf_admin_action', array( $this, 'handle_admin_action' ) );
		add_action( 'wp_ajax_cf_dismiss_recaptcha_notice', array( $this, 'dismiss_recaptcha_notice' ) );
		add_action( 'cf_admin_action_save_form', array( $this, 'process_save_form' ) );
		add_action( 'cf_admin_action_bulk_delete_submissions', array( $this, 'process_bulk_delete_submissions' ) );

		add_action( 'cf_admin_output_form_tab_fields', array( $this, 'tab_fields' ) );
		add_action( 'cf_admin_output_form_tab_messages', array( $this, 'tab_messages' ) );
		add_action( 'cf_admin_output_form_tab_settings', array( $this, 'tab_settings' ) );
		add_action( 'cf_admin_output_form_tab_actions', array( $this, 'tab_actions' ) );
		add_action( 'cf_admin_output_form_tab_submissions', array( $this, 'tab_submissions_list' ) );
		add_action( 'cf_admin_output_form_tab_submissions', array( $this, 'tab_submissions_detail' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_gutenberg_assets' ) );
	}

	public function enqueue_gutenberg_assets() {
		wp_enqueue_script(
			'core-forms-block',
			plugins_url( 'assets/js/gutenberg-block.js', $this->plugin_file ),
			array(
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-components',
				'wp-block-editor',
			)
		);
		$forms = cf_get_forms();
		$data  = array();
		foreach ( $forms as $form ) {
			$data[] = array(
				'title' => $form->title,
				'slug'  => $form->slug,
				'id'    => $form->ID,
			);
		}
		wp_localize_script( 'core-forms-block', 'core_forms', $data );
	}

	public function register_settings() {
		// register settings
		register_setting( 'cf_settings', 'cf_settings', array( $this, 'sanitize_settings' ) );
	}

	public function run_migrations() {
		$version_from = get_option( 'cf_version', '0.0' );
		$version_to   = CORE_FORMS_VERSION;

		if ( version_compare( $version_from, $version_to, '>=' ) ) {
			return;
		}

		$migrations = new Migrations( $version_from, $version_to, dirname( $this->plugin_file ) . '/migrations' );
		$migrations->run();
		update_option( 'cf_version', CORE_FORMS_VERSION );
	}

	/**
	 * @param array $dirty
	 *
	 * @return array
	 */
	public function sanitize_settings( $dirty ) {
        if ( isset( $dirty['wrapper_tag'] ) ) {
            $dirty['wrapper_tag'] = sanitize_text_field( $dirty['wrapper_tag'] );
        }

		return $dirty;
	}

	public function listen() {
		if ( isset( $_GET['_cf_admin_action'] ) ) {
			$action = (string) $_GET['_cf_admin_action'];
		} elseif ( isset( $_POST['_cf_admin_action'] ) ) {
			$action = (string) $_POST['_cf_admin_action'];
		} else {
			return;
		}

		// verify nonce
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], '_cf_admin_action' ) ) {
			wp_nonce_ays( $action );
			exit;
		}

		// do nothing if logged in user is not of role administrator
		if ( ! current_user_can( 'edit_forms' ) ) {
			return;
		}

		/**
		 * Allows you to hook into requests containing `_cf_admin_action` => action name.
		 *
		 * The dynamic portion of the hook name, `$action`, refers to the action name.
		 *
		 * By the time this hook is fired, the user is already authorized. After processing all the registered hooks,
		 * the request is redirected back to the referring URL.
		 *
		 * @since 3.0
		 */
		do_action( 'cf_admin_action_' . $action );

		// redirect back to where we came from
		$redirect_url = ! empty( $_REQUEST['_redirect_to'] ) ? $_REQUEST['_redirect_to'] : remove_query_arg( '_cf_admin_action' );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	public function assets() {
		if ( empty( $_GET['page'] ) || strpos( $_GET['page'], 'core-forms' ) !== 0 ) {
			return;
		}

		$settings = cf_get_settings();

		wp_enqueue_style( 'core-forms-admin', plugins_url( 'assets/css/admin.css', $this->plugin_file ), array(), CORE_FORMS_VERSION );
		wp_enqueue_script( 'core-forms-admin', plugins_url( 'assets/js/admin.js', $this->plugin_file ), array(), CORE_FORMS_VERSION, true );
		wp_localize_script(
			'core-forms-admin',
			'cf_options',
			array(
				'page'    => $_GET['page'],
				'view'    => empty( $_GET['view'] ) ? '' : $_GET['view'],
				'form_id' => empty( $_GET['form_id'] ) ? 0 : (int) $_GET['form_id'],
				'wrapper_tag' => empty( $settings['wrapper_tag'] ) ? 'p' : $settings['wrapper_tag'],
			)
		);
	}

	public function menu() {
		$capability = 'edit_forms';
		// Core Forms icon - using a simple CF styled icon
		$svg_icon   = '<svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="256.000000pt" height="256.000000pt" viewBox="0 0 256.000000 256.000000" preserveAspectRatio="xMidYMid meet"><g transform="translate(0.000000,256.000000) scale(0.100000,-0.100000)"
			fill="#000000" stroke="none"><path d="M1120 2545 c-344 -56 -636 -238 -830 -515 -89 -128 -166 -305 -200 -460 -26 -119 -35 -337 -19 -460 35 -271 146 -516 330 -725 35 -40 142 -138 197 -180 189 -147 427 -237 677 -256 116 -9 296 6 405 34 91 23 240 81 330 128 80 42 220 136 220 148 0 4 -34 43 -75 86 l-76 79 -62 -46 c-84 -62 -230 -138 -332 -173 -96 -33 -212 -55 -330 -62 -133 -9 -272 14 -411 66 -304 116 -541 372 -639 689 -36 117 -50 240 -41 372 23 345 189 638 469 823 177 116 365 177 582 187 203 10 379 -30 556 -125 69 -38 199 -127 199 -137 0 -2 35 -38 78 -79 l77 -75 59 55 c32 30 70 66 85 80 l27 26 -82 74 c-115 105 -274 206 -419 264 -193 79 -466 112 -680 82z"/></g></svg>';
		add_menu_page(
			'Core Forms',
			'Core Forms',
			$capability,
			'core-forms',
			array(
				$this,
				'page_overview',
			),
			'data:image/svg+xml;base64,' . base64_encode( $svg_icon ),
			'99.88491'
		);
		add_submenu_page(
			'core-forms',
			__( 'Forms', 'core-forms' ),
			__( 'All Forms', 'core-forms' ),
			$capability,
			'core-forms',
			array(
				$this,
				'page_overview',
			)
		);
		add_submenu_page(
			'core-forms',
			__( 'Add New Form', 'core-forms' ),
			__( 'Add New', 'core-forms' ),
			$capability,
			'core-forms-add-form',
			array(
				$this,
				'page_new_form',
			)
		);
		add_submenu_page(
			'core-forms',
			__( 'Settings', 'core-forms' ),
			__( 'Settings', 'core-forms' ),
			$capability,
			'core-forms-settings',
			array(
				$this,
				'page_settings',
			)
		);
		// Premium features are now integrated - no need for upsell page
	}

	public function add_screen_options() {
		// only run on the submissions overview page (not detail)
		if ( empty( $_GET['page'] ) || $_GET['page'] !== 'core-forms' || empty( $_GET['view'] ) || $_GET['view'] !== 'edit' || empty( $_GET['form_id'] ) || ! empty( $_GET['submission_id'] ) ) {
			return;
		}

		// don't run if form does not exist or does not have submissions enabled
		try {
			$form = cf_get_form( $_GET['form_id'] );
		} catch ( \Exception $e ) {
			return;
		}
		if ( ! $form->settings['save_submissions'] ) {
			return;
		}

		// tell screen options to show columns option
		$submissions = cf_get_form_submissions( $_GET['form_id'] );
		$columns     = $this->get_submission_columns( $submissions );
		add_filter(
			'manage_toplevel_page_core-forms_columns',
			function ( $unused ) use ( $columns ) {
				return $columns;
			}
		);
		add_screen_option( 'layout_columns' );
	}

	public function page_overview() {
		if ( ! empty( $_GET['view'] ) && $_GET['view'] === 'edit' ) {
			$this->page_edit_form();

			return;
		}

		$settings = cf_get_settings();

		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		$table = new Table( $settings );

		require dirname( $this->plugin_file ) . '/views/page-overview.php';
	}

	public function page_new_form() {
		require dirname( $this->plugin_file ) . '/views/page-add-form.php';
	}

	public function page_settings() {
		$settings = cf_get_settings();
        $wrapper_tags = array ( 'p', 'div', 'span' );
        
		require dirname( $this->plugin_file ) . '/views/page-global-settings.php';
	}

	public function page_premium() {
		require dirname( $this->plugin_file ) . '/views/page-premium.php';
	}

	public function page_edit_form() {
		$active_tab = ! empty( $_GET['tab'] ) ? $_GET['tab'] : 'fields';
		$form_id    = (int) $_GET['form_id'];
		try {
			$form = cf_get_form( $form_id );
		} catch ( \Exception $e ) {
			wp_safe_redirect( admin_url( 'admin.php?page=core-forms' ) );
			exit;
		}
		$settings   = cf_get_settings();
		require dirname( $this->plugin_file ) . '/views/page-edit-form.php';
	}

	public function tab_fields( Form $form ) {
		$form_preview_url = add_query_arg(
			array(
				'cf_preview_form' => $form->ID,
			),
			site_url( '/', 'admin' )
		);
		require dirname( $this->plugin_file ) . '/views/tab-fields.php';
	}

	public function tab_messages( Form $form ) {
		require dirname( $this->plugin_file ) . '/views/tab-messages.php';
	}


	public function tab_settings( Form $form ) {
		require dirname( $this->plugin_file ) . '/views/tab-settings.php';
	}


	public function tab_actions( Form $form ) {
		require dirname( $this->plugin_file ) . '/views/tab-actions.php';
	}

	public function get_submission_columns( array $submissions ) {
		$columns = array();
		foreach ( $submissions as $s ) {
			if ( ! is_array( $s->data ) ) {
				continue;
			}

			foreach ( $s->data as $field => $value ) {
				if ( ! isset( $columns[ $field ] ) ) {
					$columns[ $field ] = esc_html( ucfirst( strtolower( str_replace( '_', ' ', $field ) ) ) );
				}
			}
		}

		return $columns;
	}

	public function tab_submissions_list( Form $form ) {
		if ( ! empty( $_GET['submission_id'] ) ) {
			return;
		}

		$items_per_page = 500;
		$total_items    = cf_count_form_submissions( $form->ID );
		$total_pages    = max( 1, ceil( $total_items / $items_per_page ) );
		$current_page   = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1;
		$current_page   = max( 1, $current_page );
		$current_page   = min( $total_pages, $current_page );
		$submissions    = cf_get_form_submissions(
			$form->ID,
			array(
				'limit'  => $items_per_page,
				'offset' => ( $current_page - 1 ) * $items_per_page,
			)
		);
		$columns        = $this->get_submission_columns( $submissions );
		$hidden_columns = get_hidden_columns( get_current_screen() );

		require dirname( $this->plugin_file ) . '/views/tab-submissions-list.php';
	}

	public function tab_submissions_detail( Form $form ) {
		if ( empty( $_GET['submission_id'] ) ) {
			return;
		}

		$submission = cf_get_form_submission( (int) $_GET['submission_id'] );
        do_action( 'cf_admin_form_submissions_detail', $submission );
		require dirname( $this->plugin_file ) . '/views/tab-submissions-detail.php';
	}

	public function process_create_form() {
		// Fix for MultiSite stripping KSES for roles other than administrator
		remove_all_filters( 'content_save_pre' );

		$data       = $_POST['form'];
		$form_title = sanitize_text_field( $data['title'] );
		$form_id    = wp_insert_post(
			array(
				'post_type'    => 'html-form',
				'post_status'  => 'publish',
				'post_title'   => $form_title,
				'post_content' => $this->get_default_form_content(),
			)
		);

		wp_safe_redirect( admin_url( 'admin.php?page=core-forms&view=edit&form_id=' . $form_id ) );
		exit;
	}

	public function process_save_form() {
		$form_id = (int) $_POST['form_id'];
		try {
			$form = cf_get_form( $form_id );
		} catch ( \Exception $e ) {
			wp_safe_redirect( admin_url( 'admin.php?page=core-forms' ) );
			exit;
		}
		$data    = $_POST['form'];

		// Fix for MultiSite stripping KSES for roles other than administrator
		remove_all_filters( 'content_save_pre' );

		// run our own kses filter
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$data['markup'] = $this->kses( $data['markup'] );
		}

		// strip <form> tag from markup
		$data['markup'] = preg_replace( '/<\/?form(.|\s)*?>/i', '', $data['markup'] );

		$form_id = wp_insert_post(
			array(
				'ID'           => $form_id,
				'post_type'    => 'html-form',
				'post_status'  => 'publish',
				'post_title'   => sanitize_text_field( $data['title'] ),
				'post_content' => $data['markup'],
				'post_name'    => sanitize_title_with_dashes( $data['slug'] ),
			)
		);

		if ( ! empty( $data['settings'] ) ) {
			update_post_meta( $form_id, '_cf_settings', $data['settings'] );
		}

		// save form messages in individual meta keys
		foreach ( $data['messages'] as $key => $message ) {
            if ( current_user_can( 'unfiltered_html' ) ) {
                update_post_meta( $form_id, 'cf_message_' . $key, $message );
            } else {
                update_post_meta( $form_id, 'cf_message_' . $key, wp_kses_post( $message ) );
            }
		}

		$redirect_url_args = array(
			'form_id' => $form_id,
			'saved'   => 1,
		);
		$redirect_url      = add_query_arg( $redirect_url_args, admin_url( 'admin.php?page=core-forms&view=edit' ) );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Get URL for a tab on the current page.
	 *
	 * @param $tab
	 *
	 * @return string
	 * @since 3.0
	 * @internal
	 */
	public function get_tab_url( $tab ) {
		$tab_url = add_query_arg( array( 'tab' => $tab ), remove_query_arg( 'tab' ) );
        
        $url_parts = parse_url($tab_url);
        if ( isset( $url_parts['query'] ) ) {
            parse_str( $url_parts['query'], $query_params );
            
            if ( isset( $query_params['submission_id'] ) ) {
                unset( $query_params['submission_id'] );
            }

            $new_query = http_build_query( $query_params );
            $new_tab_url = $url_parts['path'];

            if ( ! empty( $new_query ) ) {
                $new_tab_url .= '?' . $new_query;
            }
        
            return $new_tab_url;
        }

        return $tab_url;
	}

	/**
	 * @return array
	 */
	public function get_available_form_actions() {
		$actions = array();

		/**
		 * Filters the available form actions
		 *
		 * @param array $actions
		 */
		$actions = apply_filters( 'cf_available_form_actions', $actions );

		return $actions;
	}

	public function process_bulk_delete_submissions() {
		global $wpdb;

		if ( empty( $_POST['id'] ) ) {
			return;
		}

		$args         = array_map( 'intval', $_POST['id'] );
		$table        = $wpdb->prefix . 'cf_submissions';
		$placeholders = rtrim( str_repeat( '%d,', count( $args ) ), ',' );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id IN( {$placeholders} );", $args ) );

		$args[] = '_cf_%%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ( {$placeholders}  ) AND meta_key LIKE %s;", $args ) );
	}

	private function get_default_form_content() {
        $settings = cf_get_settings();
        $wrapper_tag = ( isset( $settings['wrapper_tag'] ) ) ? $settings['wrapper_tag'] : 'p';

		$html  = '';
		$html .= sprintf( "<%1\$s>\n\t<label>%2\$s</label>\n\t<input type=\"text\" name=\"NAME\" placeholder=\"%2\$s\" required />\n</%1\$s>", $wrapper_tag, __( 'Your name', 'core-forms' ) ) . PHP_EOL;
		$html .= sprintf( "<%1\$s>\n\t<label>%2\$s</label>\n\t<input type=\"email\" name=\"EMAIL\" placeholder=\"%2\$s\" required />\n</%1\$s>", $wrapper_tag, __( 'Your email', 'core-forms' ) ) . PHP_EOL;
		$html .= sprintf( "<%1\$s>\n\t<label>%2\$s</label>\n\t<input type=\"text\" name=\"SUBJECT\" placeholder=\"%2\$s\" required />\n</%1\$s>", $wrapper_tag, __( 'Subject', 'core-forms' ) ) . PHP_EOL;
		$html .= sprintf( "<%1\$s>\n\t<label>%2\$s</label>\n\t<textarea name=\"MESSAGE\" placeholder=\"%2\$s\" required></textarea>\n</%1\$s>", $wrapper_tag, __( 'Message', 'core-forms' ) ) . PHP_EOL;
		$html .= sprintf( "<%1\$s>\n\t<input type=\"submit\" value=\"%2\$s\" />\n</%1\$s>", $wrapper_tag, __( 'Send', 'core-forms' ) );

		return $html;
	}

	/**
	 * Filters string and strips out all HTML tags and attributes, except what's in our whitelist.
	 *
	 * @param string $string The string to apply KSES whitelist on
	 * @return string
	 */
	private function kses( $string ) {
		$always_allowed_attr = array_fill_keys(
			array(
				'aria-describedby',
				'aria-details',
				'aria-label',
				'aria-labelledby',
				'aria-hidden',
				'aria-*',
				'class',
				'id',
				'style',
				'title',
				'role',
				'data-*',
				'data-confirm',
				'tabindex',
			),
			true
		);
		$input_allowed_attr  = array_merge(
			$always_allowed_attr,
			array_fill_keys(
				array(
					'type',
					'required',
					'placeholder',
					'value',
					'name',
					'step',
					'min',
					'max',
					'checked',
					'width',
					'autocomplete',
					'autofocus',
					'minlength',
					'maxlength',
					'size',
					'pattern',
					'disabled',
					'readonly',
				),
				true
			)
		);

		$allowed = array(
			'p'        => $always_allowed_attr,
			'label'    => array_merge( $always_allowed_attr, array( 'for' => true ) ),
			'input'    => $input_allowed_attr,
			'button'   => $input_allowed_attr,
			'fieldset' => $always_allowed_attr,
			'legend'   => $always_allowed_attr,
			'ul'       => $always_allowed_attr,
			'ol'       => $always_allowed_attr,
			'li'       => $always_allowed_attr,
			'select'   => array_merge( $input_allowed_attr, array( 'multiple' => true ) ),
			'option'   => array_merge( $input_allowed_attr, array( 'selected' => true ) ),
			'optgroup' => array(
				'disabled' => true,
				'label'    => true,
			),
			'textarea' => array_merge(
				$input_allowed_attr,
				array(
					'rows' => true,
					'cols' => true,
				)
			),
			'div'      => $always_allowed_attr,
			'strong'   => $always_allowed_attr,
			'b'        => $always_allowed_attr,
			'i'        => $always_allowed_attr,
			'br'       => array(),
			'em'       => $always_allowed_attr,
			'span'     => $always_allowed_attr,
			'a'        => array_merge( $always_allowed_attr, array( 'href' => true ) ),
			'img'      => array_merge(
				$always_allowed_attr,
				array(
					'src'            => true,
					'alt'            => true,
					'width'          => true,
					'height'         => true,
					'srcset'         => true,
					'sizes'          => true,
					'referrerpolicy' => true,
					'loading'        => true,
					'decoding'       => true,
				)
			),
			'u'        => $always_allowed_attr,
			'table'    => $always_allowed_attr,
			'tr'       => $always_allowed_attr,
			'td'       => $always_allowed_attr,
			'th'       => $always_allowed_attr,
			'thead'    => $always_allowed_attr,
			'tbody'    => $always_allowed_attr,
			'picture'  => $always_allowed_attr,
			'video'    => $always_allowed_attr,
		);

		return wp_kses( $string, $allowed );
	}
}
