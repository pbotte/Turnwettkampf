<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

/*
Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Die Webseite soll die Bearbeitung der SQL-Tabelle "Turner" ermöglichen. Möglich sein soll: 
- Neuen Eintrag hinzufügen,
- Bestehenden bearbeiten,
- bestehenden Löschen.

Alle Einträge aus der SQL-Tabelle sollen in einer Tabelle ausgegeben werden. Sortierung nach dem Alphabet.

Die Spalten "WettkampfID", "GeschlechtID" und "RiegenID" sollen in den Tabellen "Wettkaempfe" und "Geschlechter" und "Riegen" nachgeschlagen werden. 

Standard bei 
- Wettkampf soll NULL
- Riege soll NULL
- MannschaftsID soll NULL
- Geschlecht soll "weiblich (also ID=3) sein.

Es sollen Dropdowns für die Nachgeschlagenen Werte verwendet werden.
Bootstrap und PDO sollen verwendet werden.

Aktuell erhalte ich den Fehler: "Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in ..." 
Um dies zu lösen, ersetze bei der Nutzung von htmlspecialchars durch eine eigene Funktion, welche bei einem übergebenen null-String einfach "-" ausgibt und sonst die Funktion "htmlspecialchars" aufruft.

gib mit dieser Korrektur nochmal die gesamte php-Seite aus. 
Ändere Ferner: Das Datum soll im Format Tag.Monat.Jahr ausgegeben werden.
*/

include 'auth.php';
include 'config.php';
// Datenbankverbindungsparameter anpassen!


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}

// Eigene Funktion, die htmlspecialchars aufruft oder "-" zurückgibt, wenn NULL übergeben wird
function custom_htmlspecialchars($string) {
    if ($string === null) {
        return "-";
    } else {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

// Hilfsfunktion zum Abruf der Nachschlagewerte
function getLookupValues($pdo, $table, $idColumn, $descriptionColumn) {
    $stmt = $pdo->prepare("SELECT $idColumn, $descriptionColumn FROM $table ORDER BY $descriptionColumn");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Nachschlagetabellen laden
$wettkaempfe = getLookupValues($pdo, "Wettkaempfe", "WettkampfID", "Beschreibung");
$geschlechter = getLookupValues($pdo, "Geschlechter", "GeschlechtID", "Beschreibung");
$riegen      = getLookupValues($pdo, "Riegen", "RiegenID", "Beschreibung");
$vereine     = getLookupValues($pdo, "Vereine", "VereinID", "Vereinsname");

// Ermittelt anhand eines übergebenen Arrays die Beschreibung zum übergebenen ID-Wert
function getDescription($lookupArray, $id, $default = '-') {
    foreach ($lookupArray as $item) {
        // Der erste Spaltenwert ist die ID; der zweite die Beschreibung (oder Vereinsname)
        if ($item[array_keys($item)[0]] == $id) {
            if (isset($item['Beschreibung'])) {
                return $item['Beschreibung'];
            } elseif (isset($item['Vereinsname'])) {
                return $item['Vereinsname'];
            }
        }
    }
    return $default;
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'add') {
    // Neuer Eintrag hinzufügen
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Eingabewerte übernehmen; bei Dropdowns wird bei leerem Wert NULL gesetzt, außer Geschlecht (Default ID 3, weiblich)
        $vorname       = $_POST['Vorname'];
        $nachname      = $_POST['Nachname'];
        $geburtsdatum  = $_POST['Geburtsdatum']; // Format yyyy-mm-dd (HTML5-Datepicker)
        $geschlechtID  = (isset($_POST['GeschlechtID']) && $_POST['GeschlechtID'] !== '') ? $_POST['GeschlechtID'] : 3;
        $vereinID      = (isset($_POST['VereinID']) && $_POST['VereinID'] !== '') ? $_POST['VereinID'] : null;
        $wettkampfID   = (isset($_POST['WettkampfID']) && $_POST['WettkampfID'] !== '') ? $_POST['WettkampfID'] : null;
        $riegenID      = (isset($_POST['RiegenID']) && $_POST['RiegenID'] !== '') ? $_POST['RiegenID'] : null;
        $mannschaftsID = (isset($_POST['MannschaftsID']) && $_POST['MannschaftsID'] !== '') ? $_POST['MannschaftsID'] : null;

        $stmt = $pdo->prepare("INSERT INTO Turner (Vorname, Nachname, Geburtsdatum, GeschlechtID, VereinID, WettkampfID, RiegenID, MannschaftsID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$vorname, $nachname, $geburtsdatum, $geschlechtID, $vereinID, $wettkampfID, $riegenID, $mannschaftsID]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Neuen Turner hinzufügen</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <style>
        body { background: #f6f7fb; }
        .page-wrap { max-width: 1200px; }
        .panel {
          background: #fff;
          border-radius: 16px;
          padding: 16px;
          box-shadow: 0 6px 18px rgba(0,0,0,0.08);
        }
        .form-select,
        .form-control { font-size: 1.05rem; }
      </style>
    </head>
    <body>
      <script src="menu.js"></script>
      <div class="container my-4 page-wrap">
        <h1 class="mb-3">Neuen Turner hinzufügen</h1>
        <div class="panel">
          <form method="post" action="">
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label">Vorname</label>
                <input type="text" name="Vorname" class="form-control" required>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Nachname</label>
                <input type="text" name="Nachname" class="form-control" required>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Geburtsdatum</label>
                <input type="date" name="Geburtsdatum" class="form-control" required>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Geschlecht</label>
                <select name="GeschlechtID" class="form-select">
                  <?php foreach ($geschlechter as $geschlecht): ?>
                    <option value="<?= $geschlecht['GeschlechtID'] ?>" <?= ($geschlecht['GeschlechtID'] == 3) ? 'selected' : '' ?>>
                      <?= custom_htmlspecialchars($geschlecht['Beschreibung']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Verein</label>
                <select name="VereinID" class="form-select">
                  <option value="">-- Bitte auswählen --</option>
                  <?php foreach ($vereine as $verein): ?>
                    <option value="<?= $verein['VereinID'] ?>">
                      <?= custom_htmlspecialchars($verein['Vereinsname']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Wettkampf</label>
                <select name="WettkampfID" class="form-select">
                  <option value="">-- Bitte auswählen --</option>
                  <?php foreach ($wettkaempfe as $wettkampf): ?>
                    <option value="<?= $wettkampf['WettkampfID'] ?>">
                      <?= custom_htmlspecialchars($wettkampf['Beschreibung']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Riege</label>
                <select name="RiegenID" class="form-select">
                  <option value="">-- Bitte auswählen --</option>
                  <?php foreach ($riegen as $riege): ?>
                    <option value="<?= $riege['RiegenID'] ?>">
                      <?= custom_htmlspecialchars($riege['Beschreibung']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">MannschaftsID</label>
                <input type="text" name="MannschaftsID" class="form-control">
              </div>
            </div>
            <div class="d-grid d-md-flex gap-2 mt-3">
              <button type="submit" class="btn btn-primary">Hinzufügen</button>
              <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Abbrechen</a>
            </div>
          </form>
        </div>
      </div>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
} elseif ($action == 'edit') {
    // Eintrag bearbeiten
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $vorname       = $_POST['Vorname'];
        $nachname      = $_POST['Nachname'];
        $geburtsdatum  = $_POST['Geburtsdatum'];
        $geschlechtID  = (isset($_POST['GeschlechtID']) && $_POST['GeschlechtID'] !== '') ? $_POST['GeschlechtID'] : 3;
        $vereinID      = (isset($_POST['VereinID']) && $_POST['VereinID'] !== '') ? $_POST['VereinID'] : null;
        $wettkampfID   = (isset($_POST['WettkampfID']) && $_POST['WettkampfID'] !== '') ? $_POST['WettkampfID'] : null;
        $riegenID      = (isset($_POST['RiegenID']) && $_POST['RiegenID'] !== '') ? $_POST['RiegenID'] : null;
        $mannschaftsID = (isset($_POST['MannschaftsID']) && $_POST['MannschaftsID'] !== '') ? $_POST['MannschaftsID'] : null;

        $stmt = $pdo->prepare("UPDATE Turner SET Vorname = ?, Nachname = ?, Geburtsdatum = ?, GeschlechtID = ?, VereinID = ?, WettkampfID = ?, RiegenID = ?, MannschaftsID = ? WHERE TurnerID = ?");
        $stmt->execute([$vorname, $nachname, $geburtsdatum, $geschlechtID, $vereinID, $wettkampfID, $riegenID, $mannschaftsID, $id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    // Vorhandenen Eintrag laden
    $stmt = $pdo->prepare("SELECT * FROM Turner WHERE TurnerID = ?");
    $stmt->execute([$id]);
    $turner = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$turner) {
        die("Turner nicht gefunden.");
    }
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Turner bearbeiten</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <style>
        body { background: #f6f7fb; }
        .page-wrap { max-width: 1200px; }
        .panel {
          background: #fff;
          border-radius: 16px;
          padding: 16px;
          box-shadow: 0 6px 18px rgba(0,0,0,0.08);
        }
        .form-select,
        .form-control { font-size: 1.05rem; }
      </style>
    </head>
    <body>
      <script src="menu.js"></script>
      <div class="container my-4 page-wrap">
        <h1 class="mb-3">Turner bearbeiten</h1>
        <div class="panel">
          <form method="post" action="">
            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label">Vorname</label>
                <input type="text" name="Vorname" class="form-control" value="<?= custom_htmlspecialchars($turner['Vorname']) ?>" required>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Nachname</label>
                <input type="text" name="Nachname" class="form-control" value="<?= custom_htmlspecialchars($turner['Nachname']) ?>" required>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Geburtsdatum</label>
                <input type="date" name="Geburtsdatum" class="form-control" value="<?= custom_htmlspecialchars($turner['Geburtsdatum']) ?>" required>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Geschlecht</label>
                <select name="GeschlechtID" class="form-select">
                  <?php foreach ($geschlechter as $geschlecht): ?>
                    <option value="<?= $geschlecht['GeschlechtID'] ?>" <?= ($geschlecht['GeschlechtID'] == $turner['GeschlechtID']) ? 'selected' : '' ?>>
                      <?= custom_htmlspecialchars($geschlecht['Beschreibung']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Verein</label>
                <select name="VereinID" class="form-select">
                  <option value="">-- Bitte auswählen --</option>
                  <?php foreach ($vereine as $verein): ?>
                    <option value="<?= $verein['VereinID'] ?>" <?= ($verein['VereinID'] == $turner['VereinID']) ? 'selected' : '' ?>>
                      <?= custom_htmlspecialchars($verein['Vereinsname']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Wettkampf</label>
                <select name="WettkampfID" class="form-select">
                  <option value="">-- Bitte auswählen --</option>
                  <?php foreach ($wettkaempfe as $wettkampf): ?>
                    <option value="<?= $wettkampf['WettkampfID'] ?>" <?= ($wettkampf['WettkampfID'] == $turner['WettkampfID']) ? 'selected' : '' ?>>
                      <?= custom_htmlspecialchars($wettkampf['Beschreibung']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Riege</label>
                <select name="RiegenID" class="form-select">
                  <option value="">-- Bitte auswählen --</option>
                  <?php foreach ($riegen as $riege): ?>
                    <option value="<?= $riege['RiegenID'] ?>" <?= ($riege['RiegenID'] == $turner['RiegenID']) ? 'selected' : '' ?>>
                      <?= custom_htmlspecialchars($riege['Beschreibung']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">MannschaftsID</label>
                <input type="text" name="MannschaftsID" class="form-control" value="<?= custom_htmlspecialchars($turner['MannschaftsID']) ?>">
              </div>
            </div>
            <div class="d-grid d-md-flex gap-2 mt-3">
              <button type="submit" class="btn btn-primary">Speichern</button>
              <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Abbrechen</a>
            </div>
          </form>
        </div>
      </div>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
} elseif ($action == 'delete') {
    // Eintrag löschen
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $stmt = $pdo->prepare("DELETE FROM Turner WHERE TurnerID = ?");
        $stmt->execute([$id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    // Bestätigung einholen
    $stmt = $pdo->prepare("SELECT * FROM Turner WHERE TurnerID = ?");
    $stmt->execute([$id]);
    $turner = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$turner) {
        die("Turner nicht gefunden.");
    }
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Turner löschen</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      <style>
        body { background: #f6f7fb; }
        .page-wrap { max-width: 1200px; }
        .panel {
          background: #fff;
          border-radius: 16px;
          padding: 16px;
          box-shadow: 0 6px 18px rgba(0,0,0,0.08);
        }
      </style>
    </head>
    <body>
      <script src="menu.js"></script>
      <div class="container my-4 page-wrap">
        <h1 class="mb-3">Turner löschen</h1>
        <div class="panel">
          <p>Sind Sie sicher, dass Sie folgenden Turner löschen möchten?</p>
          <p class="fw-semibold"><?= custom_htmlspecialchars($turner['Vorname']) ?> <?= custom_htmlspecialchars($turner['Nachname']) ?></p>
          <form method="post" action="">
            <div class="d-grid d-md-flex gap-2">
              <button type="submit" class="btn btn-danger">Löschen</button>
              <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-secondary">Abbrechen</a>
            </div>
          </form>
        </div>
      </div>
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit;
}

// Standard: Alle Einträge anzeigen, sortiert nach Nachname und Vorname
$stmt = $pdo->prepare("SELECT * FROM Turner ORDER BY Nachname, Vorname");
$stmt->execute();
$turnerListe = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Turner Verwaltung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      body { background: #f6f7fb; }
      .page-wrap { max-width: 1200px; }
      .panel {
        background: #fff;
        border-radius: 16px;
        padding: 16px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.08);
      }
      .action-group {
        display: flex;
        gap: 0.5rem;
        align-items: center;
      }
      .action-group .btn {
        white-space: nowrap;
      }
      @media (max-width: 768px) {
        .table-mobile thead {
          display: none;
        }
        .table-mobile tr {
          display: block;
          margin-bottom: 0.75rem;
          border: 1px solid #e6e6e6;
          border-radius: 12px;
          padding: 0.25rem 0;
          background: #fff;
        }
        .table-mobile td {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 0.5rem 0.75rem;
          border-top: 1px solid #f0f0f0;
        }
        .table-mobile td:first-child {
          border-top: 0;
        }
        .table-mobile td::before {
          content: attr(data-label);
          font-weight: 600;
          color: #6c757d;
          margin-right: 1rem;
        }
        .table-mobile .action-cell {
          justify-content: flex-end;
        }
        .table-mobile .action-cell::before {
          content: "";
        }
        .action-group {
          flex-direction: column;
          width: 100%;
        }
        .action-group .btn {
          width: 100%;
        }
      }
    </style>
  </head>
<body>
  <script src="menu.js"></script>
  <div class="container my-4 page-wrap">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
      <h1 class="m-0">Turner Verwaltung</h1>
      <a href="?action=add" class="btn btn-success">Neuen Turner hinzufügen</a>
    </div>
    <div class="table-responsive panel">
    <table class="table table-striped table-mobile align-middle mb-0">
      <thead>
        <tr>
          <th>Vorname</th>
          <th>Nachname</th>
          <th>Geburtsdatum</th>
          <th>Geschlecht</th>
          <th>Verein</th>
          <th>Wettkampf</th>
          <th>Riege</th>
          <th>MannschaftsID</th>
          <th>Aktionen</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($turnerListe as $turner): ?>
        <tr>
          <td data-label="Vorname"><?= custom_htmlspecialchars($turner['Vorname']) ?></td>
          <td data-label="Nachname"><?= custom_htmlspecialchars($turner['Nachname']) ?></td>
          <td data-label="Geburtsdatum">
            <?php 
              $date = date_create($turner['Geburtsdatum']);
              echo $date ? date_format($date, 'd.m.Y') : '-';
            ?>
          </td>
          <td data-label="Geschlecht"><?= custom_htmlspecialchars(getDescription($geschlechter, $turner['GeschlechtID'])) ?></td>
          <td data-label="Verein"><?= custom_htmlspecialchars(getDescription($vereine, $turner['VereinID'])) ?></td>
          <td data-label="Wettkampf"><?= custom_htmlspecialchars(getDescription($wettkaempfe, $turner['WettkampfID'])) ?></td>
          <td data-label="Riege"><?= custom_htmlspecialchars(getDescription($riegen, $turner['RiegenID'])) ?></td>
          <td data-label="MannschaftsID"><?= custom_htmlspecialchars($turner['MannschaftsID']) ?></td>
          <td data-label="Aktionen" class="action-cell">
            <div class="action-group">
              <a href="?action=edit&id=<?= $turner['TurnerID'] ?>" class="btn btn-primary btn-sm">Bearbeiten</a>
              <a href="?action=delete&id=<?= $turner['TurnerID'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Wollen Sie diesen Turner wirklich löschen?')">Löschen</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
