<?php

namespace HTML_Forms\File_Upload;

add_filter('hf_form_default_messages', function ($msgs) {
    $msgs['file_too_large'] = __('Uploaded file is too large.', 'html-forms-premium');
    $msgs['file_upload_error'] = __('An upload error occurred. Please try again later.', 'html-forms-premium');
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
