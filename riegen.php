<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');
?>
<?php /*
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

?>
<?php include 'auth.php'; ?>
<?php include 'config.php'; ?>
<?php

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
</head>
<body>
<script src="menu.js"></script>
<div class="container my-4">
  <h1 class="mb-4">Riegenlisten</h1>
  
  <!-- Dropdown zur Auswahl der Riege -->
  <form method="GET" class="mb-4">
    <div class="row g-2 align-items-center">
      <div class="col-auto">
        <label for="riege" class="col-form-label">Riege auswählen:</label>
      </div>
      <div class="col-auto">
        <select name="riege" id="riege" class="form-select" onchange="this.form.submit()">
          <option value="alle" <?php echo ($selectedRiege === 'alle' ? 'selected' : ''); ?>>Alle</option>
          <?php foreach ($riegen as $riege): ?>
          <option value="<?php echo safe_html($riege['RiegenID']); ?>" <?php echo ($selectedRiege == $riege['RiegenID'] ? 'selected' : ''); ?>>
            <?php echo safe_html($riege['Beschreibung']) . ' (' . safe_html($riege['RiegenID']) . ')'; ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </form>
  
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
      echo '<h2 class="mt-5">' . safe_html($riege['Beschreibung']) . ' (' . safe_html($riege['RiegenID']) . ')</h2>';
      
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
          echo '<table class="table table-striped table-bordered">';
          echo '<thead class="table-dark"><tr>
                  <th>Vorname</th>
                  <th>Nachname</th>
                  <th>Jahrgang</th>
                  <th>Geschlecht</th>
                  <th>Verein</th>
                </tr></thead><tbody>';
          
          foreach ($turnerList as $turner) {
              echo '<tr>';
              echo '<td>' . safe_html($turner['Vorname']) . '</td>';
              echo '<td>' . safe_html($turner['Nachname']) . '</td>';
              echo '<td>' . safe_html($turner['Jahrgang']) . '</td>';
              echo '<td>' . safe_html($turner['Beschreibung_kurz']) . '</td>';
              echo '<td>' . safe_html($turner['Verein']) . '</td>';
              echo '</tr>';
          }
          echo '</tbody></table></div>';
      } else {
          echo '<p>Keine Turner in dieser Riege.</p>';
      }
  }
  ?>
  
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
