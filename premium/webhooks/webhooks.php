<?php

require __DIR__ . '/src/Action.php';

// Support both new and legacy namespace
$action = new Core_Forms\Actions\Webhook();
$action->hook();
