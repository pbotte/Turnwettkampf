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
render_header('Gerätetypen Verwaltung');
?>
<div class="container my-4 page-wrap">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="m-0">Gerätetypen Verwaltung</h1>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTypModal">Hinzufügen</button>
  </div>

  <?php if (isset($_GET['message'])): ?>
    <div class="alert alert-info"><?= h($_GET['message']) ?></div>
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
          <tr class="clickable-row" data-bs-toggle="modal" data-bs-target="#editTypModal<?= h($entry['GeraeteTypID']) ?>">
            <td data-label="Beschreibung"><?= h($entry['Beschreibung']) ?></td>
            <td data-label="Reihenfolge"><?= h($entry['Reihenfolge']) ?></td>
            <td data-label="Geräte" class="text-center"><?= h($entry['AnzahlGeraete']) ?></td>
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
  <div class="modal fade" id="editTypModal<?= h($entry['GeraeteTypID']) ?>" tabindex="-1" aria-labelledby="editTypLabel<?= h($entry['GeraeteTypID']) ?>" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editTypLabel<?= h($entry['GeraeteTypID']) ?>">Gerätetyp bearbeiten</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
        </div>
        <form method="post" action="geraetetypen_verwaltung.php" id="editTypForm<?= h($entry['GeraeteTypID']) ?>">
          <div class="modal-body">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= h($entry['GeraeteTypID']) ?>">
            <div class="mb-3">
              <label for="beschreibung<?= h($entry['GeraeteTypID']) ?>" class="form-label">Beschreibung</label>
              <input type="text" class="form-control" id="beschreibung<?= h($entry['GeraeteTypID']) ?>" name="beschreibung" value="<?= h($entry['Beschreibung']) ?>" required>
            </div>
            <div>
              <label for="reihenfolge<?= h($entry['GeraeteTypID']) ?>" class="form-label">Reihenfolge</label>
              <input type="number" class="form-control" id="reihenfolge<?= h($entry['GeraeteTypID']) ?>" name="reihenfolge" value="<?= h($entry['Reihenfolge']) ?>" required>
            </div>
          </div>
        </form>
        <div class="modal-footer justify-content-between">
          <form method="post" action="geraetetypen_verwaltung.php" onsubmit="return confirm('Eintrag wirklich löschen?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= h($entry['GeraeteTypID']) ?>">
            <button type="submit" class="btn btn-danger">Löschen</button>
          </form>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
            <button type="submit" form="editTypForm<?= h($entry['GeraeteTypID']) ?>" class="btn btn-primary">Speichern</button>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<?php render_footer(); ?>
