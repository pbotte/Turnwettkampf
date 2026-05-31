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
    header("Location: riegen_verwaltung.php?message=" . urlencode($message));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $beschreibung = trim($_POST['beschreibung'] ?? '');

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO Riegen (Beschreibung) VALUES (?)");
        $stmt->execute([$beschreibung]);
        redirect_with_message("Riege wurde hinzugefügt.");
    }

    if ($action === 'update') {
        $stmt = $pdo->prepare("UPDATE Riegen SET Beschreibung = ? WHERE RiegenID = ?");
        $stmt->execute([$beschreibung, $id]);
        redirect_with_message("Riege wurde aktualisiert.");
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM Riegen WHERE RiegenID = ?");
        $stmt->execute([$id]);
        redirect_with_message("Riege wurde gelöscht.");
    }
}

$stmt = $pdo->query(
    "SELECT r.*, COALESCE(tc.AnzahlTurner, 0) AS AnzahlTurner
     FROM Riegen r
     LEFT JOIN (
       SELECT RiegenID, COUNT(*) AS AnzahlTurner
       FROM Turner
       WHERE RiegenID IS NOT NULL
       GROUP BY RiegenID
     ) tc ON tc.RiegenID = r.RiegenID
     ORDER BY r.Beschreibung ASC"
);
$entries = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Riegen Verwaltung</title>
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
    .action-group { display: flex; gap: .5rem; align-items: center; }
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
      .table-mobile .action-cell::before { content: ""; }
    }
  </style>
</head>
<body>
<script src="menu.js"></script>
<div class="container my-4 page-wrap">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="m-0">Riegen Verwaltung</h1>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addRiegeModal">Hinzufügen</button>
  </div>

  <?php if (isset($_GET['message'])): ?>
    <div class="alert alert-info"><?= safe_html($_GET['message']) ?></div>
  <?php endif; ?>

  <div class="table-responsive panel">
    <table class="table table-striped table-mobile align-middle mb-0">
      <thead>
        <tr>
          <th>Beschreibung</th>
          <th class="text-center">Turner</th>
          <th>Aktionen</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$entries): ?>
          <tr><td colspan="3" class="text-center text-muted py-4">Keine Einträge gefunden.</td></tr>
        <?php endif; ?>
        <?php foreach ($entries as $entry): ?>
          <tr class="clickable-row" data-bs-toggle="modal" data-bs-target="#editRiegeModal<?= safe_html($entry['RiegenID']) ?>">
            <td data-label="Beschreibung"><?= safe_html($entry['Beschreibung']) ?></td>
            <td data-label="Turner" class="text-center"><?= safe_html($entry['AnzahlTurner']) ?></td>
            <td data-label="Aktionen" class="action-cell">
              <div class="action-group">
                <a href="turner_verwaltung.php?RiegenID=<?= urlencode($entry['RiegenID']) ?>" class="btn btn-sm btn-secondary row-action">Turner</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="addRiegeModal" tabindex="-1" aria-labelledby="addRiegeLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addRiegeLabel">Riege hinzufügen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <form method="post" action="riegen_verwaltung.php">
        <div class="modal-body">
          <input type="hidden" name="action" value="add">
          <label for="add_beschreibung" class="form-label">Beschreibung</label>
          <input type="text" class="form-control" id="add_beschreibung" name="beschreibung" required>
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
  <div class="modal fade" id="editRiegeModal<?= safe_html($entry['RiegenID']) ?>" tabindex="-1" aria-labelledby="editRiegeLabel<?= safe_html($entry['RiegenID']) ?>" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editRiegeLabel<?= safe_html($entry['RiegenID']) ?>">Riege bearbeiten</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
        </div>
        <form method="post" action="riegen_verwaltung.php" id="editRiegeForm<?= safe_html($entry['RiegenID']) ?>">
          <div class="modal-body">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= safe_html($entry['RiegenID']) ?>">
            <label for="beschreibung<?= safe_html($entry['RiegenID']) ?>" class="form-label">Beschreibung</label>
            <input type="text" class="form-control" id="beschreibung<?= safe_html($entry['RiegenID']) ?>" name="beschreibung" value="<?= safe_html($entry['Beschreibung']) ?>" required>
          </div>
        </form>
        <div class="modal-footer justify-content-between">
          <form method="post" action="riegen_verwaltung.php" onsubmit="return confirm('Eintrag wirklich löschen?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= safe_html($entry['RiegenID']) ?>">
            <button type="submit" class="btn btn-danger">Löschen</button>
          </form>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
            <button type="submit" form="editRiegeForm<?= safe_html($entry['RiegenID']) ?>" class="btn btn-primary">Speichern</button>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.querySelectorAll('.row-action').forEach((element) => {
    element.addEventListener('click', (event) => event.stopPropagation());
  });
</script>
</body>
</html>
