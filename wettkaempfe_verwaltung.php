<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

/*

Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Der Seitentitel lautet: "Wettkämpfe".


Die Webseite soll die Bearbeitung der SQL-Tabelle "Wettkaempfe" ermöglichen. Möglich sein soll: 
- Neuen Eintrag hinzufügen,
- Bestehenden bearbeiten,
- bestehenden Löschen.

Alle Einträge aus der SQL-Tabelle sollen in einer Tabelle ausgegeben werden. Sortierung nach dem Alphabet.

Die Spalten "WettkampfmodusID" und "WettkampfSprungmodusID" "GeschlechtID" sollen in den Tabellen "Wettkaempfe_Modi" und "Wettkaempfe_Modi_Sprung" und "Geschlechter" nachgeschlagen werden. Standard soll "gemischt" (also ID=1) sein.

Es sollen Dropdowns für die Nachgeschlagenen Werte verwendet werden.





Bootstrap und PDO sollen verwendet werden.

Um den Fehler: "Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in ..." zu umgehen, nutze die  Funktion htmlspecialchars nicht direkt, sondern nutze eine eigene Funktion, welche bei einem übergebenen null-String einfach "-" ausgibt und andernfalls die Funktion "htmlspecialchars" aufruft.

Für die Anbingung an die Datenbank sollen folgende Variablen verwendet werden: $dbHost, $dbName, $dbUser, $dbPass
und als charset: "utf8".

In der PHP-Datei soll:
- diese Zeile in der Zeile nach "<body>" eingefügt werden: 
  <script src="menu.js"></script>
- diese Zeile in der <head> Section:
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
- Und diese Zeile bevor dem </body> tag:
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
*/

include 'auth.php';
include 'config.php';



try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}

// Eigene Funktion zur Ausgabe mit htmlspecialchars, die null abfängt
function safeHtml($string) {
    return is_null($string) ? '-' : htmlspecialchars($string);
}

// Verarbeiten von Formularaktionen (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Neuen Eintrag hinzufügen
        if ($_POST['action'] === 'add') {
            $beschreibung = $_POST['beschreibung'] ?? '';
            $wettkampfmodusID = $_POST['wettkampfmodusID'] ?? 1;
            $wettkampfSprungmodusID = $_POST['wettkampfSprungmodusID'] ?? 1;
            $geschlechtID = $_POST['geschlechtID'] ?? 1;
            $nWertungen = $_POST['nWertungen'] ?? 4;
            $nGeraeteMax = $_POST['nGeraeteMax'] ?? 4;
            
            $stmt = $pdo->prepare("INSERT INTO Wettkaempfe (Beschreibung, WettkampfmodusID, WettkampfSprungmodusID, GeschlechtID, NWertungen, NGeraeteMax) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$beschreibung, $wettkampfmodusID, $wettkampfSprungmodusID, $geschlechtID, $nWertungen, $nGeraeteMax]);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
        // Bestehenden Eintrag bearbeiten
        if ($_POST['action'] === 'edit' && isset($_POST['WettkampfID'])) {
            $wettkampfID = $_POST['WettkampfID'];
            $beschreibung = $_POST['beschreibung'] ?? '';
            $wettkampfmodusID = $_POST['wettkampfmodusID'] ?? 1;
            $wettkampfSprungmodusID = $_POST['wettkampfSprungmodusID'] ?? 1;
            $geschlechtID = $_POST['geschlechtID'] ?? 1;
            $nWertungen = $_POST['nWertungen'] ?? 4;
            $nGeraeteMax = $_POST['nGeraeteMax'] ?? 4;
            
            $stmt = $pdo->prepare("UPDATE Wettkaempfe SET Beschreibung = ?, WettkampfmodusID = ?, WettkampfSprungmodusID = ?, GeschlechtID = ?, NWertungen = ?, NGeraeteMax = ? WHERE WettkampfID = ?");
            $stmt->execute([$beschreibung, $wettkampfmodusID, $wettkampfSprungmodusID, $geschlechtID, $nWertungen, $nGeraeteMax, $wettkampfID]);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
        // Eintrag löschen
        if ($_POST['action'] === 'delete' && isset($_POST['WettkampfID'])) {
            $wettkampfID = $_POST['WettkampfID'];
            $stmt = $pdo->prepare("DELETE FROM Wettkaempfe WHERE WettkampfID = ?");
            $stmt->execute([$wettkampfID]);
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Lookup-Daten für Dropdowns abrufen
$modiStmt = $pdo->query("SELECT * FROM Wettkaempfe_Modi");
$wettkaempfeModi = $modiStmt->fetchAll(PDO::FETCH_ASSOC);

$sprungModiStmt = $pdo->query("SELECT * FROM Wettkaempfe_Modi_Sprung");
$wettkaempfeModiSprung = $sprungModiStmt->fetchAll(PDO::FETCH_ASSOC);

$geschlechterStmt = $pdo->query("SELECT * FROM Geschlechter");
$geschlechter = $geschlechterStmt->fetchAll(PDO::FETCH_ASSOC);

// Alle Wettkämpfe alphabetisch sortiert abrufen
$stmt = $pdo->query("SELECT * FROM Wettkaempfe ORDER BY Beschreibung ASC");
$wettkaempfe = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Wenn ein Bearbeiten-Parameter übergeben wurde, Datensatz zum Editieren laden
$editRecord = null;
if (isset($_GET['edit'])) {
    $editID = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM Wettkaempfe WHERE WettkampfID = ?");
    $stmt->execute([$editID]);
    $editRecord = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Wettkämpfe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<script src="menu.js"></script>
<div class="container mt-4">
  <h1 class="mb-4">Wettkämpfe</h1>

  <!-- Tabelle mit allen Wettkämpfen -->
  <table class="table table-striped table-responsive">
    <thead>
      <tr>
        <th>Beschreibung</th>
        <th>Sprungmodus</th>
        <th>Geschlecht</th>
        <th>Anzahl Wertungen</th>
        <th>Max. Geräte</th>
        <th>Aktionen</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($wettkaempfe as $wettkampf): ?>
      <tr>
        <td><?= safeHtml($wettkampf['Beschreibung']) ?></td>
        <td>
          <?php 
            $sprung = '-';
            foreach ($wettkaempfeModiSprung as $s) {
              if ($s['WettkampfSprungmodusID'] == $wettkampf['WettkampfSprungmodusID']) {
                $sprung = safeHtml($s['Beschreibung']);
                break;
              }
            }
            echo $sprung;
          ?>
        </td>
        <td>
          <?php 
            $geschlecht = '-';
            foreach ($geschlechter as $g) {
              if ($g['GeschlechtID'] == $wettkampf['GeschlechtID']) {
                $geschlecht = safeHtml($g['Beschreibung']);
                break;
              }
            }
            echo $geschlecht;
          ?>
        </td>
        <td><?= safeHtml($wettkampf['NWertungen']) ?></td>
        <td><?= safeHtml($wettkampf['NGeraeteMax']) ?></td>
        <td>
          <a href="?edit=<?= $wettkampf['WettkampfID'] ?>" class="btn btn-sm btn-primary">Bearbeiten</a>
          <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post" style="display:inline-block;" onsubmit="return confirm('Wollen Sie diesen Eintrag wirklich löschen?');">
            <input type="hidden" name="WettkampfID" value="<?= $wettkampf['WettkampfID'] ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-sm btn-danger">Löschen</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Formular zum Hinzufügen / Bearbeiten -->
  <div class="card mt-4">
    <div class="card-header">
      <?php if ($editRecord): ?>
        Wettkampf bearbeiten
      <?php else: ?>
        Neuen Wettkampf hinzufügen
      <?php endif; ?>
    </div>
    <div class="card-body">
      <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
        <?php if ($editRecord): ?>
          <input type="hidden" name="WettkampfID" value="<?= $editRecord['WettkampfID'] ?>">
          <input type="hidden" name="action" value="edit">
        <?php else: ?>
          <input type="hidden" name="action" value="add">
        <?php endif; ?>
        <div class="mb-3">
          <label for="beschreibung" class="form-label">Beschreibung</label>
          <input type="text" class="form-control" id="beschreibung" name="beschreibung" value="<?= $editRecord ? safeHtml($editRecord['Beschreibung']) : '' ?>" required>
        </div>
        <div class="mb-3">
          <label for="wettkampfmodusID" class="form-label">Wettkampfmodus</label>
          <select class="form-select" id="wettkampfmodusID" name="wettkampfmodusID">
            <?php foreach ($wettkaempfeModi as $m): ?>
              <option value="<?= $m['WettkampfmodusID'] ?>" <?= ($editRecord && $editRecord['WettkampfmodusID'] == $m['WettkampfmodusID']) || (!$editRecord && $m['WettkampfmodusID'] == 1) ? 'selected' : '' ?>>
                <?= safeHtml($m['Beschreibung']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label for="wettkampfSprungmodusID" class="form-label">Sprungmodus</label>
          <select class="form-select" id="wettkampfSprungmodusID" name="wettkampfSprungmodusID">
            <?php foreach ($wettkaempfeModiSprung as $s): ?>
              <option value="<?= $s['WettkampfSprungmodusID'] ?>" <?= ($editRecord && $editRecord['WettkampfSprungmodusID'] == $s['WettkampfSprungmodusID']) || (!$editRecord && $s['WettkampfSprungmodusID'] == 1) ? 'selected' : '' ?>>
                <?= safeHtml($s['Beschreibung']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label for="geschlechtID" class="form-label">Geschlecht</label>
          <select class="form-select" id="geschlechtID" name="geschlechtID">
            <?php foreach ($geschlechter as $g): ?>
              <option value="<?= $g['GeschlechtID'] ?>" <?= ($editRecord && $editRecord['GeschlechtID'] == $g['GeschlechtID']) || (!$editRecord && $g['GeschlechtID'] == 1) ? 'selected' : '' ?>>
                <?= safeHtml($g['Beschreibung']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label for="nWertungen" class="form-label">Anzahl Wertungen</label>
          <input type="number" class="form-control" id="nWertungen" name="nWertungen" value="<?= $editRecord ? safeHtml($editRecord['NWertungen']) : '4' ?>" required>
        </div>
        <div class="mb-3">
          <label for="nGeraeteMax" class="form-label">Maximale Anzahl turnbarer Geräte</label>
          <input type="number" class="form-control" id="nGeraeteMax" name="nGeraeteMax" value="<?= $editRecord ? safeHtml($editRecord['NGeraeteMax']) : '4' ?>" required>
        </div>
        <button type="submit" class="btn btn-success"><?= $editRecord ? 'Änderungen speichern' : 'Hinzufügen' ?></button>
        <?php if ($editRecord): ?>
          <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Abbrechen</a>
        <?php endif; ?>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
