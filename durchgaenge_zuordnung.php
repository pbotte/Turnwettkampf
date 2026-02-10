<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

/*
Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Die Webseite soll die Bearbeitung der SQL-Tabelle "Verbindung_Durchgaenge_Riegen_Geraete" ermöglichen. 

Angezeigt werden soll eine Tabelle, in der für jede Riege eine Zeile vorhanden ist.
Der Name der Riege wird in der ersten Spalte angzeigt.
Für jeden Durchgang gibt es nun eine weitere Spalte.
Der Name des Durchgangs erscheint in der obersten Zeile als Titel.
In jedem Feld kann nun ein Gerät aus der Tabelle "Geraete" aus einem Dropdownmenü ausgewählt werden.

Wird nun auf Speichern gedrückt, so soll für jede Auswahl ein Eintrage in der Tabelle "Verbindung_Durchgaenge_Riegen_Geraete" mit der 
korrekte RiegenID und DurchgangID und ausgewählten GeraetID.

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

// Formularverarbeitung: Beim Klick auf "Speichern" wird die gesamte Matrix verarbeitet.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    try {
        $pdo->beginTransaction();
        // Alte Zuordnungen löschen
        $pdo->exec("DELETE FROM Verbindung_Durchgaenge_Riegen_Geraete");
        
        // $_POST['geraet'] ist ein mehrdimensionales Array: [RiegenID][DurchgangID] => GeraetID
        if (isset($_POST['geraet']) && is_array($_POST['geraet'])) {
            $stmtInsert = $pdo->prepare("INSERT INTO Verbindung_Durchgaenge_Riegen_Geraete (RiegenID, DurchgangID, GeraetID) VALUES (?, ?, ?)");
            foreach ($_POST['geraet'] as $riegeID => $durchgaenge) {
                foreach ($durchgaenge as $durchgangID => $geraetID) {
                    // Nur speichern, wenn ein Gerät ausgewählt wurde
                    if ($geraetID !== '') {
                        $stmtInsert->execute([$riegeID, $durchgangID, $geraetID]);
                    }
                }
            }
        }
        $pdo->commit();
        $message = "Speichern erfolgreich.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Fehler beim Speichern: " . $e->getMessage();
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?message=" . urlencode($message));
    exit;
}

// Falls eine Nachricht via GET übergeben wurde
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}

// Daten laden:
// Riegen (Zeilen)
$stmtRiegen = $pdo->query("SELECT * FROM Riegen ORDER BY Beschreibung ASC");
$riegen = $stmtRiegen->fetchAll();

// Durchgaenge (Spalten) – sortiert nach der Spalte "Reihenfolge"
$stmtDurchgaenge = $pdo->query("SELECT * FROM Durchgaenge ORDER BY Reihenfolge ASC");
$durchgaenge = $stmtDurchgaenge->fetchAll();

// Alle Geräte für das Dropdown
$stmtGeraete = $pdo->query("SELECT * FROM Geraete ORDER BY GeraetID ASC");
$geraete = $stmtGeraete->fetchAll();

// Vorhandene Zuordnungen aus Verbindung_Durchgaenge_Riegen_Geraete
$assignments = [];
$stmtAssignments = $pdo->query("SELECT * FROM Verbindung_Durchgaenge_Riegen_Geraete");
while ($row = $stmtAssignments->fetch()) {
    $assignments[$row['RiegenID']][$row['DurchgangID']] = $row['GeraetID'];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Verbindung Durchgaenge Riegen Geraete</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f6f7fb;
    }
    .page-wrap {
      max-width: 1200px;
    }
    .panel {
      background: #fff;
      border-radius: 16px;
      padding: 16px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    }
    .form-select {
      font-size: 0.95rem;
      min-width: 180px;
    }
    .table thead th {
      position: sticky;
      top: 0;
      background: #fff;
      z-index: 2;
    }
    .table thead th:first-child,
    .table tbody td:first-child {
      position: sticky;
      left: 0;
      background: #fff;
      z-index: 1;
    }
    .table thead th:first-child {
      z-index: 3;
    }
    @media (max-width: 768px) {
      .table thead th,
      .table tbody td {
        white-space: nowrap;
      }
      .form-select {
        min-width: 150px;
      }
      .table-responsive {
        border-radius: 12px;
      }
    }
  </style>
</head>
<body>
<script src="menu.js"></script>
<div class="container my-4 page-wrap">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="m-0">Verbindung Durchgaenge Riegen Geraete</h1>
    <button type="submit" form="zuordnungForm" class="btn btn-primary">Speichern</button>
  </div>
  
  <?php if ($message): ?>
    <div class="alert alert-success"><?= safeHtml($message) ?></div>
  <?php endif; ?>
  
  <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>" id="zuordnungForm">
    <input type="hidden" name="action" value="save">
    <div class="table-responsive panel">
      <table class="table table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th>Riege</th>
            <?php foreach ($durchgaenge as $durchgang): ?>
              <th><?= safeHtml($durchgang['Beschreibung']) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($riegen as $riege): ?>
            <tr>
              <td><?= safeHtml($riege['Beschreibung']) ?></td>
              <?php foreach ($durchgaenge as $durchgang): ?>
                <?php
                  // Vorab selektierter GeraetID, falls vorhanden
                  $selected = '';
                  if (isset($assignments[$riege['RiegenID']][$durchgang['DurchgangID']])) {
                      $selected = $assignments[$riege['RiegenID']][$durchgang['DurchgangID']];
                  }
                ?>
                <td>
                  <select class="form-select" name="geraet[<?= safeHtml($riege['RiegenID']) ?>][<?= safeHtml($durchgang['DurchgangID']) ?>]">
                    <option value="">-- bitte wählen --</option>
                    <?php foreach ($geraete as $geraet): ?>
                      <option value="<?= safeHtml($geraet['GeraetID']) ?>" <?= ($geraet['GeraetID'] == $selected ? 'selected' : '') ?>>
                        <?= safeHtml($geraet['Beschreibung']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </form>
</div>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
