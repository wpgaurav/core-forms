<?php

namespace HTML_Forms\Submission_limit;

use HTML_Forms\Form;

class Frontend
{
    public function hook()
    {
		add_filter( 'hf_form_html', array( $this, 'submission_limit' ), 10, 2 );
    }	
    
    public function submission_limit( $html, $form ) {
        $submission_limit = ( isset( $form->settings['submission_limit'] ) && is_numeric ( $form->settings['submission_limit'] ) ? $form->settings['submission_limit'] : 0 );

        if ( empty( $_GET['hf_preview_form'] ) && ( $submission_limit > 0 && hf_count_form_submissions( $form->id ) >= $submission_limit ) ) {
            $html = '<div class="hf-message hf-message-warning hf-message-submission-limit">' . $form->messages['submission_limit_reached'] . '</div>';
        }

		return $html;
	}
}
