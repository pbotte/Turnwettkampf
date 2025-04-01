<?php
session_start();

// Passwort, das abgeglichen werden soll – bitte anpassen
$correct_password = '???';
$correct_password_kari = '???';

// Falls der Nutzer sich abmelden möchte:
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['logged_in']);
    unset($_SESSION['user_level']);
    session_destroy();
    header("Location: login.php");
    exit();
}

// Prüfen, ob der Nutzer bereits authentifiziert ist
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Aktuelle URL speichern, damit nach erfolgreichem Login zurückgeleitet werden kann
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}
?>
