<?php

namespace Core_Forms\Notifications;

function get_notifications() {
	global $wpdb;
	$meta_key = '_cf_unseen_submissions';
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = %s", $meta_key ) );

	$global_notifications = array();
	foreach( $rows as $row ) {
		$form_notifications = empty($row->meta_value) ? array() : unserialize( $row->meta_value );
		$form_id = $row->post_id;
		$form_notifications = array_values($form_notifications);
		$form_notifications = array_filter($form_notifications);
        $global_notifications["{$form_id}"] = $form_notifications;
    }

	return $global_notifications;
}

function get_notification_count() {
	$notifications = get_notifications();
	$count = 0;

	foreach( $notifications as $form_id => $unseen_submissions ) {
		$count += count( $unseen_submissions );
	}

	return $count;
}

function get_notifications_for_form( $form_id ) {
	$notifications = get_post_meta( $form_id, '_cf_unseen_submissions', true );
	if( empty( $notifications ) ) {
		return array();
	}

	$notifications = (array) $notifications;
	$notifications = array_values($notifications);
	$notifications = array_filter($notifications);
	return $notifications;
}

function set_notifications_for_form( $form_id, array $notifications ) {
	update_post_meta( $form_id, '_cf_unseen_submissions', array_values($notifications));
}

function get_notification_count_for_form( $form_id ) {
	$notifications = get_notifications_for_form( $form_id );
	return count( $notifications );
}

function add_notification_for_form( $form_id, $submission_id ) {
	$notifications = get_notifications_for_form( $form_id );

	// filter out unexisting submissions (deleted ones)
	$submissions = cf_get_form_submissions( $form_id );
	foreach( $notifications as $key => $nsub_id ) {
		if( ! isset( $submissions[$nsub_id] ) ) {
			unset( $notifications[$key] );
		}
	}

	if( ! in_array( $submission_id, $notifications ) ) {
        $notifications = array_values($notifications);
        $notifications[] = $submission_id;
		set_notifications_for_form( $form_id, $notifications );
	}
}

function is_unseen( $form_id, $submission_id ) {
	$notifications = get_notifications_for_form( $form_id );
	return in_array( $submission_id, $notifications );
}
