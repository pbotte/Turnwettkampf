<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

include 'auth.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/layout.php';
require_once 'includes/lookups.php';

$pdo = db();

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
            redirect_self();
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
            redirect_self();
        }
        // Eintrag löschen
        if ($_POST['action'] === 'delete' && isset($_POST['WettkampfID'])) {
            $wettkampfID = $_POST['WettkampfID'];
            $stmt = $pdo->prepare("DELETE FROM Wettkaempfe WHERE WettkampfID = ?");
            $stmt->execute([$wettkampfID]);
            redirect_self();
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
$wettkaempfeModiSprungById = rows_by_id($wettkaempfeModiSprung, 'WettkampfSprungmodusID');
$geschlechterById = rows_by_id($geschlechter, 'GeschlechtID');

// Alle Wettkämpfe alphabetisch sortiert abrufen
$stmt = $pdo->query(
    "SELECT w.*, COALESCE(tc.AnzahlTurner, 0) AS AnzahlTurner
     FROM Wettkaempfe w
     LEFT JOIN (
       SELECT WettkampfID, COUNT(*) AS AnzahlTurner
       FROM Turner
       WHERE WettkampfID IS NOT NULL
       GROUP BY WettkampfID
     ) tc ON tc.WettkampfID = w.WettkampfID
     ORDER BY w.Beschreibung ASC"
);
$wettkaempfe = $stmt->fetchAll(PDO::FETCH_ASSOC);
render_header('Wettkämpfe');
?>
<div class="container my-4 page-wrap">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <h1 class="m-0">Wettkämpfe</h1>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addWettkampfModal">
      Hinzufügen
    </button>
  </div>

  <!-- Tabelle mit allen Wettkämpfen -->
  <div class="table-responsive">
    <table class="table table-striped table-mobile align-middle">
      <thead>
        <tr>
          <th>Beschreibung</th>
          <th>Sprungmodus</th>
          <th>Geschlecht</th>
          <th>Anzahl Wertungen</th>
          <th>Max. Geräte</th>
          <th class="text-center">Turner</th>
          <th>Aktionen</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($wettkaempfe as $wettkampf): ?>
        <tr class="clickable-row" data-bs-toggle="modal" data-bs-target="#editWettkampfModal<?= h($wettkampf['WettkampfID']) ?>">
          <td data-label="Beschreibung"><?= h($wettkampf['Beschreibung']) ?></td>
          <td data-label="Sprungmodus"><?= h($wettkaempfeModiSprungById[$wettkampf['WettkampfSprungmodusID']]['Beschreibung'] ?? null) ?></td>
          <td data-label="Geschlecht"><?= h($geschlechterById[$wettkampf['GeschlechtID']]['Beschreibung'] ?? null) ?></td>
          <td data-label="Anzahl Wertungen"><?= h($wettkampf['NWertungen']) ?></td>
          <td data-label="Max. Geräte"><?= h($wettkampf['NGeraeteMax']) ?></td>
          <td data-label="Turner" class="text-center"><?= h($wettkampf['AnzahlTurner']) ?></td>
          <td data-label="Aktionen" class="action-cell">
            <div class="action-group">
              <a href="turner_verwaltung.php?WettkampfID=<?= urlencode($wettkampf['WettkampfID']) ?>" class="btn btn-sm btn-secondary row-action">Turner</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php foreach ($wettkaempfe as $wettkampf): ?>
<!-- Modal: Wettkampf bearbeiten -->
<div class="modal fade" id="editWettkampfModal<?= h($wettkampf['WettkampfID']) ?>" tabindex="-1" aria-labelledby="editWettkampfLabel<?= h($wettkampf['WettkampfID']) ?>" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editWettkampfLabel<?= h($wettkampf['WettkampfID']) ?>">Wettkampf bearbeiten</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <div class="modal-body">
        <form action="<?= h_attr($_SERVER['PHP_SELF']) ?>" method="post" id="editWettkampfForm<?= h($wettkampf['WettkampfID']) ?>">
          <input type="hidden" name="WettkampfID" value="<?= h($wettkampf['WettkampfID']) ?>">
          <input type="hidden" name="action" value="edit">
          <div class="row g-3">
            <div class="col-12">
              <label for="beschreibung<?= h($wettkampf['WettkampfID']) ?>" class="form-label">Beschreibung</label>
              <input type="text" class="form-control" id="beschreibung<?= h($wettkampf['WettkampfID']) ?>" name="beschreibung" value="<?= h($wettkampf['Beschreibung']) ?>" required>
            </div>
            <div class="col-12 col-md-4">
              <label for="wettkampfmodusID<?= h($wettkampf['WettkampfID']) ?>" class="form-label">Wettkampfmodus</label>
              <select class="form-select" id="wettkampfmodusID<?= h($wettkampf['WettkampfID']) ?>" name="wettkampfmodusID">
                <?php foreach ($wettkaempfeModi as $m): ?>
                  <option value="<?= h($m['WettkampfmodusID']) ?>" <?= ($wettkampf['WettkampfmodusID'] == $m['WettkampfmodusID']) ? 'selected' : '' ?>>
                    <?= h($m['Beschreibung']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label for="wettkampfSprungmodusID<?= h($wettkampf['WettkampfID']) ?>" class="form-label">Sprungmodus</label>
              <select class="form-select" id="wettkampfSprungmodusID<?= h($wettkampf['WettkampfID']) ?>" name="wettkampfSprungmodusID">
                <?php foreach ($wettkaempfeModiSprung as $s): ?>
                  <option value="<?= h($s['WettkampfSprungmodusID']) ?>" <?= ($wettkampf['WettkampfSprungmodusID'] == $s['WettkampfSprungmodusID']) ? 'selected' : '' ?>>
                    <?= h($s['Beschreibung']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label for="geschlechtID<?= h($wettkampf['WettkampfID']) ?>" class="form-label">Geschlecht</label>
              <select class="form-select" id="geschlechtID<?= h($wettkampf['WettkampfID']) ?>" name="geschlechtID">
                <?php foreach ($geschlechter as $g): ?>
                  <option value="<?= h($g['GeschlechtID']) ?>" <?= ($wettkampf['GeschlechtID'] == $g['GeschlechtID']) ? 'selected' : '' ?>>
                    <?= h($g['Beschreibung']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label for="nWertungen<?= h($wettkampf['WettkampfID']) ?>" class="form-label">Anzahl Wertungen</label>
              <input type="number" class="form-control" id="nWertungen<?= h($wettkampf['WettkampfID']) ?>" name="nWertungen" value="<?= h($wettkampf['NWertungen']) ?>" required>
            </div>
            <div class="col-12 col-md-6">
              <label for="nGeraeteMax<?= h($wettkampf['WettkampfID']) ?>" class="form-label">Maximale Anzahl turnbarer Geräte</label>
              <input type="number" class="form-control" id="nGeraeteMax<?= h($wettkampf['WettkampfID']) ?>" name="nGeraeteMax" value="<?= h($wettkampf['NGeraeteMax']) ?>" required>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer justify-content-between">
        <form action="<?= h_attr($_SERVER['PHP_SELF']) ?>" method="post" onsubmit="return confirm('Wollen Sie diesen Eintrag wirklich löschen?');">
          <input type="hidden" name="WettkampfID" value="<?= h($wettkampf['WettkampfID']) ?>">
          <input type="hidden" name="action" value="delete">
          <button type="submit" class="btn btn-danger">Löschen</button>
        </form>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
          <button type="submit" form="editWettkampfForm<?= h($wettkampf['WettkampfID']) ?>" class="btn btn-success">Änderungen speichern</button>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>

<!-- Modal: Neuen Wettkampf hinzufügen -->
<div class="modal fade" id="addWettkampfModal" tabindex="-1" aria-labelledby="addWettkampfLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addWettkampfLabel">Neuen Wettkampf hinzufügen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <div class="modal-body">
        <form action="<?= h_attr($_SERVER['PHP_SELF']) ?>" method="post" id="addWettkampfForm">
          <input type="hidden" name="action" value="add">
          <div class="row g-3">
            <div class="col-12">
              <label for="add_beschreibung" class="form-label">Beschreibung</label>
              <input type="text" class="form-control" id="add_beschreibung" name="beschreibung" value="" required>
            </div>
            <div class="col-12 col-md-4">
              <label for="add_wettkampfmodusID" class="form-label">Wettkampfmodus</label>
              <select class="form-select" id="add_wettkampfmodusID" name="wettkampfmodusID">
                <?php foreach ($wettkaempfeModi as $m): ?>
                  <option value="<?= $m['WettkampfmodusID'] ?>" <?= ($m['WettkampfmodusID'] == 1) ? 'selected' : '' ?>>
                    <?= h($m['Beschreibung']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label for="add_wettkampfSprungmodusID" class="form-label">Sprungmodus</label>
              <select class="form-select" id="add_wettkampfSprungmodusID" name="wettkampfSprungmodusID">
                <?php foreach ($wettkaempfeModiSprung as $s): ?>
                  <option value="<?= $s['WettkampfSprungmodusID'] ?>" <?= ($s['WettkampfSprungmodusID'] == 1) ? 'selected' : '' ?>>
                    <?= h($s['Beschreibung']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label for="add_geschlechtID" class="form-label">Geschlecht</label>
              <select class="form-select" id="add_geschlechtID" name="geschlechtID">
                <?php foreach ($geschlechter as $g): ?>
                  <option value="<?= $g['GeschlechtID'] ?>" <?= ($g['GeschlechtID'] == 1) ? 'selected' : '' ?>>
                    <?= h($g['Beschreibung']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label for="add_nWertungen" class="form-label">Anzahl Wertungen</label>
              <input type="number" class="form-control" id="add_nWertungen" name="nWertungen" value="4" required>
            </div>
            <div class="col-12 col-md-6">
              <label for="add_nGeraeteMax" class="form-label">Maximale Anzahl turnbarer Geräte</label>
              <input type="number" class="form-control" id="add_nGeraeteMax" name="nGeraeteMax" value="4" required>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
        <button type="submit" form="addWettkampfForm" class="btn btn-success">Hinzufügen</button>
      </div>
    </div>
  </div>
</div>
<script>
  document.querySelectorAll('.row-action').forEach((element) => {
    element.addEventListener('click', (event) => {
      event.stopPropagation();
    });
  });
</script>
<?php render_footer(); ?>
