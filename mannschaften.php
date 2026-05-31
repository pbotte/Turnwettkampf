<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

include 'auth.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/layout.php';
require_once 'includes/lookups.php';

$pdo = db();

/**
 * Formatiert ein Datum (YYYY-MM-DD oder Timestamp) als Tag.Monat.Jahr oder "-" wenn leer.
 */
function h_date(?string $d): string {
    if (empty($d)) {
        return '-';
    }
    $ts = strtotime($d);
    return $ts ? date('d.m.Y', $ts) : '-';
}

// --- Action-Verarbeitung ---
$action = $_REQUEST['action'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Felder aus POST holen
    $beschreibung = trim($_POST['beschreibung'] ?? '');
    $vereinID     = nullable_int($_POST['vereinID'] ?? null);
    $wettkampfID  = nullable_int($_POST['wettkampfID'] ?? null);
    $riegenID     = nullable_int($_POST['riegenID'] ?? null);

    if ($action === 'add') {
        $stmt = $pdo->prepare("
            INSERT INTO Mannschaften (Beschreibung, VereinID, WettkampfID, RiegenID)
            VALUES (:beschreibung, :verein, :wettkampf, :riege)
        ");
        $stmt->execute([
            ':beschreibung' => $beschreibung,
            ':verein'       => $vereinID,
            ':wettkampf'    => $wettkampfID,
            ':riege'        => $riegenID,
        ]);
    }
    elseif ($action === 'update' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("
            UPDATE Mannschaften
            SET Beschreibung = :beschreibung,
                VereinID     = :verein,
                WettkampfID  = :wettkampf,
                RiegenID     = :riege
            WHERE MannschaftsID = :id
        ");
        $stmt->execute([
            ':beschreibung' => $beschreibung,
            ':verein'       => $vereinID,
            ':wettkampf'    => $wettkampfID,
            ':riege'        => $riegenID,
            ':id'           => $id,
        ]);
    }
    redirect_self();
}
elseif ($action === 'delete' && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM Mannschaften WHERE MannschaftsID = :id");
    $stmt->execute([':id' => $id]);
    redirect_self();
}

// Wenn edit geladen wird, bestehende Daten holen
$editData = null;
if ($action === 'edit' && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM Mannschaften WHERE MannschaftsID = :id");
    $stmt->execute([':id' => $id]);
    $editData = $stmt->fetch();
}

// --- Referenztabellen laden ---
$vereine = lookup_options($pdo, 'Vereine', 'VereinID', 'Vereinsname', 'Vereinsname');
$wettkaempfe = lookup_options($pdo, 'Wettkaempfe', 'WettkampfID', 'Beschreibung', 'Beschreibung');
$riegen = lookup_options($pdo, 'Riegen', 'RiegenID', 'Beschreibung', 'Beschreibung');

// --- Mannschaften mit Join laden ---
$stmt = $pdo->query("
    SELECT
      M.MannschaftsID,
      M.Beschreibung AS M_Beschr,
      V.Vereinsname,
      W.Beschreibung AS W_Beschr,
      R.Beschreibung AS R_Beschr
    FROM Mannschaften M
    LEFT JOIN Vereine V ON M.VereinID = V.VereinID
    LEFT JOIN Wettkaempfe W ON M.WettkampfID = W.WettkampfID
    LEFT JOIN Riegen R ON M.RiegenID = R.RiegenID
    ORDER BY M.Beschreibung
");
$mannschaften = $stmt->fetchAll();
$count = count($mannschaften);
render_header('Mannschaften verwalten');
?>
<div class="container my-4 page-wrap">
  <h1 class="mb-4">Mannschaften verwalten</h1>

  <div class="card mb-4">
    <div class="card-body">
      <h5 class="card-title"><?= $editData ? 'Mannschaft bearbeiten' : 'Neue Mannschaft hinzufügen' ?></h5>
      <form method="post" action="">
        <input type="hidden" name="action" value="<?= $editData ? 'update' : 'add' ?>">
        <?php if ($editData): ?>
          <input type="hidden" name="id" value="<?= (int)$editData['MannschaftsID'] ?>">
        <?php endif; ?>

        <div class="mb-3">
          <label class="form-label">Beschreibung</label>
          <input type="text" name="beschreibung" class="form-control" required
                 value="<?= h($editData['Beschreibung'] ?? '') ?>">
        </div>

        <div class="row">
          <div class="col-12 col-md-4 mb-3">
            <label class="form-label">Verein</label>
            <select name="vereinID" class="form-select">
              <option value="">-</option>
              <?php foreach ($vereine as $v): ?>
                <option value="<?= h($v['id']) ?>"
                  <?= isset($editData['VereinID']) && $editData['VereinID'] == $v['id'] ? 'selected' : '' ?>>
                  <?= h($v['label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-4 mb-3">
            <label class="form-label">Wettkampf</label>
            <select name="wettkampfID" class="form-select">
              <option value="">-</option>
              <?php foreach ($wettkaempfe as $w): ?>
                <option value="<?= h($w['id']) ?>"
                  <?= isset($editData['WettkampfID']) && $editData['WettkampfID'] == $w['id'] ? 'selected' : '' ?>>
                  <?= h($w['label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-4 mb-3">
            <label class="form-label">Riege</label>
            <select name="riegenID" class="form-select">
              <option value="">-</option>
              <?php foreach ($riegen as $r): ?>
                <option value="<?= h($r['id']) ?>"
                  <?= isset($editData['RiegenID']) && $editData['RiegenID'] == $r['id'] ? 'selected' : '' ?>>
                  <?= h($r['label']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <button type="submit" class="btn btn-primary">
          <?= $editData ? 'Aktualisieren' : 'Hinzufügen' ?>
        </button>
        <?php if ($editData): ?>
          <a href="<?= h_attr($_SERVER['PHP_SELF']) ?>" class="btn btn-secondary">Abbrechen</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Beschreibung</th>
          <th>Verein</th>
          <th>Wettkampf</th>
          <th>Riege</th>
          <th class="text-center">Aktionen</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($mannschaften as $m): ?>
          <tr>
            <td><?= h($m['M_Beschr']) ?></td>
            <td><?= h($m['Vereinsname']) ?></td>
            <td><?= h($m['W_Beschr']) ?></td>
            <td><?= h($m['R_Beschr']) ?></td>
            <td class="text-center">
              <a href="?action=edit&id=<?= h($m['MannschaftsID']) ?>" class="btn btn-sm btn-outline-secondary">Bearbeiten</a>
              <a href="?action=delete&id=<?= h($m['MannschaftsID']) ?>"
                 onclick="return confirm('Löschen wirklich?')" class="btn btn-sm btn-outline-danger">Löschen</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <p class="mt-3"><strong>Gesamtanzahl Mannschaften:</strong> <?= $count ?></p>
</div>
<?php render_footer(); ?>
