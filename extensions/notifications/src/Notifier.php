<?php

namespace Core_Forms\Notifications;

class Notifier {
	
	public function hook() {
		add_filter( 'cf_form_default_settings', array( $this, 'default_form_settings' ) );
		add_action( 'cf_form_success', array( $this, 'increment_notification_count' ), 10, 2 );
	}

	public function default_form_settings( $defaults ) {
		$defaults['enable_notifications'] = 1;
		return $defaults;
	}

	public function increment_notification_count( $submission, $form ) {
		if( $form->settings['save_submissions'] && $form->settings['enable_notifications'] ) {
			add_notification_for_form( $form->id, $submission->id );
		}
	}

}
