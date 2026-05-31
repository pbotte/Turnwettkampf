<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

/*

Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Der Seitentitel lautet: "Kari Wertungseingabe".

Die Webseite soll ganz am Anfang, direkt nach dem Titel zwei Dropdownmenüs anzeigen:
- das erste erlaubt eine Auswahl der RiegenID. Die Einträge im Dropdownmenü kommen aus dem Beschreibungsfeld der Tabelle Riegen. Es muss immer ein Eintrag ausgewählt sein.
- das zweite wählt die GeraetID (angezeigt werden soll die Beschreibung aus der Tabelle Geraete). Es muss zwingend ein Wert ausgewählt werden. Die Auswahlmöglichkeiten sind abhängig von der Ausgewählten Riege im ersten Dropdownmenü. Die Möglichkeiten sind durch die Tabelle "Verbindung_Durchgaenge_Riegen_Geraete" gegeben.
Bei einem Wechsel der Auswahl im ersten Dropdownmenü soll automatisch das zweite Dropdownmenü aktualisiert werden. ("menu.js" hat damit nichts zu tun.)

Mit den ausgewählten RiegenID soll nun eine Liste der Turner (die RiegenID gleich der ausgewählten RiegenID haben) angezeigt werden. Ausgabe von: Nachnane, Vornahnme, Jahrgang, Geschlecht (in Kurzform, aus Geschlechter Tabelle via GeschlechtID), Vereinsname (via VereinsID in Tabelle Vereine). Sortierung nach Nachname, Vorname.
Ist eine Wertung für den Turner an dem ausgewählen Gerät (GeraetID aus zweiten Dropdownmenü) bereits vorhanden (siehe Tabelle Wertungen), dann soll der Wert aus den Spalten P-Stufe und Gesamtwertung angezeigt werden. Falls nicht vorhanden, dann "-".
In der letzten Spalte soll für jeden Turner ein Knopf "Eintragen" (falls noch keine Wertung vorhanden ist) bzw. Bearbeiten (Falls bereits eine vorhanden ist) angezeigt werden. Mit dem Klick auf diesen Knopf öffnet sich eine neue php-Seite mit dem Namen "kari-wert.php", welcher als get parameter TurnerID, RiegeID, GeraetID übergeben wird.


Bootstrap und PDO sollen verwendet werden.

Aktuell erhalte ich den Fehler: "Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in ..." 
Um dies zu lösen, ersetze bei der Nutzung von htmlspecialchars durch eine eigene Funktion, welche bei einem übergebenen null-String einfach "-" ausgibt und sonst die Funktion "htmlspecialchars" aufruft.

Für die Anbingung an die Datenbank sollen folgende Variablen verwendet werden: $dbHost, $dbName, $dbUser, $dbPass
und als charset: "utf8".




Bootstrap und PDO sollen verwendet werden.

Um den Fehler: "Deprecated: htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in ..." zu umgehen, nutze die  Funktion htmlspecialchars nicht direkt, sondern nutze eine eigene Funktion, welche bei einem übergebenen null-String einfach "-" ausgibt und andernfalls die Funktion "htmlspecialchars" aufruft.

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

$user_level_required = 1;
include 'auth.php';
include 'config.php';




try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}

// Eigene Funktion zur sicheren Ausgabe: Falls der übergebene String null ist, wird "-" zurückgegeben.
function my_htmlspecialchars($string) {
    return ($string === null) ? "-" : htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// AJAX-Endpunkt: Wird aufgerufen, wenn das Gerätedropdown (GeraetID) aktualisiert werden soll
if (isset($_GET['action']) && $_GET['action'] == 'getDevices') {
    $selectedRiege = isset($_GET['RiegeID']) ? intval($_GET['RiegeID']) : 0;
    $stmt = $pdo->prepare("
        SELECT v.GeraetID, g.Beschreibung 
        FROM Verbindung_Durchgaenge_Riegen_Geraete v 
        JOIN Geraete g ON v.GeraetID = g.GeraetID 
        WHERE v.RiegenID = ?
    ");
    $stmt->execute([$selectedRiege]);
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode($devices);
    exit;
}

// Ausgewählte Riege und Gerät aus GET-Parametern übernehmen (Standard: erster Eintrag, falls nicht gesetzt)
$selectedRiege = isset($_GET['RiegeID']) ? intval($_GET['RiegeID']) : 0;
$selectedGeraet  = isset($_GET['GeraetID']) ? intval($_GET['GeraetID']) : 0;

// Alle Riegen laden
$stmt = $pdo->query("SELECT RiegenID, Beschreibung FROM Riegen ORDER BY Beschreibung");
$riegen = $stmt->fetchAll(PDO::FETCH_ASSOC);
if ($selectedRiege == 0 && count($riegen) > 0) {
    $selectedRiege = $riegen[0]['RiegenID'];
}

// Geräte (Geraete) abhängig von der ausgewählten Riege laden
$stmt = $pdo->prepare("
    SELECT v.GeraetID, g.Beschreibung 
    FROM Verbindung_Durchgaenge_Riegen_Geraete v 
    JOIN Geraete g ON v.GeraetID = g.GeraetID 
    WHERE v.RiegenID = ?
");
$stmt->execute([$selectedRiege]);
$geraete = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Turner (Gymnastinnen und Turner) der ausgewählten Riege laden
$stmt = $pdo->prepare("
    SELECT 
        t.TurnerID, 
        t.Nachname, 
        t.Vorname, 
        YEAR(t.Geburtsdatum) AS Jahrgang, 
        gk.Beschreibung_kurz AS Geschlecht, 
        v.Vereinsname, 
        w.`P-Stufe`, 
        w.Gesamtwertung,
        w.WertungID
    FROM Turner t 
    LEFT JOIN Geschlechter gk ON t.GeschlechtID = gk.GeschlechtID 
    LEFT JOIN Vereine v ON t.VereinID = v.VereinID 
    LEFT JOIN Wertungen w ON t.TurnerID = w.TurnerID AND w.GeraetID = ? 
    WHERE t.RiegenID = ? 
    ORDER BY t.Nachname, t.Vorname
");
$stmt->execute([$selectedGeraet, $selectedRiege]);
$turnerList = $stmt->fetchAll(PDO::FETCH_ASSOC);
$gesamtTurner = count($turnerList);
$eingetrageneWertungen = 0;
foreach ($turnerList as $turner) {
    if (!empty($turner['WertungID'])) {
        $eingetrageneWertungen++;
    }
}
$offeneWertungen = max(0, $gesamtTurner - $eingetrageneWertungen);
$fortschrittProzent = $gesamtTurner > 0 ? (int) round(($eingetrageneWertungen / $gesamtTurner) * 100) : 0;
$savedName = trim($_GET['saved'] ?? '');
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kari Wertungseingabe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: #f6f7fb;
    }
    .page-wrap {
      max-width: 1200px;
    }
    .panel {
      background: #fff;
      border-radius: 16px;
      padding: 16px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    }
    .form-select,
    .form-control {
      font-size: 1.05rem;
    }
    .progress {
      height: 0.65rem;
    }
    .turner-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 0.75rem;
    }
    .turner-card {
      background: #fff;
      border: 1px solid #e6e8ee;
      border-left: 6px solid #d3d8e2;
      border-radius: 12px;
      padding: 14px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    a.turner-card {
      color: inherit;
      display: block;
      text-decoration: none;
      transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
    }
    a.turner-card:focus,
    a.turner-card:hover {
      border-color: #9fb4d8;
      box-shadow: 0 8px 18px rgba(0,0,0,0.09);
      transform: translateY(-1px);
    }
    a.turner-card:focus {
      outline: 3px solid rgba(13,110,253,0.25);
      outline-offset: 2px;
    }
    .turner-card.wertung-vorhanden {
      border-left-color: #1f8f4f;
    }
    .turner-card.wertung-fehlt {
      border-left-color: #d98300;
    }
    .turner-name {
      font-size: 1.12rem;
      font-weight: 700;
      line-height: 1.2;
    }
    .turner-meta {
      color: #5f6875;
      font-size: 0.92rem;
    }
    .score-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.5rem;
      margin: 0.85rem 0;
    }
    .score-box {
      background: #f6f7fb;
      border-radius: 8px;
      padding: 0.55rem 0.65rem;
    }
    .score-label {
      color: #6c757d;
      display: block;
      font-size: 0.76rem;
      font-weight: 600;
    }
    .score-value {
      font-size: 1.05rem;
      font-weight: 700;
    }
    .status-badge {
      display: inline-block;
      padding: 0.2rem 0.5rem;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 600;
      margin-right: 0.5rem;
    }
    .status-vorhanden {
      background: #1f8f4f;
      color: #fff;
    }
    .status-fehlt {
      background: #b26a00;
      color: #fff;
    }
    .status-neutral {
      background: #6c757d;
      color: #fff;
    }
    @media (max-width: 768px) {
      .container {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
      }
      .panel {
        border-radius: 12px;
        padding: 12px;
      }
    }
  </style>
</head>
<body>
  <script src="menu.js"></script>
  <div class="container my-4 page-wrap">
    <h1 class="mb-3">Kari Wertungseingabe</h1>

    <?php if ($savedName !== ''): ?>
      <div class="alert alert-success alert-dismissible fade show auto-dismiss-alert" role="alert">
        Wertung gespeichert: <?= my_htmlspecialchars($savedName) ?>
        <?php if (isset($_GET['done']) && $_GET['done'] === '1'): ?>
          <span class="d-block small">Für diese Riege und dieses Gerät sind alle Wertungen eingetragen.</span>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></button>
      </div>
    <?php endif; ?>

    <div class="panel mb-3">
      <form method="get" id="selectionForm">
        <div class="row g-2 align-items-end">
          <!-- Dropdown für Riegen -->
          <div class="col-12 col-md-6">
            <label for="RiegeID" class="form-label">Riege</label>
            <select class="form-select" name="RiegeID" id="RiegeID" required>
              <?php foreach ($riegen as $riege): ?>
                <option value="<?= my_htmlspecialchars($riege['RiegenID']) ?>" <?= $riege['RiegenID'] == $selectedRiege ? 'selected' : '' ?>>
                  <?= my_htmlspecialchars($riege['Beschreibung']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <!-- Dropdown für Geräte -->
          <div class="col-12 col-md-6">
          <label for="GeraetID" class="form-label">Gerät</label>
          <select class="form-select" name="GeraetID" id="GeraetID" required>
            <option value="0" <?= $selectedGeraet == 0 ? 'selected' : '' ?>>Nicht ausgewählt</option>
            <?php foreach ($geraete as $geraet): ?>
              <option value="<?= my_htmlspecialchars($geraet['GeraetID']) ?>" <?= $geraet['GeraetID'] == $selectedGeraet ? 'selected' : '' ?>>
                <?= my_htmlspecialchars($geraet['Beschreibung']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          </div>
        </div>
      </form>

      <?php if ($selectedGeraet == 0): ?>
        <div class="alert alert-warning mt-3 mb-0">
          Bitte zuerst ein Gerät auswählen, um Wertungen eintragen oder bearbeiten zu können.
        </div>
      <?php endif; ?>

      <div class="mt-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
          <strong>Fortschritt</strong>
          <span class="text-muted small"><?= $eingetrageneWertungen ?> von <?= $gesamtTurner ?> eingetragen</span>
        </div>
        <div class="progress" role="progressbar" aria-valuenow="<?= $fortschrittProzent ?>" aria-valuemin="0" aria-valuemax="100">
          <div class="progress-bar bg-success" style="width: <?= $fortschrittProzent ?>%"></div>
        </div>
        <div class="text-muted small mt-1">Offen: <?= $offeneWertungen ?></div>
      </div>

      <div class="row g-2 mt-2">
        <div class="col-12 col-md-6">
          <label for="searchInput" class="form-label">Suche</label>
          <input type="search" id="searchInput" class="form-control" placeholder="Name, Verein, Jahrgang ...">
        </div>
        <div class="col-12 col-md-6 d-flex align-items-end justify-content-md-end">
          <div class="text-muted small">Turner gefunden: <?php echo count($turnerList); ?></div>
        </div>
      </div>
    </div>
    
    <div class="turner-grid" id="turnerCards">
      <?php $geraetNichtAusgewaehlt = ($selectedGeraet == 0); ?>
      <?php foreach ($turnerList as $turner): ?>
        <?php
          $hatWertung = !empty($turner['WertungID']);
          $geschlechtKurz = strtolower(trim((string) ($turner['Geschlecht'] ?? '')));
          $turnerLabel = ($geschlechtKurz === 'w') ? 'Turnerin' : 'Turner';
          $kartenLink = "kari-wert.php?TurnerID=" . urlencode((string) $turner['TurnerID']) . "&RiegeID=" . urlencode((string) $selectedRiege) . "&GeraetID=" . urlencode((string) $selectedGeraet);
        ?>
        <?php if ($geraetNichtAusgewaehlt): ?>
          <div class="turner-card <?= $hatWertung ? 'wertung-vorhanden' : 'wertung-fehlt' ?>">
        <?php else: ?>
          <a class="turner-card <?= $hatWertung ? 'wertung-vorhanden' : 'wertung-fehlt' ?>" href="<?= my_htmlspecialchars($kartenLink) ?>" aria-label="<?= my_htmlspecialchars(($turner['WertungID'] ? 'Wertung bearbeiten für ' : 'Wertung eintragen für ') . $turner['Vorname'] . ' ' . $turner['Nachname']) ?>">
        <?php endif; ?>
          <div class="d-flex justify-content-between gap-2">
            <div>
              <div class="turner-name">
                <?= my_htmlspecialchars($turner['Nachname']) ?>, <?= my_htmlspecialchars($turner['Vorname']) ?>
              </div>
              <div class="turner-meta">
                <?= my_htmlspecialchars($turnerLabel) ?> · <?= my_htmlspecialchars($turner['Jahrgang']) ?> · <?= my_htmlspecialchars($turner['Geschlecht']) ?>
              </div>
            </div>
            <span class="status-badge <?= $hatWertung ? 'status-vorhanden' : 'status-fehlt' ?>">
              <?= $hatWertung ? 'Fertig' : 'Offen' ?>
            </span>
          </div>
          <div class="turner-meta mt-2"><?= my_htmlspecialchars($turner['Vereinsname']) ?></div>
          <div class="score-row">
            <div class="score-box">
              <span class="score-label">P-Stufe</span>
              <span class="score-value"><?= my_htmlspecialchars($turner['P-Stufe']) ?></span>
            </div>
            <div class="score-box">
              <span class="score-label">Gesamt</span>
              <span class="score-value"><?= my_htmlspecialchars($turner['Gesamtwertung']) ?></span>
            </div>
          </div>
          <?php if ($geraetNichtAusgewaehlt): ?>
            <div class="text-muted small">Bitte Gerät wählen</div>
          <?php endif; ?>
        <?php if ($geraetNichtAusgewaehlt): ?>
          </div>
        <?php else: ?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  </div>
  
  <script>
    // Beim Wechsel der Riege werden die Geräte per AJAX neu geladen
    document.getElementById('RiegeID').addEventListener('change', function() {
      const selectedRiege = this.value;
      const geraetSelect = document.getElementById('GeraetID');
      const previousLabel = geraetSelect.selectedOptions.length
        ? geraetSelect.selectedOptions[0].textContent.trim()
        : '';

      fetch('?action=getDevices&RiegeID=' + selectedRiege)
        .then(response => response.json())
        .then(data => {
          geraetSelect.innerHTML = '';
          const placeholder = document.createElement('option');
          placeholder.value = '0';
          placeholder.textContent = 'Nicht ausgewählt';
          geraetSelect.appendChild(placeholder);

          let matchedValue = '';
          data.forEach(function(item) {
            const option = document.createElement('option');
            option.value = item.GeraetID;
            option.textContent = item.Beschreibung;
            if (previousLabel && item.Beschreibung.trim() === previousLabel) {
              matchedValue = item.GeraetID;
            }
            geraetSelect.appendChild(option);
          });

          if (matchedValue) {
            geraetSelect.value = matchedValue;
          } else {
            geraetSelect.value = '0';
          }

          // Nach Aktualisierung der Geräteauswahl wird das Formular abgeschickt
          document.getElementById('selectionForm').submit();
        })
        .catch(error => console.error('Fehler beim Laden der Geräte:', error));
    });
    
    // Formular wird auch beim Wechsel der Geräteauswahl abgeschickt
    document.getElementById('GeraetID').addEventListener('change', function() {
      document.getElementById('selectionForm').submit();
    });

    // Client-seitige Suche in den Turner-Karten
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const cards = document.querySelectorAll('#turnerCards .turner-card');
        cards.forEach(card => {
          const text = card.textContent.toLowerCase();
          card.style.display = text.includes(query) ? '' : 'none';
        });
      });
    }

    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.auto-dismiss-alert').forEach(function(alertElement) {
        setTimeout(function() {
          const alert = bootstrap.Alert.getOrCreateInstance(alertElement);
          alert.close();
        }, 3000);
      });
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
