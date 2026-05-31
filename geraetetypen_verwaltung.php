<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

include 'auth.php';
include 'config.php';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}

function safe_html($value) {
    return $value === null ? '-' : htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect_with_message($message) {
    header("Location: geraetetypen_verwaltung.php?message=" . urlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $beschreibung = trim($_POST['beschreibung'] ?? '');
    $reihenfolge = isset($_POST['reihenfolge']) ? (int)$_POST['reihenfolge'] : 0;

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO GeraeteTypen (Beschreibung, Reihenfolge) VALUES (?, ?)");
        $stmt->execute([$beschreibung, $reihenfolge]);
        redirect_with_message("Gerätetyp wurde hinzugefügt.");
    }

    if ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE GeraeteTypen SET Beschreibung = ?, Reihenfolge = ? WHERE GeraeteTypID = ?");
        $stmt->execute([$beschreibung, $reihenfolge, $id]);
        redirect_with_message("Gerätetyp wurde aktualisiert.");
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM GeraeteTypen WHERE GeraeteTypID = ?");
        $stmt->execute([$id]);
        redirect_with_message("Gerätetyp wurde gelöscht.");
    }
}

$stmt = $pdo->query(
    "SELECT gt.*, COALESCE(gc.AnzahlGeraete, 0) AS AnzahlGeraete
     FROM GeraeteTypen gt
     LEFT JOIN (
       SELECT GeraeteTypID, COUNT(*) AS AnzahlGeraete
       FROM Geraete
       WHERE GeraeteTypID IS NOT NULL
       GROUP BY GeraeteTypID
     ) gc ON gc.GeraeteTypID = gt.GeraeteTypID
     ORDER BY gt.Reihenfolge ASC, gt.Beschreibung ASC"
);
$entries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Gerätetypen Verwaltung</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
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
    <h1 class="m-0">Gerätetypen Verwaltung</h1>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTypModal">Hinzufügen</button>
  </div>

  <?php if (isset($_GET['message'])): ?>
    <div class="alert alert-info"><?= safe_html($_GET['message']) ?></div>
  <?php endif; ?>

  <div class="table-responsive panel">
    <table class="table table-striped table-mobile align-middle mb-0">
      <thead>
        <tr>
          <th>Beschreibung</th>
          <th>Reihenfolge</th>
          <th class="text-center">Geräte</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$entries): ?>
          <tr><td colspan="3" class="text-center text-muted py-4">Keine Einträge gefunden.</td></tr>
        <?php endif; ?>
        <?php foreach ($entries as $entry): ?>
          <tr class="clickable-row" data-bs-toggle="modal" data-bs-target="#editTypModal<?= safe_html($entry['GeraeteTypID']) ?>">
            <td data-label="Beschreibung"><?= safe_html($entry['Beschreibung']) ?></td>
            <td data-label="Reihenfolge"><?= safe_html($entry['Reihenfolge']) ?></td>
            <td data-label="Geräte" class="text-center"><?= safe_html($entry['AnzahlGeraete']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="addTypModal" tabindex="-1" aria-labelledby="addTypLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addTypLabel">Gerätetyp hinzufügen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <form method="post" action="geraetetypen_verwaltung.php">
        <div class="modal-body">
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label for="add_beschreibung" class="form-label">Beschreibung</label>
            <input type="text" class="form-control" id="add_beschreibung" name="beschreibung" required>
          </div>
          <div>
            <label for="add_reihenfolge" class="form-label">Reihenfolge</label>
            <input type="number" class="form-control" id="add_reihenfolge" name="reihenfolge" required>
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
  <div class="modal fade" id="editTypModal<?= safe_html($entry['GeraeteTypID']) ?>" tabindex="-1" aria-labelledby="editTypLabel<?= safe_html($entry['GeraeteTypID']) ?>" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editTypLabel<?= safe_html($entry['GeraeteTypID']) ?>">Gerätetyp bearbeiten</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
        </div>
        <form method="post" action="geraetetypen_verwaltung.php" id="editTypForm<?= safe_html($entry['GeraeteTypID']) ?>">
          <div class="modal-body">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= safe_html($entry['GeraeteTypID']) ?>">
            <div class="mb-3">
              <label for="beschreibung<?= safe_html($entry['GeraeteTypID']) ?>" class="form-label">Beschreibung</label>
              <input type="text" class="form-control" id="beschreibung<?= safe_html($entry['GeraeteTypID']) ?>" name="beschreibung" value="<?= safe_html($entry['Beschreibung']) ?>" required>
            </div>
            <div>
              <label for="reihenfolge<?= safe_html($entry['GeraeteTypID']) ?>" class="form-label">Reihenfolge</label>
              <input type="number" class="form-control" id="reihenfolge<?= safe_html($entry['GeraeteTypID']) ?>" name="reihenfolge" value="<?= safe_html($entry['Reihenfolge']) ?>" required>
            </div>
          </div>
        </form>
        <div class="modal-footer justify-content-between">
          <form method="post" action="geraetetypen_verwaltung.php" onsubmit="return confirm('Eintrag wirklich löschen?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= safe_html($entry['GeraeteTypID']) ?>">
            <button type="submit" class="btn btn-danger">Löschen</button>
          </form>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
            <button type="submit" form="editTypForm<?= safe_html($entry['GeraeteTypID']) ?>" class="btn btn-primary">Speichern</button>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
