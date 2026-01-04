<?php

namespace Core_Forms\Submission_limit;

// Support both cf_ and cf_ prefixed filters
add_filter('cf_form_default_messages', function ($msgs) {
    $msgs['submission_limit_reached'] = __('The submission limit for this form has been reached.', 'core-forms');
    return $msgs;
});
add_filter('cf_form_default_messages', function ($msgs) {
    $msgs['submission_limit_reached'] = __('The submission limit for this form has been reached.', 'core-forms');
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
