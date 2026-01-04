<?php

namespace HTML_Forms\Required_User_Logged_In;

use HTML_Forms\Form;

class Frontend
{
    public function hook()
    {
		add_filter( 'hf_form_html', array( $this, 'require_user_logged_in' ), 10, 2 );
    }	
    
    public function require_user_logged_in( $html, $form ) {
        if ( empty( $_GET['hf_preview_form'] ) && ( isset( $form->settings['require_user_logged_in'] ) && $form->settings['require_user_logged_in'] && !is_user_logged_in() ) ) {
            $html = '<div class="hf-message hf-message-warning hf-message-require-user-logged-in">' . $form->messages['require_user_logged_in'] . '</div>';
        }

		return $html;
	}
}
