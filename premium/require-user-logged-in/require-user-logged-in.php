<?php

namespace HTML_Forms\Required_User_Logged_In;

add_filter('hf_form_default_messages', function ($msgs) {
    $msgs['require_user_logged_in'] = __('You must be logged in before you can use this form.', 'html-forms-premium');
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
