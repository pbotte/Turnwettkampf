<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

include 'auth.php';
include 'config.php';

$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    die("Datenbank-Verbindung fehlgeschlagen: " . $e->getMessage());
}

function safeHtml($value) {
    return $value === null ? '-' : htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect_to_self($message) {
    header("Location: durchgaenge.php?message=" . urlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['DurchgangID']) ? (int)$_POST['DurchgangID'] : 0;
    $reihenfolge = $_POST['Reihenfolge'] ?? null;
    $beschreibung = $_POST['Beschreibung'] ?? null;

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO Durchgaenge (Reihenfolge, Beschreibung, Startzeitpunkt) VALUES (?, ?, NULL)");
        $stmt->execute([$reihenfolge, $beschreibung]);
        redirect_to_self("Durchgang wurde hinzugefügt.");
    }

    if ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE Durchgaenge SET Reihenfolge = ?, Beschreibung = ? WHERE DurchgangID = ?");
        $stmt->execute([$reihenfolge, $beschreibung, $id]);
        redirect_to_self("Durchgang wurde aktualisiert.");
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM Durchgaenge WHERE DurchgangID = ?");
        $stmt->execute([$id]);
        redirect_to_self("Durchgang wurde gelöscht.");
    }
}

$stmt = $pdo->query(
    "SELECT d.*, COALESCE(vc.AnzahlZuordnungen, 0) AS AnzahlZuordnungen
     FROM Durchgaenge d
     LEFT JOIN (
       SELECT DurchgangID, COUNT(*) AS AnzahlZuordnungen
       FROM Verbindung_Durchgaenge_Riegen_Geraete
       GROUP BY DurchgangID
     ) vc ON vc.DurchgangID = d.DurchgangID
     ORDER BY d.Reihenfolge ASC"
);
$entries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Durchgänge Verwaltung</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f6f7fb; }
    .page-wrap { max-width: 1200px; }
    .panel {
      background: #fff;
      border-radius: 8px;
      padding: 16px;
      box-shadow: 0 4px 14px rgba(0,0,0,0.07);
    }
    .clickable-row { cursor: pointer; }
    .clickable-row:hover { --bs-table-bg: #eef4ff; }
    @media (max-width: 768px) {
      .table-mobile thead { display: none; }
      .table-mobile tr {
        display: block;
        margin-bottom: .75rem;
        border: 1px solid #e6e6e6;
        border-radius: 8px;
        background: #fff;
      }
      .table-mobile td {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: .5rem .75rem;
        border-top: 1px solid #f0f0f0;
      }
      .table-mobile td:first-child { border-top: 0; }
      .table-mobile td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #6c757d;
      }
    }
  </style>
</head>
<body>
<script src="menu.js"></script>
<div class="container my-4 page-wrap">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="m-0">Durchgänge Verwaltung</h1>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addDurchgangModal">Hinzufügen</button>
  </div>

  <?php if (isset($_GET['message'])): ?>
    <div class="alert alert-info"><?= safeHtml($_GET['message']) ?></div>
  <?php endif; ?>

  <div class="table-responsive panel">
    <table class="table table-striped table-mobile align-middle mb-0">
      <thead>
        <tr>
          <th>Reihenfolge</th>
          <th>Beschreibung</th>
          <th>Startzeitpunkt</th>
          <th class="text-center">Zuordnungen</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$entries): ?>
          <tr><td colspan="4" class="text-center text-muted py-4">Keine Einträge gefunden.</td></tr>
        <?php endif; ?>
        <?php foreach ($entries as $entry): ?>
          <tr class="clickable-row" data-bs-toggle="modal" data-bs-target="#editDurchgangModal<?= safeHtml($entry['DurchgangID']) ?>">
            <td data-label="Reihenfolge"><?= safeHtml($entry['Reihenfolge']) ?></td>
            <td data-label="Beschreibung"><?= safeHtml($entry['Beschreibung']) ?></td>
            <td data-label="Startzeitpunkt"><?= safeHtml($entry['Startzeitpunkt']) ?></td>
            <td data-label="Zuordnungen" class="text-center"><?= safeHtml($entry['AnzahlZuordnungen']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="addDurchgangModal" tabindex="-1" aria-labelledby="addDurchgangLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addDurchgangLabel">Durchgang hinzufügen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <form method="post" action="durchgaenge.php">
        <div class="modal-body">
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label for="add_Reihenfolge" class="form-label">Reihenfolge</label>
            <input type="number" class="form-control" name="Reihenfolge" id="add_Reihenfolge" required>
          </div>
          <div>
            <label for="add_Beschreibung" class="form-label">Beschreibung</label>
            <textarea class="form-control" name="Beschreibung" id="add_Beschreibung"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
          <button type="submit" class="btn btn-success">Hinzufügen</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php foreach ($entries as $entry): ?>
  <div class="modal fade" id="editDurchgangModal<?= safeHtml($entry['DurchgangID']) ?>" tabindex="-1" aria-labelledby="editDurchgangLabel<?= safeHtml($entry['DurchgangID']) ?>" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editDurchgangLabel<?= safeHtml($entry['DurchgangID']) ?>">Durchgang bearbeiten</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
        </div>
        <form method="post" action="durchgaenge.php" id="editDurchgangForm<?= safeHtml($entry['DurchgangID']) ?>">
          <div class="modal-body">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="DurchgangID" value="<?= safeHtml($entry['DurchgangID']) ?>">
            <div class="mb-3">
              <label for="Reihenfolge<?= safeHtml($entry['DurchgangID']) ?>" class="form-label">Reihenfolge</label>
              <input type="number" class="form-control" name="Reihenfolge" id="Reihenfolge<?= safeHtml($entry['DurchgangID']) ?>" value="<?= safeHtml($entry['Reihenfolge']) ?>" required>
            </div>
            <div>
              <label for="Beschreibung<?= safeHtml($entry['DurchgangID']) ?>" class="form-label">Beschreibung</label>
              <textarea class="form-control" name="Beschreibung" id="Beschreibung<?= safeHtml($entry['DurchgangID']) ?>"><?= safeHtml($entry['Beschreibung']) ?></textarea>
            </div>
          </div>
        </form>
        <div class="modal-footer justify-content-between">
          <form method="post" action="durchgaenge.php" onsubmit="return confirm('Wollen Sie diesen Eintrag wirklich löschen?');">
            <input type="hidden" name="DurchgangID" value="<?= safeHtml($entry['DurchgangID']) ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-danger">Löschen</button>
          </form>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
            <button type="submit" form="editDurchgangForm<?= safeHtml($entry['DurchgangID']) ?>" class="btn btn-primary">Speichern</button>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
