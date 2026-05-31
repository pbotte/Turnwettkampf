<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

include 'auth.php';
require_once 'includes/db.php';
require_once 'includes/helpers.php';
require_once 'includes/layout.php';

$pdo = db();
render_header('Berechnungen ausführen', ['extraCss' => "    body {\n      padding-top: 1rem;\n      padding-bottom: 1rem;\n    }"]);
?>
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
      echo "<h5 class='card-title'>" . h($turner['Vorname']) . " " . h($turner['Nachname']) .
           " (ID: " . h($turner['TurnerID']) . ")</h5>";
      $jahrgang = date("Y", strtotime($turner['Geburtsdatum']));
      echo "<p class='card-text'>Jahrgang: " . h($jahrgang) . " &ndash; Geschlecht: " . h($turner['GeschlechtKurz']) . "</p>";

      // Abfrage der Wettkampfparameter NWertungen und NGeraeteMax über WettkampfID
      $stmtWett = $pdo->prepare("SELECT NWertungen, NGeraeteMax FROM Wettkaempfe WHERE WettkampfID = ?");
      $stmtWett->execute([$turner['WettkampfID']]);
      $wettkampf = $stmtWett->fetch(PDO::FETCH_ASSOC);
      if (!$wettkampf) {
          echo "<p class='text-danger'>Fehler: Wettkampf nicht gefunden für diesen Turner.</p>";
          echo "</div></div>";
          continue;
      }
      echo "<p class='card-text'>NWertungen: " . h($wettkampf['NWertungen']) .
           " &ndash; NGeraeteMax: " . h($wettkampf['NGeraeteMax']) . "</p>";

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
      echo "<p class='card-text'>Anzahl der gültigen Wertungen: " . h($anzahlWertungen) . "</p>";

      // Falls die Anzahl der gültigen Wertungen NGeraeteMax überschreitet, Fehler ausgeben
      if ($anzahlWertungen > $wettkampf['NGeraeteMax']) {
          echo "<p class='text-danger'>Fehler: Anzahl der Wertungen (" . h($anzahlWertungen) .
               ") ist größer als NGeraeteMax (" . h($wettkampf['NGeraeteMax']) . ")!</p>";
      }

      // Bestimme die Summe der besten Wertungen, aber nur so viele wie in NWertungen angegeben
      // Sortiere absteigend und summiere die obersten NWertungen
      rsort($validWertungen);
      $nwertungenLimit = intval($wettkampf['NWertungen']);
      $selectedWertungen = array_slice($validWertungen, 0, $nwertungenLimit);
      $wertungssumme = array_sum($selectedWertungen);
      echo "<p class='card-text'>Wertungssumme (Summe der besten " . h($nwertungenLimit) . " Wertungen): " . h($wertungssumme) . "</p>";

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
<?php render_footer(); ?>
