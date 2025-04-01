<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

/*

Programmiere eine php-Seite für Mobil-Geräte optimiert und in einem modernen Design.
Sie soll auf eine SQL-Datenbank zugreifen, deren Struktur angehängt ist.

Der Seitentitel lautet: "Berechnungen ausführen".


Iteriere durch alle Einträge in der Tabelle Turner:
0. Gib den Namen (Vorname, Nachname, TurnerID, Jahrgang, Geschlecht in Kurzform (über GeschlechtID in Tabelle Geschlechter nachschauen)) des Turners aus.
1. Schaue über die WettkampfID in der Tabelle Wettkaempfe nach den Spalten NWertungen und NGeraeteMax.
2. Suche über die TunerID in der Tabelle Wertungen nach der Spalte Gesamtwertung.
3. Zähle die Anzahl der Wertungen und gebe sie aus. Wertungen mit NULL oder 0,00 werden als nicht vorhanden interpretiert. Ist die Gesamtzahl größer als NGeraeteMax, so gebe einen Fehler aus.
4. Zähler die besten Wertungen zusammen, aber nur maximal so viele wie in NWertungen angegeben. Gebe das Ergebnis aus und speichere es in der Spalte "Wertungssumme".

Wenn Du durch alle Einträge in Turner durch bist, schreibe, dass du mit deinem Schritt fertig bist.


Nächste Schritt, Platzierungen bestimmen:
Iteriere erneut durch alle Einträge in der Tabelle Turner, sortiere nach WettkampfID und Wertungssumme (absteigend):
1. Vergebe die Platzierung für jeden Wettkampf wie folgt:
2. Vergebe den 1. Platz an den Turner mit der höchsten Punktzahl im Wettkampf. 
3. Fahre mit dem nächsten in der Tabelle fort, mit einem Platz schlechter.
4. Sollten es mehrere mit gleicher Punktzahl sein, so erhalten alle den gleichen Platz. Die nächsten vergebenen Plätze sind dann um die Anzahl der mehrfachvergebenen Plätze nach hinten verschoben. (übliches Prozedere)


Wenn Du durch alle Einträge in Turner durch bist, schreibe, dass du mit diesem Schritt fertig bist und nun alle Plätze vergeben sind.

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

include 'auth.php';
include 'config.php';
 





// Eigene Funktion zur sicheren Ausgabe
function safe_html($string) {
    if ($string === null) {
        return "-";
    } else {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . safe_html($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Berechnungen ausführen</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* Hier können weitere Styles für ein modernes, mobil-optimiertes Design ergänzt werden */
    body {
      padding-top: 1rem;
      padding-bottom: 1rem;
    }
  </style>
</head>
<body>
<script src="menu.js"></script>
<div class="container my-4">
  <h1 class="mb-4">Berechnungen ausführen</h1>

  <?php
  /********** Erster Schritt: Wertungssummen berechnen **********/
  echo "<h2>1. Berechnung der Wertungssummen</h2>";

  // Hole alle Turner inklusive Geschlechtskurzbezeichnung (via JOIN)
  $sql = "SELECT t.TurnerID, t.Vorname, t.Nachname, t.Geburtsdatum, t.GeschlechtID, t.WettkampfID, g.Beschreibung_kurz AS GeschlechtKurz
          FROM Turner t
          LEFT JOIN Geschlechter g ON t.GeschlechtID = g.GeschlechtID";
  $stmt = $pdo->query($sql);
  $turnerList = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($turnerList as $turner) {
      echo "<div class='card mb-3'><div class='card-body'>";
      // Ausgabe Name, TurnerID, Jahrgang (hier als Geburtsjahr berechnet) und Geschlecht in Kurzform
      echo "<h5 class='card-title'>" . safe_html($turner['Vorname']) . " " . safe_html($turner['Nachname']) .
           " (ID: " . safe_html($turner['TurnerID']) . ")</h5>";
      $jahrgang = date("Y", strtotime($turner['Geburtsdatum']));
      echo "<p class='card-text'>Jahrgang: " . safe_html($jahrgang) . " &ndash; Geschlecht: " . safe_html($turner['GeschlechtKurz']) . "</p>";

      // Abfrage der Wettkampfparameter NWertungen und NGeraeteMax über WettkampfID
      $stmtWett = $pdo->prepare("SELECT NWertungen, NGeraeteMax FROM Wettkaempfe WHERE WettkampfID = ?");
      $stmtWett->execute([$turner['WettkampfID']]);
      $wettkampf = $stmtWett->fetch(PDO::FETCH_ASSOC);
      if (!$wettkampf) {
          echo "<p class='text-danger'>Fehler: Wettkampf nicht gefunden für diesen Turner.</p>";
          echo "</div></div>";
          continue;
      }
      echo "<p class='card-text'>NWertungen: " . safe_html($wettkampf['NWertungen']) .
           " &ndash; NGeraeteMax: " . safe_html($wettkampf['NGeraeteMax']) . "</p>";

      // Hole alle Wertungen (Gesamtwertung) des Turners
      $stmtWertungen = $pdo->prepare("SELECT Gesamtwertung FROM Wertungen WHERE TurnerID = ?");
      $stmtWertungen->execute([$turner['TurnerID']]);
      $wertungen = $stmtWertungen->fetchAll(PDO::FETCH_ASSOC);

      // Zähle nur gültige Wertungen (Gesamtwertung nicht NULL und ungleich 0,00)
      $validWertungen = [];
      foreach ($wertungen as $wertung) {
          if ($wertung['Gesamtwertung'] !== null && floatval($wertung['Gesamtwertung']) != 0.00) {
              $validWertungen[] = floatval($wertung['Gesamtwertung']);
          }
      }
      $anzahlWertungen = count($validWertungen);
      echo "<p class='card-text'>Anzahl der gültigen Wertungen: " . safe_html($anzahlWertungen) . "</p>";

      // Falls die Anzahl der gültigen Wertungen NGeraeteMax überschreitet, Fehler ausgeben
      if ($anzahlWertungen > $wettkampf['NGeraeteMax']) {
          echo "<p class='text-danger'>Fehler: Anzahl der Wertungen (" . safe_html($anzahlWertungen) .
               ") ist größer als NGeraeteMax (" . safe_html($wettkampf['NGeraeteMax']) . ")!</p>";
      }

      // Bestimme die Summe der besten Wertungen, aber nur so viele wie in NWertungen angegeben
      // Sortiere absteigend und summiere die obersten NWertungen
      rsort($validWertungen);
      $nwertungenLimit = intval($wettkampf['NWertungen']);
      $selectedWertungen = array_slice($validWertungen, 0, $nwertungenLimit);
      $wertungssumme = array_sum($selectedWertungen);
      echo "<p class='card-text'>Wertungssumme (Summe der besten " . safe_html($nwertungenLimit) . " Wertungen): " . safe_html($wertungssumme) . "</p>";

      // Aktualisiere in der Turner-Tabelle die Spalte "Wertungssumme"
      $stmtUpdate = $pdo->prepare("UPDATE Turner SET Wertungssumme = ? WHERE TurnerID = ?");
      $stmtUpdate->execute([$wertungssumme, $turner['TurnerID']]);

      echo "</div></div>";
  }

  echo "<div class='alert alert-info'>Erster Schritt fertig: Berechnungen der Wertungssummen abgeschlossen.</div>";

  /********** Zweiter Schritt: Platzierungen bestimmen **********/
  echo "<h2>2. Platzierungen bestimmen</h2>";

  // Hole alle unterschiedlichen WettkampfIDs aus der Turner-Tabelle
  $sqlDistinctWettkampf = "SELECT DISTINCT WettkampfID FROM Turner";
  $stmtDistinct = $pdo->query($sqlDistinctWettkampf);
  $wettkaempfe = $stmtDistinct->fetchAll(PDO::FETCH_ASSOC);

  // Für jeden Wettkampf: Sortiere alle Turner nach Wertungssumme (absteigend) und vergebe die Plätze
  foreach ($wettkaempfe as $wett) {
      $wettkampfID = $wett['WettkampfID'];
      // Alle Turner dieses Wettkampfs, sortiert nach Wertungssumme DESC
      $stmtTurnerWett = $pdo->prepare("SELECT TurnerID, Wertungssumme FROM Turner WHERE WettkampfID = ? ORDER BY Wertungssumme DESC");
      $stmtTurnerWett->execute([$wettkampfID]);
      $turnerWettList = $stmtTurnerWett->fetchAll(PDO::FETCH_ASSOC);

      $currentPlace = 0;
      $previousWertungssumme = null;
      $placeCounter = 0;

      foreach ($turnerWettList as $t) {
          $placeCounter++;
          // Wenn die aktuelle Wertungssumme gleich der vorherigen ist, behalte denselben Platz
          if ($previousWertungssumme !== null && floatval($t['Wertungssumme']) == floatval($previousWertungssumme)) {
              $place = $currentPlace;
          } else {
              $currentPlace = $placeCounter;
              $place = $currentPlace;
          }
          // Aktualisiere die Platzierung des Turners
          $stmtUpdatePlace = $pdo->prepare("UPDATE Turner SET Platzierung = ? WHERE TurnerID = ?");
          $stmtUpdatePlace->execute([$place, $t['TurnerID']]);
          $previousWertungssumme = $t['Wertungssumme'];
      }
  }

  echo "<div class='alert alert-info'>Zweiter Schritt fertig: Alle Platzierungen wurden vergeben.</div>";
  ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
