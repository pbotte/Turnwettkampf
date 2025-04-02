<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

/*

Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Der Seitentitel lautet: "Wettkampf-Ergebnisse".


Iteriere durch alle Einträge in der Tabelle Wettkaempfe, sortiere nach Beschreibung:
1: Gebe Details zum jeweiligen Wettkampf aus, also dessen Beschreibung, Geschlecht (nachschlagen über GeschlechtID), Nwertungen und NGeraeteMax
2: Suche jetzt nach allen Turnern, die in diesem Wettkampf sind (schlag nach über WettkampfID), sortiere nach Platzierung
3: gebe eine Liste alle Turner aus mit Platzierung, Nachname, Vorname, Jahrgang, Geschlecht (in Kurzform, über GeschlechtID nachschlagen), Verein (über VereinID nachschlagen), Wertungssumme


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


include 'config.php';


/**
 * Eigene Funktion zur sicheren Ausgabe von Strings.
 * Gibt bei NULL den Wert "-" zurück, ansonsten wendet sie htmlspecialchars an.
 */
function safe_html($string) {
    if ($string === null) {
        return '-';
    } else {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

try {
    // PDO-Verbindung aufbauen
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Verbindungsfehler: " . $e->getMessage());
}

// Alle Wettkämpfe sortiert nach Beschreibung abfragen
$stmtWettkaempfe = $pdo->query("SELECT * FROM Wettkaempfe ORDER BY Beschreibung ASC");
$wettkaempfe = $stmtWettkaempfe->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Wettkampf-Ergebnisse</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <script src="menu.js"></script>
  <div class="container my-4">
    <h1 class="mb-4">Wettkampf-Ergebnisse</h1>
    <?php foreach ($wettkaempfe as $wettkampf): ?>
      <div class="card mb-4">
        <div class="card-header">
          <h2><?php echo safe_html($wettkampf['Beschreibung']); ?></h2>
        </div>
        <div class="card-body">
          <p>
            <strong>Geschlecht:</strong>
            <?php
              // Geschlecht über GeschlechtID nachschlagen
              $stmtGeschlecht = $pdo->prepare("SELECT Beschreibung FROM Geschlechter WHERE GeschlechtID = ?");
              $stmtGeschlecht->execute([$wettkampf['GeschlechtID']]);
              $geschlecht = $stmtGeschlecht->fetch(PDO::FETCH_ASSOC);
              echo safe_html($geschlecht['Beschreibung']);
            ?>
          </p>
          <p><strong>NWertungen:</strong> <?php echo safe_html($wettkampf['NWertungen']); ?></p>
          <p><strong>NGeraeteMax:</strong> <?php echo safe_html($wettkampf['NGeraeteMax']); ?></p>
          
          <h3>Turner</h3>
          <?php
            // Alle Turner zum aktuellen Wettkampf abrufen und nach Platzierung sortieren
            $stmtTurner = $pdo->prepare(
              "SELECT t.*, g.Beschreibung_kurz, v.Vereinsname 
               FROM Turner t 
               LEFT JOIN Geschlechter g ON t.GeschlechtID = g.GeschlechtID 
               LEFT JOIN Vereine v ON t.VereinID = v.VereinID 
               WHERE t.WettkampfID = ? 
               ORDER BY t.Platzierung ASC"
            );
            $stmtTurner->execute([$wettkampf['WettkampfID']]);
            $turnerListe = $stmtTurner->fetchAll(PDO::FETCH_ASSOC);
          ?>
          <?php if (count($turnerListe) > 0): ?>
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Platzierung</th>
                  <th>Nachname</th>
                  <th>Vorname</th>
                  <th>Jahrgang</th>
                  <th>Geschlecht</th>
                  <th>Verein</th>
                  <th>Wertungssumme</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($turnerListe as $turner): ?>
                  <tr>
                    <td><?php echo safe_html($turner['Platzierung']); ?></td>
                    <td><?php echo safe_html($turner['Nachname']); ?></td>
                    <td><?php echo safe_html($turner['Vorname']); ?></td>
                    <td><?php echo date('Y', strtotime($turner['Geburtsdatum'])); ?></td>
                    <td><?php echo safe_html($turner['Beschreibung_kurz']); ?></td>
                    <td><?php echo safe_html($turner['Vereinsname']); ?></td>
                    <td><?php echo safe_html($turner['Wertungssumme']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <p>Keine Turner gefunden.</p>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
