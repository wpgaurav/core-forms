<?php
/**
 * Fullscreen Forms Feature
 *
 * Typeform-style one-question-at-a-time fullscreen forms
 */

namespace Core_Forms\Fullscreen_Forms;

// Add default settings
add_filter('cf_form_default_settings', function ($settings) {
    $settings['display_mode'] = 'normal';
    $settings['fullscreen_theme'] = 'light';
    $settings['fullscreen_show_progress'] = '1';
    $settings['fullscreen_animation'] = 'slide';
    $settings['fullscreen_page_title'] = '';
    $settings['fullscreen_external_css'] = '';
    $settings['fullscreen_external_js'] = '';
    return $settings;
});

require __DIR__ . '/src/class-frontend.php';
$frontend = new Frontend();
$frontend->hook();

if (is_admin() && ( ! defined('DOING_AJAX') || ! DOING_AJAX )) {
    require __DIR__ . '/src/class-admin.php';
    $admin = new Admin();
    $admin->hook();
}
