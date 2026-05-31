<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');
?>
<?php
/*
Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Die Webseite soll die Bearbeitung der SQL-Tabelle "Mannschaften" ermöglichen. Möglich sein soll: 
- Neuen Eintrag hinzufügen,
- Bestehenden bearbeiten,
- bestehenden Löschen.

Alle Einträge aus der SQL-Tabelle sollen in einer Tabelle ausgegeben werden. Sortierung nach dem Alphabet nach Spalte Beschreibung.

Die Spalten "VereinID", "WettkampfID" und "RiegenID" sollen in den Tabellen "Vereine", "Wettkaempfe" und "Riegen" nachgeschlagen werden. 
Die Einträge in den dafür erzeugten Dropdown-Felder sollen immer sortiert sein.

Standard bei 
- Wettkampf soll NULL
- Riege soll NULL
- Verein soll NULL.

Es sollen Dropdowns für die Nachgeschlagenen Werte verwendet werden.
Bootstrap und PDO sollen verwendet werden.

Gebe am Ende der Seite die Gesamtzahl der Mannschaften aus.

Aktuell erhalte ich den Fehler: "Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in ..." 
Um dies zu lösen, ersetze bei der Nutzung von htmlspecialchars durch eine eigene Funktion, welche bei einem übergebenen null-String einfach "-" ausgibt und sonst die Funktion "htmlspecialchars" aufruft.

gib mit dieser Korrektur nochmal die gesamte php-Seite aus. 


Für die Anbingung an die Datenbank sollen folgende Variablen verwendet werden: $dbHost, $dbName, $dbUser, $dbPass
und als charset: "utf8".

In der PHP-Datei soll:
- diese Zeile in der Zeile nach "<body>" eingefügt werden: 
  <script src="menu.js"></script>
- diese Zeile in der <head> Section:
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
- Und diese Zeile bevor dem </body> tag:
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

*/


include 'auth.php';
include 'config.php';
// Datenbankverbindungsparameter anpassen!
$charset = 'utf8mb4';


$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
$pdo = new PDO($dsn, $user, $pass, $options);

// --- Hilfsfunktionen ---
/**
 * Escaped string or returns "-" if null/empty.
 */
function h(?string $s): string {
    if ($s === null || $s === '') {
        return '-';
    }
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

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
    $vereinID     = $_POST['vereinID'] !== '' ? (int)$_POST['vereinID'] : null;
    $wettkampfID  = $_POST['wettkampfID'] !== '' ? (int)$_POST['wettkampfID'] : null;
    $riegenID     = $_POST['riegenID'] !== '' ? (int)$_POST['riegenID'] : null;

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
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
}
elseif ($action === 'delete' && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM Mannschaften WHERE MannschaftsID = :id");
    $stmt->execute([':id' => $id]);
    header('Location: '.$_SERVER['PHP_SELF']);
    exit;
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
$vereine = $pdo->query("SELECT VereinID, Vereinsname FROM Vereine ORDER BY Vereinsname")->fetchAll();
$wettkaempfe = $pdo->query("SELECT WettkampfID, Beschreibung FROM Wettkaempfe ORDER BY Beschreibung")->fetchAll();
$riegen = $pdo->query("SELECT RiegenID, Beschreibung FROM Riegen ORDER BY Beschreibung")->fetchAll();

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
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mannschaften verwalten</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { padding-top: 1rem; }
    .table-responsive { margin-top: 1rem; }
  </style>
</head>
<body>
<script src="menu.js"></script>
<div class="container">
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
              <option value="">—</option>
              <?php foreach ($vereine as $v): ?>
                <option value="<?= $v['VereinID'] ?>"
                  <?= isset($editData['VereinID']) && $editData['VereinID']==$v['VereinID'] ? 'selected' : '' ?>>
                  <?= h($v['Vereinsname']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-4 mb-3">
            <label class="form-label">Wettkampf</label>
            <select name="wettkampfID" class="form-select">
              <option value="">—</option>
              <?php foreach ($wettkaempfe as $w): ?>
                <option value="<?= $w['WettkampfID'] ?>"
                  <?= isset($editData['WettkampfID']) && $editData['WettkampfID']==$w['WettkampfID'] ? 'selected' : '' ?>>
                  <?= h($w['Beschreibung']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-4 mb-3">
            <label class="form-label">Riege</label>
            <select name="riegenID" class="form-select">
              <option value="">—</option>
              <?php foreach ($riegen as $r): ?>
                <option value="<?= $r['RiegenID'] ?>"
                  <?= isset($editData['RiegenID']) && $editData['RiegenID']==$r['RiegenID'] ? 'selected' : '' ?>>
                  <?= h($r['Beschreibung']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <button type="submit" class="btn btn-primary">
          <?= $editData ? 'Aktualisieren' : 'Hinzufügen' ?>
        </button>
        <?php if ($editData): ?>
          <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Abbrechen</a>
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
              <a href="?action=edit&id=<?= $m['MannschaftsID'] ?>" class="btn btn-sm btn-outline-secondary">✎</a>
              <a href="?action=delete&id=<?= $m['MannschaftsID'] ?>"
                 onclick="return confirm('Löschen wirklich?')" class="btn btn-sm btn-outline-danger">🗑️</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <p class="mt-3"><strong>Gesamtanzahl Mannschaften:</strong> <?= $count ?></p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
