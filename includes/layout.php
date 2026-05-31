<?php

require_once __DIR__ . '/helpers.php';

function render_header(string $title, array $options = []): void
{
    $includeMenu = $options['includeMenu'] ?? true;
    $bodyClass = $options['bodyClass'] ?? '';
    $extraCss = $options['extraCss'] ?? '';
    $includeAppCss = $options['includeAppCss'] ?? true;
    $includeBootstrap = $options['includeBootstrap'] ?? true;
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($title) ?></title>
<?php if ($includeBootstrap): ?>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<?php endif; ?>
<?php if ($includeAppCss): ?>
  <link href="assets/app.css" rel="stylesheet">
<?php endif; ?>
<?php if ($extraCss !== ''): ?>
  <style>
<?= $extraCss ?>
  </style>
<?php endif; ?>
</head>
<body<?= $bodyClass !== '' ? ' class="' . h_attr($bodyClass) . '"' : '' ?>>
<?php if ($includeMenu): ?>
<script src="menu.js"></script>
<?php endif; ?>
<?php
}

function render_footer(array $options = []): void
{
    $includeBootstrap = $options['includeBootstrap'] ?? true;
    ?>
<?php if ($includeBootstrap): ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php endif; ?>
</body>
</html>
<?php
}
