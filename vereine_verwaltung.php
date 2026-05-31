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
    $vereinID = isset($_POST['VereinID']) ? (int) $_POST['VereinID'] : 0;
    $vereinsname = trim($_POST['Vereinsname'] ?? '');
    $stadt = trim($_POST['Stadt'] ?? '');

    if ($action === 'add') {
        $meldeGeheimnis = bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("INSERT INTO Vereine (Vereinsname, Stadt, Geheimnis_fuer_Meldung) VALUES (?, ?, ?)");
        $stmt->execute([$vereinsname, $stadt, $meldeGeheimnis]);
        redirect_with_message("Verein wurde hinzugefügt.");
    }

    if ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE Vereine SET Vereinsname = ?, Stadt = ? WHERE VereinID = ?");
        $stmt->execute([$vereinsname, $stadt, $vereinID]);
        redirect_with_message("Verein wurde aktualisiert.");
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM Vereine WHERE VereinID = ?");
        $stmt->execute([$vereinID]);
        redirect_with_message("Verein wurde gelöscht.");
    }
}

$vereine = $pdo->query(
    "SELECT v.*, COALESCE(tc.AnzahlTurner, 0) AS AnzahlTurner
     FROM Vereine v
     LEFT JOIN (
       SELECT VereinID, COUNT(*) AS AnzahlTurner
       FROM Turner
       WHERE VereinID IS NOT NULL
       GROUP BY VereinID
     ) tc ON tc.VereinID = v.VereinID
     ORDER BY v.Vereinsname ASC"
)->fetchAll();

render_header('Vereinsverwaltung');
?>
<div class="container my-4 page-wrap">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="m-0">Vereinsverwaltung</h1>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addVereinModal">Hinzufügen</button>
  </div>

  <?php if (isset($_GET['message'])): ?>
    <div class="alert alert-info"><?= h($_GET['message']) ?></div>
  <?php endif; ?>

  <div class="table-responsive panel">
    <table class="table table-striped table-mobile align-middle mb-0">
      <thead>
        <tr>
          <th>Vereinsname</th>
          <th>Stadt</th>
          <th class="text-center">Turner</th>
          <th>Aktionen</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$vereine): ?>
          <tr><td colspan="4" class="text-center text-muted py-4">Keine Einträge gefunden.</td></tr>
        <?php endif; ?>
        <?php foreach ($vereine as $verein): ?>
          <?php $meldungHash = hash('sha256', $verein['Geheimnis_fuer_Meldung'] ?? ''); ?>
          <tr class="clickable-row" data-bs-toggle="modal" data-bs-target="#editVereinModal<?= h($verein['VereinID']) ?>">
            <td data-label="Vereinsname"><?= h($verein['Vereinsname']) ?></td>
            <td data-label="Stadt"><?= h($verein['Stadt']) ?></td>
            <td data-label="Turner" class="text-center"><?= h($verein['AnzahlTurner']) ?></td>
            <td data-label="Aktionen" class="action-cell">
              <div class="action-group">
                <a href="turner_verwaltung.php?VereinID=<?= urlencode($verein['VereinID']) ?>" class="btn btn-sm btn-secondary row-action">Turner</a>
                <a target="_blank" href="turner_meldung.php?VereinID=<?= urlencode($verein['VereinID']) ?>&hash=<?= urlencode($meldungHash) ?>" class="btn btn-sm btn-outline-secondary row-action">Meldelink</a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="addVereinModal" tabindex="-1" aria-labelledby="addVereinLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addVereinLabel">Verein hinzufügen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <form method="post" action="vereine_verwaltung.php">
        <div class="modal-body">
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label for="add_Vereinsname" class="form-label">Vereinsname</label>
            <input type="text" class="form-control" id="add_Vereinsname" name="Vereinsname" required>
          </div>
          <div>
            <label for="add_Stadt" class="form-label">Stadt</label>
            <input type="text" class="form-control" id="add_Stadt" name="Stadt" required>
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

<?php foreach ($vereine as $verein): ?>
  <div class="modal fade" id="editVereinModal<?= h($verein['VereinID']) ?>" tabindex="-1" aria-labelledby="editVereinLabel<?= h($verein['VereinID']) ?>" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editVereinLabel<?= h($verein['VereinID']) ?>">Verein bearbeiten</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
        </div>
        <form method="post" action="vereine_verwaltung.php" id="editVereinForm<?= h($verein['VereinID']) ?>">
          <div class="modal-body">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="VereinID" value="<?= h($verein['VereinID']) ?>">
            <div class="mb-3">
              <label for="Vereinsname<?= h($verein['VereinID']) ?>" class="form-label">Vereinsname</label>
              <input type="text" class="form-control" id="Vereinsname<?= h($verein['VereinID']) ?>" name="Vereinsname" value="<?= h($verein['Vereinsname']) ?>" required>
            </div>
            <div>
              <label for="Stadt<?= h($verein['VereinID']) ?>" class="form-label">Stadt</label>
              <input type="text" class="form-control" id="Stadt<?= h($verein['VereinID']) ?>" name="Stadt" value="<?= h($verein['Stadt']) ?>" required>
            </div>
          </div>
        </form>
        <div class="modal-footer justify-content-between">
          <form method="post" action="vereine_verwaltung.php" onsubmit="return confirm('Wollen Sie diesen Verein wirklich löschen?');">
            <input type="hidden" name="VereinID" value="<?= h($verein['VereinID']) ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-danger">Löschen</button>
          </form>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
            <button type="submit" form="editVereinForm<?= h($verein['VereinID']) ?>" class="btn btn-primary">Speichern</button>
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
