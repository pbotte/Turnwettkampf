<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

$user_level_required = 1;
include 'auth.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/layout.php';

$pdo = db();

// Ausgewählte Riege (über GET, z. B. index.php?riege=3); wenn nicht gesetzt, dann "alle"
$selectedRiege = isset($_GET['riege']) ? $_GET['riege'] : 'alle';

// Alle Riegen für das Dropdown abrufen
$stmt = $pdo->query("SELECT * FROM Riegen ORDER BY Beschreibung ASC");
$riegen = $stmt->fetchAll(PDO::FETCH_ASSOC);
render_header('Riegenlisten');
?>
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
            <option value="<?php echo h($riege['RiegenID']); ?>" <?php echo ($selectedRiege == $riege['RiegenID'] ? 'selected' : ''); ?>>
              <?php echo h($riege['Beschreibung']) . ' (' . h($riege['RiegenID']) . ')'; ?>
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
      echo '<h2 class="h5 m-0">' . h($riege['Beschreibung']) . ' (' . h($riege['RiegenID']) . ')</h2>';
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
              echo '<td data-label="Vorname">' . h($turner['Vorname']) . '</td>';
              echo '<td data-label="Nachname">' . h($turner['Nachname']) . '</td>';
              echo '<td data-label="Jahrgang">' . h($turner['Jahrgang']) . '</td>';
              echo '<td data-label="Geschlecht">' . h($turner['Beschreibung_kurz']) . '</td>';
              echo '<td data-label="Verein">' . h($turner['Verein']) . '</td>';
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
<?php render_footer(); ?>
