<?php

namespace Core_Forms\File_Upload;

// Support both cf_ and cf_ prefixed filters
add_filter('cf_form_default_messages', function ($msgs) {
    $msgs['file_too_large'] = __('Uploaded file is too large.', 'core-forms');
    $msgs['file_upload_error'] = __('An upload error occurred. Please try again later.', 'core-forms');
    return $msgs;
});
add_filter('cf_form_default_messages', function ($msgs) {
    $msgs['file_too_large'] = __('Uploaded file is too large.', 'core-forms');
    $msgs['file_upload_error'] = __('An upload error occurred. Please try again later.', 'core-forms');
    return $msgs;
});

require __DIR__ . '/includes/class-uploader.php';
$uploader = new Uploader();
$uploader->hook();

if (is_admin() && ( ! defined('DOING_AJAX') || ! DOING_AJAX )) {
    require __DIR__ . '/includes/class-admin.php';
    $admin = new Admin(__FILE__);
    $admin->hook();
}
