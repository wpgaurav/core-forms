<?php

namespace Core_Forms\Admin;

use Core_Forms\Form;
use WP_List_Table, WP_Post;

// Create backward compatible alias for old namespace
// Note: This must be done after class definition due to conditional class definition

// Check if WP Core class exists so that we can keep testing rest of Core Forms in isolation..
if ( class_exists( 'WP_List_Table' ) ) {

    class Table extends WP_List_Table {

		/**
		 * @var bool
		 */
		public $is_trash = false;

		/**
		 * @var array
		 */
		private $settings = array();

		/**
		 * Constructor
		 */
		public function __construct( array $settings ) {
			parent::__construct(
				array(
					'singular' => 'form',
					'plural'   => 'forms',
					'ajax'     => false,
				)
			);

			$this->settings = $settings;
			$this->process_bulk_action();
			$this->prepare_items();
		}

		public function prepare_items() {
			$columns               = $this->get_columns();
			$sortable              = $this->get_sortable_columns();
			$hidden                = array();
			$this->_column_headers = array( $columns, $hidden, $sortable );
			$this->is_trash        = isset( $_REQUEST['post_status'] ) && $_REQUEST['post_status'] === 'trash';
			$this->items           = $this->get_items();
			$this->set_pagination_args(
				array(
					'per_page'    => 50,
					'total_items' => count( $this->items ),
				)
			);
		}

		/**
		 * Get an associative array ( id => link ) with the list
		 * of views available on this table.
		 *
		 * @return array
		 * @since 3.1.0
		 * @access protected
		 *
		 */
		public function get_views() {
			$counts    = wp_count_posts( 'core-form' );
			$current   = isset( $_GET['post_status'] ) ? $_GET['post_status'] : '';
			$count_any = ( isset( $counts->publish ) ? $counts->publish : 0 )
			           + ( isset( $counts->draft ) ? $counts->draft : 0 )
			           + ( isset( $counts->future ) ? $counts->future : 0 )
			           + ( isset( $counts->pending ) ? $counts->pending : 0 )
			           + ( isset( $counts->private ) ? $counts->private : 0 );
			$count_trash = isset( $counts->trash ) ? $counts->trash : 0;

			return array(
				''      => sprintf( '<a href="%s" class="%s">%s</a> (%d)', esc_url( remove_query_arg( 'post_status' ) ), $current == '' ? 'current' : '', __( 'All', 'core-forms' ), $count_any ),
				'trash' => sprintf( '<a href="%s" class="%s">%s</a> (%d)', esc_url( add_query_arg( array( 'post_status' => 'trash' ) ) ), $current == 'trash' ? 'current' : '', __( 'Trash', 'core-forms' ), $count_trash ),
			);
		}

		/**
		 * @return array
		 */
		public function get_bulk_actions() {

			$actions = array();

			if ( $this->is_trash ) {
				$actions['untrash'] = __( 'Restore', 'core-forms' );
				$actions['delete']  = __( 'Delete Permanently', 'core-forms' );

				return $actions;
			}

			$actions['trash']     = __( 'Delete Permanently', 'core-forms' );
			$actions['duplicate'] = __( 'Duplicate', 'core-forms' );

			return $actions;
		}

		public function get_default_primary_column_name() {
			return 'form_name';
		}

		/**
		 * @return array
		 */
		public function get_table_classes() {
			return array( 'widefat', 'fixed', 'striped', 'core-forms-table' );
		}

		/**
		 * @return array
		 */
		public function get_columns() {
			return array(
				'cb'        => '<input type="checkbox" />',
				'form_name' => __( 'Form', 'core-forms' ),
				'shortcode' => __( 'Shortcode', 'core-forms' ),
				'submissions' => __( 'Submissions', 'core-forms' ),
			);
		}

		/**
		 * @return array
		 */
		public function get_hidden_columns() {
			return array();
		}

		/**
		 * @return array
		 */
		public function get_sortable_columns() {
			return array(
				'form_name' => array( 'post_title', true ),
			);
		}

		/**
		 * @return array
		 */
		public function get_items() {
			$args = array();

			if ( ! empty( $_GET['s'] ) ) {
				$args['s'] = sanitize_text_field( $_GET['s'] );
			}

			if ( ! empty( $_GET['post_status'] ) ) {
				$args['post_status'] = sanitize_text_field( $_GET['post_status'] );
			}

			if ( ! empty( $_GET['order'] ) ) {
				$args['order'] = $_GET['order'];
			}

			if ( ! empty( $_GET['orderby'] ) ) {
				$args['orderby'] = $_GET['orderby'];
			}

			return cf_get_forms( $args );
		}

		/**
		 * @param Form $form
		 *
		 * @return string
		 */
		public function column_cb( $form ) {
			return sprintf( '<input type="checkbox" name="forms[]" value="%s" />', $form->ID );
		}

		/**
		 * @param Form $form
		 *
		 * @return mixed
		 */
		public function column_ID( Form $form ) {
			return $form->ID;
		}

		/**
		 * @param Form $form
		 *
		 * @return string
		 */
		public function column_form_name( Form $form ) {
			if ( $this->is_trash ) {
				return sprintf( '<strong>%s</strong>', esc_html( $form->title ) );
			}

			$edit_link = admin_url( 'admin.php?page=core-forms&view=edit&form_id=' . $form->ID );
			$title     = '<strong><a class="row-title" href="' . $edit_link . '">' . esc_html( $form->title ) . '</a></strong>';

			$actions = array();
			$tabs    = cf_get_admin_tabs( $form );

			foreach ( $tabs as $tab_slug => $tab_title ) {
				$actions[ $tab_slug ] = '<a href="' . esc_attr( add_query_arg( array( 'tab' => $tab_slug ), $edit_link ) ) . '">' . $tab_title . '</a>';
			}

			return $title . $this->row_actions( $actions );
		}

		/**
		 * @param Form $form
		 *
		 * @return string
		 */
		public function column_shortcode( Form $form ) {
			if ( $this->is_trash ) {
				return '';
			}

			return sprintf( '<input type="text" onfocus="this.select();" readonly="readonly" value="%s">', esc_attr( '[cf_form slug="' . $form->slug . '"]' ) );
		}

        /**
		 * @param Form $form
		 *
		 * @return string
		 */
		public function column_submissions( Form $form ) {
			$edit_link = admin_url( 'admin.php?page=core-forms&view=edit&form_id=' . $form->ID );
            $submissions = cf_count_form_submissions( $form->id );

            if ($submissions == 0 || $this->is_trash) {
                return $submissions;
            } else {
    			return sprintf( '<a href="' . esc_attr( add_query_arg( array( 'tab' =>'submissions' ), $edit_link ) ) . '">' . $submissions . '</a>');
            }
		}

		/**
		 * The text that is shown when there are no items to show
		 */
		public function no_items() {
			echo sprintf( __( 'No forms found. <a href="%s">Would you like to create one now</a>?', 'core-forms' ), admin_url( 'admin.php?page=core-forms-add-form' ) );
		}

		/**
		 *
		 */
		public function process_bulk_action() {
			$action = $this->current_action();
			if ( empty( $action ) ) {
				return false;
			}

            if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-' . $this->_args['plural'] ) ) {
                return false;
            }

			$method = 'process_bulk_action_' . $action;
			$forms  = (array) $_REQUEST['forms'];
			if ( method_exists( $this, $method ) ) {
				return call_user_func_array( array( $this, $method ), array( $forms ) );
			}

			return false;
		}

		public function process_bulk_action_duplicate( $forms ) {
			foreach ( $forms as $form_id ) {
				$post      = get_post( $form_id );
				$post_meta = get_post_meta( $form_id );

				$new_post_id = wp_insert_post(
					array(
						'post_title'   => $post->post_title,
						'post_content' => $post->post_content,
						'post_type'    => 'core-form',
						'post_status'  => 'publish',
					)
				);
				foreach ( $post_meta as $meta_key => $meta_value ) {
					$meta_value = maybe_unserialize( $meta_value[0] );
					update_post_meta( $new_post_id, $meta_key, $meta_value );
				}
			}
		}

		public function process_bulk_action_trash( $forms ) {
            array_map( 'wp_trash_post', $forms );
		}

		public function process_bulk_action_delete( $forms ) {
			array_map( 'wp_delete_post', $forms );
		}

		public function process_bulk_action_untrash( $forms ) {
			array_map( 'wp_untrash_post', $forms );
		}

		/**
		 * Generates content for a single row of the table
		 *
		 * @param Form $form The current item
		 *
		 * @since 3.1.0
		 *
		 */
		public function single_row( $form ) {
			echo sprintf( '<tr id="cf-forms-item-%d">', $form->ID );
			$this->single_row_columns( $form );
			echo '</tr>';
		}

	}

}
