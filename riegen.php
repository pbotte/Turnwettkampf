<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

/*
Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Die Webseite soll Riegenlisten ausgeben, d.h. für jeden Eintrag in der Tabelle "Riegen" eine Tabelle ausgeben mit
den Turnern (siehe Tabelle Turner, verbunden über das Feld RiegenID), die in der Riege sind. Jede Zeile soll enthalten:
- Vorname, 
- Nachname, 
- Jahrgang, 
- Geschlecht (in abgekürzter Form, nachgeschaut über GeschlechID in Tabelle Geschlechter), 
- Vereinsname mit Angabe der Stadt (nachgeschaut über VereinID)

Sortierung nach Stadt, Vereinsname, Nachname und dann nach Vorname.
Sollte einer Verweise (Joins) nicht möglich sein, dann soll "-" angezeigt werden (verwende left join).

Alle Einträge aus der SQL-Tabelle sollen in einer Tabelle ausgegeben werden. Sortierung nach dem Alphabet.

Ganz am Anfang der Seite (also nach der Überschrift) soll ein Dropdown mit allen Riegen angezeigt werden. Das Dropdown enthält die BEschreibung und die ID einer Riege. Zusätzlich soll ganz am Anfang der Eintrag "Alle" angezeigt werden. Ist eine Reige ausgewählt, so soll nur diese angezeigt werden, andernfalls alle.

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
    die("Verbindung fehlgeschlagen: " . $e->getMessage());
}

// Eigene Funktion, um htmlspecialchars aufzurufen und null-Werte abzufangen
function safe_html($string) {
    if ($string === null) {
        return '-';
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Ausgewählte Riege (über GET, z. B. index.php?riege=3); wenn nicht gesetzt, dann "alle"
$selectedRiege = isset($_GET['riege']) ? $_GET['riege'] : 'alle';

// Alle Riegen für das Dropdown abrufen
$stmt = $pdo->query("SELECT * FROM Riegen ORDER BY Beschreibung ASC");
$riegen = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Riegenlisten</title>
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
    .form-select {
      font-size: 1.05rem;
    }
    .riege-panel.collapsed .riege-content {
      display: none;
    }
    .riege-header .riege-toggle {
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
    }
  </style>
</head>
<body>
<script src="menu.js"></script>
<div class="container my-4 page-wrap">
  <h1 class="mb-3">Riegenlisten</h1>
  
  <!-- Dropdown zur Auswahl der Riege -->
  <div class="panel mb-4">
    <form method="GET">
      <div class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
          <label for="riege" class="form-label">Riege auswählen:</label>
          <select name="riege" id="riege" class="form-select" onchange="this.form.submit()">
            <option value="alle" <?php echo ($selectedRiege === 'alle' ? 'selected' : ''); ?>>Alle</option>
            <?php foreach ($riegen as $riege): ?>
            <option value="<?php echo safe_html($riege['RiegenID']); ?>" <?php echo ($selectedRiege == $riege['RiegenID'] ? 'selected' : ''); ?>>
              <?php echo safe_html($riege['Beschreibung']) . ' (' . safe_html($riege['RiegenID']) . ')'; ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12 col-md-4 d-flex align-items-end">
          <button type="button" id="toggleAllRiegen" class="btn btn-outline-secondary w-100">
            Alle einklappen
          </button>
        </div>
      </div>
    </form>
  </div>
  
  <?php
  // Bestimmen, welche Riegen angezeigt werden sollen
  $showRiegen = array();
  if ($selectedRiege === 'alle') {
      $showRiegen = $riegen;
  } else {
      foreach ($riegen as $riege) {
          if ($riege['RiegenID'] == $selectedRiege) {
              $showRiegen[] = $riege;
              break;
          }
      }
  }
  
  // Für jede Riege werden die Turner abgefragt und in einer Tabelle ausgegeben
  foreach ($showRiegen as $riege) {
      echo '<div class="panel mb-4 riege-panel">';
      echo '<div class="riege-header d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">';
      echo '<h2 class="h5 m-0">' . safe_html($riege['Beschreibung']) . ' (' . safe_html($riege['RiegenID']) . ')</h2>';
      echo '<button type="button" class="btn btn-sm btn-outline-secondary riege-toggle">Einklappen</button>';
      echo '</div>';
      echo '<div class="riege-content">';
      
      // Query: Turner der aktuellen Riege inkl. Verknüpfungen über Geschlechter und Vereine (left join)
      $sql = "SELECT t.Vorname, t.Nachname, YEAR(t.Geburtsdatum) AS Jahrgang, 
                     g.Beschreibung_kurz, 
                     CONCAT(v.Vereinsname, ' (', v.Stadt, ')') AS Verein
              FROM Turner t
              LEFT JOIN Geschlechter g ON t.GeschlechtID = g.GeschlechtID
              LEFT JOIN Vereine v ON t.VereinID = v.VereinID
              WHERE t.RiegenID = :riegenid
              ORDER BY v.Stadt, v.Vereinsname, t.Nachname, t.Vorname";
      $stmt2 = $pdo->prepare($sql);
      $stmt2->execute(['riegenid' => $riege['RiegenID']]);
      $turnerList = $stmt2->fetchAll(PDO::FETCH_ASSOC);
      
      if (count($turnerList) > 0) {
          echo '<div class="table-responsive">';
          echo '<table class="table table-striped table-bordered table-mobile align-middle mb-0">';
          echo '<thead class="table-dark"><tr>
                  <th>Vorname</th>
                  <th>Nachname</th>
                  <th>Jahrgang</th>
                  <th>Geschlecht</th>
                  <th>Verein</th>
                </tr></thead><tbody>';
          
          foreach ($turnerList as $turner) {
              echo '<tr>';
              echo '<td data-label="Vorname">' . safe_html($turner['Vorname']) . '</td>';
              echo '<td data-label="Nachname">' . safe_html($turner['Nachname']) . '</td>';
              echo '<td data-label="Jahrgang">' . safe_html($turner['Jahrgang']) . '</td>';
              echo '<td data-label="Geschlecht">' . safe_html($turner['Beschreibung_kurz']) . '</td>';
              echo '<td data-label="Verein">' . safe_html($turner['Verein']) . '</td>';
              echo '</tr>';
          }
          echo '</tbody></table></div>';
      } else {
          echo '<p>Keine Turner in dieser Riege.</p>';
      }
      echo '</div>';
      echo '</div>';
  }
  ?>
  
</div>
<script>
  (function() {
    const toggleBtn = document.getElementById('toggleAllRiegen');
    const panels = Array.from(document.querySelectorAll('.riege-panel'));
    if (!toggleBtn || panels.length === 0) return;

    const updateGlobalToggle = () => {
      const allCollapsed = panels.every(panel => panel.classList.contains('collapsed'));
      toggleBtn.textContent = allCollapsed ? 'Alle ausklappen' : 'Alle einklappen';
    };

    toggleBtn.addEventListener('click', function() {
      const allCollapsed = panels.every(panel => panel.classList.contains('collapsed'));
      panels.forEach(panel => {
        panel.classList.toggle('collapsed', !allCollapsed);
        const btn = panel.querySelector('.riege-toggle');
        if (btn) btn.textContent = !allCollapsed ? 'Ausklappen' : 'Einklappen';
      });
      updateGlobalToggle();
    });

    panels.forEach(panel => {
      const btn = panel.querySelector('.riege-toggle');
      if (!btn) return;
      btn.addEventListener('click', function() {
        const isCollapsed = panel.classList.toggle('collapsed');
        btn.textContent = isCollapsed ? 'Ausklappen' : 'Einklappen';
        updateGlobalToggle();
      });
    });
  })();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
