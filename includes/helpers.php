<?php

function h($value): string
{
    return $value === null ? '-' : htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function h_attr($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header("Location: {$url}");
    exit;
}

function redirect_self(array $params = []): never
{
    $url = $_SERVER['PHP_SELF'];
    if ($params) {
        $url .= '?' . http_build_query($params);
    }

    redirect($url);
}

function redirect_with_message(string $message): never
{
    redirect_self(['message' => $message]);
}

function nullable_int($value): ?int
{
    return $value === '' || $value === null ? null : (int) $value;
}

function page_url(array $params = []): string
{
    $query = array_merge($_GET, $params);

    foreach ($query as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        }
    }

    $url = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
    if ($query) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h_attr(csrf_token()) . '">';
}

function require_valid_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(400);
        die('Ungültige Formularanfrage.');
    }
}

function flash(string $type, string $message): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}

function render_flash(?array $flash): void
{
    if (!$flash) {
        return;
    }

    $type = $flash['type'] === 'danger' ? 'danger' : 'success';
    ?>
    <div class="alert alert-<?= h_attr($type) ?> alert-dismissible fade show" role="alert">
      <?= h($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
    </div>
    <?php
}

function render_options(array $items, $selected, bool $withEmpty = true): void
{
    if ($withEmpty) {
        echo '<option value="">-- Bitte auswählen --</option>';
    }

    foreach ($items as $item) {
        $isSelected = ((string) $item['id'] === (string) $selected) ? ' selected' : '';
        echo '<option value="' . h_attr($item['id']) . '"' . $isSelected . '>' . h($item['label']) . '</option>';
    }
}

function format_date_de($date): string
{
    if (!$date) {
        return '-';
    }

    $parsed = DateTime::createFromFormat('Y-m-d', (string) $date);
    if (!$parsed) {
        return '-';
    }

    return $parsed->format('d.m.Y');
}
