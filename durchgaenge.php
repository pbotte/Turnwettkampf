<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

/*
Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Die Webseite soll die Bearbeitung der SQL-Tabelle "Durchgaenge" ermöglichen. 
Möglich sein soll: 
- Neuen Eintrag hinzufügen,
- Bestehenden bearbeiten,
- bestehenden Löschen.

Alle Einträge aus der SQL-Tabelle sollen in einer Tabelle ausgegeben werden. Sortierung nach der Spalte Reihenfolge. 

Standard beim Eingaben der Werte soll sein:
- Startzeitpunkt soll NULL sein

Bootstrap und PDO sollen verwendet werden.

Aktuell erhalte ich den Fehler: "Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in ..." 
Um dies zu lösen, ersetze bei der Nutzung von htmlspecialchars durch eine eigene Funktion, welche bei einem übergebenen null-String einfach "-" ausgibt und sonst die Funktion "htmlspecialchars" aufruft.

Für die Anbingung an die Datenbank sollen folgende Variablen verwendet werden: $dbHost, $dbName, $dbUser, $dbPass
und als charset: "utf8".
*/

include 'auth.php';
include 'config.php';
// Datenbankverbindungsparameter anpassen!


$charset = 'utf8';

$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    die("Datenbank-Verbindung fehlgeschlagen: " . $e->getMessage());
}

// Eigene Funktion, die null-Werte abfängt und ansonsten htmlspecialchars aufruft
function safeHtml($string) {
    if ($string === null) {
        return '-';
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

$message = '';

// Formularverarbeitung (Hinzufügen, Bearbeiten, Löschen)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        // Neuen Eintrag einfügen (Startzeitpunkt wird als NULL gesetzt)
        $reihenfolge  = $_POST['Reihenfolge'] ?? null;
        $beschreibung = $_POST['Beschreibung'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO Durchgaenge (Reihenfolge, Beschreibung, Startzeitpunkt) VALUES (?, ?, NULL)");
        $stmt->execute([$reihenfolge, $beschreibung]);
        $message = "Eintrag hinzugefügt.";
    } elseif ($action === 'edit') {
        // Bestehenden Eintrag bearbeiten
        $id           = $_POST['DurchgangID'] ?? null;
        $reihenfolge  = $_POST['Reihenfolge'] ?? null;
        $beschreibung = $_POST['Beschreibung'] ?? null;
        $stmt = $pdo->prepare("UPDATE Durchgaenge SET Reihenfolge = ?, Beschreibung = ? WHERE DurchgangID = ?");
        $stmt->execute([$reihenfolge, $beschreibung, $id]);
        $message = "Eintrag aktualisiert.";
    } elseif ($action === 'delete') {
        // Eintrag löschen
        $id = $_POST['DurchgangID'] ?? null;
        $stmt = $pdo->prepare("DELETE FROM Durchgaenge WHERE DurchgangID = ?");
        $stmt->execute([$id]);
        $message = "Eintrag gelöscht.";
    }
    // Nach der Aktion wird per Redirect ein erneutes Senden des Formulars vermieden
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Prüfen, ob ein Formular zum Hinzufügen oder Bearbeiten angezeigt werden soll
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Durchgänge Verwaltung</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      padding: 20px;
    }
  </style>
</head>
<body>
<div class="container">
  <h1 class="mb-4">Durchgänge Verwaltung</h1>
  
  <?php if ($message): ?>
    <div class="alert alert-success"><?= safeHtml($message) ?></div>
  <?php endif; ?>

  <?php if ($action === 'add' || ($action === 'edit' && $id)): ?>
    <?php
    // Bei Bearbeitung: Datensatz laden
    if ($action === 'edit' && $id) {
        $stmt = $pdo->prepare("SELECT * FROM Durchgaenge WHERE DurchgangID = ?");
        $stmt->execute([$id]);
        $entry = $stmt->fetch();
        if (!$entry) {
            echo '<div class="alert alert-danger">Eintrag nicht gefunden.</div>';
            $action = ''; // Aktion zurücksetzen
        }
    }
    ?>
    <?php if ($action === 'add' || ($action === 'edit' && isset($entry))): ?>
      <div class="card mb-4">
        <div class="card-header">
          <?= ($action === 'add' ? 'Neuen Eintrag hinzufügen' : 'Eintrag bearbeiten') ?>
        </div>
        <div class="card-body">
          <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
            <?php if ($action === 'edit'): ?>
              <input type="hidden" name="DurchgangID" value="<?= safeHtml($entry['DurchgangID']) ?>">
            <?php endif; ?>
            <input type="hidden" name="action" value="<?= safeHtml($action) ?>">
            <div class="mb-3">
              <label for="Reihenfolge" class="form-label">Reihenfolge</label>
              <input type="number" class="form-control" name="Reihenfolge" id="Reihenfolge" value="<?= $action === 'edit' ? safeHtml($entry['Reihenfolge']) : '' ?>" required>
            </div>
            <div class="mb-3">
              <label for="Beschreibung" class="form-label">Beschreibung</label>
              <textarea class="form-control" name="Beschreibung" id="Beschreibung"><?= $action === 'edit' ? safeHtml($entry['Beschreibung']) : '' ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><?= $action === 'add' ? 'Hinzufügen' : 'Aktualisieren' ?></button>
            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Abbrechen</a>
          </form>
        </div>
      </div>
    <?php endif; ?>
  <?php else: ?>
    <!-- Übersicht aller Einträge -->
    <div class="mb-3">
      <a href="<?= $_SERVER['PHP_SELF'] ?>?action=add" class="btn btn-success">Neuen Eintrag hinzufügen</a>
    </div>
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Reihenfolge</th>
          <th>Beschreibung</th>
          <th>Startzeitpunkt</th>
          <th>Aktionen</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $stmt = $pdo->query("SELECT * FROM Durchgaenge ORDER BY Reihenfolge ASC");
        while ($row = $stmt->fetch()):
        ?>
          <tr>
            <td><?= safeHtml($row['Reihenfolge']) ?></td>
            <td><?= safeHtml($row['Beschreibung']) ?></td>
            <td><?= safeHtml($row['Startzeitpunkt']) ?></td>
            <td>
              <a href="<?= $_SERVER['PHP_SELF'] ?>?action=edit&id=<?= safeHtml($row['DurchgangID']) ?>" class="btn btn-sm btn-primary">Bearbeiten</a>
              <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>" style="display:inline-block;" onsubmit="return confirm('Wollen Sie diesen Eintrag wirklich löschen?');">
                <input type="hidden" name="DurchgangID" value="<?= safeHtml($row['DurchgangID']) ?>">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-sm btn-danger">Löschen</button>
              </form>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
