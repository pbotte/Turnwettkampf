<?php

require_once __DIR__ . '/includes/auth_helpers.php';

ensure_session_started();

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout_user();
}

$user_level_required = $user_level_required ?? ROLE_ADMIN;
$user_logged_in = current_user_level() !== null;
$user_level_fulfilled = user_has_level((int) $user_level_required);

if (!$user_level_fulfilled) {
    require_user_level((int) $user_level_required);
}
