<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

/*
Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Die Webseite soll die Bearbeitung der SQL-Tabelle "Vereine" ermöglichen. Möglich sein soll: 
- Neuen Eintrag hinzufügen,
- Bestehenden bearbeiten,
- bestehenden Löschen.

Alle Einträge aus der SQL-Tabelle sollen in einer Tabelle ausgegeben werden. Sortierung nach dem Alphabet.

Bootstrap und PDO sollen verwendet werden.

Um den Fehler: "Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in ..." zu umgehen, nutze die  Funktion htmlspecialchars nicht direkt, sondern nutze eine eigene Funktion, welche bei einem übergebenen null-String einfach "-" ausgibt und andernfalls die Funktion "htmlspecialchars" aufruft.

*/

include 'auth.php';
include 'config.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}

// Eigene Funktion zur Umgehung des htmlspecialchars()-Fehlers bei NULL
function custom_htmlspecialchars($string) {
    if ($string === null) {
        return "-";
    } else {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'add') {
    // Neuer Eintrag hinzufügen
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $vereinsname = $_POST['Vereinsname'];
        $stadt       = $_POST['Stadt'];

        $stmt = $pdo->prepare("INSERT INTO Vereine (Vereinsname, Stadt) VALUES (?, ?)");
        $stmt->execute([$vereinsname, $stadt]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Neuen Verein hinzufügen</title>
      <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
      <div class="container">
        <h1 class="mt-4">Neuen Verein hinzufügen</h1>
        <form method="post" action="">
          <div class="form-group">
            <label>Vereinsname</label>
            <input type="text" name="Vereinsname" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Stadt</label>
            <input type="text" name="Stadt" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary">Hinzufügen</button>
          <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Abbrechen</a>
        </form>
      </div>
    </body>
    </html>
    <?php
    exit;
} elseif ($action == 'edit') {
    // Bestehenden Eintrag bearbeiten
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $vereinsname = $_POST['Vereinsname'];
        $stadt       = $_POST['Stadt'];

        $stmt = $pdo->prepare("UPDATE Vereine SET Vereinsname = ?, Stadt = ? WHERE VereinID = ?");
        $stmt->execute([$vereinsname, $stadt, $id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    // Datensatz laden
    $stmt = $pdo->prepare("SELECT * FROM Vereine WHERE VereinID = ?");
    $stmt->execute([$id]);
    $verein = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$verein) {
        die("Verein nicht gefunden.");
    }
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Verein bearbeiten</title>
      <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
      <div class="container">
        <h1 class="mt-4">Verein bearbeiten</h1>
        <form method="post" action="">
          <div class="form-group">
            <label>Vereinsname</label>
            <input type="text" name="Vereinsname" class="form-control" value="<?= custom_htmlspecialchars($verein['Vereinsname']) ?>" required>
          </div>
          <div class="form-group">
            <label>Stadt</label>
            <input type="text" name="Stadt" class="form-control" value="<?= custom_htmlspecialchars($verein['Stadt']) ?>" required>
          </div>
          <button type="submit" class="btn btn-primary">Speichern</button>
          <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Abbrechen</a>
        </form>
      </div>
    </body>
    </html>
    <?php
    exit;
} elseif ($action == 'delete') {
    // Eintrag löschen
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $stmt = $pdo->prepare("DELETE FROM Vereine WHERE VereinID = ?");
        $stmt->execute([$id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    // Datensatz laden
    $stmt = $pdo->prepare("SELECT * FROM Vereine WHERE VereinID = ?");
    $stmt->execute([$id]);
    $verein = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$verein) {
        die("Verein nicht gefunden.");
    }
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Verein löschen</title>
      <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
      <div class="container">
        <h1 class="mt-4">Verein löschen</h1>
        <p>Sind Sie sicher, dass Sie folgenden Verein löschen möchten?</p>
        <p><?= custom_htmlspecialchars($verein['Vereinsname']) ?> (<?= custom_htmlspecialchars($verein['Stadt']) ?>)</p>
        <form method="post" action="">
          <button type="submit" class="btn btn-danger">Löschen</button>
          <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Abbrechen</a>
        </form>
      </div>
    </body>
    </html>
    <?php
    exit;
}

// Standard: Alle Einträge anzeigen, sortiert nach Vereinsname
$stmt = $pdo->prepare("SELECT * FROM Vereine ORDER BY Vereinsname");
$stmt->execute();
$vereineListe = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Vereinsverwaltung</title>
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <style>
    table { font-size: 0.9rem; }
  </style>
</head>
<body>
  <div class="container">
    <h1 class="mt-4">Vereinsverwaltung (<a href="/">zurück</a>)</h1>
    <a href="?action=add" class="btn btn-success mb-3">Neuen Verein hinzufügen</a>
    <table class="table table-striped table-responsive">
      <thead>
        <tr>
          <th>Vereinsname</th>
          <th>Stadt</th>
          <th>Aktionen</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($vereineListe as $verein): ?>
        <tr>
          <td><?= custom_htmlspecialchars($verein['Vereinsname']) ?></td>
          <td><?= custom_htmlspecialchars($verein['Stadt']) ?></td>
          <td>
            <a href="?action=edit&id=<?= $verein['VereinID'] ?>" class="btn btn-primary btn-sm">Bearbeiten</a>
            <a href="?action=delete&id=<?= $verein['VereinID'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Wollen Sie diesen Verein wirklich löschen?')">Löschen</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
