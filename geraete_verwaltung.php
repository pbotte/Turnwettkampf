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
    header("Location: geraete_verwaltung.php?message=" . urlencode($message));
    exit;
}

function nullable_int($value) {
    return $value === '' || $value === null ? null : (int)$value;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $geraetID = isset($_POST['GeraetID']) ? (int)$_POST['GeraetID'] : 0;
    $geraeteTypID = nullable_int($_POST['GeraeteTypID'] ?? null);
    $beschreibung = $_POST['Beschreibung'] ?? '';

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO Geraete (GeraeteTypID, Beschreibung) VALUES (?, ?)");
        $stmt->execute([$geraeteTypID, $beschreibung]);
        redirect_to_self("Gerät wurde hinzugefügt.");
    }

    if ($action === 'edit') {
        $stmt = $pdo->prepare("UPDATE Geraete SET GeraeteTypID = ?, Beschreibung = ? WHERE GeraetID = ?");
        $stmt->execute([$geraeteTypID, $beschreibung, $geraetID]);
        redirect_to_self("Gerät wurde aktualisiert.");
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM Geraete WHERE GeraetID = ?");
        $stmt->execute([$geraetID]);
        redirect_to_self("Gerät wurde gelöscht.");
    }
}

$geraeteTypen = $pdo->query("SELECT * FROM GeraeteTypen ORDER BY Reihenfolge ASC, Beschreibung ASC")->fetchAll();

$stmt = $pdo->query(
    "SELECT
       g.*,
       gt.Beschreibung AS TypBeschreibung,
       COALESCE(wc.AnzahlWertungen, 0) AS AnzahlWertungen
     FROM Geraete g
     LEFT JOIN GeraeteTypen gt ON g.GeraeteTypID = gt.GeraeteTypID
     LEFT JOIN (
       SELECT GeraetID, COUNT(*) AS AnzahlWertungen
       FROM Wertungen
       GROUP BY GeraetID
     ) wc ON wc.GeraetID = g.GeraetID
     ORDER BY g.GeraetID ASC"
);
$geraete = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Geräte Verwaltung</title>
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
    <h1 class="m-0">Geräte Verwaltung</h1>
    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addGeraetModal">Hinzufügen</button>
  </div>

  <?php if (isset($_GET['message'])): ?>
    <div class="alert alert-info"><?= safeHtml($_GET['message']) ?></div>
  <?php endif; ?>

  <div class="table-responsive panel">
    <table class="table table-striped table-mobile align-middle mb-0">
      <thead>
        <tr>
          <th>GeraetID</th>
          <th>Gerätetyp</th>
          <th>Beschreibung</th>
          <th class="text-center">Wertungen</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$geraete): ?>
          <tr><td colspan="4" class="text-center text-muted py-4">Keine Einträge gefunden.</td></tr>
        <?php endif; ?>
        <?php foreach ($geraete as $row): ?>
          <tr class="clickable-row" data-bs-toggle="modal" data-bs-target="#editGeraetModal<?= safeHtml($row['GeraetID']) ?>">
            <td data-label="GeraetID"><?= safeHtml($row['GeraetID']) ?></td>
            <td data-label="Gerätetyp"><?= $row['GeraeteTypID'] === null ? 'Pause' : safeHtml($row['TypBeschreibung']) ?></td>
            <td data-label="Beschreibung"><?= safeHtml($row['Beschreibung']) ?></td>
            <td data-label="Wertungen" class="text-center"><?= safeHtml($row['AnzahlWertungen']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="addGeraetModal" tabindex="-1" aria-labelledby="addGeraetLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addGeraetLabel">Gerät hinzufügen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
      </div>
      <form method="post" action="geraete_verwaltung.php">
        <div class="modal-body">
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label for="add_GeraeteTypID" class="form-label">Gerätetyp</label>
            <select class="form-select" name="GeraeteTypID" id="add_GeraeteTypID">
              <option value="">Pause</option>
              <?php foreach ($geraeteTypen as $typ): ?>
                <option value="<?= safeHtml($typ['GeraeteTypID']) ?>"><?= safeHtml($typ['Beschreibung']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="add_Beschreibung" class="form-label">Beschreibung</label>
            <input type="text" class="form-control" name="Beschreibung" id="add_Beschreibung" required>
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

<?php foreach ($geraete as $row): ?>
  <div class="modal fade" id="editGeraetModal<?= safeHtml($row['GeraetID']) ?>" tabindex="-1" aria-labelledby="editGeraetLabel<?= safeHtml($row['GeraetID']) ?>" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editGeraetLabel<?= safeHtml($row['GeraetID']) ?>">Gerät bearbeiten</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
        </div>
        <form method="post" action="geraete_verwaltung.php" id="editGeraetForm<?= safeHtml($row['GeraetID']) ?>">
          <div class="modal-body">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="GeraetID" value="<?= safeHtml($row['GeraetID']) ?>">
            <div class="mb-3">
              <label for="GeraeteTypID<?= safeHtml($row['GeraetID']) ?>" class="form-label">Gerätetyp</label>
              <select class="form-select" name="GeraeteTypID" id="GeraeteTypID<?= safeHtml($row['GeraetID']) ?>">
                <option value="">Pause</option>
                <?php foreach ($geraeteTypen as $typ): ?>
                  <option value="<?= safeHtml($typ['GeraeteTypID']) ?>" <?= $row['GeraeteTypID'] == $typ['GeraeteTypID'] ? 'selected' : '' ?>>
                    <?= safeHtml($typ['Beschreibung']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label for="Beschreibung<?= safeHtml($row['GeraetID']) ?>" class="form-label">Beschreibung</label>
              <input type="text" class="form-control" name="Beschreibung" id="Beschreibung<?= safeHtml($row['GeraetID']) ?>" value="<?= safeHtml($row['Beschreibung']) ?>" required>
            </div>
          </div>
        </form>
        <div class="modal-footer justify-content-between">
          <form method="post" action="geraete_verwaltung.php" onsubmit="return confirm('Wollen Sie diesen Eintrag wirklich löschen?');">
            <input type="hidden" name="GeraetID" value="<?= safeHtml($row['GeraetID']) ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-danger">Löschen</button>
          </form>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
            <button type="submit" form="editGeraetForm<?= safeHtml($row['GeraetID']) ?>" class="btn btn-primary">Speichern</button>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
