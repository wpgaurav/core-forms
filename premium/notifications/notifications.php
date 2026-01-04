<?php

require __DIR__ . '/src/functions.php';
require __DIR__ . '/src/Notifier.php';

$notifier = new HTML_Forms\Notifications\Notifier();
$notifier->hook();

if (is_admin()) {
    if (! defined('DOING_AJAX') || ! DOING_AJAX) {
        require __DIR__ . '/src/Admin.php';
        $admin = new HTML_Forms\Notifications\Admin();
        $admin->hook();
    }
}
