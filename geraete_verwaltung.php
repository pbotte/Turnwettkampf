<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');
?>
<?php /*
Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Die Webseite soll die Bearbeitung der SQL-Tabelle "Geraete" ermöglichen. Möglich sein soll: 
- Neuen Eintrag hinzufügen,
- Bestehenden bearbeiten,
- bestehenden Löschen.

Alle Einträge aus der SQL-Tabelle sollen in einer Tabelle ausgegeben werden. 

Die Spalte "GeraeteTypeID" soll dabei in der SQL-Tabelle "GeraeteTypen" nachgeschlagen werden.

Bootstrap und PDO sollen verwendet werden.

Aktuell erhalte ich den Fehler: "Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in ..." 
Um dies zu lösen, ersetze bei der Nutzung von htmlspecialchars durch eine eigene Funktion, welche bei einem übergebenen null-String einfach "-" ausgibt und sonst die Funktion "htmlspecialchars" aufruft.

Für die Anbingung an die Datenbank sollen folgende Variablen verwendet werden: $dbHost, $dbName, $dbUser, $dbPass
und als charset: "utf8".
*/
include 'auth.php';
include 'config.php';



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
        // Neuen Eintrag einfügen; falls kein Gerätetyp gewählt wurde, wird NULL gespeichert (Pause)
        $geraeteTypID = $_POST['GeraeteTypID'] ?? '';
        $geraeteTypID = ($geraeteTypID === '' ? null : $geraeteTypID);
        $beschreibung = $_POST['Beschreibung'] ?? null;
        $stmt = $pdo->prepare("INSERT INTO Geraete (GeraeteTypID, Beschreibung) VALUES (?, ?)");
        $stmt->execute([$geraeteTypID, $beschreibung]);
        $message = "Eintrag hinzugefügt.";
    } elseif ($action === 'edit') {
        // Bestehenden Eintrag bearbeiten
        $geraetID = $_POST['GeraetID'] ?? '';
        $geraeteTypID = $_POST['GeraeteTypID'] ?? '';
        $geraeteTypID = ($geraeteTypID === '' ? null : $geraeteTypID);
        $beschreibung = $_POST['Beschreibung'] ?? null;
        $stmt = $pdo->prepare("UPDATE Geraete SET GeraeteTypID = ?, Beschreibung = ? WHERE GeraetID = ?");
        $stmt->execute([$geraeteTypID, $beschreibung, $geraetID]);
        $message = "Eintrag aktualisiert.";
    } elseif ($action === 'delete') {
        // Eintrag löschen
        $geraetID = $_POST['GeraetID'] ?? '';
        $stmt = $pdo->prepare("DELETE FROM Geraete WHERE GeraetID = ?");
        $stmt->execute([$geraetID]);
        $message = "Eintrag gelöscht.";
    }
    // Redirect nach POST, um erneutes Senden zu vermeiden
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Prüfen, ob ein Formular zum Hinzufügen oder Bearbeiten angezeigt werden soll
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

// Beim Bearbeiten den entsprechenden Datensatz laden
if ($action === 'edit' && $id) {
    $stmt = $pdo->prepare("SELECT * FROM Geraete WHERE GeraetID = ?");
    $stmt->execute([$id]);
    $entry = $stmt->fetch();
    if (!$entry) {
        $message = "Eintrag nicht gefunden.";
        $action = '';
    }
}

// Für das Dropdown: Gerätetypen laden (sortiert nach Reihenfolge)
$stmt = $pdo->query("SELECT * FROM GeraeteTypen ORDER BY Reihenfolge ASC");
$geraeteTypen = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Geräte Verwaltung</title>
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
    .form-select,
    .form-control {
      font-size: 1.05rem;
    }
    .action-group {
      display: flex;
      gap: 0.5rem;
      align-items: center;
    }
    .action-group form {
      margin: 0;
    }
    .action-group .btn {
      white-space: nowrap;
    }
    @media (max-width: 768px) {
      .table-mobile thead {
        display: none;
      }
      .table-mobile tr {
        display: block;
        margin-bottom: 0.75rem;
        border: 1px solid #e6e6e6;
        border-radius: 12px;
        padding: 0.25rem 0;
        background: #fff;
      }
      .table-mobile td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem 0.75rem;
        border-top: 1px solid #f0f0f0;
      }
      .table-mobile td:first-child {
        border-top: 0;
      }
      .table-mobile td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #6c757d;
        margin-right: 1rem;
      }
      .table-mobile .action-cell {
        justify-content: flex-end;
      }
      .table-mobile .action-cell::before {
        content: "";
      }
      .action-group {
        flex-direction: column;
        width: 100%;
      }
      .action-group .btn {
        width: 100%;
      }
    }
  </style>
</head>
<body>
<script src="menu.js"></script>
<div class="container my-4 page-wrap">
  <h1 class="mb-4">Geräte Verwaltung</h1>
  
  <?php if ($message): ?>
    <div class="alert alert-success"><?= safeHtml($message) ?></div>
  <?php endif; ?>
  
  <?php if ($action === 'add' || ($action === 'edit' && isset($entry))): ?>
    <div class="panel mb-4">
      <div class="mb-3 fw-semibold">
        <?= $action === 'add' ? 'Neuen Eintrag hinzufügen' : 'Eintrag bearbeiten' ?>
      </div>
      <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <?php if ($action === 'edit'): ?>
          <input type="hidden" name="GeraetID" value="<?= safeHtml($entry['GeraetID']) ?>">
        <?php endif; ?>
        <input type="hidden" name="action" value="<?= safeHtml($action) ?>">
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label for="GeraeteTypID" class="form-label">Gerätetyp</label>
            <select class="form-select" name="GeraeteTypID" id="GeraeteTypID">
              <option value="">Pause</option>
              <?php foreach ($geraeteTypen as $typ): ?>
                <?php 
                  $selected = '';
                  if ($action === 'edit' && isset($entry)) {
                    if ($entry['GeraeteTypID'] == $typ['GeraeteTypID']) {
                      $selected = 'selected';
                    }
                  }
                ?>
                <option value="<?= safeHtml($typ['GeraeteTypID']) ?>" <?= $selected ?>>
                  <?= safeHtml($typ['Beschreibung']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label for="Beschreibung" class="form-label">Beschreibung</label>
            <input type="text" class="form-control" name="Beschreibung" id="Beschreibung" value="<?= $action === 'edit' ? safeHtml($entry['Beschreibung']) : '' ?>" required>
          </div>
        </div>
        <div class="d-grid d-md-flex gap-2 mt-3">
          <button type="submit" class="btn btn-primary"><?= $action === 'add' ? 'Hinzufügen' : 'Aktualisieren' ?></button>
          <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Abbrechen</a>
        </div>
      </form>
    </div>
  <?php else: ?>
    <!-- Übersicht aller Einträge -->
    <div class="panel mb-3">
      <div class="d-grid d-md-flex justify-content-md-start">
        <a href="<?= $_SERVER['PHP_SELF'] ?>?action=add" class="btn btn-success">Neuen Eintrag hinzufügen</a>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-striped table-mobile align-middle">
        <thead>
          <tr>
            <th>GeraetID</th>
            <th>Gerätetyp</th>
            <th>Beschreibung</th>
            <th>Aktionen</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Alle Einträge aus Geraete anzeigen, Gerätetypen über LEFT JOIN nachschlagen
          $stmt = $pdo->query("SELECT g.*, gt.Beschreibung AS TypBeschreibung FROM Geraete g LEFT JOIN GeraeteTypen gt ON g.GeraeteTypID = gt.GeraeteTypID ORDER BY g.GeraetID ASC");
          while ($row = $stmt->fetch()):
          ?>
            <tr>
              <td data-label="GeraetID"><?= safeHtml($row['GeraetID']) ?></td>
              <td data-label="Gerätetyp"><?= $row['GeraeteTypID'] === null ? 'Pause' : safeHtml($row['TypBeschreibung']) ?></td>
              <td data-label="Beschreibung"><?= safeHtml($row['Beschreibung']) ?></td>
              <td data-label="Aktionen" class="action-cell">
                <div class="action-group">
                  <a href="<?= $_SERVER['PHP_SELF'] ?>?action=edit&id=<?= safeHtml($row['GeraetID']) ?>" class="btn btn-sm btn-primary">Bearbeiten</a>
                  <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>" onsubmit="return confirm('Wollen Sie diesen Eintrag wirklich löschen?');">
                    <input type="hidden" name="GeraetID" value="<?= safeHtml($row['GeraetID']) ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-sm btn-danger">Löschen</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
