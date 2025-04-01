<?php
session_start();

// Falls der Nutzer sich abmelden möchte:
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['user_level']);
    session_destroy();
    header("Location: login.php");
    exit();
}

if (!isset($user_level_required)) $user_level_required = 2;

// Prüfen, ob der Nutzer bereits authentifiziert ist
$user_level_fulfilled = false;
$user_logged_in = true;
if (isset($_SESSION['user_level'])) {
    $user_logged_in = false;
    if ($user_level_required <= $_SESSION['user_level']) {
        $user_level_fulfilled = true;
    }
}

if (!$user_level_fulfilled) {
    // Aktuelle URL speichern, damit nach erfolgreichem Login zurückgeleitet werden kann
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php?message=Rechte%20nicht%20ausreichend");
    exit();
}



?>
