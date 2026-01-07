<?php

namespace Core_Forms\Submission_limit;

use Core_Forms\Form;

class Frontend
{
    public function hook()
    {
		add_filter( 'cf_form_html', array( $this, 'submission_limit' ), 10, 2 );
    }	
    
    public function submission_limit( $html, $form ) {
        $submission_limit = ( isset( $form->settings['submission_limit'] ) && is_numeric ( $form->settings['submission_limit'] ) ? $form->settings['submission_limit'] : 0 );

        if ( empty( $_GET['cf_preview_form'] ) && ( $submission_limit > 0 && cf_count_form_submissions( $form->id ) >= $submission_limit ) ) {
            $html = '<div class="cf-message cf-message-warning cf-message-submission-limit">' . $form->messages['submission_limit_reached'] . '</div>';
        }

		return $html;
	}
}
