<?php

namespace Core_Forms\Admin;

class AllSubmissions {

    private $plugin_file;

    public function __construct( $plugin_file ) {
        $this->plugin_file = $plugin_file;
    }

    public function hook() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
    }

    public function add_menu_page() {
        add_submenu_page(
            'core-forms',
            __( 'All Submissions', 'core-forms' ),
            __( 'All Submissions', 'core-forms' ),
            'edit_forms',
            'core-forms-submissions',
            array( $this, 'render_page' )
        );
    }

    public function render_page() {
        require_once dirname( $this->plugin_file ) . '/views/page-all-submissions.php';
    }

}
