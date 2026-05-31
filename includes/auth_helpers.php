<?php

const ROLE_KARI = 1;
const ROLE_ADMIN = 2;

const ADMIN_PASSWORD = 'TurnvaterJahn2026';
const KARI_PASSWORD = '4711';

function ensure_session_started(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function current_user_level(): ?int
{
    ensure_session_started();
    return isset($_SESSION['user_level']) ? (int) $_SESSION['user_level'] : null;
}

function user_has_level(int $requiredLevel): bool
{
    $level = current_user_level();
    return $level !== null && $level >= $requiredLevel;
}

function login_level_for_password(string $password): ?int
{
    if ($password === ADMIN_PASSWORD) {
        return ROLE_ADMIN;
    }

    if ($password === KARI_PASSWORD) {
        return ROLE_KARI;
    }

    return null;
}

function login_user(int $level): void
{
    ensure_session_started();
    $_SESSION['user_level'] = $level;
}

function logout_user(): never
{
    ensure_session_started();
    unset($_SESSION['user_level']);
    session_destroy();
    header("Location: login.php");
    exit;
}

function redirect_after_login(): never
{
    ensure_session_started();
    $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
    unset($_SESSION['redirect_after_login']);
    header("Location: {$redirect}");
    exit;
}

function require_user_level(int $requiredLevel): void
{
    ensure_session_started();

    if (user_has_level($requiredLevel)) {
        return;
    }

    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php?message=Rechte%20nicht%20ausreichend");
    exit;
}

