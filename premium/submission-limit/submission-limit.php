<?php

namespace HTML_Forms\Submission_limit;

add_filter('hf_form_default_messages', function ($msgs) {
    $msgs['submission_limit_reached'] = __('The submission limit for this form has been reached.', 'html-forms-premium');
    return $msgs;
});

require __DIR__ . '/src/class-frontend.php';
$frontend = new Frontend();
$frontend->hook();

if (is_admin() && ( ! defined('DOING_AJAX') || ! DOING_AJAX )) {
    require __DIR__ . '/src/class-admin.php';
    $admin = new Admin();
    $admin->hook();
}
