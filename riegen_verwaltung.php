<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

include 'auth.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/layout.php';

$pdo = db();

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
render_header('Riegen Verwaltung');
?>
<div class="container my-4 page-wrap">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="m-0">Riegen Verwaltung</h1>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addRiegeModal">Hinzufügen</button>
  </div>

  <?php if (isset($_GET['message'])): ?>
    <div class="alert alert-info"><?= h($_GET['message']) ?></div>
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
          <tr class="clickable-row" data-bs-toggle="modal" data-bs-target="#editRiegeModal<?= h($entry['RiegenID']) ?>">
            <td data-label="Beschreibung"><?= h($entry['Beschreibung']) ?></td>
            <td data-label="Turner" class="text-center"><?= h($entry['AnzahlTurner']) ?></td>
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
  <div class="modal fade" id="editRiegeModal<?= h($entry['RiegenID']) ?>" tabindex="-1" aria-labelledby="editRiegeLabel<?= h($entry['RiegenID']) ?>" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editRiegeLabel<?= h($entry['RiegenID']) ?>">Riege bearbeiten</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
        </div>
        <form method="post" action="riegen_verwaltung.php" id="editRiegeForm<?= h($entry['RiegenID']) ?>">
          <div class="modal-body">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= h($entry['RiegenID']) ?>">
            <label for="beschreibung<?= h($entry['RiegenID']) ?>" class="form-label">Beschreibung</label>
            <input type="text" class="form-control" id="beschreibung<?= h($entry['RiegenID']) ?>" name="beschreibung" value="<?= h($entry['Beschreibung']) ?>" required>
          </div>
        </form>
        <div class="modal-footer justify-content-between">
          <form method="post" action="riegen_verwaltung.php" onsubmit="return confirm('Eintrag wirklich löschen?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= h($entry['RiegenID']) ?>">
            <button type="submit" class="btn btn-danger">Löschen</button>
          </form>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
            <button type="submit" form="editRiegeForm<?= h($entry['RiegenID']) ?>" class="btn btn-primary">Speichern</button>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<script>
  document.querySelectorAll('.row-action').forEach((element) => {
    element.addEventListener('click', (event) => event.stopPropagation());
  });
</script>
<?php render_footer(); ?>
