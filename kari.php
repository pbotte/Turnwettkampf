<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

$user_level_required = 1;
include 'auth.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/layout.php';

$pdo = db();

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
render_header('Kari Wertungseingabe');
?>
  <div class="container my-4 page-wrap">
    <h1 class="mb-3">Kari Wertungseingabe</h1>

    <?php if ($savedName !== ''): ?>
      <div class="alert alert-success alert-dismissible fade show auto-dismiss-alert" role="alert">
        Wertung gespeichert: <?= h($savedName) ?>
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
                <option value="<?= h($riege['RiegenID']) ?>" <?= $riege['RiegenID'] == $selectedRiege ? 'selected' : '' ?>>
                  <?= h($riege['Beschreibung']) ?>
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
              <option value="<?= h($geraet['GeraetID']) ?>" <?= $geraet['GeraetID'] == $selectedGeraet ? 'selected' : '' ?>>
                <?= h($geraet['Beschreibung']) ?>
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
          <a class="turner-card <?= $hatWertung ? 'wertung-vorhanden' : 'wertung-fehlt' ?>" href="<?= h($kartenLink) ?>" aria-label="<?= h(($turner['WertungID'] ? 'Wertung bearbeiten für ' : 'Wertung eintragen für ') . $turner['Vorname'] . ' ' . $turner['Nachname']) ?>">
        <?php endif; ?>
          <div class="d-flex justify-content-between gap-2">
            <div>
              <div class="turner-name">
                <?= h($turner['Nachname']) ?>, <?= h($turner['Vorname']) ?>
              </div>
              <div class="turner-meta">
                <?= h($turnerLabel) ?> · <?= h($turner['Jahrgang']) ?> · <?= h($turner['Geschlecht']) ?>
              </div>
            </div>
            <span class="status-badge <?= $hatWertung ? 'status-vorhanden' : 'status-fehlt' ?>">
              <?= $hatWertung ? 'Fertig' : 'Offen' ?>
            </span>
          </div>
          <div class="turner-meta mt-2"><?= h($turner['Vereinsname']) ?></div>
          <div class="score-row">
            <div class="score-box">
              <span class="score-label">P-Stufe</span>
              <span class="score-value"><?= h($turner['P-Stufe']) ?></span>
            </div>
            <div class="score-box">
              <span class="score-label">Gesamt</span>
              <span class="score-value"><?= h($turner['Gesamtwertung']) ?></span>
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
<?php render_footer(); ?>
