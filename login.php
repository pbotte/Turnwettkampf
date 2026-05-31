<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once 'includes/auth_helpers.php';
require_once 'includes/helpers.php';
require_once 'includes/layout.php';

ensure_session_started();

$error = $_GET['message'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $level = login_level_for_password($password);

    if ($level !== null) {
        login_user($level);
        redirect_after_login();
    }

    $error = "Falsches Passwort!";
}
render_header('Login', ['includeMenu' => false, 'bodyClass' => 'bg-light']);
?>
  <div class="container my-5" style="max-width: 420px;">
    <div class="card shadow-sm">
      <div class="card-body">
        <h1 class="h4 mb-3">Bitte Passwort eingeben</h1>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="post" action="">
          <div class="mb-3">
            <label for="password" class="form-label">Passwort</label>
            <input type="password" name="password" id="password" class="form-control" required autofocus>
          </div>
          <button type="submit" class="btn btn-primary w-100">Einloggen</button>
        </form>
      </div>
    </div>
  </div>
<?php render_footer(); ?>
