<?php
session_start();

// Gleiches Passwort wie in auth.php (anpassen!)
$correct_password = 'geheim';
$correct_password_kari = '1234';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if ($password === $correct_password) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_level'] = 1;
        // Weiterleitung zur ursprünglichen Seite oder Standardseite, falls keine URL gespeichert wurde
        $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
        header("Location: $redirect");
        exit();
    } elseif ($password === $correct_password_kari) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_level'] = 2;
        // Weiterleitung zur ursprünglichen Seite oder Standardseite, falls keine URL gespeichert wurde
        $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : 'index.php';
        header("Location: $redirect");
        exit();
    } else {
        $error = "Falsches Passwort!";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <!-- Mobile Optimierung -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
      body { font-family: Arial, sans-serif; margin: 20px; }
      .container { max-width: 400px; margin: 0 auto; }
      input[type="password"] { width: 100%; padding: 10px; margin: 10px 0; }
      input[type="submit"] { padding: 10px 20px; }
      .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
      <h2>Bitte Passwort eingeben</h2>
      <?php if (isset($error)) { echo '<p class="error">'.$error.'</p>'; } ?>
      <form method="post" action="">
          <label for="password">Passwort:</label>
          <input type="password" name="password" id="password" required>
          <input type="submit" value="Einloggen">
      </form>
    </div>
</body>
</html>
