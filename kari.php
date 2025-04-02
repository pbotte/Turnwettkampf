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
if ($selectedGeraet == 0 && count($geraete) > 0) {
    $selectedGeraet = $geraete[0]['GeraetID'];
}

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
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kari Wertungseingabe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <script src="menu.js"></script>
  <div class="container my-4">
    <h1 class="mb-4">Kari Wertungseingabe</h1>
    <form method="get" id="selectionForm">
      <div class="row mb-3">
        <!-- Dropdown für Riegen -->
        <div class="col-6">
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
        <div class="col-6">
          <label for="GeraetID" class="form-label">Gerät</label>
          <select class="form-select" name="GeraetID" id="GeraetID" required>
            <?php foreach ($geraete as $geraet): ?>
              <option value="<?= my_htmlspecialchars($geraet['GeraetID']) ?>" <?= $geraet['GeraetID'] == $selectedGeraet ? 'selected' : '' ?>>
                <?= my_htmlspecialchars($geraet['Beschreibung']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </form>
    
    <!-- Tabelle der Turner -->
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Nachname</th>
          <th>Vorname</th>
          <th>Jahrgang</th>
          <th>Geschlecht</th>
          <th>Verein</th>
          <th>P-Stufe</th>
          <th>Gesamtwertung</th>
          <th>Aktion</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($turnerList as $turner): ?>
          <tr>
            <td><?= my_htmlspecialchars($turner['Nachname']) ?></td>
            <td><?= my_htmlspecialchars($turner['Vorname']) ?></td>
            <td><?= my_htmlspecialchars($turner['Jahrgang']) ?></td>
            <td><?= my_htmlspecialchars($turner['Geschlecht']) ?></td>
            <td><?= my_htmlspecialchars($turner['Vereinsname']) ?></td>
            <td><?= my_htmlspecialchars($turner['P-Stufe']) ?></td>
            <td><?= my_htmlspecialchars($turner['Gesamtwertung']) ?></td>
            <td>
              <!-- Je nach Vorhandensein einer Wertung wird "Eintragen" oder "Bearbeiten" angezeigt -->
              <a href="kari-wert.php?TurnerID=<?= my_htmlspecialchars($turner['TurnerID']) ?>&RiegeID=<?= my_htmlspecialchars($selectedRiege) ?>&GeraetID=<?= my_htmlspecialchars($selectedGeraet) ?>" class="btn btn-primary">
                <?= $turner['WertungID'] ? 'Bearbeiten' : 'Eintragen' ?>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  
  <script>
    // Beim Wechsel der Riege werden die Geräte per AJAX neu geladen
    document.getElementById('RiegeID').addEventListener('change', function() {
      const selectedRiege = this.value;
      fetch('?action=getDevices&RiegeID=' + selectedRiege)
        .then(response => response.json())
        .then(data => {
          const geraetSelect = document.getElementById('GeraetID');
          geraetSelect.innerHTML = '';
          data.forEach(function(item) {
            const option = document.createElement('option');
            option.value = item.GeraetID;
            option.textContent = item.Beschreibung;
            geraetSelect.appendChild(option);
          });
          // Nach Aktualisierung der Geräteauswahl wird das Formular abgeschickt
          document.getElementById('selectionForm').submit();
        })
        .catch(error => console.error('Fehler beim Laden der Geräte:', error));
    });
    
    // Formular wird auch beim Wechsel der Geräteauswahl abgeschickt
    document.getElementById('GeraetID').addEventListener('change', function() {
      document.getElementById('selectionForm').submit();
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
