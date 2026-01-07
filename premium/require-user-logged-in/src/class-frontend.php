<?php

namespace Core_Forms\Required_User_Logged_In;

use Core_Forms\Form;

class Frontend
{
    public function hook()
    {
		add_filter( 'cf_form_html', array( $this, 'require_user_logged_in' ), 10, 2 );
    }	
    
    public function require_user_logged_in( $html, $form ) {
        if ( empty( $_GET['cf_preview_form'] ) && ( isset( $form->settings['require_user_logged_in'] ) && $form->settings['require_user_logged_in'] && !is_user_logged_in() ) ) {
            $html = '<div class="cf-message cf-message-warning cf-message-require-user-logged-in">' . $form->messages['require_user_logged_in'] . '</div>';
        }

		return $html;
	}
}
