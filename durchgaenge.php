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
    $id = isset($_POST['DurchgangID']) ? (int)$_POST['DurchgangID'] : 0;
    $reihenfolge = $_POST['Reihenfolge'] ?? null;
    $beschreibung = $_POST['Beschreibung'] ?? null;

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO Durchgaenge (Reihenfolge, Beschreibung, Startzeitpunkt) VALUES (?, ?, NULL)");
        $stmt->execute([$reihenfolge, $beschreibung]);
        redirect_with_message("Durchgang wurde hinzugefügt.");
    }

    if ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE Durchgaenge SET Reihenfolge = ?, Beschreibung = ? WHERE DurchgangID = ?");
        $stmt->execute([$reihenfolge, $beschreibung, $id]);
        redirect_with_message("Durchgang wurde aktualisiert.");
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM Durchgaenge WHERE DurchgangID = ?");
        $stmt->execute([$id]);
        redirect_with_message("Durchgang wurde gelöscht.");
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
render_header('Durchgänge Verwaltung');
?>
<div class="container my-4 page-wrap">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="m-0">Durchgänge Verwaltung</h1>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addDurchgangModal">Hinzufügen</button>
  </div>

  <?php if (isset($_GET['message'])): ?>
    <div class="alert alert-info"><?= h($_GET['message']) ?></div>
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
          <tr class="clickable-row" data-bs-toggle="modal" data-bs-target="#editDurchgangModal<?= h($entry['DurchgangID']) ?>">
            <td data-label="Reihenfolge"><?= h($entry['Reihenfolge']) ?></td>
            <td data-label="Beschreibung"><?= h($entry['Beschreibung']) ?></td>
            <td data-label="Startzeitpunkt"><?= h($entry['Startzeitpunkt']) ?></td>
            <td data-label="Zuordnungen" class="text-center"><?= h($entry['AnzahlZuordnungen']) ?></td>
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
  <div class="modal fade" id="editDurchgangModal<?= h($entry['DurchgangID']) ?>" tabindex="-1" aria-labelledby="editDurchgangLabel<?= h($entry['DurchgangID']) ?>" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editDurchgangLabel<?= h($entry['DurchgangID']) ?>">Durchgang bearbeiten</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
        </div>
        <form method="post" action="durchgaenge.php" id="editDurchgangForm<?= h($entry['DurchgangID']) ?>">
          <div class="modal-body">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="DurchgangID" value="<?= h($entry['DurchgangID']) ?>">
            <div class="mb-3">
              <label for="Reihenfolge<?= h($entry['DurchgangID']) ?>" class="form-label">Reihenfolge</label>
              <input type="number" class="form-control" name="Reihenfolge" id="Reihenfolge<?= h($entry['DurchgangID']) ?>" value="<?= h($entry['Reihenfolge']) ?>" required>
            </div>
            <div>
              <label for="Beschreibung<?= h($entry['DurchgangID']) ?>" class="form-label">Beschreibung</label>
              <textarea class="form-control" name="Beschreibung" id="Beschreibung<?= h($entry['DurchgangID']) ?>"><?= h($entry['Beschreibung']) ?></textarea>
            </div>
          </div>
        </form>
        <div class="modal-footer justify-content-between">
          <form method="post" action="durchgaenge.php" onsubmit="return confirm('Wollen Sie diesen Eintrag wirklich löschen?');">
            <input type="hidden" name="DurchgangID" value="<?= h($entry['DurchgangID']) ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-danger">Löschen</button>
          </form>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
            <button type="submit" form="editDurchgangForm<?= h($entry['DurchgangID']) ?>" class="btn btn-primary">Speichern</button>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<?php render_footer(); ?>
